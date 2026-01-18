<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/JWT.php';

class User
{
  private $db;

  public function __construct()
  {
    $this->db = new Database();
  }

  public function getByEmail($email)
  {
    return $this->db->fetchOne(
      "SELECT u.user_id, u.username, u.email, u.user_type, u.full_name, 
            u.city_id, c.city_name, u.current_level_id, 
            u.registration_date, u.is_active, u.updated_at
     FROM users u
     LEFT JOIN cities c ON u.city_id = c.city_id
     WHERE u.email = ? AND u.user_type IN ('student', 'tutor', 'admin')",
      [$email]
    );
  }

  public function getByUsername($username)
  {
    return $this->db->fetchOne(
      "SELECT u.user_id, u.username, u.email, u.user_type, u.full_name, 
            u.city_id, c.city_name, u.current_level_id, 
            u.registration_date, u.is_active, u.updated_at
     FROM users u
     LEFT JOIN cities c ON u.city_id = c.city_id
     WHERE u.username = ? AND u.user_type IN ('student', 'tutor', 'admin')",
      [$username]
    );
  }

  public function getCurrentUser()
  {
    $token = null;

    if (isset($_GET['token'])) {
      $token = $_GET['token'];
    }

    if (!$token && isset($_COOKIE['english_token'])) {
      $token = $_COOKIE['english_token'];
    }

    if (!$token) {
      $headers = getallheaders();
      if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') === 0) {
          $token = substr($authHeader, 7);
        }
      }
    }

    if (!$token) {
      return null;
    }

    try {
      $decoded = JWT::decode($token);
      if (!$decoded || !isset($decoded['user_id'])) {
        return null;
      }

      return $this->getById($decoded['user_id']);
    } catch (Exception $e) {
      error_log("Invalid token: " . $e->getMessage());
      return null;
    }
  }

  public function register($data)
  {
    $required = ['email', 'password', 'username', 'full_name', 'user_type'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        throw new Exception("Поле '$field' обязательно для заполнения");
      }
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      throw new Exception("Некорректный email адрес");
    }

    $existing = $this->db->fetchOne("SELECT user_id FROM users WHERE email = ?", [$data['email']]);
    if ($existing) {
      throw new Exception("Пользователь с таким email уже существует");
    }

    $existing = $this->db->fetchOne("SELECT user_id FROM users WHERE username = ?", [$data['username']]);
    if ($existing) {
      throw new Exception("Пользователь с таким именем уже существует");
    }

    $allowed_types = ['student', 'tutor'];
    if (!in_array($data['user_type'], $allowed_types)) {
      $data['user_type'] = 'student';
    }

    $city_id_for_user = null;
    $city_id_for_tutor = null;

    if (!empty($data['city_id']) && is_numeric($data['city_id'])) {
      $city = $this->db->fetchOne("SELECT city_id FROM cities WHERE city_id = ?", [$data['city_id']]);
      if ($city) {
        if ($data['user_type'] === 'student') {
          $city_id_for_user = $data['city_id'];
        } else {
          $city_id_for_tutor = $data['city_id'];
        }
      }
    }

    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);

    $userData = [
      'email' => trim($data['email']),
      'username' => trim($data['username']),
      'password_hash' => $password_hash,
      'full_name' => trim($data['full_name']),
      'city_id' => $city_id_for_user,
      'user_type' => $data['user_type'],
      'current_level_id' => 1,
      'registration_date' => date('Y-m-d H:i:s'),
      'last_login' => date('Y-m-d H:i:s'),
      'is_active' => 1
    ];

    $user_id = $this->db->insert('users', $userData);

    if ($data['user_type'] === 'tutor') {
      $tutorData = [
        'user_id' => $user_id,
        'full_name' => trim($data['full_name']),
        'email' => trim($data['email']),
        'city_id' => $city_id_for_tutor,
        'is_active' => 0,
        'is_verified' => 0,
        'created_at' => date('Y-m-d H:i:s')
      ];
      $this->db->insert('tutors', $tutorData);
    }

    if ($data['user_type'] === 'student') {
      $progressData = [
        'user_id' => $user_id,
        'level_id' => 1,
        'tasks_completed' => 0,
        'current_score' => 0,
        'status' => 'not_started',
        'updated_at' => date('Y-m-d H:i:s')
      ];
      $this->db->insert('user_progress', $progressData);
    }

    $token = $this->generateToken($user_id, $data['email'], $data['user_type']);

    $user = $this->getById($user_id);

    return [
      'user' => $user,
      'token' => $token
    ];
  }

  public function login($email, $password)
  {
    $user = $this->db->fetchOne(
      "SELECT u.*, c.city_name 
         FROM users u
         LEFT JOIN cities c ON u.city_id = c.city_id
         WHERE u.email = ? AND u.user_type IN ('student', 'tutor', 'admin')",
      [$email]
    );

    if (!$user) {
      throw new Exception("Пользователь не найден");
    }

    if (!$user['is_active']) {
      throw new Exception("Аккаунт заблокирован");
    }

    if (!password_verify($password, $user['password_hash'])) {
      throw new Exception("Неверный пароль");
    }

    $this->db->update(
      'users',
      ['last_login' => date('Y-m-d H:i:s')],
      'user_id = ?',
      [$user['user_id']]
    );

    $token = $this->generateToken($user['user_id'], $user['email'], $user['user_type']);

    return [
      'user' => $this->getById($user['user_id']),
      'token' => $token
    ];
  }

  private function generateToken($user_id, $email, $user_type)
  {
    return JWT::encode([
      'user_id' => $user_id,
      'email' => $email,
      'user_type' => $user_type,
      'iat' => time(),
      'exp' => time() + (60 * 60 * 24) // 24 часа
    ]);
  }

  public function getById($id)
  {
    $user = $this->db->fetchOne(
      "SELECT u.user_id, u.username, u.email, u.user_type, u.full_name, 
            u.city_id, c.city_name, u.current_level_id, 
            u.registration_date, u.is_active, u.updated_at
     FROM users u
     LEFT JOIN cities c ON u.city_id = c.city_id
     WHERE u.user_id = ?",
      [$id]
    );

    if (!$user) {
      throw new Exception("Пользователь не найден");
    }

    if ($user['user_type'] === 'student' && $user['current_level_id']) {
      $level = $this->db->fetchOne(
        "SELECT level_code, level_name FROM levels WHERE level_id = ?",
        [$user['current_level_id']]
      );
      if ($level) {
        $user['current_level'] = $level;
      }
    }

    return $user;
  }

  public function updateProfile($user_id, $data)
  {
    $allowed_fields = ['full_name', 'avatar_url'];
    $update_data = [];

    foreach ($allowed_fields as $field) {
      if (isset($data[$field])) {
        $update_data[$field] = trim($data[$field]);
      }
    }

    if (isset($data['city_id']) && is_numeric($data['city_id'])) {
      $city = $this->db->fetchOne("SELECT city_id FROM cities WHERE city_id = ?", [$data['city_id']]);
      if ($city) {
        $update_data['city_id'] = $data['city_id'];
      }
    }

    if (!empty($update_data)) {
      $update_data['updated_at'] = date('Y-m-d H:i:s');
      $this->db->update('users', $update_data, 'user_id = ?', [$user_id]);
    }

    return $this->getById($user_id);
  }

  public function getAllCities()
  {
    return $this->db->fetchAll(
      "SELECT city_id, city_name, region, country 
             FROM cities 
             WHERE is_active = 1 
             ORDER BY city_name"
    );
  }

  public function searchCities($query)
  {
    $searchQuery = "%" . strtolower($query) . "%";

    return $this->db->fetchAll(
      "SELECT city_id, city_name, region, country 
         FROM cities 
         WHERE (LOWER(city_name) LIKE ? OR LOWER(region) LIKE ?) 
         AND is_active = 1 
         ORDER BY 
           CASE 
             WHEN LOWER(city_name) LIKE ? THEN 1
             WHEN LOWER(region) LIKE ? THEN 2
             ELSE 3
           END,
           city_name
         LIMIT 10",
      [$searchQuery, $searchQuery, $searchQuery . '%', $searchQuery . '%']
    );
  }

  public function getCityById($city_id)
  {
    return $this->db->fetchOne(
      "SELECT city_id, city_name, region, country 
             FROM cities 
             WHERE city_id = ?",
      [$city_id]
    );
  }

  public function searchTutors($filters = [])
  {
    $sql = "SELECT t.tutor_id, t.full_name, t.email, t.phone, 
                 t.bio, t.experience_years, t.hourly_rate, 
                 t.rating, t.total_reviews,
                 t.is_verified, t.is_active,
                 c.city_id, c.city_name, c.region, c.country,
                 ts.name as specialization_name,
                 u.user_id
          FROM tutors t
          LEFT JOIN cities c ON t.city_id = c.city_id
          LEFT JOIN tutor_specializations ts ON t.specialization_id = ts.specialization_id
          LEFT JOIN users u ON t.user_id = u.user_id
          WHERE t.is_active = 1 AND u.is_active = 1";

    $params = [];

    if (!empty($filters['city_id'])) {
      $sql .= " AND t.city_id = ?";
      $params[] = $filters['city_id'];
    }

    if (!empty($filters['specialization_id'])) {
      $sql .= " AND t.specialization_id = ?";
      $params[] = $filters['specialization_id'];
    }

    if (!empty($filters['min_rating'])) {
      $sql .= " AND t.rating >= ?";
      $params[] = $filters['min_rating'];
    }


    if (!empty($filters['max_price'])) {
      $sql .= " AND (t.hourly_rate <= ? OR t.hourly_rate IS NULL)";
      $params[] = $filters['max_price'];
    }

    $sql .= " ORDER BY t.is_verified DESC, t.rating DESC";

    return $this->db->fetchAll($sql, $params);
  }
  public function checkAndUpgradeLevel($user_id)
  {
    $user = $this->getById($user_id);
    $progress = $this->db->fetchOne(
      "SELECT * FROM user_progress WHERE user_id = ? AND level_id = ?",
      [$user_id, $user['current_level_id']]
    );

    if ($progress && $progress['completion_percentage'] >= 100) {
      $nextLevel = $this->db->fetchOne(
        "SELECT level_id FROM levels WHERE level_id > ? ORDER BY level_id ASC LIMIT 1",
        [$user['current_level_id']]
      );

      if ($nextLevel) {
        $this->db->update(
          'users',
          ['current_level_id' => $nextLevel['level_id'], 'updated_at' => date('Y-m-d H:i:s')],
          'user_id = ?',
          [$user_id]
        );

        $this->db->insert('user_progress', [
          'user_id' => $user_id,
          'level_id' => $nextLevel['level_id'],
          'tasks_completed' => 0,
          'current_score' => 0,
          'completion_percentage' => 0,
          'status' => 'not_started',
          'last_activity_date' => date('Y-m-d H:i:s'),
          'updated_at' => date('Y-m-d H:i:s')
        ]);

        return true;
      }
    }
    return false;
  }

  public function updateTutorCity($user_id, $city_id)
  {
    $user = $this->getById($user_id);
    if ($user['user_type'] !== 'tutor') {
      throw new Exception("Пользователь не является репетитором");
    }

    if ($city_id) {
      $city = $this->db->fetchOne("SELECT city_id FROM cities WHERE city_id = ?", [$city_id]);
      if (!$city) {
        throw new Exception("Указанный город не существует");
      }
    }

    return $this->db->update(
      'tutors',
      ['city_id' => $city_id, 'updated_at' => date('Y-m-d H:i:s')],
      'user_id = ?',
      [$user_id]
    );
  }
}

?>
<?php
require_once '../../config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

try {
  $user = new User();
  $currentUser = $user->getCurrentUser();

  if (!$currentUser || $currentUser['user_type'] !== 'admin') {
    throw new Exception('Доступ запрещен');
  }

  $required = ['full_name', 'email', 'username', 'password', 'user_type'];
  foreach ($required as $field) {
    if (empty($_POST[$field])) {
      throw new Exception("Поле '$field' обязательно");
    }
  }

  if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Некорректный email');
  }

  if (strlen($_POST['password']) < 6) {
    throw new Exception('Пароль должен быть не менее 6 символов');
  }

  $db = new Database();

  $existing = $db->fetchOne("SELECT user_id FROM users WHERE email = ?", [$_POST['email']]);
  if ($existing) {
    throw new Exception('Пользователь с таким email уже существует');
  }

  $existing = $db->fetchOne("SELECT user_id FROM users WHERE username = ?", [$_POST['username']]);
  if ($existing) {
    throw new Exception('Пользователь с таким именем уже существует');
  }

  $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

  $userData = [
    'email' => trim($_POST['email']),
    'username' => trim($_POST['username']),
    'password_hash' => $password_hash,
    'full_name' => trim($_POST['full_name']),
    'city_id' => !empty($_POST['city_id']) ? (int) $_POST['city_id'] : null,
    'user_type' => $_POST['user_type'],
    'current_level_id' => 1,
    'registration_date' => date('Y-m-d H:i:s'),
    'last_login' => date('Y-m-d H:i:s'),
    'is_active' => 1
  ];

  $user_id = $db->insert('users', $userData);

  if ($_POST['user_type'] === 'tutor') {
    $tutorData = [
      'user_id' => $user_id,
      'full_name' => trim($_POST['full_name']),
      'email' => trim($_POST['email']),
      'city_id' => !empty($_POST['city_id']) ? (int) $_POST['city_id'] : null,
      'is_active' => 0,
      'is_verified' => 0,
      'created_at' => date('Y-m-d H:i:s')
    ];
    $db->insert('tutors', $tutorData);
  }

  if ($_POST['user_type'] === 'student') {
    $progressData = [
      'user_id' => $user_id,
      'level_id' => 1,
      'tasks_completed' => 0,
      'current_score' => 0,
      'status' => 'not_started',
      'updated_at' => date('Y-m-d H:i:s')
    ];
    $db->insert('user_progress', $progressData);
  }

  $response = [
    'success' => true,
    'message' => 'Пользователь успешно добавлен'
  ];

} catch (Exception $e) {
  $response = [
    'success' => false,
    'message' => $e->getMessage()
  ];
  http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
ob_start();

$response = [
  'success' => false,
  'message' => 'Неизвестная ошибка',
  'data' => null
];

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception('Только POST метод разрешен');
  }

  $rawInput = file_get_contents('php://input');

  if (empty($rawInput)) {
    throw new Exception('Нет данных в запросе');
  }

  $input = json_decode($rawInput, true);

  if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('Некорректный JSON: ' . json_last_error_msg());
  }

  $required = ['email', 'password', 'username', 'full_name', 'user_type'];

  foreach ($required as $field) {
    if (empty($input[$field])) {
      throw new Exception("Поле '$field' обязательно");
    }
  }

  if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Некорректный email');
  }

  if (strlen($input['password']) < 6) {
    throw new Exception('Пароль должен быть не менее 6 символов');
  }

  $allowed_types = ['student', 'tutor'];
  if (!in_array($input['user_type'], $allowed_types)) {
    $input['user_type'] = 'student';
  }

  // подготавливаем данные и регистрируем пользователя
  $user = new User();
  $result = $user->register([
    'email' => trim($input['email']),
    'password' => $input['password'],
    'username' => trim($input['username']),
    'full_name' => trim($input['full_name']),
    'city_id' => isset($input['city_id']) && $input['city_id'] !== '' ? (int) $input['city_id'] : null,
    'user_type' => $input['user_type']
  ]);

  $response = [
    'success' => true,
    'message' => 'Регистрация успешна',
    'data' => $result
  ];

} catch (Exception $e) {
  $response = [
    'success' => false,
    'message' => $e->getMessage()
  ];
  http_response_code(400);
  error_log('Register error: ' . $e->getMessage());
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
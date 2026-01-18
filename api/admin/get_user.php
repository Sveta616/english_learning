<?php
require_once '../../config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'user' => null];

try {
  if (!isset($_GET['id'])) {
    throw new Exception('ID пользователя не указан');
  }

  $user_id = (int) $_GET['id'];

  $user = new User();
  $currentUser = $user->getCurrentUser();

  if (!$currentUser || $currentUser['user_type'] !== 'admin') {
    throw new Exception('Доступ запрещен');
  }

  $db = new Database();
  $user_data = $db->fetchOne(
    "SELECT u.*, c.city_name 
         FROM users u 
         LEFT JOIN cities c ON u.city_id = c.city_id 
         WHERE u.user_id = ?",
    [$user_id]
  );

  if (!$user_data) {
    throw new Exception('Пользователь не найден');
  }

  $response = [
    'success' => true,
    'user' => $user_data
  ];

} catch (Exception $e) {
  $response['error'] = $e->getMessage();
  http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
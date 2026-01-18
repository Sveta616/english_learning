<?php
require_once '../../config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false];

try {
  $input = json_decode(file_get_contents('php://input'), true);

  if (empty($input['id']) || empty($input['type']) || !isset($input['status'])) {
    throw new Exception('Недостаточно данных');
  }

  $user = new User();
  $currentUser = $user->getCurrentUser();

  if (!$currentUser || $currentUser['user_type'] !== 'admin') {
    throw new Exception('Доступ запрещен');
  }

  $id = (int) $input['id'];
  $type = $input['type'];
  $currentStatus = $input['status'];

  $db = new Database();
  $newStatus = null;

  switch ($type) {
    case 'user_active':
      $newStatus = $currentStatus == 1 ? 0 : 1;
      $db->update('users', ['is_active' => $newStatus], 'user_id = ?', [$id]);
      break;

    case 'tutor_active':
      $newStatus = $currentStatus == 1 ? 0 : 1;
      $db->update('tutors', ['is_active' => $newStatus], 'tutor_id = ?', [$id]);
      break;

    case 'tutor_verified':
      $newStatus = $currentStatus == 1 ? 0 : 1;
      $db->update('tutors', ['is_verified' => $newStatus], 'tutor_id = ?', [$id]);
      break;

    case 'city_active':
      $newStatus = $currentStatus == 1 ? 0 : 1;
      $db->update('cities', ['is_active' => $newStatus], 'city_id = ?', [$id]);
      break;

    case 'task_active':
      $newStatus = $currentStatus == 1 ? 0 : 1;
      $db->update('tasks', ['is_active' => $newStatus], 'task_id = ?', [$id]);
      break;

    case 'module_active':
      $newStatus = $currentStatus == 1 ? 0 : 1;
      $db->update('modules', ['is_active' => $newStatus], 'module_id = ?', [$id]);
      break;

    default:
      throw new Exception('Неизвестный тип объекта');
  }

  $response = [
    'success' => true,
    'new_status' => $newStatus
  ];

} catch (Exception $e) {
  $response['error'] = $e->getMessage();
  http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
<?php
require_once '../../config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'request' => null];

try {
  if (!isset($_GET['id'])) {
    throw new Exception('ID заявки не указан');
  }

  $request_id = (int) $_GET['id'];

  $user = new User();
  $currentUser = $user->getCurrentUser();

  if (!$currentUser || $currentUser['user_type'] !== 'tutor') {
    throw new Exception('Доступ запрещен');
  }

  $db = new Database();
  $tutor = $db->fetchOne(
    "SELECT tutor_id FROM tutors WHERE user_id = ?",
    [$currentUser['user_id']]
  );

  if (!$tutor) {
    throw new Exception('Репетитор не найден');
  }

  $request = $db->fetchOne(
    "SELECT tr.*, u.full_name as student_name, u.email as student_email, 
                c.city_name as student_city
         FROM tutor_requests tr 
         LEFT JOIN users u ON tr.student_id = u.user_id 
         LEFT JOIN cities c ON u.city_id = c.city_id 
         WHERE tr.request_id = ? AND tr.tutor_id = ?",
    [$request_id, $tutor['tutor_id']]
  );

  if (!$request) {
    throw new Exception('Заявка не найдена');
  }

  $response = [
    'success' => true,
    'request' => $request
  ];

} catch (Exception $e) {
  $response['error'] = $e->getMessage();
  http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
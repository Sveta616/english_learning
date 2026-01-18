<?php
require_once '../../config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'tutor' => null];

try {
  if (!isset($_GET['id'])) {
    throw new Exception('ID репетитора не указан');
  }

  $tutor_id = (int) $_GET['id'];

  $user = new User();
  $currentUser = $user->getCurrentUser();

  if (!$currentUser || $currentUser['user_type'] !== 'admin') {
    throw new Exception('Доступ запрещен');
  }

  $db = new Database();
  $tutor = $db->fetchOne(
    "SELECT t.*, c.city_name, ts.name as specialization_name 
         FROM tutors t 
         LEFT JOIN cities c ON t.city_id = c.city_id 
         LEFT JOIN tutor_specializations ts ON t.specialization_id = ts.specialization_id 
         WHERE t.tutor_id = ?",
    [$tutor_id]
  );

  if (!$tutor) {
    throw new Exception('Репетитор не найден');
  }

  $response = [
    'success' => true,
    'tutor' => $tutor
  ];

} catch (Exception $e) {
  $response['error'] = $e->getMessage();
  http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
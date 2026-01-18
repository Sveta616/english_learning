<?php
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['cities' => []];

try {
  if (!isset($_GET['q']) || strlen($_GET['q']) < 2) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
  }

  $query = trim($_GET['q']);

  error_log("Searching cities for query: " . $query);

  $user = new User();
  $cities = $user->searchCities($query);

  error_log("Found " . count($cities) . " cities for query: " . $query);

  $response['cities'] = $cities;

} catch (Exception $e) {
  error_log('Cities search error: ' . $e->getMessage());
  $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
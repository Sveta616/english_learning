<?php
require_once '../../config.php';

header('Content-Type: application/json; charset=utf-8');

$user = new User();
$currentUser = $user->getCurrentUser();

if (!$currentUser || $currentUser['user_type'] !== 'admin') {
  header('HTTP/1.1 403 Forbidden');
  echo json_encode(['error' => 'Доступ запрещен']);
  exit;
}

$db = new Database();

$total_stats = $db->fetchOne(
  "SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE user_type = 'student') as total_students,
        (SELECT COUNT(*) FROM users WHERE user_type = 'tutor') as total_tutors,
        (SELECT COUNT(*) FROM users WHERE user_type = 'admin') as total_admins,
        (SELECT COUNT(*) FROM tasks) as total_tasks,
        (SELECT COUNT(*) FROM user_answers) as total_answers,
        (SELECT COUNT(*) FROM tutor_requests) as total_requests,
        (SELECT COUNT(*) FROM cities WHERE is_active = 1) as active_cities"
);


$level_distribution = $db->fetchAll(
  "SELECT l.level_code, l.level_name, COUNT(u.user_id) as user_count
     FROM levels l
     LEFT JOIN users u ON l.level_id = u.current_level_id AND u.user_type = 'student'
     GROUP BY l.level_id, l.level_code, l.level_name
     ORDER BY l.level_id"
);

$top_tutors = $db->fetchAll(
  "SELECT t.full_name, t.rating, t.total_reviews, t.experience_years,
            c.city_name, ts.name as specialization_name
     FROM tutors t
     LEFT JOIN cities c ON t.city_id = c.city_id
     LEFT JOIN tutor_specializations ts ON t.specialization_id = ts.specialization_id
     WHERE t.is_active = 1 AND t.is_verified = 1 AND t.rating > 0
     ORDER BY t.rating DESC, t.total_reviews DESC
     LIMIT 10"
);

$top_students = $db->fetchAll(
  "SELECT u.full_name, u.email, l.level_code, up.tasks_completed, up.current_score,
            (SELECT COUNT(*) FROM user_answers ua WHERE ua.user_id = u.user_id AND ua.is_correct = 1) as correct_answers
     FROM users u
     JOIN user_progress up ON u.user_id = up.user_id
     JOIN levels l ON up.level_id = l.level_id
     WHERE u.user_type = 'student' AND u.is_active = 1
     ORDER BY up.current_score DESC, up.tasks_completed DESC
     LIMIT 10"
);

$output = fopen('php://output', 'w');
$filename = 'statistics_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";

fputcsv($output, ['Показатель', 'Значение'], ';');
fputcsv($output, ['Всего пользователей', $total_stats['total_users']], ';');
fputcsv($output, ['Студентов', $total_stats['total_students']], ';');
fputcsv($output, ['Репетиторов', $total_stats['total_tutors']], ';');
fputcsv($output, ['Администраторов', $total_stats['total_admins']], ';');
fputcsv($output, ['Заданий в системе', $total_stats['total_tasks']], ';');
fputcsv($output, ['Выполненных заданий', $total_stats['total_answers']], ';');
fputcsv($output, ['Заявок к репетиторам', $total_stats['total_requests']], ';');
fputcsv($output, ['Активных городов', $total_stats['active_cities']], ';');

fputcsv($output, [], ';');
fputcsv($output, ['РАСПРЕДЕЛЕНИЕ ПО УРОВНЯМ', ''], ';');

foreach ($level_distribution as $level) {
  fputcsv($output, [
    $level['level_code'] . ' - ' . $level['level_name'],
    $level['user_count']
  ], ';');
}

fputcsv($output, [], ';');
fputcsv($output, ['ТОП РЕПЕТИТОРОВ ПО РЕЙТИНГУ', '', '', '', ''], ';');
fputcsv($output, ['Репетитор', 'Рейтинг', 'Отзывы', 'Опыт', 'Город', 'Специализация'], ';');

foreach ($top_tutors as $tutor) {
  fputcsv($output, [
    $tutor['full_name'],
    number_format($tutor['rating'], 1),
    $tutor['total_reviews'],
    $tutor['experience_years'] ? $tutor['experience_years'] . ' лет' : 'Не указан',
    $tutor['city_name'] ?? 'Не указан',
    $tutor['specialization_name'] ?? 'Не указана'
  ], ';');
}

fputcsv($output, [], ';');
fputcsv($output, ['ТОП СТУДЕНТОВ ПО ПРОГРЕССУ', '', '', '', ''], ';');
fputcsv($output, ['Студент', 'Email', 'Уровень', 'Заданий выполнено', 'Правильных ответов', 'Баллов'], ';');

foreach ($top_students as $student) {
  fputcsv($output, [
    $student['full_name'],
    $student['email'],
    $student['level_code'],
    $student['tasks_completed'],
    $student['correct_answers'],
    $student['current_score']
  ], ';');
}

fclose($output);
exit;
?>
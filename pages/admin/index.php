<?php
// ЭТОТ ИНДЕКС АНАЛОГИЧЕН ДВУМ ДРУГИМ ИНДЕКСАМ У ДРУГИХ РОЛЕЙ, ВСЕ ЧТО МЕНЯТЕСЯ ЭТО ДАННЫЕ В ЗАПРОСЕ И СЛЕГКА ВНЕШНИЙ ВИД - ВСЕ ОСТАЛЬНОЕ ТОЖЕ САМОЕ
session_start();
require_once '../../config.php';

$userClass = new User();
$currentUser = $userClass->getCurrentUser();

if (!$currentUser || $currentUser['user_type'] !== 'admin') {
  header('Location: ../../main_index.php');
  exit;
}

$db = new Database();

$users_stats = $db->fetchOne(
  "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN user_type = 'student' THEN 1 ELSE 0 END) as students,
        SUM(CASE WHEN user_type = 'tutor' THEN 1 ELSE 0 END) as tutors,
        SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as blocked_users
     FROM users"
);

$tutors_stats = $db->fetchOne(
  "SELECT 
        COUNT(*) as total_tutors,
        SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_tutors,
        SUM(CASE WHEN t.is_active = 1 THEN 1 ELSE 0 END) as active_tutors
     FROM tutors t"
);

$tasks_stats = $db->fetchOne(
  "SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN task_type = 'multiple_choice' THEN 1 ELSE 0 END) as mc_tasks,
        SUM(CASE WHEN task_type = 'fill_blank' THEN 1 ELSE 0 END) as fb_tasks,
        SUM(CASE WHEN task_type = 'essay' THEN 1 ELSE 0 END) as essay_tasks
     FROM tasks"
);

$requests_stats = $db->fetchOne(
  "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_requests,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
     FROM tutor_requests"
);

$recent_users = $db->fetchAll(
  "SELECT user_id, username, email, user_type, full_name, 
            city_name, registration_date, u.is_active
     FROM users u
     LEFT JOIN cities ON u.city_id = cities.city_id
     ORDER BY registration_date DESC 
     LIMIT 10"
);

$recent_requests = $db->fetchAll(
  "SELECT tr.request_id, tr.request_text, tr.status, tr.request_date,
            u.full_name as student_name, t.full_name as tutor_name
     FROM tutor_requests tr
     JOIN users u ON tr.student_id = u.user_id
     JOIN tutors t ON tr.tutor_id = t.tutor_id
     ORDER BY tr.request_date DESC 
     LIMIT 10"
);

$notifications = $db->fetchAll(
  "SELECT tr.*, u.full_name as student_name, c.city_name as student_city 
     FROM tutor_requests tr 
     LEFT JOIN users u ON tr.student_id = u.user_id 
     LEFT JOIN cities c ON u.city_id = c.city_id 
     WHERE tr.status = 'pending' 
     ORDER BY tr.request_date DESC 
     LIMIT 10"
);

$page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard';
$allowed_pages = ['dashboard', 'users', 'tutors', 'content'];
if (!in_array($page, $allowed_pages)) {
  $page = 'dashboard';
}

$userInitials = '';
$fullName = $currentUser['full_name'] ?? '';
$names = explode(' ', $fullName, 2);
if (!empty($names[0])) {
  $userInitials = strtoupper(mb_substr($names[0], 0, 1, 'UTF-8'));
  if (!empty($names[1])) {
    $userInitials .= strtoupper(mb_substr($names[1], 0, 1, 'UTF-8'));
  }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Панель управления - English Learning</title>
  <link rel="stylesheet" href="../../assets/css/colors.css">
  <link rel="stylesheet" href="../../assets/css/base.css">
  <link rel="stylesheet" href="../../assets/css/components.css">
  <link rel="stylesheet" href="../../assets/css/forms.css">
  <link rel="stylesheet" href="../../assets/css/layout.css">
  <link rel="stylesheet" href="../../assets/css/modules.css">
  <link rel="stylesheet" href="../../assets/css/schedule.css">
  <link rel="stylesheet" href="../../assets/css/results.css">
</head>

<body>
  <div class="admin-container">
    <aside class="admin-sidebar">
      <div class="admin-logo">
        <a href="index.php">English<span>Learning</span> Admin</a>
      </div>

      <div class="admin-profile">
        <div class="admin-avatar"><?php echo $userInitials; ?></div>
        <div class="admin-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
        <span class="admin-badge">Администратор</span>
      </div>

      <nav class="admin-menu">
        <a href="?page=dashboard" class="admin-menu-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
          Дашборд
        </a>
        <a href="?page=users" class="admin-menu-item <?php echo $page === 'users' ? 'active' : ''; ?>">
          Пользователи
        </a>
        <a href="?page=tutors" class="admin-menu-item <?php echo $page === 'tutors' ? 'active' : ''; ?>">
          Репетиторы
        </a>
        <a href="?page=content" class="admin-menu-item <?php echo $page === 'content' ? 'active' : ''; ?>">
          Контент
        </a>
        <a href="../../logout.php" class="admin-menu-item">
          Выйти
        </a>
      </nav>
    </aside>

    <main class="admin-content">
      <header class="admin-header">
        <h1>
          <?php
          $titles = [
            'dashboard' => 'Панель управления',
            'users' => 'Управление пользователями',
            'tutors' => 'Управление репетиторами',
            'content' => 'Управление учебным контентом',
          ];
          echo $titles[$page] ?? 'Панель управления';
          ?>
        </h1>
        <div class="breadcrumbs">
          <a href="index.php">Главная</a> / <?php echo $titles[$page] ?? 'Панель управления'; ?>
        </div>
      </header>

      <?php
      $page_file = __DIR__ . "/{$page}.php";

      if (file_exists($page_file)) {
        include $page_file;
      } else {
        include __DIR__ . '/dashboard.php';
      }
      ?>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-modal]').forEach(button => {
        button.addEventListener('click', function () {
          const modalId = this.dataset.modal;
          const modal = document.getElementById(modalId);
          if (modal) {
            modal.style.display = 'flex';
          }
        });
      });

      document.querySelectorAll('.modal-close, .modal').forEach(element => {
        element.addEventListener('click', function (e) {
          if (e.target.classList.contains('modal') || e.target.classList.contains('modal-close')) {
            this.style.display = 'none';
          }
        });
      });

      document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function (e) {
          if (!confirm('Вы уверены, что хотите удалить этот элемент? Это действие нельзя отменить.')) {
            e.preventDefault();
          }
        });
      });

      document.querySelectorAll('.status-toggle').forEach(button => {
        button.addEventListener('click', function () {
          const id = this.dataset.id;
          const type = this.dataset.type;
          const currentStatus = this.dataset.status;

          fetch(`../../api/admin/toggle_status.php`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              id: id,
              type: type,
              status: currentStatus
            })
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                location.reload();
              } else {
                alert('Ошибка: ' + data.message);
              }
            });
        });
      });
    });
  </script>
</body>

</html>
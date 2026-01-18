<?php
// подключаем конфиг
session_start();
require_once './config.php';

$userClass = new User();
$currentUser = $userClass->getCurrentUser();

// проверяем роль
if (!$currentUser || $currentUser['user_type'] !== 'tutor') {
  header('Location: ../../main_index.php');
  exit;
}

// получаем инфу препода
$db = new Database();
$tutor = $db->fetchOne(
  "SELECT t.*, c.city_name, ts.name as specialization_name 
     FROM tutors t 
     LEFT JOIN cities c ON t.city_id = c.city_id 
     LEFT JOIN tutor_specializations ts ON t.specialization_id = ts.specialization_id 
     WHERE t.user_id = ?",
  [$currentUser['user_id']]
);

if (!$tutor) {
  $db->insert('tutors', [
    'user_id' => $currentUser['user_id'],
    'full_name' => $currentUser['full_name'],
    'email' => $currentUser['email'],
    'city_id' => $currentUser['city_id'],
    'is_active' => 0,
    'created_at' => date('Y-m-d H:i:s')
  ]);
  $tutor = $db->fetchOne(
    "SELECT t.*, c.city_name 
         FROM tutors t 
         LEFT JOIN cities c ON t.city_id = c.city_id 
         WHERE t.user_id = ?",
    [$currentUser['user_id']]
  );
}

$page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard';
$allowed_pages = ['dashboard', 'profile', 'requests', 'schedule'];
if (!in_array($page, $allowed_pages)) {
  $page = 'dashboard';
}

// уведомления (было убрано)
$notifications = $db->fetchAll(
  "SELECT tr.*, u.full_name as student_name, c.city_name as student_city 
     FROM tutor_requests tr 
     LEFT JOIN users u ON tr.student_id = u.user_id 
     LEFT JOIN cities c ON u.city_id = c.city_id 
     WHERE tr.tutor_id = ? AND tr.status = 'pending' 
     ORDER BY tr.request_date DESC 
     LIMIT 10",
  [$tutor['tutor_id']]
);

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
  <title>Личный кабинет репетитора - English Learning</title>
  <link rel="stylesheet" href="../../assets/css/colors.css">
  <link rel="stylesheet" href="../../assets/css/base.css">
  <link rel="stylesheet" href="../../assets/css/components.css">
  <link rel="stylesheet" href="../../assets/css/forms.css">
  <link rel="stylesheet" href="../../assets/css/layout.css">
  <link rel="stylesheet" href="../../assets/css/modules.css">
  <link rel="stylesheet" href="../../assets/css/schedule.css">
  <link rel="stylesheet" href="../../assets/css/results.css">
  <style>
    /* стили для этой страницы */
    .tutor-container {
      display: flex;
      min-height: 100vh;
      background-color: var(--gray-light);
    }

    .tutor-sidebar {
      width: 250px;
      background: var(--blue-dark);
      color: white;
      padding: 20px 0;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
    }

    .tutor-logo {
      padding: 0 20px 30px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .tutor-logo a {
      color: white;
      font-size: 1.5rem;
      font-weight: bold;
      text-decoration: none;
    }

    .tutor-logo span {
      color: var(--red-dark);
    }

    .tutor-profile {
      padding: 20px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .tutor-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--red-dark), var(--red-light));
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      font-weight: bold;
      margin: 0 auto 15px;
    }

    .tutor-name {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .tutor-badge {
      display: inline-block;
      background: var(--red-dark);
      color: white;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 12px;
      margin-top: 5px;
    }

    .tutor-verification {
      font-size: 12px;
      color: var(--gray-medium);
      margin-top: 5px;
    }

    .tutor-verification.verified {
      color: var(--green-success);
    }

    .tutor-menu {
      padding: 20px 0;
    }

    .tutor-menu-item {
      display: block;
      padding: 12px 20px;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: all 0.3s;
      border-left: 3px solid transparent;
    }

    .tutor-menu-item:hover {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border-left-color: var(--red-dark);
    }

    .tutor-menu-item.active {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border-left-color: var(--red-dark);
    }

    .tutor-menu-item i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    .tutor-content {
      flex: 1;
      margin-left: 250px;
      padding: 20px;
    }

    .tutor-header {
      background: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .tutor-header h1 {
      margin: 0;
      color: var(--blue-dark);
    }

    .notification-badge {
      background: var(--red-dark);
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      position: absolute;
      top: -5px;
      right: -5px;
    }

    .notification-btn {
      position: relative;
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: var(--blue-dark);
    }

    .tutor-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .stat-icon {
      font-size: 40px;
      margin-bottom: 15px;
      color: var(--red-dark);
    }

    .stat-number {
      font-size: 36px;
      font-weight: bold;
      color: var(--blue-dark);
      margin-bottom: 5px;
    }

    .stat-label {
      color: var(--gray-medium);
      font-size: 14px;
    }

    .tutor-section {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }

    .requests-list {
      margin-top: 20px;
    }

    .request-item {
      border: 1px solid var(--gray-light);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 15px;
      transition: all 0.3s;
    }

    .request-item:hover {
      border-color: var(--red-dark);
      box-shadow: 0 2px 10px rgba(217, 4, 41, 0.1);
    }

    .request-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .request-student {
      font-weight: 600;
      color: var(--blue-dark);
    }

    .request-date {
      color: var(--gray-medium);
      font-size: 14px;
    }

    .request-meta {
      display: flex;
      gap: 20px;
      margin: 10px 0;
      color: var(--gray-medium);
      font-size: 14px;
    }

    .request-actions {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }

    .btn-sm {
      padding: 8px 16px;
      font-size: 14px;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .profile-form .form-group {
      margin-bottom: 20px;
    }

    .profile-form label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .profile-form .form-control {
      width: 100%;
      max-width: 400px;
    }

    .verification-status {
      padding: 20px;
      background: var(--warning-light);
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .verification-status.verified {
      background: var(--success-light);
    }

    @media (max-width: 768px) {
      .tutor-sidebar {
        width: 100%;
        height: auto;
        position: relative;
      }

      .tutor-content {
        margin-left: 0;
      }

      .tutor-stats {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <!-- боковая панель -->
  <div class="tutor-container">
    <aside class="tutor-sidebar">
      <div class="tutor-logo">
        <a href="index.php">English<span>Learning</span></a>
      </div>

      <div class="tutor-profile">
        <div class="tutor-avatar"><?php echo $userInitials; ?></div>
        <div class="tutor-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
        <span class="tutor-badge">Репетитор</span>
        <div class="tutor-verification <?php echo $tutor['is_verified'] ? 'verified' : ''; ?>">
          <?php echo $tutor['is_verified'] ? ' Подтвержден' : ' На проверке'; ?>
        </div>
      </div>

      <nav class="tutor-menu">
        <a href="?page=dashboard" class="tutor-menu-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
          Дашборд
        </a>
        <a href="?page=profile" class="tutor-menu-item <?php echo $page === 'profile' ? 'active' : ''; ?>">
          Профиль
        </a>
        <a href="?page=requests" class="tutor-menu-item <?php echo $page === 'requests' ? 'active' : ''; ?>">
          Заявки
          <?php if (!empty($notifications)): ?>
            <span class="notification-badge"><?php echo count($notifications); ?></span>
          <?php endif; ?>
        </a>
        <a href="?page=schedule" class="tutor-menu-item <?php echo $page === 'schedule' ? 'active' : ''; ?>">
          Расписание
        </a>
        <a href="../../logout.php" class="tutor-menu-item">
          Выйти
        </a>
      </nav>
    </aside>

    <main class="tutor-content">
      <header class="tutor-header">
        <h1>
          <!-- заголовки для страниц -->
          <?php
          $titles = [
            'dashboard' => 'Дашборд',
            'profile' => 'Мой профиль',
            'requests' => 'Заявки от студентов',
            'schedule' => 'Расписание занятий',
          ];
          echo $titles[$page] ?? 'Личный кабинет';
          ?>
        </h1>
        <div class="header-actions">
          <a href="?page=requests" class="notification-btn" title="Уведомления">
            Уведомления
            <?php if (!empty($notifications)): ?>
              <span class="notification-badge"><?php echo count($notifications); ?></span>
            <?php endif; ?>
          </a>
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
    const verificationStatus = document.querySelector('.tutor-verification');
    if (verificationStatus && verificationStatus.classList.contains('verified')) {
      verificationStatus.innerHTML = '<strong>Аккаунт подтвержден</strong><br>';
    }
  </script>
</body>

</html>
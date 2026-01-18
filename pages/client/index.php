<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// подключаем конфиг
require_once './config.php';
$userClass = new User();
$currentUser = $userClass->getCurrentUser();

// определяем роль
if (!$currentUser || $currentUser['user_type'] !== 'student') {
  header('Location: ../../main_index.php');
  exit;
}

$db = new Database();

// зашита через разрешенные страницы
$page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard';
$allowed_pages = ['dashboard', 'learning', 'tutors', 'profile', 'module', 'module_tasks', 'schedule'];

if (!in_array($page, $allowed_pages)) {
  $page = 'dashboard';
}

// получаем прогресс
$progress = $db->fetchOne(
  "SELECT up.*, l.level_code, l.level_name 
   FROM user_progress up 
   LEFT JOIN levels l ON up.level_id = l.level_id 
   WHERE up.user_id = ? AND up.level_id = (SELECT current_level_id FROM users WHERE user_id = ?)",
  [$currentUser['user_id'], $currentUser['user_id']]
);
if (!$progress) {
  $current_level_id = $currentUser['current_level_id'] ?? 1;
  $db->insert('user_progress', [
    'user_id' => $currentUser['user_id'],
    'level_id' => $current_level_id,
    'tasks_completed' => 0,
    'current_score' => 0,
    'status' => 'not_started',
    'updated_at' => date('Y-m-d H:i:s')
  ]);
  $progress = $db->fetchOne(
    "SELECT up.*, l.level_code, l.level_name 
     FROM user_progress up 
     LEFT JOIN levels l ON up.level_id = l.level_id 
     WHERE up.user_id = ? AND up.level_id = ?",
    [$currentUser['user_id'], $current_level_id]
  );
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

$show_sidebar = !in_array($page, ['task']);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Личный кабинет студента - English Learning</title>
  <link rel="stylesheet" href="../../assets/css/colors.css">
  <link rel="stylesheet" href="../../assets/css/base.css">
  <link rel="stylesheet" href="../../assets/css/components.css">
  <link rel="stylesheet" href="../../assets/css/forms.css">
  <link rel="stylesheet" href="../../assets/css/layout.css">
  <link rel="stylesheet" href="../../assets/css/modules.css">
  <link rel="stylesheet" href="../../assets/css/schedule.css">
  <link rel="stylesheet" href="../../assets/css/results.css">
  <!-- стили для текущей страницы -->
  <style>
    .student-container {
      display: flex;
      min-height: 100vh;
      background-color: var(--gray-light);
    }

    .student-sidebar {
      width: 250px;
      background: var(--blue-dark);
      color: white;
      padding: 20px 0;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      <?php if (!$show_sidebar)
        echo 'display: none;'; ?>
    }

    .student-content {
      flex: 1;
      margin-left:
        <?php echo $show_sidebar ? '250px' : '0'; ?>
      ;
      padding: 20px;
    }

    .student-logo {
      padding: 0 20px 30px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .student-logo a {
      color: white;
      font-size: 1.5rem;
      font-weight: bold;
      text-decoration: none;
    }

    .student-logo span {
      color: var(--red-dark);
    }

    .student-profile {
      padding: 20px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .student-avatar {
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

    .student-name {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .student-badge {
      display: inline-block;
      background: var(--red-dark);
      color: white;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 12px;
      margin-top: 5px;
    }

    .student-level {
      font-size: 14px;
      color: var(--gray-medium);
      margin-top: 10px;
    }

    .student-level span {
      color: white;
      font-weight: bold;
    }

    .student-menu {
      padding: 20px 0;
    }

    .student-menu-item {
      display: block;
      padding: 12px 20px;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: all 0.3s;
      border-left: 3px solid transparent;
      position: relative;
    }

    .student-menu-item:hover {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border-left-color: var(--red-dark);
    }

    .student-menu-item.active {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border-left-color: var(--red-dark);
    }

    .student-menu-item i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    .student-header {
      background: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .student-header h1 {
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

    .student-stats {
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
      transition: transform 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
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

    .student-section {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }

    .progress-bar {
      height: 10px;
      background: var(--gray-light);
      border-radius: 5px;
      overflow: hidden;
      margin: 10px 0;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--red-dark), var(--red-light));
      border-radius: 5px;
      transition: width 0.3s;
    }

    .achievement-item {
      display: flex;
      align-items: center;
      padding: 15px;
      border: 1px solid var(--gray-light);
      border-radius: 8px;
      margin-bottom: 10px;
    }

    .achievement-icon {
      font-size: 24px;
      margin-right: 15px;
    }

    .level-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
      color: white;
      margin-right: 10px;
    }

    .level-a1 {
      background: var(--red-dark);
    }

    .level-a2 {
      background: var(--red-light);
    }

    .level-b1 {
      background: var(--gray-medium);
    }

    .level-b2 {
      background: var(--blue-dark);
    }

    .level-c1 {
      background: var(--blue-accent);
    }

    .level-c2 {
      background: var(--blue-primary);
    }

    .btn {
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      transition: all 0.3s;
      border: none;
      cursor: pointer;
    }

    .btn-primary {
      background: var(--red-dark);
      color: white;
    }

    .btn-primary:hover {
      background: var(--red-light);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(217, 4, 41, 0.3);
    }

    .btn-secondary {
      background: var(--blue-dark);
      color: white;
    }

    .btn-outline {
      background: transparent;
      color: var(--red-dark);
      border: 2px solid var(--red-dark);
    }

    .btn-outline:hover {
      background: var(--red-dark);
      color: white;
    }

    .alert {
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
    }

    .alert-success {
      background: var(--success-light);
      border: 1px solid var(--success-border);
      color: var(--green-success);
    }

    .alert-warning {
      background: var(--warning-light);
      border: 1px solid var(--warning-border);
      color: var(--yellow-warning);
    }

    .alert-error {
      background: var(--error-light);
      border: 1px solid var(--error-border);
      color: var(--red-dark);
    }

    @media (max-width: 768px) {
      .student-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        display: block;
      }

      .student-content {
        margin-left: 0;
      }

      .student-stats {
        grid-template-columns: 1fr;
      }
    }

    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      z-index: 10000;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      width: 90%;
      max-width: 500px;
      border-radius: 10px;
      padding: 30px;
      max-height: 90vh;
      overflow-y: auto;
    }
  </style>
</head>

<body>
  <!-- боковое меню -->
  <?php if ($show_sidebar): ?>
    <div class="student-container">
      <aside class="student-sidebar">
        <div class="student-logo">
          <a href="index.php">English<span>Learning</span></a>
        </div>

        <div class="student-profile">
          <div class="student-avatar"><?php echo $userInitials; ?></div>
          <div class="student-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
          <span class="student-badge">Студент</span>
          <div class="student-level">
            Текущий уровень:
            <span><?php echo $progress ? $progress['level_code'] . ' - ' . $progress['level_name'] : 'A1 - Beginner'; ?></span>
          </div>
        </div>

        <nav class="student-menu">
          <a href="?page=dashboard" class="student-menu-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
            Дашборд
          </a>
          <a href="?page=learning" class="student-menu-item <?php echo $page === 'learning' ? 'active' : ''; ?>">
            Обучение
          </a>
          <a href="?page=schedule" class="student-menu-item <?php echo $page === 'schedule' ? 'active' : ''; ?>">
            Расписание
          </a>
          <a href="?page=tutors" class="student-menu-item <?php echo $page === 'tutors' ? 'active' : ''; ?>">
            Репетиторы
          </a>
          <a href="?page=profile" class="student-menu-item <?php echo $page === 'profile' ? 'active' : ''; ?>">
            Профиль
          </a>
          <a href="../../logout.php" class="student-menu-item">
            Выйти
          </a>
        </nav>
      </aside>
    <?php endif; ?>
    <!-- вставка нужного контента -->
    <main class="student-content">
      <?php
      $page_file = __DIR__ . "/{$page}.php";

      if (file_exists($page_file)) {
        include $page_file;
      } else {
        include __DIR__ . '/dashboard.php';
      }
      ?>
    </main>

    <?php if ($show_sidebar): ?>
    </div>
  <?php endif; ?>
</body>

</html>
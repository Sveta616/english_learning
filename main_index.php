<?php
require_once 'config.php';

// обработка токена из URL
if (isset($_GET['token'])) {
  $token = $_GET['token'];
  setcookie('english_token', $token, time() + 86400 * 30, '/', '', false, true);
  header('Location: main_index.php');
  exit;
}

$currentUser = null;
$userInitials = '';
$userName = '';
$userRole = '';
$userType = '';

// проверка на токен
if (isset($_COOKIE['english_token'])) {
  try {
    $decoded = JWT::decode($_COOKIE['english_token']);
    $user = new User();
    $currentUser = $user->getById($decoded['user_id']);

    if ($currentUser) {
      $fullName = $currentUser['full_name'] ?? '';
      $names = explode(' ', $fullName, 2);
      $firstLetter = !empty($names[0]) ? mb_substr($names[0], 0, 1, 'UTF-8') : 'U';
      $lastLetter = !empty($names[1]) ? mb_substr($names[1], 0, 1, 'UTF-8') : 'S';
      $userInitials = strtoupper($firstLetter . $lastLetter);
      $userName = htmlspecialchars($fullName);
      $userType = $currentUser['user_type'] ?? 'student';

      $roles = [
        'admin' => 'Администратор',
        'tutor' => 'Репетитор',
        'student' => 'Студент'
      ];
      $userRole = isset($roles[$userType]) ? $roles[$userType] : $userType;
    }
  } catch (Exception $e) {
    setcookie('english_token', '', time() - 3600, '/');
    error_log('Token decode error: ' . $e->getMessage());
  }
}

// определяем какую страницу показывать
$page = 'main'; // по кд главная
if ($currentUser) {
  // проверяем GET для страницы
  $page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard';

  // защита : выдаем только разрешенные страницы
  $allowed_pages = ['dashboard', 'profile', 'requests', 'schedule', 'statistics'];
  if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
  }
}

// если пользователь авторизован, показываем нужный интерфейс
if ($currentUser) {
  switch ($userType) {
    case 'student':
      if ($page === 'main') {
        header('Location: pages/client/index.php');
        exit;
      } else {
        require_once 'pages/client/index.php';
        exit;
      }
      break;
    case 'tutor':
      require_once 'pages/tutor/index.php';
      exit;

    case 'admin':
      header('Location: pages/admin/index.php');
      exit;

    default:
      break;
  }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>English Learning - Изучение английского языка</title>
  <!-- <link rel="stylesheet" href="assets/css/styles.css"> -->
  <link rel="stylesheet" href="assets/css/colors.css">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/forms.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/modules.css">
  <link rel="stylesheet" href="assets/css/schedule.css">
  <link rel="stylesheet" href="assets/css/results.css">
</head>

<body>
  <nav class="navbar">
    <div class="navbar-content">
      <a href="main_index.php" class="logo">English<span>Learning</span></a>
      <div class="user-menu">
        <a href="login.html" class="btn btn-secondary">Вход</a>
        <a href="register.html" class="btn btn-primary">Регистрация</a>
      </div>
    </div>
  </nav>

  <!-- основной контент -->
  <div class="container">
    <?php if (!$currentUser): ?>
      <div class="hero fade-in">
        <h1>Добро пожаловать в English Learning!</h1>
        <p class="card" style="font-size: 1.2rem; line-height: 1.6;">
          Эффективная платформа для изучения английского языка. Начните с любого уровня от A1 до C2,
          выполняйте интерактивные задания, отслеживайте прогресс и находите репетиторов для персональных занятий.
        </p>

        <div style="margin-top: 40px; text-align: center;">
          <a href="register.html" class="btn btn-primary"
            style="padding: 15px 40px; font-size: 1.2rem; margin-right: 20px;">
            Начать обучение
          </a>
          <a href="login.html" class="btn btn-outline" style="padding: 15px 40px; font-size: 1.2rem;">
            Уже есть аккаунт
          </a>
        </div>
      </div>

      <div class="features" style="margin-top: 60px;">
        <div class="row">
          <div class="col">
            <div class="card">
              <div style="font-size: 3rem; color: var(--primary-red); margin-bottom: 20px;"></div>
              <h3>Поэтапное обучение</h3>
              <p>Система уровней от A1 (Beginner) до C2 (Mastery) с постепенным увеличением сложности заданий</p>
            </div>
          </div>
          <div class="col">
            <div class="card">
              <div style="font-size: 3rem; color: var(--primary-red); margin-bottom: 20px;"></div>
              <h3>Поиск репетиторов</h3>
              <p>Найдите подходящего преподавателя в вашем городе для индивидуальных занятий</p>
            </div>
          </div>
          <div class="col">
            <div class="card">
              <div style="font-size: 3rem; color: var(--primary-red); margin-bottom: 20px;"></div>
              <h3>Отслеживание прогресса</h3>
              <p>Детальная статистика и визуализация ваших успехов в изучении языка</p>
            </div>
          </div>
        </div>
      </div>

      <!-- вывод уровней языка -->
      <div class="levels-info card" style="margin-top: 40px;">
        <h2 style="text-align: center;">Уровни обучения</h2>
        <div class="row" style="margin-top: 30px;">
          <?php
          $levels = [
            ['code' => 'A1', 'name' => 'Beginner', 'desc' => 'Начальный уровень'],
            ['code' => 'A2', 'name' => 'Elementary', 'desc' => 'Базовый уровень'],
            ['code' => 'B1', 'name' => 'Intermediate', 'desc' => 'Средний уровень'],
            ['code' => 'B2', 'name' => 'Upper Intermediate', 'desc' => 'Выше среднего'],
            ['code' => 'C1', 'name' => 'Advanced', 'desc' => 'Продвинутый уровень'],
            ['code' => 'C2', 'name' => 'Mastery', 'desc' => 'Владение в совершенстве']
          ];

          foreach ($levels as $level): ?>
            <div class="col" style="margin-bottom: 20px;">
              <div class="card" style="height: 100%;">
                <span class="level-badge level-<?php echo strtolower($level['code']); ?>">
                  <?php echo $level['code']; ?>
                </span>
                <h4 style="margin-top: 15px;"><?php echo $level['name']; ?></h4>
                <p><?php echo $level['desc']; ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    </p>
  </div>

  <footer
    style="margin-top: 60px; text-align: center; padding: 30px; color: var(--medium-gray); border-top: 1px solid var(--light-gray);">
    <p>© 2024 English Learning Platform. Все права защищены.</p>
    <p style="margin-top: 10px;">Телефон: +7 (999) 123-45-67 | Email: info@english-learning.ru</p>
  </footer>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // проверка токена в localStorage 
      const token = localStorage.getItem('english_token');
      const user = localStorage.getItem('english_user');

      if (token && user && !document.cookie.includes('english_token')) {
        document.cookie = `english_token=${token}; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Strict`;

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('just_logged_in')) {
          window.location.href = 'main_index.php';
        }
      }

      // анимация появления элементов
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
          }
        });
      }, { threshold: 0.1 });

      document.querySelectorAll('.card').forEach(card => {
        observer.observe(card);
      });
    });
  </script>
</body>

</html>
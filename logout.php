<?php
// удаляем cookie
setcookie('english_token', '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Выход - English Learning</title>
  <script>
    // очищаем localStorage
    localStorage.removeItem('english_token');
    localStorage.removeItem('english_user');

    // перенаправляем на главную
    setTimeout(function () {
      window.location.href = 'main_index.php';
    }, 1000);
  </script>
</head>

<!-- окно при выходе -->

<body
  style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #2b2d42 0%, #1a1c2e 100%);">
  <div style="background: white; padding: 40px; border-radius: 10px; text-align: center;">
    <h1 style="color: #2b2d42; margin-bottom: 20px;">Выход из системы</h1>
    <p style="color: #8d99ae;">Вы успешно вышли из системы. Перенаправление на главную страницу...</p>
    <div style="margin-top: 30px;">
      <div
        style="width: 50px; height: 50px; border: 3px solid #edf2f4; border-top-color: #d90429; border-radius: 50%; margin: 0 auto; animation: spin 1s linear infinite;">
      </div>
    </div>
  </div>

  <style>
    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }
  </style>
</body>

</html>
<?php

$module_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
// проверяем модуль
if (!$module_id) {
  $module_id = $_SESSION['last_completed_module'] ?? 0;
}

if (!$module_id) {
  echo '<div class="student-section">
            <div class="alert alert-error">
                Модуль не найден.
            </div>
            <a href="?page=learning" class="btn btn-primary">Вернуться к модулям</a>
          </div>';
  exit;
}

// результаты
$results = $db->fetchOne(
  "SELECT 
        COUNT(ua.answer_id) as total_answered,
        SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
        SUM(ua.points_earned) as total_points,
        SUM(t.points) as max_possible_points
     FROM user_answers ua
     JOIN tasks t ON ua.task_id = t.task_id
     JOIN modules m ON t.module_id = m.module_id
     WHERE ua.user_id = ? AND m.module_id = ?",
  [$currentUser['user_id'], $module_id]
);

// инфа модуля
$module_info = $db->fetchOne(
  "SELECT m.*, l.level_code 
     FROM modules m
     JOIN levels l ON m.level_id = l.level_id
     WHERE m.module_id = ?",
  [$module_id]
);

// если ошибка
if (!$module_info) {
  echo '<div class="student-section">
            <div class="alert alert-error">
                Модуль не найден.
            </div>
            <a href="?page=learning" class="btn btn-primary">Вернуться к модулям</a>
          </div>';
  exit;
}

// статистика
$total_tasks_in_module = $db->fetchOne(
  "SELECT COUNT(*) as total FROM tasks WHERE module_id = ? AND is_active = 1",
  [$module_id]
);


$percentage_correct = 0;
if ($results && $results['total_answered'] > 0) {
  $percentage_correct = round(($results['correct_answers'] / $results['total_answered']) * 100);
}

// украшаем статистику
$grade = '';
$grade_color = '';
if ($percentage_correct >= 90) {
  $grade = 'Отлично';
  $grade_color = '#2ed573';
} elseif ($percentage_correct >= 70) {
  $grade = 'Хорошо';
  $grade_color = '#4cc9f0';
} elseif ($percentage_correct >= 50) {
  $grade = 'Удовлетворительно';
  $grade_color = '#ffc107';
} else {
  $grade = 'Плохо';
  $grade_color = '#d90429';
}
?>

<!-- контент при заверршении модуля -->
<div class="task-container">
  <div class="results-container">

    <h1 class="results-title">Модуль завершен!</h1>
    <p class="module-name"><?php echo htmlspecialchars($module_info['module_name'] ?? ''); ?></p>

    <div class="results-score" style="color: <?php echo $grade_color; ?>;">
      <?php echo $results['total_points'] ?? 0; ?>
    </div>
    <div class="results-max-score">
      из <?php echo $results['max_possible_points'] ?? 0; ?> возможных баллов
    </div>

    <div style="font-size: 18px; color: <?php echo $grade_color; ?>; font-weight: bold; margin-bottom: 30px;">
      <?php echo $grade; ?> (<?php echo $percentage_correct; ?>%)
    </div>

    <div class="results-stats">
      <div class="result-stat">
        <div class="number"><?php echo $results['correct_answers'] ?? 0; ?></div>
        <div class="label">Правильных ответов</div>
      </div>

      <div class="result-stat">
        <div class="number"><?php echo $results['total_answered'] ?? 0; ?></div>
        <div class="label">Всего заданий</div>
      </div>

      <div class="result-stat">
        <div class="number"><?php echo $percentage_correct; ?>%</div>
        <div class="label">Процент успеха</div>
      </div>

      <div class="result-stat">
        <div class="number">+<?php echo $results['total_points'] ?? 0; ?></div>
        <div class="label">Получено баллов</div>
      </div>
    </div>

    <?php if ($percentage_correct < 70): ?>
      <div class="alert alert-warning" style="margin: 30px 0; text-align: left;">
        <strong>Рекомендация:</strong> Для лучшего усвоения материала рекомендуем повторить модуль
        через несколько дней или обратиться к репетитору для разбора ошибок.
      </div>
    <?php endif; ?>

    <!-- кнопки переходы -->
    <div class="results-actions">
      <a href="?page=learning" class="btn btn-primary">
        К списку модулей
      </a>

      <a href="?page=dashboard" class="btn btn-outline">
        На дашборд
      </a>

      <?php if ($percentage_correct < 70): ?>
        <a href="?page=tutors" class="btn btn-secondary">
          Найти репетитора
        </a>
      <?php endif; ?>
    </div>

    <!-- инфа -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--light-gray);">
      <h4>Информация о модуле:</h4>
      <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
        <div>
          <strong>Название:</strong><br>
          <?php echo htmlspecialchars($module_info['module_name'] ?? ''); ?>
        </div>
        <div>
          <strong>Тип:</strong><br>
          <?php
          $module_type = $module_info['module_type'] ?? '';
          echo $module_type === 'grammar' ? 'Грамматика' :
            ($module_type === 'vocabulary' ? 'Словарь' :
              ($module_type === 'reading' ? 'Чтение' : 'Общий'));
          ?>
        </div>
        <div>
          <strong>Уровень:</strong><br>
          <?php echo htmlspecialchars($module_info['level_code'] ?? ''); ?>
        </div>
        <div>
          <strong>Дата завершения:</strong><br>
          <?php echo date('d.m.Y H:i'); ?>
        </div>
      </div>
    </div>
  </div>
</div>
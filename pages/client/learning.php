<?php
$currentLevelId = $currentUser['current_level_id'] ?? 1;

// логирования ошибок
error_log("=== Learning.php ЗАПУСК в " . date('H:i:s') . " ===");
error_log("User ID: " . $currentUser['user_id'] . ", Level: " . $currentLevelId);

// получаем модули
$modules = $db->fetchAll(
  "SELECT 
        m.module_id,
        m.module_name,
        m.description,
        m.module_type,
        m.order_number,
        m.is_active,
        m.level_id,
        COUNT(DISTINCT t.task_id) as total_tasks,
        COALESCE(SUM(t.points), 0) as total_points,
        COUNT(DISTINCT ua.task_id) as completed_tasks
     FROM modules m
     LEFT JOIN tasks t ON m.module_id = t.module_id AND t.is_active = 1
     LEFT JOIN user_answers ua ON t.task_id = ua.task_id AND ua.user_id = ?
     WHERE m.level_id = ? AND m.is_active = 1
     GROUP BY m.module_id, m.module_name, m.description, m.module_type, m.order_number, m.is_active, m.level_id
     ORDER BY m.order_number",
  [$currentUser['user_id'], $currentLevelId]
);

// логирование
error_log("Найдено модулей в БД: " . count($modules));

// высчитываем прогресс каждого модуля
$processedModules = [];
foreach ($modules as $module) {
  $status = 'not_started';
  $progress = 0;

  if ($module['completed_tasks'] > 0) {
    if ($module['completed_tasks'] >= $module['total_tasks']) {
      $status = 'completed';
      $progress = 100;
    } else {
      $status = 'in_progress';
      $progress = round(($module['completed_tasks'] / $module['total_tasks']) * 100);
    }
  }

  $processedModules[] = [
    'id' => $module['module_id'],
    'name' => $module['module_name'],
    'description' => $module['description'],
    'type' => $module['module_type'],
    'total_tasks' => $module['total_tasks'],
    'total_points' => $module['total_points'],
    'completed_tasks' => $module['completed_tasks'],
    'status' => $status,
    'progress' => $progress
  ];

  error_log("Обработан модуль ID={$module['module_id']}: {$module['module_name']}, статус={$status}");
}

$currentLevel = $db->fetchOne(
  "SELECT * FROM levels WHERE level_id = ?",
  [$currentLevelId]
);

$progress = $db->fetchOne(
  "SELECT completion_percentage 
     FROM user_progress 
     WHERE user_id = ? AND level_id = ?",
  [$currentUser['user_id'], $currentLevelId]
);

error_log("Прогресс уровня: " . ($progress['completion_percentage'] ?? 0) . "%");
error_log("=== Learning.php ЗАВЕРШЕН ОБРАБОТКА PHP ===");
?>

<div class="student-section">
  <h2>Обучение английскому</h2>

  <!-- потом убратт-->
  <div id="debug-info" style="display: none; background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px;">
    <strong>Отладка:</strong> PHP обработал <?php echo count($processedModules); ?> модулей
  </div>

  <div style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
      <span>Текущий уровень:
        <strong><?php echo $currentLevel['level_code'] . ' - ' . $currentLevel['level_name']; ?></strong>
      </span>
      <span>Прогресс: <?php echo $progress['completion_percentage'] ?? 0; ?>%</span>
    </div>
    <div class="progress-bar">
      <div class="progress-fill" style="width: <?php echo $progress['completion_percentage'] ?? 0; ?>%"></div>
    </div>
  </div>

  <!-- при отсутсвии модулей -->
  <?php if (empty($processedModules)): ?>
    <div class="alert alert-warning">
      <p>Модули для вашего уровня еще не добавлены. Пожалуйста, обратитесь к администратору.</p>
    </div>
  <?php else: ?>
    <h3>Доступные модули</h3>
    <p style="color: var(--medium-gray); margin-bottom: 20px;">
      Выберите модуль для изучения. Каждый модуль содержит набор заданий для улучшения ваших навыков.
    </p>

    <div class="modules-grid" id="modules-container">
      <?php
      $renderedCount = 0;
      $renderedIds = [];

      foreach ($processedModules as $module):
        $renderedCount++;
        $moduleId = $module['id'];

        if (in_array($moduleId, $renderedIds)) {
          error_log("ОШИБКА: Попытка отрендерить дубликат модуля ID=$moduleId");
          continue;
        }
        $renderedIds[] = $moduleId;

        // тип модуля
        $icon = '';
        $type_class = '';
        switch ($module['type']) {
          case 'grammar':
            $icon = '';
            $type_class = 'grammar';
            break;
          case 'vocabulary':
            $icon = '';
            $type_class = 'vocabulary';
            break;
          case 'reading':
            $icon = '';
            $type_class = 'reading';
            break;
          default:
            $icon = '';
            $type_class = 'grammar';
        }

        // статус модуля
        $status_text = '';
        $status_class = $module['status'];
        switch ($module['status']) {
          case 'completed':
            $status_text = 'Завершен';
            break;
          case 'in_progress':
            $status_text = 'В процессе';
            break;
          default:
            $status_text = 'Не начат';
        }
        ?>

        <div class="module-card <?php echo $status_class; ?>" data-module-id="<?php echo $moduleId; ?>"
          onclick="window.location.href='?page=module&id=<?php echo $moduleId; ?>'">

          <div class="module-header">
            <div>
              <div class="module-icon"><?php echo $icon; ?></div>
              <span class="module-type <?php echo $type_class; ?>">
                <?php echo $module['type'] === 'grammar' ? 'Грамматика' :
                  ($module['type'] === 'vocabulary' ? 'Словарь' : 'Чтение'); ?>
              </span>
            </div>
            <span class="module-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
          </div>

          <h4 class="module-title"><?php echo htmlspecialchars($module['name']); ?></h4>

          <p class="module-description">
            <?php echo htmlspecialchars($module['description'] ?? 'Описание модуля'); ?>
          </p>

          <div class="module-meta">
            <div class="module-meta-item">
              <?php echo $module['total_tasks']; ?> заданий
            </div>
            <div class="module-meta-item">
              <?php echo $module['total_points']; ?> очков
            </div>
            <div class="module-meta-item">
              <?php echo $module['completed_tasks']; ?> выполнено
            </div>
          </div>

          <?php if ($module['status'] === 'in_progress'): ?>
            <div style="margin-top: 10px;">
              <div style="height: 6px; background: var(--light-gray); border-radius: 3px;">
                <div style="width: <?php echo $module['progress']; ?>%; height: 100%; 
                                     background: #ffc107; border-radius: 3px;"></div>
              </div>
              <div style="font-size: 12px; color: var(--medium-gray); margin-top: 5px;">
                Прогресс: <?php echo $module['progress']; ?>%
              </div>
            </div>
          <?php endif; ?>

          <!-- кнопка в зависимости от прогресса меняется -->
          <div style="margin-top: 15px;">
            <?php if ($module['status'] === 'completed'): ?>
              <button class="btn btn-secondary" disabled style="width: 100%;">
                Завершено
              </button>
            <?php elseif ($module['status'] === 'in_progress'): ?>
              <button class="btn btn-primary" style="width: 100%;">
                Продолжить обучение
              </button>
            <?php else: ?>
              <button class="btn btn-primary" style="width: 100%;">
                Начать обучение
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- тутор -->
    <div style="margin-top: 30px; padding: 20px; background: var(--light-gray); border-radius: 8px;">
      <h4>Как работает обучение:</h4>
      <ol style="margin-left: 20px; color: var(--medium-gray);">
        <li>Выберите модуль для изучения</li>
        <li>Выполняйте задания последовательно</li>
        <li>Получайте баллы за правильные ответы</li>
        <li>После завершения модуля баллы добавляются к вашему прогрессу</li>
        <li>Модуль можно пройти только один раз</li>
      </ol>
    </div>
  <?php endif; ?>
</div>
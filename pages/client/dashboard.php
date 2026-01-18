<?php
// получаем стату
if (!isset($achievements)) {
  $db = new Database();
  $achievements = $db->fetchAll(
    "SELECT * FROM achievements WHERE user_id = ? ORDER BY earned_date DESC LIMIT 5",
    [$currentUser['user_id']]
  );
}

// получаем прогресс
if (!isset($progress)) {
  $db = new Database();
  $progress = $db->fetchOne(
    "SELECT up.*, l.level_code, l.level_name 
         FROM user_progress up 
         LEFT JOIN levels l ON up.level_id = l.level_id 
         WHERE up.user_id = ? AND up.level_id = (SELECT current_level_id FROM users WHERE user_id = ?)",
    [$currentUser['user_id'], $currentUser['user_id']]
  );
}

// получаем уровень языка
if (!isset($currentLevel) && isset($progress['level_id'])) {
  $db = new Database();
  $currentLevel = $db->fetchOne(
    "SELECT * FROM levels WHERE level_id = ?",
    [$progress['level_id']]
  );
}

// получаем завершенность уровня языка
if (isset($_POST['next_level']) && $progress['completion_percentage'] >= 100) {
  $nextLevel = $db->fetchOne(
    "SELECT level_id FROM levels WHERE level_id > ? ORDER BY level_id ASC LIMIT 1",
    [$currentUser['current_level_id']]
  );

  // обновляем данные при переходе на новый уровень
  if ($nextLevel) {
    $db->update(
      'users',
      ['current_level_id' => $nextLevel['level_id'], 'updated_at' => date('Y-m-d H:i:s')],
      'user_id = ?',
      [$currentUser['user_id']]
    );

    $db->insert('user_progress', [
      'user_id' => $currentUser['user_id'],
      'level_id' => $nextLevel['level_id'],
      'tasks_completed' => 0,
      'current_score' => 0,
      'completion_percentage' => 0,
      'status' => 'not_started',
      'last_activity_date' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ]);
    $db->update(
      'user_progress',
      ['status' => 'completed', 'updated_at' => date('Y-m-d H:i:s')],
      'user_id = ? AND level_id = ?',
      [$currentUser['user_id'], $currentUser['current_level_id']]
    );

    $currentUser['current_level_id'] = $nextLevel['level_id'];
    $progress = $db->fetchOne(
      "SELECT up.*, l.level_code, l.level_name 
             FROM user_progress up 
             LEFT JOIN levels l ON up.level_id = l.level_id 
             WHERE up.user_id = ? AND up.level_id = ?",
      [$currentUser['user_id'], $nextLevel['level_id']]
    );
    $currentLevel = $db->fetchOne(
      "SELECT * FROM levels WHERE level_id = ?",
      [$nextLevel['level_id']]
    );

    $success_message = "Вы успешно перешли на уровень " . $currentLevel['level_code'] . "!";
  }
}
?>

<!-- карточки дашборда -->
<div class="student-stats">
  <div class="stat-card">
    <div class="stat-icon"></div>
    <div class="stat-number"><?php echo $progress['tasks_completed'] ?? 0; ?></div>
    <div class="stat-label">Выполнено заданий</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon"></div>
    <div class="stat-number"><?php echo $progress['current_score'] ?? 0; ?></div>
    <div class="stat-label">Текущий счет</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon"></div>
    <div class="stat-number"><?php echo $progress['completion_percentage'] ?? 0; ?>%</div>
    <div class="stat-label">Завершение уровня</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon"></div>
    <div class="stat-number"><?php echo is_array($achievements) ? count($achievements) : 0; ?></div>
    <div class="stat-label">Достижения</div>
  </div>
</div>

<div class="student-section">
  <h2>Ваш прогресс обучения</h2>

  <!-- уведомление о возможности повышения уровня -->
  <?php if (isset($success_message)): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
      <strong>Поздравляем!</strong> <?php echo $success_message; ?>
    </div>
  <?php endif; ?>

  <div style="margin: 20px 0;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
      <span>Текущий уровень:
        <strong><?php echo $currentLevel ? $currentLevel['level_code'] . ' - ' . $currentLevel['level_name'] : 'A1 - Beginner'; ?></strong></span>
      <span><?php echo $progress['completion_percentage'] ?? 0; ?>% завершено</span>
    </div>
    <div class="progress-bar">
      <div class="progress-fill" style="width: <?php echo $progress['completion_percentage'] ?? 0; ?>%"></div>
    </div>
  </div>

  <?php if (($progress['status'] ?? '') === 'completed'): ?>
    <div class="alert alert-success">
      <strong>Поздравляем!</strong> Вы успешно завершили уровень <?php echo $currentLevel['level_code']; ?>.
      <form method="POST" style="display: inline-block; margin-left: 10px;">
        <button type="submit" name="next_level" class="btn btn-primary">Перейти к следующему уровню</button>
      </form>
    </div>
  <?php elseif ($progress['completion_percentage'] >= 100): ?>
    <div class="alert alert-success">
      <strong>Поздравляем!</strong> Вы выполнили все задания уровня <?php echo $currentLevel['level_code']; ?>.
      <form method="POST" style="display: inline-block; margin-left: 10px;">
        <button type="submit" name="next_level" class="btn btn-primary">Перейти к следующему уровню</button>
      </form>
    </div>
  <?php else: ?>
    <div class="alert alert-warning">
      Продолжайте обучение, чтобы перейти на следующий уровень! (Завершено
      <?php echo $progress['completion_percentage'] ?? 0; ?>%)
    </div>
  <?php endif; ?>

  <div style="margin-top: 30px;">
    <a href="?page=learning" class="btn btn-primary">Продолжить обучение</a>
    <a href="?page=tutors" class="btn btn-outline" style="margin-left: 10px;">Найти репетитора</a>
  </div>
</div>

<div class="student-section">
  <!-- вывод достижений если есть -->
  <h2>Последние достижения</h2>
  <?php if (!empty($achievements) && is_array($achievements)): ?>
    <div style="margin-top: 20px;">
      <?php foreach ($achievements as $achievement): ?>
        <div class="achievement-item">
          <div class="achievement-icon">
            <?php
            switch ($achievement['badge_type']) {
              case 'level_completed':
                echo '🏆';
                break;
              case 'task_milestone':
                echo '📚';
                break;
              case 'streak':
                echo '🔥';
                break;
              case 'first_login':
                echo '⭐';
                break;
              default:
                echo '🎯';
            }
            ?>
          </div>
          <div style="flex: 1;">
            <div style="font-weight: bold;"><?php echo htmlspecialchars($achievement['achievement_name']); ?></div>
            <div style="color: var(--medium-gray); font-size: 14px;">
              <?php echo htmlspecialchars($achievement['achievement_description']); ?>
            </div>
            <div style="font-size: 12px; color: var(--medium-gray); margin-top: 5px;">
              Получено: <?php echo date('d.m.Y', strtotime($achievement['earned_date'])); ?>
            </div>
          </div>
          <div style="color: var(--primary-red); font-weight: bold;">
            +<?php echo $achievement['points_awarded']; ?> баллов
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p style="color: var(--medium-gray); text-align: center; padding: 30px;">
      У вас пока нет достижений. Начните обучение, чтобы их получить!
    </p>
  <?php endif; ?>
</div>
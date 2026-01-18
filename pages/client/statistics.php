<?php
// получаем статистику
$statistics = $db->fetchAll(
  "SELECT 
      up.level_id,
      l.level_code,
      l.level_name,
      up.tasks_completed,
      up.current_score,
      up.completion_percentage,
      up.status,
      up.last_activity_date,
      COUNT(DISTINCT ua.task_id) as total_attempted_tasks,
      SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
   FROM user_progress up
   LEFT JOIN levels l ON up.level_id = l.level_id
   LEFT JOIN user_answers ua ON up.user_id = ua.user_id AND ua.answered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
   WHERE up.user_id = ?
   GROUP BY up.level_id, l.level_code, l.level_name, up.tasks_completed, up.current_score, 
            up.completion_percentage, up.status, up.last_activity_date
   ORDER BY up.level_id",
  [$currentUser['user_id']]
);

// переменные для статистики
$progressData = [];
foreach ($statistics as $stat) {
  $progressData[] = [
    'level' => $stat['level_code'],
    'completion' => $stat['completion_percentage'],
    'score' => $stat['current_score'],
    'tasks' => $stat['tasks_completed']
  ];
}

// активность
$activityData = $db->fetchAll(
  "SELECT 
      DATE(answered_at) as date,
      COUNT(*) as tasks_completed,
      SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
   FROM user_answers 
   WHERE user_id = ? AND answered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
   GROUP BY DATE(answered_at)
   ORDER BY date",
  [$currentUser['user_id']]
);
?>

<!-- контент -->
<div class="student-section">
  <h2>Статистика прогресса</h2>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-number"><?php echo $progress['completion_percentage'] ?? 0; ?>%</div>
      <div class="stat-label">Общий прогресс</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-number"><?php echo $progress['tasks_completed'] ?? 0; ?></div>
      <div class="stat-label">Всего заданий</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-number"><?php echo $progress['current_score'] ?? 0; ?></div>
      <div class="stat-label">Общий счет</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-number"><?php echo count($activityData); ?></div>
      <div class="stat-label">Активных дней</div>
    </div>
  </div>

  <div class="student-section">
    <h3>Прогресс по уровням</h3>
    <div style="margin-top: 20px;">
      <table style="width: 100%; border-collapse: collapse;">

        <thead>
          <tr style="background: var(--gray-light);">
            <th style="padding: 12px; text-align: left;">Уровень</th>
            <th style="padding: 12px; text-align: center;">Завершение</th>
            <th style="padding: 12px; text-align: center;">Выполнено заданий</th>
            <th style="padding: 12px; text-align: center;">Счет</th>
            <th style="padding: 12px; text-align: center;">Статус</th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($statistics as $stat): ?>
            <tr style="border-bottom: 1px solid var(--gray-light);">
              <td style="padding: 12px;">
                <span class="level-badge level-<?php echo strtolower($stat['level_code']); ?>">
                  <?php echo $stat['level_code']; ?> - <?php echo $stat['level_name']; ?>
                </span>
              </td>
              <td style="padding: 12px; text-align: center;">
                <div style="display: flex; align-items: center; gap: 10px; justify-content: center;">
                  <div style="width: 100px; height: 8px; background: var(--gray-light); border-radius: 4px;">
                    <div style="height: 100%; background: var(--primary-red); border-radius: 4px; 
                                width: <?php echo $stat['completion_percentage']; ?>%;"></div>
                  </div>
                  <span><?php echo $stat['completion_percentage']; ?>%</span>
                </div>
              </td>
              <td style="padding: 12px; text-align: center;">
                <?php echo $stat['tasks_completed']; ?>
              </td>
              <td style="padding: 12px; text-align: center;">
                <strong><?php echo $stat['current_score']; ?></strong>
              </td>
              <td style="padding: 12px; text-align: center;">
                <?php
                switch ($stat['status']) {
                  case 'completed':
                    echo '<span style="color: #2ed573;"> Завершен</span>';
                    break;
                  case 'in_progress':
                    echo '<span style="color: #ffc107;"> В процессе</span>';
                    break;
                  default:
                    echo '<span style="color: var(--gray-medium);"> Не начат</span>';
                }
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>

      </table>
    </div>
  </div>

  <!-- выводим активность -->
  <div class="student-section">
    <h3>Активность за последние 30 дней</h3>
    <?php if (!empty($activityData)): ?>
      <div style="margin-top: 20px; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">

          <thead>
            <tr style="background: var(--gray-light);">
              <th style="padding: 10px; text-align: left;">Дата</th>
              <th style="padding: 10px; text-align: center;">Выполнено заданий</th>
              <th style="padding: 10px; text-align: center;">Правильных ответов</th>
              <th style="padding: 10px; text-align: center;">Процент успеха</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($activityData as $activity):
              $successRate = $activity['tasks_completed'] > 0 ?
                round(($activity['correct_answers'] / $activity['tasks_completed']) * 100) : 0;
              ?>
              <tr style="border-bottom: 1px solid var(--gray-light);">
                <td style="padding: 10px;"><?php echo date('d.m.Y', strtotime($activity['date'])); ?></td>
                <td style="padding: 10px; text-align: center;"><?php echo $activity['tasks_completed']; ?></td>
                <td style="padding: 10px; text-align: center;"><?php echo $activity['correct_answers']; ?></td>
                <td style="padding: 10px; text-align: center;">
                  <span
                    style="color: <?php echo $successRate >= 70 ? '#2ed573' : ($successRate >= 50 ? '#ffc107' : '#d90429'); ?>;">
                    <?php echo $successRate; ?>%
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>

        </table>
      </div>
      <!-- если ничего нет -->
    <?php else: ?>
      <p style="color: var(--gray-medium); text-align: center; padding: 30px;">
        У вас пока нет активности. Начните обучение, чтобы отслеживать прогресс!
      </p>
    <?php endif; ?>
  </div>
</div>
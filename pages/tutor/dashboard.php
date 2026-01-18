<?php
// получаем стату для дашборда
$stats = $db->fetchOne(
  "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_requests,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests
     FROM tutor_requests 
     WHERE tutor_id = ?",
  [$tutor['tutor_id']]
);
// получаем последние заявки из бд
$recent_requests = $db->fetchAll(
  "SELECT tr.*, u.full_name as student_name, c.city_name as student_city 
     FROM tutor_requests tr 
     LEFT JOIN users u ON tr.student_id = u.user_id 
     LEFT JOIN cities c ON u.city_id = c.city_id 
     WHERE tr.tutor_id = ? 
     ORDER BY tr.request_date DESC 
     LIMIT 5",
  [$tutor['tutor_id']]
);
?>
<!-- стата -->
<div class="tutor-stats">
  <div class="stat-card">
    <div class="stat-number"><?php echo $stats['total_requests'] ?? 0; ?></div>
    <div class="stat-label">Всего заявок</div>
  </div>
  <div class="stat-card">
    <div class="stat-number"><?php echo $stats['pending_requests'] ?? 0; ?></div>
    <div class="stat-label">Ожидают ответа</div>
  </div>
  <div class="stat-card">
    <div class="stat-number"><?php echo $stats['accepted_requests'] ?? 0; ?></div>
    <div class="stat-label">Принятые</div>
  </div>
  <div class="stat-card">
    <div class="stat-number"><?php echo $tutor['rating'] ?? '5.00'; ?></div>
    <div class="stat-label">Рейтинг</div>
  </div>
</div>
<div class="tutor-section">
  <h2>Быстрые действия</h2>
  <div style="display: flex; gap: 15px; margin-top: 20px;">
    <a href="?page=profile" class="btn btn-primary">Редактировать профиль</a>
    <a href="?page=requests" class="btn btn-outline">Просмотреть заявки</a>
    <a href="?page=schedule" class="btn btn-outline">Посмотреть расписание</a>
  </div>
</div>

<!-- вывод последних заявок -->
<div class="tutor-section">
  <h2>Последние заявки</h2>
  <?php if (!empty($recent_requests)): ?>
    <div class="requests-list">
      <?php foreach ($recent_requests as $request): ?>
        <div class="request-item">
          <div class="request-header">
            <div class="request-student"><?php echo htmlspecialchars($request['student_name']); ?></div>
            <div class="request-date">
              <?php echo date('d.m.Y', strtotime($request['request_date'])); ?>
            </div>
          </div>
          <?php if ($request['request_text']): ?>
            <p><?php echo htmlspecialchars($request['request_text']); ?></p>
          <?php endif; ?>
          <div class="request-meta">
            <span>Город: <?php echo htmlspecialchars($request['student_city'] ?? 'Не указан'); ?></span>
            <span>Статус:
              <span style="color: 
                                <?php
                                $status_colors = [
                                  'pending' => 'var(--primary-red)',
                                  'accepted' => 'var(--success)',
                                  'rejected' => 'var(--medium-gray)',
                                  'completed' => 'var(--dark-blue)'
                                ];
                                echo $status_colors[$request['status']] ?? 'var(--medium-gray)';
                                ?>
                            ">
                <?php
                $status_names = [
                  'pending' => 'Ожидает',
                  'accepted' => 'Принята',
                  'rejected' => 'Отклонена',
                  'completed' => 'Завершена'
                ];
                echo $status_names[$request['status']] ?? $request['status'];
                ?>
              </span>
            </span>
          </div>
          <?php if ($request['status'] === 'pending'): ?>
            <div class="request-actions">
              <a href="?page=requests&action=accept&id=<?php echo $request['request_id']; ?>"
                class="btn btn-primary btn-sm">Принять</a>
              <a href="?page=requests&action=reject&id=<?php echo $request['request_id']; ?>"
                class="btn btn-secondary btn-sm">Отклонить</a>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align: center; margin-top: 20px;">
      <a href="?page=requests" class="btn btn-outline">Все заявки</a>
    </div>
  <?php else: ?>
    <p style="text-align: center; padding: 30px; color: var(--medium-gray);">
      У вас пока нет заявок от студентов
    </p>
  <?php endif; ?>
</div>

<div class="tutor-section">
  <h2>Статус аккаунта</h2>
  <div class="verification-status <?php echo $tutor['is_verified'] ? 'verified' : ''; ?>">
    <?php if ($tutor['is_verified']): ?>
      <h3 style="color: #2ed573; margin-bottom: 10px;">Ваш аккаунт подтвержден</h3>
      <p>Вы получаете больше заявок от студентов и отображаетесь в результатах поиска.</p>
    <?php else: ?>
      <h3 style="color: #ffc107; margin-bottom: 10px;">Аккаунт на проверке</h3>
      <p>Наш администратор проверяет ваши данные. Обычно это занимает 1-2 рабочих дня.</p>
      <p style="margin-top: 10px;">
        <strong>Что можно сделать:</strong><br>
        1. Заполните профиль полностью<br>
        2. Добавьте описание и опыт работы<br>
        3. Укажите специализацию и стоимость занятий
      </p>
      <div style="margin-top: 15px;">
        <a href="?page=profile" class="btn btn-primary">Заполнить профиль</a>
      </div>
    <?php endif; ?>
  </div>
</div>
<div class="admin-section">
  <h2>Общая статистика</h2>
  <!-- общая статистика -->
  <div class="admin-stats">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-number"><?php echo $users_stats['total_users'] ?? 0; ?></div>
      <div class="stat-label">Всего пользователей</div>
      <div style="margin-top: 10px; font-size: 14px;">
        <span style="color: var(--primary-red);"><?php echo $users_stats['students'] ?? 0; ?> студентов</span><br>
        <span style="color: var(--dark-blue);"><?php echo $users_stats['tutors'] ?? 0; ?> репетиторов</span><br>
        <span style="color: var(--medium-gray);"><?php echo $users_stats['admins'] ?? 0; ?> администраторов</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-number"><?php echo $tutors_stats['total_tutors'] ?? 0; ?></div>
      <div class="stat-label">Репетиторы</div>
      <div style="margin-top: 10px; font-size: 14px;">
        <span class="status-verified" style="padding: 3px 8px;"><?php echo $tutors_stats['verified_tutors'] ?? 0; ?>
          проверено</span><br>
        <span class="status-active" style="padding: 3px 8px; margin-top: 5px;">
          <?php echo $tutors_stats['active_tutors'] ?? 0; ?> активно</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-number"><?php echo $tasks_stats['total_tasks'] ?? 0; ?></div>
      <div class="stat-label">Заданий в системе</div>
      <div style="margin-top: 10px; font-size: 14px;">
        <span> <?php echo $tasks_stats['mc_tasks'] ?? 0; ?> выбор ответа</span><br>
        <span><?php echo $tasks_stats['fb_tasks'] ?? 0; ?> заполнение</span><br>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-number"><?php echo $requests_stats['total_requests'] ?? 0; ?></div>
      <div class="stat-label">Заявок к репетиторам</div>
      <div style="margin-top: 10px; font-size: 14px;">
        <span class="status-pending" style="padding: 3px 8px;"> <?php echo $requests_stats['pending_requests'] ?? 0; ?>
          ожидают</span><br>
        <span class="status-active" style="padding: 3px 8px; margin-top: 5px;">
          <?php echo $requests_stats['accepted_requests'] ?? 0; ?> принято</span>
      </div>
    </div>
  </div>
</div>

<!-- тут формируется таблица последних новых пользователей -->
<div class="admin-section">
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div>
      <h3>Последние пользователи</h3>
      <?php if (!empty($recent_users)): ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Имя</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
                <th>Статус</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_users as $user): ?>
                <tr>
                  <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td>
                    <?php
                    $role_labels = [
                      'student' => 'Студент',
                      'tutor' => 'Репетитор',
                      'admin' => 'Админ'
                    ];
                    echo $role_labels[$user['user_type']] ?? $user['user_type'];
                    ?>
                  </td>
                  <td><?php echo date('d.m.Y', strtotime($user['registration_date'])); ?></td>
                  <td>
                    <?php if ($user['is_active']): ?>
                      <span class="status-badge status-active">Активен</span>
                    <?php else: ?>
                      <span class="status-badge status-inactive">Заблокирован</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="text-align: center; margin-top: 15px;">
          <a href="?page=users" class="btn btn-primary">Все пользователи</a>
        </div>
      <?php else: ?>
        <p>Нет зарегистрированных пользователей</p>
      <?php endif; ?>
    </div>

    <!-- тут формируем таблицу псоледних заявок -->
    <div>
      <h3>Последние заявки к репетиторам</h3>
      <?php if (!empty($recent_requests)): ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Студент</th>
                <th>Репетитор</th>
                <th>Дата</th>
                <th>Статус</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_requests as $request): ?>
                <tr>
                  <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                  <td><?php echo htmlspecialchars($request['tutor_name']); ?></td>
                  <td><?php echo date('d.m.Y H:i', strtotime($request['request_date'])); ?></td>
                  <td>
                    <?php
                    $status_styles = [
                      'pending' => ['class' => 'status-pending', 'label' => 'Ожидает'],
                      'accepted' => ['class' => 'status-active', 'label' => 'Принята'],
                      'rejected' => ['class' => 'status-inactive', 'label' => 'Отклонена'],
                      'completed' => ['class' => 'status-verified', 'label' => 'Завершена']
                    ];
                    $style = $status_styles[$request['status']] ?? ['class' => 'status-inactive', 'label' => $request['status']];
                    ?>
                    <span class="status-badge <?php echo $style['class']; ?>">
                      <?php echo $style['label']; ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="text-align: center; margin-top: 15px;">
          <a href="?page=statistics" class="btn btn-primary">Подробная статистика</a>
        </div>
      <?php else: ?>
        <p>Нет заявок к репетиторам</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- быстрый переход -->
<div class="admin-section">
  <h3>Быстрые действия</h3>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
    <a href="?page=users&action=add" class="btn btn-primary" style="text-align: center; padding: 20px;">
      <div>Добавить пользователя</div>
    </a>

    <a href="?page=tutors&action=add" class="btn btn-primary" style="text-align: center; padding: 20px;">
      <div>Добавить репетитора</div>
    </a>

    <a href="?page=cities&action=add" class="btn btn-primary" style="text-align: center; padding: 20px;">
      <div>Добавить город</div>
    </a>
  </div>
</div>
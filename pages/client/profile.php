<?php
// получаем инфу для логики профиля
$last_profile_update = $currentUser['updated_at'] ?? $currentUser['registration_date'] ?? null;
$can_update = true;
$next_update_in = 0;

if ($last_profile_update) {
  $last_update_date = new DateTime($last_profile_update);
  $now = new DateTime();
  $interval = $last_update_date->diff($now);
  $days_passed = $interval->days;

  $can_update = $days_passed >= 7;
  $next_update_in = 7 - $days_passed;
  if ($next_update_in < 0)
    $next_update_in = 0;
}

// сообщение обновления инфы
$update_message = '';
$just_updated = false; // Флаг, что только что обновили профиль

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // проверяем можно ли обновлять
  if (!$can_update) {
    $update_message = '<div class="alert alert-warning">Вы не можете редактировать профиль сейчас. Следующее редактирование будет доступно через ' . $next_update_in . ' дней.</div>';
  } else {
    // можно обновлять
    $changes_made = false;

    if (!empty($_POST['email']) && $_POST['email'] !== $currentUser['email']) {
      $existing = $db->fetchOne(
        "SELECT user_id FROM users WHERE email = ? AND user_id != ?",
        [trim($_POST['email']), $currentUser['user_id']]
      );
      if (!$existing) {
        $db->update(
          'users',
          ['email' => trim($_POST['email']), 'updated_at' => date('Y-m-d H:i:s')],
          'user_id = ?',
          [$currentUser['user_id']]
        );
        $changes_made = true;
      } else {
        $update_message .= '<span style="color: var(--primary-red);">Email уже занят другим пользователем.</span><br>';
      }
    }

    if (isset($_POST['city_id']) && is_numeric($_POST['city_id']) && $_POST['city_id'] != $currentUser['city_id']) {
      $db->update(
        'users',
        ['city_id' => (int) $_POST['city_id'], 'updated_at' => date('Y-m-d H:i:s')],
        'user_id = ?',
        [$currentUser['user_id']]
      );
      $changes_made = true;
    }

    if ($changes_made) {
      // обновляем данные пользователя
      $currentUser = $userClass->getById($currentUser['user_id']);

      // обновляем переменные для отображения
      $last_profile_update = date('Y-m-d H:i:s');
      $can_update = false;
      $next_update_in = 7;
      $just_updated = true;

      $update_message = '<div class="alert alert-success">Профиль успешно обновлен!</div>';
    } elseif (empty($update_message)) {
      // если ничего не изменили но форма отправлена
      $update_message = '<div class="alert alert-info">Изменений не обнаружено.</div>';
    }
  }
}

$cities = $db->fetchAll("SELECT city_id, city_name FROM cities WHERE is_active = 1 ORDER BY city_name");

$levels = $db->fetchAll("SELECT * FROM levels ORDER BY level_id");
?>

<div class="student-section">
  <h2>Мой профиль</h2>

  <?php if ($update_message): ?>
    <?php echo $update_message; ?>
  <?php endif; ?>

  <?php if (!$can_update && !$just_updated): ?>
    <!-- вывод предупреждения только если не сейчас обновили -->
    <div class="alert alert-warning">
      <strong>Внимание!</strong> Вы можете редактировать профиль только раз в 7 дней.<br>
      Следующее редактирование будет доступно через <strong><?php echo $next_update_in; ?> дней</strong>.
      <?php if ($last_profile_update): ?>
        <br><small>Последнее обновление: <?php echo date('d.m.Y H:i', strtotime($last_profile_update)); ?></small>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- форма в которой заполняется профиль -->
  <form method="POST" class="profile-form">
    <div style="background: var(--light-gray); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
      <h3 style="color: var(--dark-blue); margin-bottom: 15px;">Информация об аккаунте</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div>
          <label style="font-size: 12px; color: var(--gray-medium);">Дата регистрации</label>
          <div style="font-weight: 600;">
            <?php echo date('d.m.Y', strtotime($currentUser['registration_date'])); ?>
          </div>
        </div>
        <div>
          <label style="font-size: 12px; color: var(--gray-medium);">Последнее обновление профиля</label>
          <div style="font-weight: 600;">
            <?php echo $last_profile_update ? date('d.m.Y H:i', strtotime($last_profile_update)) : 'Никогда'; ?>
          </div>
        </div>
        <div>
          <label style="font-size: 12px; color: var(--gray-medium);">Статус аккаунта</label>
          <div style="font-weight: 600; color: <?php echo $currentUser['is_active'] ? '#2ed573' : '#d90429'; ?>;">
            <?php echo $currentUser['is_active'] ? ' Активен' : ' Заблокирован'; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- поля ввода значений для изменения профиля -->
    <div class="form-group">
      <label for="username" class="required">Имя пользователя</label>
      <input type="text" id="username" class="form-control"
        value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
      <small style="color: var(--gray-medium);">Имя пользователя нельзя изменить</small>
    </div>

    <div class="form-group">
      <label for="email" class="required">Email</label>
      <input type="email" id="email" name="email" class="form-control"
        value="<?php echo htmlspecialchars($currentUser['email']); ?>" <?php echo !$can_update ? 'disabled' : ''; ?>
        required>
      <small style="color: var(--gray-medium);">На этот email будут приходить уведомления</small>
    </div>

    <div class="form-group">
      <label for="full_name" class="required">Полное имя</label>
      <input type="text" id="full_name" class="form-control"
        value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" disabled>
      <small style="color: var(--gray-medium);">Для изменения имени обратитесь в поддержку</small>
    </div>

    <div class="form-group">
      <label for="city_id">Город</label>
      <select id="city_id" name="city_id" class="form-control" <?php echo !$can_update ? 'disabled' : ''; ?>>
        <option value="">Выберите город</option>
        <?php foreach ($cities as $city): ?>
          <option value="<?php echo $city['city_id']; ?>" <?php echo ($currentUser['city_id'] ?? 0) == $city['city_id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($city['city_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small style="color: var(--gray-medium);">Укажите для поиска репетиторов в вашем городе</small>
    </div>

    <div class="form-group">
      <label for="current_level_id">Текущий уровень</label>
      <select id="current_level_id" class="form-control" disabled>
        <?php foreach ($levels as $level): ?>
          <option value="<?php echo $level['level_id']; ?>" <?php echo ($currentUser['current_level_id'] ?? 1) == $level['level_id'] ? 'selected' : ''; ?>>
            <?php echo $level['level_code']; ?> - <?php echo $level['level_name']; ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small style="color: var(--gray-medium);">Уровень автоматически повышается при успешном обучении</small>
    </div>

    <div class="form-group">
      <?php if ($can_update): ?>
        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        <small style="display: block; margin-top: 5px; color: var(--gray-medium);">
          Вы можете редактировать профиль 1 раз в 7 дней
        </small>
      <?php else: ?>
        <button type="button" class="btn btn-secondary" disabled>Редактирование временно недоступно</button>
        <small style="display: block; margin-top: 5px; color: var(--gray-medium);">
          Следующее редактирование будет доступно через <?php echo $next_update_in; ?> дней
          <?php if ($last_profile_update): ?>
            (после <?php echo date('d.m.Y', strtotime($last_profile_update . ' +7 days')); ?>)
          <?php endif; ?>
        </small>
      <?php endif; ?>
      <a href="?page=dashboard" class="btn btn-outline" style="margin-left: 10px;">Отмена</a>
    </div>
  </form>
</div>

<!-- секция безопасности -->
<div class="student-section">
  <h2>Безопасность</h2>
  <p style="color: var(--gray-medium); margin-bottom: 20px;">
    Для изменения пароля или других настроек безопасности обратитесь в поддержку.
  </p>

  <div style="background: var(--light-gray); padding: 20px; border-radius: 8px;">
    <h4 style="color: var(--dark-blue); margin-bottom: 15px;"> Статистика безопасности</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
      <div>
        <label style="font-size: 12px; color: var(--gray-medium);">Тип аутентификации</label>
        <div style="font-weight: 600;">Email + Пароль</div>
      </div>
    </div>
  </div>
</div>
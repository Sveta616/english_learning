<?php
// инфа профиля о последнем изменении
$last_update = $tutor['updated_at'] ?? $tutor['created_at'] ?? null;
$can_update = true;
$next_update_in = 0;

if ($last_update) {
  $last_update_date = new DateTime($last_update);
  $now = new DateTime();
  $interval = $last_update_date->diff($now);
  $days_passed = $interval->days;

  $can_update = $days_passed >= 7;
  $next_update_in = 7 - $days_passed;
  if ($next_update_in < 0)
    $next_update_in = 0;
}

// сообщение и логика обработки обновления профиля
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_update) {
  $update_data = [
    'bio' => $_POST['bio'] ?? null,
    'experience_years' => isset($_POST['experience_years']) ? (int) $_POST['experience_years'] : null,
    'hourly_rate' => isset($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : null,
    'specialization_id' => isset($_POST['specialization_id']) ? (int) $_POST['specialization_id'] : null,
    'phone' => $_POST['phone'] ?? null
  ];

  if (!empty($_POST['city_id'])) {
    $update_data['city_id'] = (int) $_POST['city_id'];
  }

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
      $db->update(
        'tutors',
        ['email' => trim($_POST['email']), 'updated_at' => date('Y-m-d H:i:s')],
        'tutor_id = ?',
        [$tutor['tutor_id']]
      );
      $update_message .= 'Email успешно обновлен.<br>';
    } else {
      $update_message .= '<span style="color: var(--primary-red);">Email уже занят другим пользователем.</span><br>';
    }
  }

  $update_data = array_filter($update_data, function ($value) {
    return $value !== null && $value !== '';
  });

  // если профиль успешно обновлен то выводим сообщение об успехе
  if (!empty($update_data)) {
    // Устанавливаем текущее время как время обновления
    $update_data['updated_at'] = date('Y-m-d H:i:s');

    $db->update('tutors', $update_data, 'tutor_id = ?', [$tutor['tutor_id']]);
    $update_message .= 'Профиль успешно обновлен!';

    // обновляем updated_at у пользователя
    $db->update(
      'users',
      ['updated_at' => date('Y-m-d H:i:s')],
      'user_id = ?',
      [$currentUser['user_id']]
    );

    // обновляем город у пользователя, если он изменен
    if (isset($update_data['city_id'])) {
      $db->update(
        'users',
        ['city_id' => $update_data['city_id']],
        'user_id = ?',
        [$currentUser['user_id']]
      );
    }

    // перезагрузка данные
    $tutor = $db->fetchOne(
      "SELECT t.*, c.city_name, ts.name as specialization_name 
             FROM tutors t 
             LEFT JOIN cities c ON t.city_id = c.city_id 
             LEFT JOIN tutor_specializations ts ON t.specialization_id = ts.specialization_id 
             WHERE t.tutor_id = ?",
      [$tutor['tutor_id']]
    );

    $currentUser = $userClass->getById($currentUser['user_id']);

    // обновляем переменные 
    $last_update = $update_data['updated_at']; // новое время обновления
    $last_update_date = new DateTime($last_update);
    $now = new DateTime();
    $interval = $last_update_date->diff($now);
    $days_passed = $interval->days;

    $can_update = false; // обновили, нужно ждать 7 дней
    $next_update_in = 7;
  }
}

$specializations = $db->fetchAll("SELECT * FROM tutor_specializations ORDER BY name");
$cities = $db->fetchAll("SELECT city_id, city_name FROM cities WHERE is_active = 1 ORDER BY city_name");
?>

<div class="tutor-section">
  <h2>Редактирование профиля</h2>

  <?php if ($update_message): ?>
    <div class="alert <?php echo strpos($update_message, 'успешно') !== false ? 'alert-success' : 'alert-warning'; ?>">
      <?php echo $update_message; ?>
    </div>
  <?php endif; ?>

  <?php if (!$can_update && $next_update_in > 0): ?>
    <div class="alert alert-warning">
      <strong>Внимание!</strong> Вы можете редактировать профиль только раз в 7 дней.<br>
      Следующее редактирование будет доступно через <strong><?php echo $next_update_in; ?> дней</strong>.
      <?php if ($last_update): ?>
        <br><small>Последнее обновление: <?php echo date('d.m.Y H:i', strtotime($last_update)); ?></small>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- форма с основной инфой и полями редактирования -->
  <form method="POST" class="profile-form">
    <div style="background: var(--light-gray); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
      <h3 style="color: var(--dark-blue); margin-bottom: 15px;">Информация об аккаунте</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div>
          <label style="font-size: 12px; color: var(--medium-gray);">Дата регистрации</label>
          <div style="font-weight: 600;"><?php echo date('d.m.Y', strtotime($currentUser['registration_date'])); ?>
          </div>
        </div>
        <div>
          <label style="font-size: 12px; color: var(--medium-gray);">Последнее обновление</label>
          <div style="font-weight: 600;">
            <?php echo $last_update ? date('d.m.Y H:i', strtotime($last_update)) : 'Никогда'; ?>
          </div>
        </div>
        <div>
          <label style="font-size: 12px; color: var(--medium-gray);">Статус аккаунта</label>
          <div style="font-weight: 600; color: <?php echo $tutor['is_verified'] ? '#2ed573' : '#ffc107'; ?>;">
            <?php echo $tutor['is_verified'] ? 'Подтвержден' : '⏳ На проверке'; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label for="email" class="required">Email</label>
      <input type="email" id="email" name="email" class="form-control"
        value="<?php echo htmlspecialchars($currentUser['email']); ?>" <?php echo !$can_update ? 'disabled' : ''; ?>
        required>
      <small style="color: var(--medium-gray);">На этот email будут приходить уведомления о заявках</small>
    </div>

    <div class="form-group">
      <label for="full_name" class="required">Полное имя</label>
      <input type="text" id="full_name" class="form-control"
        value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" disabled>
      <small style="color: var(--medium-gray);">Для изменения имени обратитесь в поддержку</small>
    </div>

    <div class="form-group">
      <label for="city_id">Город</label>
      <select id="city_id" name="city_id" class="form-control" <?php echo !$can_update ? 'disabled' : ''; ?>>
        <option value="">Выберите город</option>
        <?php foreach ($cities as $city): ?>
          <option value="<?php echo $city['city_id']; ?>" <?php echo ($tutor['city_id'] ?? 0) == $city['city_id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($city['city_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small style="color: var(--medium-gray);">Студенты будут искать репетиторов по городу</small>
    </div>

    <div class="form-group">
      <label for="phone">Телефон</label>
      <input type="tel" id="phone" name="phone" class="form-control"
        value="<?php echo htmlspecialchars($tutor['phone'] ?? ''); ?>" <?php echo !$can_update ? 'disabled' : ''; ?>
        placeholder="+7 (999) 123-45-67">
      <small style="color: var(--medium-gray);">Необязательно. Будет виден только после принятия заявки</small>
    </div>

    <div class="form-group">
      <label for="specialization_id">Специализация</label>
      <select id="specialization_id" name="specialization_id" class="form-control" <?php echo !$can_update ? 'disabled' : ''; ?>>
        <option value="">Выберите специализацию</option>
        <?php foreach ($specializations as $spec): ?>
          <option value="<?php echo $spec['specialization_id']; ?>" <?php echo ($tutor['specialization_id'] ?? 0) == $spec['specialization_id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($spec['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="experience_years">Опыт работы (лет)</label>
      <input type="number" id="experience_years" name="experience_years" class="form-control"
        value="<?php echo $tutor['experience_years'] ?? ''; ?>" <?php echo !$can_update ? 'disabled' : ''; ?> min="0"
        max="50" step="1">
    </div>

    <div class="form-group">
      <label for="hourly_rate">Стоимость занятия (₽/час)</label>
      <input type="number" id="hourly_rate" name="hourly_rate" class="form-control"
        value="<?php echo $tutor['hourly_rate'] ?? ''; ?>" <?php echo !$can_update ? 'disabled' : ''; ?> min="0"
        step="100">
    </div>

    <div class="form-group">
      <label for="bio">О себе</label>
      <textarea id="bio" name="bio" class="form-control" rows="6" <?php echo !$can_update ? 'disabled' : ''; ?>
        placeholder="Расскажите о себе, своем опыте преподавания, методиках, образовании..."><?php echo htmlspecialchars($tutor['bio'] ?? ''); ?></textarea>
      <small style="color: var(--medium-gray);">Чем подробнее описание, тем больше студентов заинтересуются</small>
    </div>

    <div class="form-group">
      <?php if ($can_update): ?>
        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
      <?php else: ?>
        <button type="button" class="btn btn-secondary" disabled>Редактирование временно недоступно</button>
      <?php endif; ?>
    </div>
  </form>
</div>
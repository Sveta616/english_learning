<?php
// логика обработки работы с репетитора (отправка заявки\своих данных\фильтрация репетиторов)
$city_id = $_POST['city_id'] ?? $_GET['city_id'] ?? null;
$specialization_id = $_POST['specialization_id'] ?? $_GET['specialization_id'] ?? null;
$min_rating = $_POST['min_rating'] ?? $_GET['min_rating'] ?? null;
$max_price = $_POST['max_price'] ?? $_GET['max_price'] ?? null;

$filters = [];
if ($city_id)
  $filters['city_id'] = $city_id;
if ($specialization_id)
  $filters['specialization_id'] = $specialization_id;
if ($min_rating)
  $filters['min_rating'] = $min_rating;
if ($max_price)
  $filters['max_price'] = $max_price;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
  $_SESSION['tutor_filters'] = $filters;
} elseif (isset($_SESSION['tutor_filters']) && empty($_POST)) {
  $filters = $_SESSION['tutor_filters'];
  $city_id = $filters['city_id'] ?? null;
  $specialization_id = $filters['specialization_id'] ?? null;
  $min_rating = $filters['min_rating'] ?? null;
  $max_price = $filters['max_price'] ?? null;
}

$tutors = $userClass->searchTutors($filters);

$specializations = $db->fetchAll("SELECT * FROM tutor_specializations ORDER BY name");

$cities = $db->fetchAll("SELECT city_id, city_name FROM cities WHERE is_active = 1 ORDER BY city_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
  $tutor_id = (int) $_POST['tutor_id'];
  $request_text = trim($_POST['request_text'] ?? '');
  $student_age = (int) $_POST['student_age'] ?? null;
  $social_media = trim($_POST['social_media'] ?? '');
  $student_contact_name = trim($_POST['student_contact_name'] ?? '');
  $student_contact_email = trim($_POST['student_contact_email'] ?? '');
  $student_contact_phone = trim($_POST['student_contact_phone'] ?? '');

  // валидация
  if (empty($request_text)) {
    $error = "Пожалуйста, напишите сообщение репетитору";
  } elseif (empty($student_contact_name) || empty($student_contact_email)) {
    $error = "Пожалуйста, заполните контактную информацию";
  } elseif ($student_age < 6 || $student_age > 100) {
    $error = "Пожалуйста, укажите корректный возраст (6-100 лет)";
  } else {
    $requestData = [
      'student_id' => $currentUser['user_id'],
      'tutor_id' => $tutor_id,
      'request_text' => $request_text,
      'student_contact_name' => $student_contact_name,
      'student_contact_email' => $student_contact_email,
      'student_contact_phone' => $student_contact_phone ?: null,
      'student_age' => $student_age,
      'social_media' => $social_media ?: null,
      'status' => 'pending',
      'request_date' => date('Y-m-d H:i:s')
    ];

    $db->insert('tutor_requests', $requestData);
    $success = "Заявка успешно отправлена репетитору!";
  }
}
?>
<style>
  #requestModal form .form-group {
    margin-bottom: 20px;
  }

  #requestModal label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark-blue);
  }

  #requestModal label.required::after {
    content: ' *';
    color: var(--primary-red);
  }

  #requestModal .form-control {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid var(--medium-gray);
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
  }

  #requestModal .form-control:focus {
    outline: none;
    border-color: var(--primary-red);
    box-shadow: 0 0 0 3px rgba(217, 4, 41, 0.1);
  }

  #requestModal textarea.form-control {
    resize: vertical;
    min-height: 120px;
  }

  #requestModal>div {
    scrollbar-width: thin;
    scrollbar-color: var(--medium-gray) transparent;
  }

  #requestModal>div::-webkit-scrollbar {
    width: 6px;
  }

  #requestModal>div::-webkit-scrollbar-track {
    background: transparent;
  }

  #requestModal>div::-webkit-scrollbar-thumb {
    background-color: var(--medium-gray);
    border-radius: 3px;
  }

  @keyframes modalFadeIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  #requestModal>div {
    animation: modalFadeIn 0.3s ease-out;
  }
</style>

<div class="student-section">
  <h2>Поиск репетиторов</h2>

  <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
  <?php endif; ?>

  <?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
  <?php endif; ?>

  <!-- форма фильтров -->
  <form method="POST" class="profile-form" style="margin-bottom: 30px;">
    <input type="hidden" name="search" value="1">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
      <!-- фильтр города  -->
      <div>
        <label for="city_id">Город</label>
        <select id="city_id" name="city_id" class="form-control">
          <option value="">Все города</option>
          <?php foreach ($cities as $city): ?>
            <option value="<?php echo $city['city_id']; ?>" <?php echo ($city_id == $city['city_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($city['city_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- фильтр специализации -->
      <div>
        <label for="specialization_id">Специализация</label>
        <select id="specialization_id" name="specialization_id" class="form-control">
          <option value="">Все специализации</option>
          <?php foreach ($specializations as $spec): ?>
            <option value="<?php echo $spec['specialization_id']; ?>" <?php echo ($specialization_id == $spec['specialization_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($spec['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- фильтр рейтинг -->
      <div>
        <label for="min_rating">Минимальный рейтинг</label>
        <select id="min_rating" name="min_rating" class="form-control">
          <option value="">Любой</option>
          <option value="4.5" <?php echo ($min_rating == '4.5') ? 'selected' : ''; ?>>4.5+ ⭐</option>
          <option value="4.0" <?php echo ($min_rating == '4.0') ? 'selected' : ''; ?>>4.0+ ⭐</option>
          <option value="3.5" <?php echo ($min_rating == '3.5') ? 'selected' : ''; ?>>3.5+ ⭐</option>
        </select>
      </div>

      <!-- фильтр по цене  -->
      <div>
        <label for="max_price">Максимальная цена (₽/час)</label>
        <input type="number" id="max_price" name="max_price" class="form-control"
          value="<?php echo htmlspecialchars($max_price ?? ''); ?>" placeholder="Например: 1500">
      </div>
    </div>

    <!-- кнопка поиска -->
    <div style="margin-top: 20px;">
      <button type="submit" class="btn btn-primary">Найти репетиторов</button>
      <a href="?page=tutors&clear_filters=1" class="btn btn-outline" style="margin-left: 10px;">Сбросить фильтры</a>
    </div>
  </form>

  <!-- вывод всех репетиторов из бд которые верефицированны\активны и соответсвуют фильтрам -->
  <div>
    <h3>Найдено репетиторов: <?php echo count($tutors); ?></h3>

    <?php if (!empty($tutors)): ?>
      <div style="margin-top: 20px;">
        <?php foreach ($tutors as $tutor): ?>
          <div class="request-item" style="margin-bottom: 20px;">
            <div style="display: flex; gap: 20px;">
              <div style="flex-shrink: 0;">
                <div
                  style="width: 80px; height: 80px; border-radius: 50%; background: var(--gray-light); 
                                            display: flex; align-items: center; justify-content: center; font-size: 24px;">
                  <?php
                  $tutorInitials = '';
                  $tutorNames = explode(' ', $tutor['full_name'], 2);
                  if (!empty($tutorNames[0])) {
                    $tutorInitials = strtoupper(mb_substr($tutorNames[0], 0, 1, 'UTF-8'));
                    if (!empty($tutorNames[1])) {
                      $tutorInitials .= strtoupper(mb_substr($tutorNames[1], 0, 1, 'UTF-8'));
                    }
                  }
                  echo $tutorInitials;
                  ?>
                </div>
              </div>

              <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                  <div>
                    <h4 style="margin: 0; color: var(--dark-blue);">
                      <?php echo htmlspecialchars($tutor['full_name']); ?>
                      <?php if ($tutor['is_verified']): ?>
                        <span style="color: #2ed573; font-size: 14px;"> Проверен</span>
                      <?php endif; ?>
                    </h4>

                    <div style="display: flex; gap: 10px; margin-top: 5px; color: var(--medium-gray); font-size: 14px;">
                      <?php if ($tutor['city_name']): ?>
                        <span><?php echo htmlspecialchars($tutor['city_name']); ?></span>
                      <?php endif; ?>

                      <?php if ($tutor['specialization_name']): ?>
                        <span> <?php echo htmlspecialchars($tutor['specialization_name']); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div style="text-align: right;">
                    <?php if ($tutor['hourly_rate']): ?>
                      <div style="font-weight: bold; color: var(--primary-red); font-size: 18px;">
                        <?php echo number_format($tutor['hourly_rate'], 0, ',', ' '); ?> ₽/час
                      </div>
                    <?php endif; ?>

                    <div style="color: #ffc107;">
                      <?php echo str_repeat('★', round($tutor['rating'])); ?>
                      <span style="color: var(--dark-blue);">(<?php echo $tutor['rating']; ?>)</span>
                    </div>
                  </div>
                </div>

                <?php if ($tutor['bio']): ?>
                  <div style="margin-top: 10px;">
                    <p style="color: var(--medium-gray); margin: 0;">
                      <?php echo nl2br(htmlspecialchars(mb_substr($tutor['bio'], 0, 200) . (strlen($tutor['bio']) > 200 ? '...' : ''))); ?>
                    </p>
                  </div>
                <?php endif; ?>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                  <span style="background: var(--light-gray); padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                    Опыт: <?php echo $tutor['experience_years'] ?? 0; ?> лет
                  </span>
                  <span style="background: var(--light-gray); padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                    Отзывов: <?php echo $tutor['total_reviews']; ?>
                  </span>
                </div>

                <div style="margin-top: 15px;">
                  <button type="button" class="btn btn-primary"
                    onclick="showRequestForm(<?php echo $tutor['tutor_id']; ?>, '<?php echo htmlspecialchars($tutor['full_name'], ENT_QUOTES); ?>')">
                    Отправить заявку
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color: var(--medium-gray); text-align: center; padding: 30px;">
        По вашему запросу репетиторы не найдены. Попробуйте изменить критерии поиска.
      </p>
    <?php endif; ?>
  </div>
</div>

<!-- модальное окно отправки заявки -->
<div id="requestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
     background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
  <div
    style="background: white; width: 90%; max-width: 600px; border-radius: 10px; padding: 30px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 style="margin: 0; color: var(--dark-blue);">Отправка заявки репетитору</h3>
      <button type="button" onclick="hideRequestForm()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--medium-gray);">
        ×
      </button>
    </div>

    <p style="color: var(--medium-gray); margin-bottom: 25px;" id="tutorName"></p>

    <!-- форма где пользователь вводит данные -->
    <form method="POST" id="requestForm">
      <input type="hidden" name="tutor_id" id="modalTutorId">
      <input type="hidden" name="send_request" value="1">

      <div style="background: rgba(217, 4, 41, 0.05); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
        <h4 style="color: var(--dark-blue); margin-bottom: 15px;">Контактная информация</h4>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
          <div class="form-group">
            <label for="student_contact_name" class="required">ФИО</label>
            <input type="text" id="student_contact_name" name="student_contact_name" class="form-control"
              value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
          </div>

          <div class="form-group">
            <label for="student_contact_email" class="required">Email</label>
            <input type="email" id="student_contact_email" name="student_contact_email" class="form-control"
              value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
          </div>

          <div class="form-group">
            <label for="student_contact_phone">Телефон</label>
            <input type="tel" id="student_contact_phone" name="student_contact_phone" class="form-control"
              placeholder="+7 (___) ___-__-__" pattern="\+7\s?[0-9]{3}\s?[0-9]{3}\s?[0-9]{2}\s?[0-9]{2}">
            <small style="color: var(--medium-gray); font-size: 12px;">Формат: +7 XXX XXX XX XX</small>
          </div>

          <div class="form-group">
            <label for="student_age" class="required">Возраст</label>
            <input type="number" id="student_age" name="student_age" class="form-control" min="6" max="100" required>
          </div>
        </div>
      </div>

      <!-- социальные сети -->
      <div class="form-group" style="margin-bottom: 25px;">
        <label for="social_media">Социальные сети / Мессенджеры</label>
        <input type="text" id="social_media" name="social_media" class="form-control"
          placeholder="@telegram_username, vk.com/id123456, instagram.com/username">
        <small style="color: var(--medium-gray); font-size: 12px;">Укажите свои соцсети для связи
          (необязательно)</small>
      </div>

      <!-- сообщение репетитору -->
      <div class="form-group" style="margin-bottom: 25px;">
        <label for="request_text" class="required">Сообщение репетитору</label>
        <textarea id="request_text" name="request_text" class="form-control" rows="5" required
          placeholder="Опишите, что вы хотите изучать, ваш текущий уровень, удобное время для занятий, продолжительность уроков и другие детали..."></textarea>
        <div style="display: flex; justify-content: space-between; margin-top: 8px;">
          <small style="color: var(--medium-gray); font-size: 12px;">
            Чем подробнее вы опишете свои цели, тем точнее репетитор сможет вам помочь
          </small>
          <small style="color: var(--medium-gray); font-size: 12px;" id="charCount">0/1000</small>
        </div>
      </div>

      <!-- информация для справки -->
      <div style="background: rgba(141, 153, 174, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 25px;">
        <div style="display: flex; align-items: flex-start; gap: 10px; color: var(--medium-gray);">
          <div style="font-size: 13px;">
            <strong>Что написать в сообщении:</strong><br>
            • Ваш текущий уровень английского<br>
            • Цели обучения (разговорный, грамматика, экзамены)<br>
            • Удобные дни и время для занятий<br>
            • Предпочтительный формат (онлайн/офлайн)<br>
            • Бюджет на занятие (если важно)
          </div>
        </div>
      </div>

      <!-- кнопки -->
      <div style="display: flex; gap: 15px; margin-top: 25px;">
        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px;">
          Отправить заявку
        </button>
        <button type="button" class="btn btn-outline" onclick="hideRequestForm()" style="padding: 12px 24px;">
          Отмена
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // функция для открытия модалки и заполнения её данными
  function showRequestForm(tutorId, tutorName) {
    document.getElementById('modalTutorId').value = tutorId;
    document.getElementById('tutorName').textContent = 'Репетитор: ' + tutorName;
    document.getElementById('requestModal').style.display = 'flex';

    document.getElementById('requestForm').reset();

    document.getElementById('student_contact_name').value = '<?php echo htmlspecialchars($currentUser['full_name']); ?>';
    document.getElementById('student_contact_email').value = '<?php echo htmlspecialchars($currentUser['email']); ?>';

    document.getElementById('student_age').focus();
  }

  function hideRequestForm() {
    document.getElementById('requestModal').style.display = 'none';
  }

  const textarea = document.getElementById('request_text');
  const charCount = document.getElementById('charCount');

  if (textarea && charCount) {
    textarea.addEventListener('input', function () {
      const length = this.value.length;
      charCount.textContent = length + '/1000';

      if (length > 1000) {
        charCount.style.color = 'var(--primary-red)';
      } else if (length > 800) {
        charCount.style.color = '#ffc107';
      } else {
        charCount.style.color = 'var(--medium-gray)';
      }
    });

    charCount.textContent = textarea.value.length + '/1000';
  }

  // валидация ввода телефона
  const phoneInput = document.getElementById('student_contact_phone');
  if (phoneInput) {
    phoneInput.addEventListener('input', function (e) {
      let value = this.value.replace(/\D/g, '');

      if (value.length > 0) {
        if (!value.startsWith('7') && !value.startsWith('8')) {
          value = '7' + value;
        }

        let formatted = '+7 ';

        if (value.length > 1) {
          formatted += value.substring(1, 4);
        }
        if (value.length > 4) {
          formatted += ' ' + value.substring(4, 7);
        }
        if (value.length > 7) {
          formatted += ' ' + value.substring(7, 9);
        }
        if (value.length > 9) {
          formatted += ' ' + value.substring(9, 11);
        }

        this.value = formatted.trim();
      }
    });
  }
  // скрытие модалки 
  document.getElementById('requestModal').addEventListener('click', function (e) {
    if (e.target === this) {
      hideRequestForm();
    }
  });
  // скрытие модалки
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('requestModal').style.display === 'flex') {
      hideRequestForm();
    }
  });
</script>
<?php
if (isset($_POST['clear_filters']) || isset($_GET['clear_filters'])) {
  unset($_SESSION['tutor_filters']);
}
?>
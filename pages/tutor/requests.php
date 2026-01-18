<?php
$tutor_id = $tutor['tutor_id'];

$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// получаем все заявки к преподу
$sql = "SELECT tr.*, 
               u.full_name as student_name, 
               u.email as student_email,
               c.city_name as student_city,
               u.current_level_id,
               l.level_code as student_level
        FROM tutor_requests tr 
        LEFT JOIN users u ON tr.student_id = u.user_id 
        LEFT JOIN cities c ON u.city_id = c.city_id 
        LEFT JOIN levels l ON u.current_level_id = l.level_id 
        WHERE tr.tutor_id = ?";

$params = [$tutor_id];
$types = "i";

if ($status_filter !== 'all') {
  $sql .= " AND tr.status = ?";
  $params[] = $status_filter;
  $types .= "s";
}

// строка поиска
if ($search_query) {
  $search_like = "%{$search_query}%";
  $sql .= " AND (u.full_name LIKE ? OR 
                  u.email LIKE ? OR 
                  tr.student_contact_name LIKE ? OR 
                  tr.student_contact_email LIKE ? OR 
                  tr.student_contact_phone LIKE ? OR 
                  tr.social_media LIKE ? OR 
                  tr.request_text LIKE ?)";
  for ($i = 0; $i < 7; $i++) {
    $params[] = $search_like;
    $types .= "s";
  }
}

// сортируем заявки
$sql .= " ORDER BY 
            CASE 
                WHEN tr.status = 'pending' THEN 1
                WHEN tr.status = 'accepted' THEN 2
                WHEN tr.status = 'completed' THEN 3
                WHEN tr.status = 'rejected' THEN 4
                ELSE 5
            END,
            tr.request_date DESC";

$requests = $db->fetchAll($sql, $params);

if (isset($_GET['action'])) {
  $request_id = (int) ($_GET['id'] ?? 0);
  $action = $_GET['action'];

  if ($request_id && in_array($action, ['accept', 'reject', 'complete'])) {
    $request = $db->fetchOne(
      "SELECT * FROM tutor_requests WHERE request_id = ? AND tutor_id = ?",
      [$request_id, $tutor_id]
    );

    // статусы заявок
    if ($request) {
      $new_status = '';
      switch ($action) {
        case 'accept':
          $new_status = 'accepted';
          break;
        case 'reject':
          $new_status = 'rejected';
          break;
        case 'complete':
          $new_status = 'completed';
          break;
      }

      // обновляем статус если изменился
      if ($new_status) {
        $db->update(
          'tutor_requests',
          [
            'status' => $new_status,
            // 'response_date' => date('Y-m-d H:i:s')
          ],
          'request_id = ?',
          [$request_id]
        );

        $requests = $db->fetchAll($sql, $params);

        echo '<div class="alert alert-success">Статус заявки успешно обновлен!</div>';
      }
    }
  }
}

// фильтрация
$pending_requests = array_filter($requests, function ($r) {
  return $r['status'] === 'pending';
});
$accepted_requests = array_filter($requests, function ($r) {
  return $r['status'] === 'accepted';
});
$completed_requests = array_filter($requests, function ($r) {
  return $r['status'] === 'completed';
});
$rejected_requests = array_filter($requests, function ($r) {
  return $r['status'] === 'rejected';
});

$upcoming_lessons = array_filter($requests, function ($r) {
  return $r['status'] === 'accepted';
});

usort($upcoming_lessons, function ($a, $b) {
  return strtotime($a['request_date']) - strtotime($b['request_date']);
});

// находим все ближайщие из массива занятий
$upcoming_lessons = array_slice($upcoming_lessons, 0, 4);
?>
<!-- разметка где выводятся заявки, с логикой обработки в зависимости от статуса и действий -->
<div class="tutor-section">
  <h2>Заявки от студентов</h2>
  <div
    style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--light-gray);">
    <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto; gap: 10px; align-items: end;">
      <input type="hidden" name="page" value="requests">

      <div>
        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--dark-blue);">
          Поиск по всем полям
        </label>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" class="form-control"
          placeholder="Имя, email, телефон, соцсети, сообщение...">
      </div>

      <div>
        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--dark-blue);">
          Фильтр по статусу
        </label>
        <select name="status" class="form-control" onchange="this.form.submit()">
          <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Все статусы</option>
          <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Ожидают
            (<?php echo count($pending_requests); ?>)</option>
          <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Принятые
            (<?php echo count($accepted_requests); ?>)</option>
          <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Завершенные
            (<?php echo count($completed_requests); ?>)</option>
          <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Отклоненные
            (<?php echo count($rejected_requests); ?>)</option>
        </select>
      </div>

      <div>
        <button type="submit" class="btn btn-primary">Применить</button>
        <a href="?page=requests" class="btn btn-secondary" style="margin-left: 10px;">Сбросить</a>
      </div>
    </form>
  </div>

  <div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
    <div style="background: var(--light-gray); padding: 15px; border-radius: 8px; text-align: center;">
      <div style="font-size: 24px; font-weight: bold; color: var(--dark-blue);">
        <?php echo count($requests); ?>
      </div>
      <div style="font-size: 14px; color: var(--medium-gray);">Всего заявок</div>
    </div>
    <div style="background: rgba(255, 193, 7, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
      <div style="font-size: 24px; font-weight: bold; color: #ffc107;">
        <?php echo count($pending_requests); ?>
      </div>
      <div style="font-size: 14px; color: var(--medium-gray);">Ожидают</div>
    </div>
    <div style="background: rgba(46, 213, 115, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
      <div style="font-size: 24px; font-weight: bold; color: #2ed573;">
        <?php echo count($accepted_requests); ?>
      </div>
      <div style="font-size: 14px; color: var(--medium-gray);">Приняты</div>
    </div>
    <div style="background: rgba(237, 242, 244, 0.8); padding: 15px; border-radius: 8px; text-align: center;">
      <div style="font-size: 24px; font-weight: bold; color: var(--dark-blue);">
        <?php echo count($completed_requests); ?>
      </div>
      <div style="font-size: 14px; color: var(--medium-gray);">Завершены</div>
    </div>
  </div>

  <?php if (!empty($upcoming_lessons)): ?>
    <div class="tutor-section" style="margin-bottom: 30px;">
      <h3>Ближайшие занятия (4 из <?php echo count($accepted_requests); ?>)</h3>
      <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
        <?php foreach ($upcoming_lessons as $lesson):
          $date = new DateTime($lesson['request_date']);
          $now = new DateTime();
          $is_today = $date->format('Y-m-d') === $now->format('Y-m-d');
          ?>
          <div style="background: white; border: 2px solid <?php echo $is_today ? 'var(--primary-red)' : 'var(--dark-blue)'; ?>; 
                            border-radius: 10px; padding: 20px; position: relative;">
            <?php if ($is_today): ?>
              <div style="position: absolute; top: -10px; right: 20px; background: var(--primary-red); color: white; 
                                padding: 5px 15px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                СЕГОДНЯ
              </div>
            <?php endif; ?>

            <div style="font-size: 18px; font-weight: bold; color: var(--dark-blue); margin-bottom: 10px;">
              <?php echo htmlspecialchars($lesson['student_name']); ?>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
              <div>
                <div style="font-size: 14px; color: var(--medium-gray);">Дата и время</div>
                <div style="font-weight: 600;"><?php echo $date->format('d.m.Y H:i'); ?></div>
              </div>
              <?php if ($lesson['student_level']): ?>
                <div>
                  <div style="font-size: 14px; color: var(--medium-gray);">Уровень</div>
                  <div style="font-weight: 600;"><?php echo $lesson['student_level']; ?></div>
                </div>
              <?php endif; ?>
            </div>

            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--light-gray);">
              <div style="font-size: 12px; color: var(--medium-gray); margin-bottom: 5px;">Контакты:</div>
              <div style="font-size: 14px;"><?php echo htmlspecialchars($lesson['student_email']); ?></div>
              <?php if ($lesson['student_contact_phone']): ?>
                <div style="font-size: 14px; margin-top: 5px;">
                  <?php echo htmlspecialchars($lesson['student_contact_phone']); ?>
                </div>
              <?php endif; ?>
            </div>

            <div style="margin-top: 15px;">
              <a href="?page=schedule" class="btn btn-primary btn-sm">В расписание</a>
              <a href="?page=requests&action=complete&id=<?php echo $lesson['request_id']; ?>"
                class="btn btn-outline btn-sm" onclick="return confirm('Отметить занятие как завершенное?')">
                Завершить
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($accepted_requests) > 4): ?>
        <div style="text-align: center; margin-top: 15px;">
          <a href="?page=schedule" class="btn btn-secondary">Показать все (<?php echo count($accepted_requests); ?>)
            занятий</a>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="tutor-section">
    <h3>Список заявок</h3>

    <!-- выводим заявки -->
    <?php if (!empty($requests)): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Студент</th>
              <th>Контактные данные</th>
              <th>Дата заявки</th>
              <th>Статус</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $request): ?>
              <?php
              $date = new DateTime($request['request_date']);
              $response_date = $request['response_date'] ? new DateTime($request['response_date']) : null;
              ?>
              <tr>
                <td>
                  <div style="font-weight: 600;"><?php echo htmlspecialchars($request['student_name']); ?></div>
                  <div style="font-size: 12px; color: var(--medium-gray);">
                    <?php if ($request['student_city']): ?>
                      <?php echo htmlspecialchars($request['student_city']); ?>
                    <?php endif; ?>
                    <?php if ($request['student_level']): ?>
                      | Уровень: <?php echo $request['student_level']; ?>
                    <?php endif; ?>
                    <?php if ($request['student_age']): ?>
                      | Возраст: <?php echo $request['student_age']; ?>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div><?php echo htmlspecialchars($request['student_email']); ?></div>
                  <?php if ($request['student_contact_phone']): ?>
                    <div>📱 <?php echo htmlspecialchars($request['student_contact_phone']); ?></div>
                  <?php endif; ?>
                  <?php if ($request['social_media']): ?>
                    <div style="font-size: 12px; color: var(--medium-gray); margin-top: 5px;">
                      <?php echo htmlspecialchars($request['social_media']); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div><?php echo $date->format('d.m.Y H:i'); ?></div>
                  <?php if ($response_date): ?>
                    <div style="font-size: 12px; color: var(--medium-gray);">
                      Ответ: <?php echo $response_date->format('d.m.Y H:i'); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                  $status_styles = [
                    'pending' => ['color' => 'var(--primary-red)', 'label' => 'Ожидает', 'icon' => ''],
                    'accepted' => ['color' => '#2ed573', 'label' => 'Принята', 'icon' => ''],
                    'rejected' => ['color' => 'var(--medium-gray)', 'label' => 'Отклонена', 'icon' => ''],
                    'completed' => ['color' => 'var(--dark-blue)', 'label' => 'Завершена', 'icon' => '']
                  ];
                  $style = $status_styles[$request['status']] ?? ['color' => 'var(--medium-gray)', 'label' => $request['status'], 'icon' => 'Неизвестно'];
                  ?>
                  <span
                    style="color: <?php echo $style['color']; ?>; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                    <?php echo $style['icon']; ?>     <?php echo $style['label']; ?>
                  </span>
                </td>
                <td>
                  <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <?php if ($request['status'] === 'pending'): ?>
                      <a href="?page=requests&action=accept&id=<?php echo $request['request_id']; ?>"
                        class="btn btn-success btn-sm" onclick="return confirm('Принять эту заявку?')">
                        Принять
                      </a>
                      <a href="?page=requests&action=reject&id=<?php echo $request['request_id']; ?>"
                        class="btn btn-outline btn-sm" onclick="return confirm('Отклонить эту заявку?')">
                        Отклонить
                      </a>
                    <?php elseif ($request['status'] === 'accepted'): ?>
                      <a href="?page=requests&action=complete&id=<?php echo $request['request_id']; ?>"
                        class="btn btn-primary btn-sm" onclick="return confirm('Отметить занятие как завершенное?')">
                        Завершить
                      </a>
                      <a href="?page=requests&action=reject&id=<?php echo $request['request_id']; ?>"
                        class="btn btn-outline btn-sm" onclick="return confirm('Отменить это занятие?')">
                        Отменить
                      </a>
                    <?php elseif ($request['status'] === 'completed'): ?>
                      <span class="btn btn-secondary btn-sm" style="background: #6f6f6f" disabled>Завершено</span>
                    <?php elseif ($request['status'] === 'rejected'): ?>
                      <a href="?page=requests&action=accept&id=<?php echo $request['request_id']; ?>"
                        class="btn btn-success btn-sm" onclick="return confirm('Восстановить эту заявку?')">
                        ↩Восстановить
                      </a>
                    <?php endif; ?>

                    <?php if ($request['request_text']): ?>
                      <button type="button" onclick="showRequestDetails(<?php echo $request['request_id']; ?>)"
                        class="btn btn-secondary btn-sm">
                        Подробнее
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- если заявок нет -->
    <?php else: ?>
      <div
        style="text-align: center; padding: 50px; background: var(--light-gray); border-radius: 8px; margin-top: 20px;">
        <h3 style="color: var(--dark-blue); margin-bottom: 15px;">
          <?php echo $search_query || $status_filter !== 'all' ? 'Заявки не найдены' : 'У вас пока нет заявок'; ?>
        </h3>
        <p style="color: var(--medium-gray); margin-bottom: 20px;">
          <?php if ($search_query || $status_filter !== 'all'): ?>
            Попробуйте изменить параметры поиска или сбросить фильтры
          <?php else: ?>
            Когда студенты будут отправлять вам заявки, они появятся здесь
          <?php endif; ?>
        </p>
        <?php if ($search_query || $status_filter !== 'all'): ?>
          <a href="?page=requests" class="btn btn-primary">Сбросить фильтры</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<!-- модалка с деталями выбранной заявки -->
<div id="requestDetailsModal"
  style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                                     background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
  <div
    style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 style="margin: 0;">Детали заявки</h3>
      <button type="button" onclick="closeDetailsModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--medium-gray);">
        ×
      </button>
    </div>
    <!-- здесь все детали -->
    <div id="requestDetailsContent"></div>
  </div>
</div>

<script>
  // открываем в модальном окне детали выбранной заявки через апи
  function showRequestDetails(requestId) {
    fetch(`../../api/tutor/get_request_details.php?id=${requestId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const details = data.request;
          const modal = document.getElementById('requestDetailsModal');
          const content = document.getElementById('requestDetailsContent');

          let html = `
                    <div style="margin-bottom: 20px;">
                        <h4 style="color: var(--dark-blue); margin-bottom: 10px;">Информация о студенте</h4>
                        <p><strong>Имя:</strong> ${details.student_name}</p>
                        <p><strong>Email:</strong> ${details.student_email}</p>
                        ${details.student_city ? `<p><strong>Город:</strong> ${details.student_city}</p>` : ''}
                    </div>
                `;

          if (details.request_text) {
            html += `
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: var(--dark-blue); margin-bottom: 10px;">Сообщение от студента</h4>
                            <p style="background: var(--light-gray); padding: 15px; border-radius: 5px; font-style: italic;">
                                "${details.request_text}"
                            </p>
                        </div>
                    `;
          }

          html += `
                    <div style="margin-bottom: 20px;">
                        <h4 style="color: var(--dark-blue); margin-bottom: 10px;">Контактная информация</h4>
                        ${details.student_contact_phone ? `<p><strong>Телефон:</strong> ${details.student_contact_phone}</p>` : ''}
                        ${details.student_age ? `<p><strong>Возраст:</strong> ${details.student_age}</p>` : ''}
                        ${details.social_media ? `<p><strong>Соцсети:</strong> ${details.social_media}</p>` : ''}
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h4 style="color: var(--dark-blue); margin-bottom: 10px;">Детали заявки</h4>
                        <p><strong>Отправлено:</strong> ${new Date(details.request_date).toLocaleString('ru-RU')}</p>
                        ${details.response_date ? `<p><strong>Ответ отправлен:</strong> ${new Date(details.response_date).toLocaleString('ru-RU')}</p>` : ''}
                    </div>
                `;

          content.innerHTML = html;
          modal.style.display = 'flex';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при загрузке деталей заявки');
      });
  }

  // функция закрытия модалки
  function closeDetailsModal() {
    document.getElementById('requestDetailsModal').style.display = 'none';
  }
  document.getElementById('requestDetailsModal').addEventListener('click', function (e) {
    if (e.target === this) {
      closeDetailsModal();
    }
  });
</script>
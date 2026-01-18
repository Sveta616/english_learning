<?php

// получаем расписание
$requests = $db->fetchAll(
  "SELECT tr.*, 
            t.tutor_id,
            t.full_name as tutor_name,
            t.rating as tutor_rating,
            t.total_reviews,
            c.city_name,
            ts.name as specialization_name
     FROM tutor_requests tr
     JOIN tutors t ON tr.tutor_id = t.tutor_id
     LEFT JOIN cities c ON t.city_id = c.city_id
     LEFT JOIN tutor_specializations ts ON t.specialization_id = ts.specialization_id
     WHERE tr.student_id = ?
     ORDER BY tr.request_date DESC",
  [$currentUser['user_id']]
);

$message = '';

// логика выставления оценки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
  $request_id = (int) ($_POST['request_id'] ?? 0);
  $rating = (int) ($_POST['rating'] ?? 0);

  $existing_request = $db->fetchOne(
    "SELECT * FROM tutor_requests WHERE request_id = ? AND student_id = ?",
    [$request_id, $currentUser['user_id']]
  );

  if ($existing_request && $existing_request['status'] === 'completed' && !$existing_request['is_rated']) {
    if ($rating >= 1 && $rating <= 5) {
      $tutor_data = $db->fetchOne(
        "SELECT rating, total_reviews FROM tutors WHERE tutor_id = ?",
        [$existing_request['tutor_id']]
      );

      if ($tutor_data) {
        $current_rating = (float) $tutor_data['rating'];
        $total_reviews = (int) $tutor_data['total_reviews'];

        $new_rating = ($current_rating + $rating) / 2;
        $new_total_reviews = $total_reviews + 1;

        // Обновляем рейтинг репетитора
        $db->update('tutors', [
          'rating' => round($new_rating, 2),
          'total_reviews' => $new_total_reviews,
          'updated_at' => date('Y-m-d H:i:s')
        ], 'tutor_id = ?', [$existing_request['tutor_id']]);

        // обновляем заявку и отмечаем что оценена
        $db->update('tutor_requests', [
          'is_rated' => 1,
          'response_date' => date('Y-m-d H:i:s')
        ], 'request_id = ?', [$request_id]);

        $message = '<div class="alert alert-success">Оценка отправлена!</div>';

        // обновляем данные 
        foreach ($requests as &$req) {
          if ($req['request_id'] == $request_id) {
            $req['tutor_rating'] = $new_rating;
            $req['total_reviews'] = $new_total_reviews;
            $req['is_rated'] = 1;
            $req['response_date'] = date('Y-m-d H:i:s');
          }
        }
      }
    } else {
      $message = '<div class="alert alert-error">Выберите оценку от 1 до 5.</div>';
    }
  } else {
    $message = '<div class="alert alert-error">Нельзя оценить эту заявку.</div>';
  }
}

// фильтры
$status_filter = $_GET['status'] ?? 'all';
$filtered_requests = [];

foreach ($requests as $request) {
  if ($status_filter === 'all' || $request['status'] === $status_filter) {
    $filtered_requests[] = $request;
  }
}

// кол-во типов заявок
$status_stats = [
  'pending' => 0,
  'accepted' => 0,
  'completed' => 0,
  'rejected' => 0,
  'all' => count($requests)
];

foreach ($requests as $request) {
  if (isset($status_stats[$request['status']])) {
    $status_stats[$request['status']]++;
  }
}
?>

<div class="student-section">
  <h2>Мои занятия</h2>

  <?php echo $message; ?>

  <!-- фильтры по статусу -->
  <div style="margin-bottom: 25px;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="?page=schedule&status=all"
        style="background: var(--dark-blue); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: 600;"
        class="<?php echo $status_filter === 'all' ? 'active' : ''; ?>">
        Все (<?php echo $status_stats['all']; ?>)
      </a>
      <a href="?page=schedule&status=pending"
        style="background: #ffc107; color: #333; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: 600;"
        class="<?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
        Ожидание (<?php echo $status_stats['pending']; ?>)
      </a>
      <a href="?page=schedule&status=accepted"
        style="background: #4cc9f0; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: 600;"
        class="<?php echo $status_filter === 'accepted' ? 'active' : ''; ?>">
        Подтверждено (<?php echo $status_stats['accepted']; ?>)
      </a>
      <a href="?page=schedule&status=completed"
        style="background: #2ed573; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: 600;"
        class="<?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
        Завершено (<?php echo $status_stats['completed']; ?>)
      </a>
      <a href="?page=schedule&status=rejected"
        style="background: var(--primary-red); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: 600;"
        class="<?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
        Отклонено (<?php echo $status_stats['rejected']; ?>)
      </a>
    </div>
  </div>

  <?php if (empty($filtered_requests)): ?>
    <div style="text-align: center; padding: 50px 20px;">
      <div style="font-size: 48px; color: var(--medium-gray); margin-bottom: 20px;">📅</div>
      <h3 style="color: var(--dark-blue); margin-bottom: 10px;">Занятий пока нет</h3>
      <p style="color: var(--medium-gray); margin-bottom: 30px;">
        У вас пока нет запланированных занятий с репетиторами.
      </p>
      <a href="?page=tutors" class="btn btn-primary">Найти репетитора</a>
    </div>
  <?php else: ?>
    <div>
      <?php foreach ($filtered_requests as $request): ?>
        <?php
        $status_colors = [
          'pending' => '#ffc107',
          'accepted' => '#4cc9f0',
          'completed' => '#2ed573',
          'rejected' => '#d90429'
        ];

        $status_labels = [
          'pending' => 'Ожидание',
          'accepted' => 'Подтверждено',
          'completed' => 'Завершено',
          'rejected' => 'Отклонено'
        ];

        $status_color = $status_colors[$request['status']] ?? '#8d99ae';
        $status_label = $status_labels[$request['status']] ?? $request['status'];

        $has_rated = $request['status'] === 'completed' && $request['is_rated'];
        ?>
        <!-- вывод заявок, внутри логика обработки и вывод в зависимости от статуса и действий пользователя -->
        <div
          style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $status_color; ?>;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
            <div>
              <h4 style="margin: 0; color: var(--dark-blue);">
                <?php echo htmlspecialchars($request['tutor_name']); ?>
              </h4>
              <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                <span
                  style="background: <?php echo $status_color; ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                  <?php echo $status_label; ?>
                </span>
                <span style="color: var(--medium-gray); font-size: 14px;">
                  <?php echo date('d.m.Y H:i', strtotime($request['request_date'])); ?>
                </span>
              </div>
            </div>

            <div style="text-align: center; min-width: 100px;">
              <div style="font-size: 20px; font-weight: bold; color: var(--dark-blue);">
                <?php echo number_format($request['tutor_rating'], 1); ?>
              </div>
              <div style="color: #ffc107; font-size: 18px;">
                <?php
                $full_stars = floor($request['tutor_rating']);
                $half_star = ($request['tutor_rating'] - $full_stars) >= 0.5;
                $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                echo str_repeat('★', $full_stars);
                echo $half_star ? '☆' : '';
                echo str_repeat('☆', $empty_stars);
                ?>
              </div>
              <div style="color: var(--medium-gray); font-size: 12px;">
                <?php echo $request['total_reviews']; ?> отзывов
              </div>
            </div>
          </div>

          <div style="margin: 15px 0;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
              <?php if ($request['city_name']): ?>
                <div>
                  <strong>Город:</strong> <?php echo htmlspecialchars($request['city_name']); ?>
                </div>
              <?php endif; ?>

              <?php if ($request['specialization_name']): ?>
                <div>
                  <strong>Специализация:</strong> <?php echo htmlspecialchars($request['specialization_name']); ?>
                </div>
              <?php endif; ?>
            </div>

            <?php if ($request['request_text']): ?>
              <div style="margin-top: 15px; padding: 15px; background: var(--light-gray); border-radius: 5px;">
                <strong>Ваше сообщение:</strong>
                <p style="margin: 5px 0 0 0;"><?php echo nl2br(htmlspecialchars($request['request_text'])); ?></p>
              </div>
            <?php endif; ?>
          </div>

          <?php if ($request['status'] === 'completed' && !$has_rated): ?>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--light-gray);">
              <h5 style="color: var(--dark-blue); margin-bottom: 15px;">Оцените занятие</h5>
              <form method="POST">
                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">

                <div style="margin-bottom: 15px;">
                  <div style="margin-bottom: 8px; color: var(--dark-blue); font-weight: 600;">
                    Оценка (1-5 звезд):
                  </div>
                  <div style="display: flex; gap: 5px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label style="cursor: pointer; font-size: 24px; color: #e4e5e9;">
                        <input type="radio" name="rating" value="<?php echo $i; ?>" required style="display: none;">
                        <span class="star">☆</span>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>

                <button type="submit" name="submit_rating" class="btn btn-primary">
                  Отправить оценку
                </button>
              </form>
            </div>

          <?php elseif ($has_rated): ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(46, 213, 115, 0.1); border-radius: 5px;">
              <div style="color: var(--dark-blue); font-weight: 600;">
                Вы уже оценили это занятие
              </div>
              <div style="color: var(--medium-gray); font-size: 14px; margin-top: 5px;">
                Оценка отправлена: <?php echo date('d.m.Y H:i', strtotime($request['response_date'])); ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // обработка звезд рейтинга
    document.querySelectorAll('form').forEach(form => {
      const stars = form.querySelectorAll('.star');
      const radios = form.querySelectorAll('input[type="radio"]');

      stars.forEach((star, index) => {
        star.addEventListener('click', function () {
          // выбираем радио-кнопку
          radios[index].checked = true;

          // подсвечиваем звезды
          stars.forEach((s, i) => {
            s.style.color = i <= index ? '#ffc107' : '#e4e5e9';
          });
        });

        // при наведении
        star.addEventListener('mouseenter', function () {
          for (let i = 0; i <= index; i++) {
            stars[i].style.color = '#ffc107';
          }
        });

        // при уходе мыши
        star.addEventListener('mouseleave', function () {
          const checkedIndex = Array.from(radios).findIndex(radio => radio.checked);
          stars.forEach((s, i) => {
            s.style.color = i <= checkedIndex ? '#ffc107' : '#e4e5e9';
          });
        });
      });
    });
  });
</script>
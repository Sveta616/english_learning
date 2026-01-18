<?php
// проверка страницы и модуля
if (!isset($_GET['id'])) {
  header('Location: ?page=learning');
  exit;
}
$module_id = (int) $_GET['id'];

$module = $db->fetchOne(
  "SELECT m.*, l.level_id, l.level_code 
     FROM modules m
     JOIN levels l ON m.level_id = l.level_id
     WHERE m.module_id = ? AND m.is_active = 1",
  [$module_id]
);

if (!$module) {
  echo '<div class="student-section">
            <div class="alert alert-error">
                Модуль не найден или недоступен.
            </div>
            <a href="?page=learning" class="btn btn-primary">Вернуться к модулям</a>
          </div>';
  exit;
}

if ($module['level_id'] != $currentUser['current_level_id']) {
  echo '<div class="student-section">
            <div class="alert alert-error">
                Этот модуль не соответствует вашему текущему уровню.
            </div>
            <a href="?page=learning" class="btn btn-primary">Вернуться к модулям</a>
          </div>';
  exit;
}

$completed_tasks = $db->fetchOne(
  "SELECT COUNT(DISTINCT task_id) as completed_count 
     FROM user_answers 
     WHERE user_id = ? AND task_id IN (
         SELECT task_id FROM tasks WHERE module_id = ?
     )",
  [$currentUser['user_id'], $module_id]
);

$total_tasks = $db->fetchOne(
  "SELECT COUNT(*) as total FROM tasks WHERE module_id = ? AND is_active = 1",
  [$module_id]
);

$is_completed = ($completed_tasks['completed_count'] ?? 0) >= ($total_tasks['total'] ?? 0);

if ($is_completed) {
  include 'module_results.php';
  exit;
}

$show_result = false;
$result_message = '';
$result_class = '';
$last_answer_data = null;

// логика обработки ответов

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
  $user_answer = $_POST['answer'] ?? '';
  $task_id = (int) ($_POST['task_id'] ?? 0);
  $is_correct = 0;
  $points_earned = 0;

  // если ничего не выбрано
  if (!$task_id) {
    $result_message = 'Ошибка: не указано задание';
    $result_class = 'alert-error';
  } else {
    $current_task = $db->fetchOne(
      "SELECT t.* FROM tasks t WHERE t.task_id = ? AND t.is_active = 1",
      [$task_id]
    );

    // обрабатываем ответ
    if ($current_task) {
      $existing_answer = $db->fetchOne(
        "SELECT answer_id FROM user_answers WHERE user_id = ? AND task_id = ?",
        [$currentUser['user_id'], $task_id]
      );

      if ($existing_answer) {
        $result_message = 'Вы уже отвечали на это задание';
        $result_class = 'alert-warning';
      } else {
        // если вопрос с множественным выбором ответа
        if ($current_task['task_type'] === 'multiple_choice') {
          $selected_option = $db->fetchOne(
            "SELECT option_text, is_correct FROM task_options WHERE option_id = ? AND task_id = ?",
            [$user_answer, $task_id]
          );

          // если с одним выбором ответа
          if ($selected_option) {
            $is_correct = $selected_option['is_correct'];
            $points_earned = $is_correct ? $current_task['points'] : 0;
          }
          // если ввод текста
        } elseif ($current_task['task_type'] === 'fill_blank') {
          $is_correct = (strtolower(trim($user_answer)) === strtolower(trim($current_task['correct_answer']))) ? 1 : 0;
          $points_earned = $is_correct ? $current_task['points'] : 0;
        }

        // вставка ответов в бд
        $db->insert('user_answers', [
          'user_id' => $currentUser['user_id'],
          'task_id' => $task_id,
          'user_answer' => $user_answer,
          'is_correct' => $is_correct,
          'points_earned' => $points_earned,
          'attempt_number' => 1,
          'answered_at' => date('Y-m-d H:i:s')
        ]);

        // проверка на прогресс
        $progress = $db->fetchOne(
          "SELECT * FROM user_progress WHERE user_id = ? AND level_id = ?",
          [$currentUser['user_id'], $module['level_id']]
        );

        if ($progress) {
          $new_score = ($progress['current_score'] ?? 0) + $points_earned;
          $new_tasks_completed = ($progress['tasks_completed'] ?? 0) + 1;

          $total_tasks_in_level = $db->fetchOne(
            "SELECT COUNT(*) as total FROM tasks t 
                         JOIN modules m ON t.module_id = m.module_id 
                         WHERE m.level_id = ? AND t.is_active = 1",
            [$module['level_id']]
          );

          $completion_percentage = $total_tasks_in_level['total'] > 0
            ? round(($new_tasks_completed / $total_tasks_in_level['total']) * 100)
            : 0;

          // проверка на статус
          $status = 'in_progress';
          if ($completion_percentage >= 100) {
            $status = 'completed';
          } elseif ($new_tasks_completed > 0) {
            $status = 'in_progress';
          } else {
            $status = 'not_started';
          }

          // обновляем данные
          $db->update('user_progress', [
            'tasks_completed' => $new_tasks_completed,
            'current_score' => $new_score,
            'completion_percentage' => $completion_percentage,
            'status' => $status,
            'last_activity_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
          ], 'progress_id = ?', [$progress['progress_id']]);
        }

        // верные / не верные ответы
        $last_answer_data = [
          'is_correct' => $is_correct,
          'points_earned' => $points_earned,
          'explanation' => $current_task['explanation'] ?? '',
          'correct_answer' => $current_task['correct_answer'] ?? ''
        ];

        $show_result = true;
        $result_class = $is_correct ? 'alert-success' : 'alert-error';
        $result_message = $is_correct
          ? 'Правильно! Вы заработали ' . $points_earned . ' баллов.'
          : 'Неправильно.';
      }
    } else {
      $result_message = 'Задание не найдено';
      $result_class = 'alert-error';
    }
  }
}

$current_task = $db->fetchOne(
  "SELECT t.* 
     FROM tasks t
     LEFT JOIN user_answers ua ON t.task_id = ua.task_id AND ua.user_id = ?
     WHERE t.module_id = ? AND t.is_active = 1 AND ua.answer_id IS NULL
     ORDER BY t.task_id
     LIMIT 1",
  [$currentUser['user_id'], $module_id]
);

if (!$current_task) {
  // session_start();
  $_SESSION['last_completed_module'] = $module_id;

  include 'module_results.php';
  exit;
}

// варианты ответа
$options = $db->fetchAll(
  "SELECT * FROM task_options WHERE task_id = ? ORDER BY order_number",
  [$current_task['task_id']]
);

// номер задания
$task_number = $db->fetchOne(
  "SELECT COUNT(*) as count 
     FROM user_answers ua
     JOIN tasks t ON ua.task_id = t.task_id
     WHERE ua.user_id = ? AND t.module_id = ?",
  [$currentUser['user_id'], $module_id]
);
$current_task_number = ($task_number['count'] ?? 0) + 1;
?>

<!-- контент где выводится задание -->
<div class="task-container">
  <div style="margin-bottom: 20px;">
    <a href="?page=learning" style="color: var(--medium-gray); text-decoration: none;">← Назад к модулям</a>
  </div>

  <div class="task-card">
    <div class="task-header">
      <div>
        <h3 class="task-title">Задание <?php echo $current_task_number; ?> из <?php echo $total_tasks['total']; ?></h3>
        <div style="color: var(--medium-gray); font-size: 14px; margin-top: 5px;">
          Модуль: <?php echo htmlspecialchars($module['module_name']); ?>
        </div>
      </div>
      <div class="task-meta">
        <span class="task-points">+<?php echo $current_task['points']; ?> баллов</span>
        <span><?php echo $current_task['difficulty_level']; ?></span>
      </div>
    </div>

    <!-- вывод результатов предыдушего ответа -->
    <?php if ($show_result && $last_answer_data): ?>
      <div class="alert <?php echo $result_class; ?>" style="margin-bottom: 25px;">
        <?php echo $result_message; ?>
        <?php if (!$last_answer_data['is_correct']): ?>
          <div style="margin-top: 10px;">
            <strong>Правильный ответ:</strong> <?php echo htmlspecialchars($last_answer_data['correct_answer']); ?>
          </div>
        <?php endif; ?>
        <?php if ($last_answer_data['explanation']): ?>
          <div style="margin-top: 10px;">
            <strong>Объяснение:</strong> <?php echo htmlspecialchars($last_answer_data['explanation']); ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="task-question">
      <?php echo nl2br(htmlspecialchars($current_task['task_text'])); ?>
    </div>

    <form method="POST" id="taskForm">
      <input type="hidden" name="submit_answer" value="1">
      <input type="hidden" name="task_id" value="<?php echo $current_task['task_id']; ?>">
      <!-- вывод заданий разных типов -->
      <?php if ($current_task['task_type'] === 'multiple_choice'): ?>
        <div class="task-options">
          <?php foreach ($options as $option): ?>
            <label class="task-option" for="option_<?php echo $option['option_id']; ?>">
              <input type="radio" name="answer" id="option_<?php echo $option['option_id']; ?>"
                value="<?php echo $option['option_id']; ?>" required <?php echo ($show_result && isset($_POST['answer']) && $_POST['answer'] == $option['option_id']) ? 'checked' : ''; ?>>
              <span class="option-radio"></span>
              <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
            </label>
          <?php endforeach; ?>
        </div>

      <?php elseif ($current_task['task_type'] === 'fill_blank'): ?>
        <div style="margin-bottom: 25px;">
          <input type="text" name="answer" class="form-control" placeholder="Введите ваш ответ..." required
            value="<?php echo $show_result ? htmlspecialchars($_POST['answer'] ?? '') : ''; ?>"
            style="font-size: 16px; padding: 15px;">
        </div>
      <?php endif; ?>

      <div class="task-navigation">
        <div class="task-progress">
          <span>Прогресс модуля:</span>
          <div class="task-progress-bar">
            <div class="task-progress-fill"
              style="width: <?php echo round((($current_task_number - 1) / $total_tasks['total']) * 100); ?>%"></div>
          </div>
          <span><?php echo round((($current_task_number - 1) / $total_tasks['total']) * 100); ?>%</span>
        </div>

        <button type="submit" class="btn btn-primary" id="submitBtn">
          <?php echo ($current_task_number == $total_tasks['total']) ? 'Завершить модуль' : 'Следующее задание'; ?>
        </button>
      </div>
    </form>
  </div>

  <div style="text-align: center; margin-top: 20px; color: var(--medium-gray);">
    <small>Задание можно выполнить только один раз. Ответы нельзя изменить после отправки.</small>
  </div>
</div>

<script>
  // считывание выбора варианта ответа
  document.addEventListener('DOMContentLoaded', function () {
    const options = document.querySelectorAll('.task-option');
    options.forEach(option => {
      option.addEventListener('click', function () {

        options.forEach(opt => {
          opt.classList.remove('selected');
          const radio = opt.querySelector('input[type="radio"]');
          if (radio) radio.checked = false;
        });

        this.classList.add('selected');
        const radio = this.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
      });
    });

    const selectedRadio = document.querySelector('input[name="answer"]:checked');
    if (selectedRadio) {
      selectedRadio.closest('.task-option').classList.add('selected');
    }
  });
</script>
<?php
$content_type = $_GET['type'] ?? 'levels'; 
$action = $_GET['action'] ?? 'list';

// Загружаем все уровни в правильном порядке (A1-C2)
$levels = $db->fetchAll("SELECT * FROM levels ORDER BY 
    CASE level_code 
        WHEN 'A1' THEN 1
        WHEN 'A2' THEN 2
        WHEN 'B1' THEN 3
        WHEN 'B2' THEN 4
        WHEN 'C1' THEN 5
        WHEN 'C2' THEN 6
        ELSE 7
    END");

// переменные для данных
$sql = '';
$params = [];
$modules_data = [];
$tasks_data = [];
$levels_data = [];

// ЗАГРУЗКА ДАННЫХ В ЗАВИСИМОСТИ ОТ ТИПА КОНТЕНТА
switch($content_type) {
    case 'levels':
        $levels_data = $db->fetchAll("SELECT * FROM levels ORDER BY 
            CASE level_code 
                WHEN 'A1' THEN 1
                WHEN 'A2' THEN 2
                WHEN 'B1' THEN 3
                WHEN 'B2' THEN 4
                WHEN 'C1' THEN 5
                WHEN 'C2' THEN 6
                ELSE 7
            END");
        break;
        
    case 'modules':
        $level_filter = $_GET['level'] ?? 'all';
        
        $sql = "SELECT m.*, l.level_code, l.level_name 
                FROM modules m 
                JOIN levels l ON m.level_id = l.level_id 
                WHERE 1=1";
        
        if ($level_filter !== 'all') {
            $sql .= " AND m.level_id = ?";
            $params[] = $level_filter;
        }
        
        $sql .= " ORDER BY 
            CASE l.level_code 
                WHEN 'A1' THEN 1
                WHEN 'A2' THEN 2
                WHEN 'B1' THEN 3
                WHEN 'B2' THEN 4
                WHEN 'C1' THEN 5
                WHEN 'C2' THEN 6
                ELSE 7
            END, m.order_number";
        $modules_data = $db->fetchAll($sql, $params);
        break;
        
    case 'tasks':
       // фильтры для заданий
        $module_filter = $_GET['module'] ?? 'all';
        $type_filter = $_GET['task_type'] ?? 'all';
        
        $sql = "SELECT t.*, m.module_name, l.level_code, l.level_id
                FROM tasks t 
                JOIN modules m ON t.module_id = m.module_id 
                JOIN levels l ON m.level_id = l.level_id 
                WHERE 1=1 AND t.is_active = 1";
        
        if ($module_filter !== 'all') {
            $sql .= " AND t.module_id = ?";
            $params[] = $module_filter;
        }
        
        if ($type_filter !== 'all') {
            $sql .= " AND t.task_type = ?";
            $params[] = $type_filter;
        }
        
        $sql .= " ORDER BY 
            CASE l.level_code 
                WHEN 'A1' THEN 1
                WHEN 'A2' THEN 2
                WHEN 'B1' THEN 3
                WHEN 'B2' THEN 4
                WHEN 'C1' THEN 5
                WHEN 'C2' THEN 6
                ELSE 7
            END, t.module_id, t.task_id";
        $tasks_data = $db->fetchAll($sql, $params);
        
        // Загружаем ВСЕ модули для выпадающего списка
        $modules = $db->fetchAll("SELECT m.module_id, m.module_name, l.level_code, l.level_id 
                                 FROM modules m 
                                 JOIN levels l ON m.level_id = l.level_id 
                                 WHERE m.is_active = 1
                                 ORDER BY 
                                     CASE l.level_code 
                                         WHEN 'A1' THEN 1
                                         WHEN 'A2' THEN 2
                                         WHEN 'B1' THEN 3
                                         WHEN 'B2' THEN 4
                                         WHEN 'C1' THEN 5
                                         WHEN 'C2' THEN 6
                                         ELSE 7
                                     END, 
                                     m.order_number");
        break;
}

// убрать
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// если хоти добавить уровень - обработка логики
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Неизвестная ошибка'];
    
    try {
        if (isset($_POST['add_level'])) {
            $insert_data = [
                'level_code' => trim($_POST['level_code']),
                'level_name' => trim($_POST['level_name']),
                'description' => trim($_POST['description'] ?? ''),
                'min_score' => !empty($_POST['min_score']) ? (int) $_POST['min_score'] : 0,
                'max_score' => !empty($_POST['max_score']) ? (int) $_POST['max_score'] : 100
            ];
            
            $db->insert('levels', $insert_data);
            $response = ['success' => true, 'message' => 'Уровень успешно добавлен'];
            
        } elseif (isset($_POST['update_level'])) {
            $level_id = (int) $_POST['level_id'];
            $update_data = [
                'level_code' => trim($_POST['level_code']),
                'level_name' => trim($_POST['level_name']),
                'description' => trim($_POST['description'] ?? ''),
                'min_score' => !empty($_POST['min_score']) ? (int) $_POST['min_score'] : 0,
                'max_score' => !empty($_POST['max_score']) ? (int) $_POST['max_score'] : 100
            ];
            
            $db->update('levels', $update_data, 'level_id = ?', [$level_id]);
            $response = ['success' => true, 'message' => 'Уровень успешно обновлен'];
            
        } elseif (isset($_POST['add_module'])) {
            $insert_data = [
                'module_name' => trim($_POST['module_name']),
                'description' => trim($_POST['description'] ?? ''),
                'module_type' => $_POST['module_type'],
                'level_id' => (int) $_POST['level_id'],
                'order_number' => !empty($_POST['order_number']) ? (int) $_POST['order_number'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $db->insert('modules', $insert_data);
            $response = ['success' => true, 'message' => 'Модуль успешно добавлен'];
            
        } elseif (isset($_POST['update_module'])) {
            $module_id = (int) $_POST['module_id'];
            $update_data = [
                'module_name' => trim($_POST['module_name']),
                'description' => trim($_POST['description'] ?? ''),
                'module_type' => $_POST['module_type'],
                'level_id' => (int) $_POST['level_id'],
                'order_number' => !empty($_POST['order_number']) ? (int) $_POST['order_number'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $db->update('modules', $update_data, 'module_id = ?', [$module_id]);
            $response = ['success' => true, 'message' => 'Модуль успешно обновлен'];
            
        } elseif (isset($_POST['add_task'])) {
            $insert_data = [
                'task_text' => trim($_POST['task_text']),
                'task_type' => $_POST['task_type'],
                'difficulty_level' => $_POST['difficulty_level'],
                'correct_answer' => trim($_POST['correct_answer']),
                'points' => (int) $_POST['points'],
                'explanation' => trim($_POST['explanation'] ?? ''),
                'module_id' => (int) $_POST['module_id'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $task_id = $db->insert('tasks', $insert_data);
            
            if ($_POST['task_type'] === 'multiple_choice' && isset($_POST['options']) && is_array($_POST['options'])) {
                foreach ($_POST['options'] as $index => $option_text) {
                    if (!empty(trim($option_text))) {
                        $option_data = [
                            'task_id' => $task_id,
                            'option_text' => trim($option_text),
                            'is_correct' => ($_POST['correct_option'] == $index) ? 1 : 0,
                            'order_number' => $index + 1
                        ];
                        $db->insert('task_options', $option_data);
                    }
                }
            }
            
            $response = ['success' => true, 'message' => 'Задание успешно добавлено'];
            
        } elseif (isset($_POST['update_task'])) {
            $task_id = (int) $_POST['task_id'];
            $update_data = [
                'task_text' => trim($_POST['task_text']),
                'task_type' => $_POST['task_type'],
                'difficulty_level' => $_POST['difficulty_level'],
                'correct_answer' => trim($_POST['correct_answer']),
                'points' => (int) $_POST['points'],
                'explanation' => trim($_POST['explanation'] ?? ''),
                'module_id' => (int) $_POST['module_id'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $db->update('tasks', $update_data, 'task_id = ?', [$task_id]);
            
            if ($_POST['task_type'] === 'multiple_choice') {
                $db->delete('task_options', 'task_id = ?', [$task_id]);
                
                if (isset($_POST['options']) && is_array($_POST['options'])) {
                    foreach ($_POST['options'] as $index => $option_text) {
                        if (!empty(trim($option_text))) {
                            $option_data = [
                                'task_id' => $task_id,
                                'option_text' => trim($option_text),
                                'is_correct' => ($_POST['correct_option'] == $index) ? 1 : 0,
                                'order_number' => $index + 1
                            ];
                            $db->insert('task_options', $option_data);
                        }
                    }
                }
            }
            
            $response = ['success' => true, 'message' => 'Задание успешно обновлено'];
        }
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        echo '<div class="alert ' . ($response['success'] ? 'alert-success' : 'alert-error') . '">' 
             . htmlspecialchars($response['message']) . '</div>';
    }
}
// если хотим удалить контент - логика обработки
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $type = $content_type;
    
    try {
        switch ($type) {
            case 'levels':
                $modules_count = $db->fetchOne(
                    "SELECT COUNT(*) as count FROM modules WHERE level_id = ?",
                    [$id]
                );
                
                if ($modules_count['count'] > 0) {
                    echo '<div class="alert alert-error">Нельзя удалить уровень, в котором есть модули. Сначала удалите все модули.</div>';
                } else {
                    $db->delete('levels', 'level_id = ?', [$id]);
                    echo '<div class="alert alert-success">Уровень успешно удален</div>';
                }

                $levels_data = $db->fetchAll("SELECT * FROM levels ORDER BY 
                    CASE level_code 
                        WHEN 'A1' THEN 1
                        WHEN 'A2' THEN 2
                        WHEN 'B1' THEN 3
                        WHEN 'B2' THEN 4
                        WHEN 'C1' THEN 5
                        WHEN 'C2' THEN 6
                        ELSE 7
                    END");
                break;
                
            case 'modules':
                $tasks_count = $db->fetchOne(
                    "SELECT COUNT(*) as count FROM tasks WHERE module_id = ?",
                    [$id]
                );
                
                if ($tasks_count['count'] > 0) {
                    echo '<div class="alert alert-error">Нельзя удалить модуль, в котором есть задания. Сначала удалите все задания.</div>';
                } else {
                    $db->delete('modules', 'module_id = ?', [$id]);
                    echo '<div class="alert alert-success">Модуль успешно удален</div>';
                }

                $level_filter = $_GET['level'] ?? 'all';
                $sql_modules = "SELECT m.*, l.level_code, l.level_name 
                               FROM modules m 
                               JOIN levels l ON m.level_id = l.level_id 
                               WHERE 1=1";
                
                $params_modules = [];
                if ($level_filter !== 'all') {
                    $sql_modules .= " AND m.level_id = ?";
                    $params_modules[] = $level_filter;
                }
                
                $sql_modules .= " ORDER BY 
                    CASE l.level_code 
                        WHEN 'A1' THEN 1
                        WHEN 'A2' THEN 2
                        WHEN 'B1' THEN 3
                        WHEN 'B2' THEN 4
                        WHEN 'C1' THEN 5
                        WHEN 'C2' THEN 6
                        ELSE 7
                    END, m.order_number";
                $modules_data = $db->fetchAll($sql_modules, $params_modules);
                break;
                
            case 'tasks':
                $db->delete('task_options', 'task_id = ?', [$id]);
                $db->delete('user_answers', 'task_id = ?', [$id]);
                $db->delete('tasks', 'task_id = ?', [$id]);
                
                echo '<div class="alert alert-success">Задание успешно удалено</div>';
                
                $module_filter = $_GET['module'] ?? 'all';
                $type_filter = $_GET['task_type'] ?? 'all';
                
                $sql_tasks = "SELECT t.*, m.module_name, l.level_code, l.level_id
                             FROM tasks t 
                             JOIN modules m ON t.module_id = m.module_id 
                             JOIN levels l ON m.level_id = l.level_id 
                             WHERE 1=1 AND t.is_active = 1";
                
                $params_tasks = [];
                if ($module_filter !== 'all') {
                    $sql_tasks .= " AND t.module_id = ?";
                    $params_tasks[] = $module_filter;
                }
                
                if ($type_filter !== 'all') {
                    $sql_tasks .= " AND t.task_type = ?";
                    $params_tasks[] = $type_filter;
                }
                
                $sql_tasks .= " ORDER BY 
                    CASE l.level_code 
                        WHEN 'A1' THEN 1
                        WHEN 'A2' THEN 2
                        WHEN 'B1' THEN 3
                        WHEN 'B2' THEN 4
                        WHEN 'C1' THEN 5
                        WHEN 'C2' THEN 6
                        ELSE 7
                    END, t.module_id, t.task_id";
                $tasks_data = $db->fetchAll($sql_tasks, $params_tasks);
                break;
        }
        
    } catch (Exception $e) {
        echo '<div class="alert alert-error">Ошибка удаления: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
if ($action === 'edit') {
    switch($content_type) {
        case 'levels':
            if (isset($_GET['id'])) {
                $level_id = (int) $_GET['id'];
                $level = $db->fetchOne("SELECT * FROM levels WHERE level_id = ?", [$level_id]);
            }
            break;
            
        case 'modules':
            if (isset($_GET['id'])) {
                $module_id = (int) $_GET['id'];
                $module = $db->fetchOne(
                    "SELECT m.*, l.level_code, l.level_name 
                     FROM modules m 
                     JOIN levels l ON m.level_id = l.level_id 
                     WHERE m.module_id = ?", 
                    [$module_id]
                );
            }
            break;
            
        case 'tasks':
            if (isset($_GET['id'])) {
                $task_id = (int) $_GET['id'];
                $task = $db->fetchOne(
                    "SELECT t.*, m.module_name, l.level_code, l.level_id
                     FROM tasks t 
                     JOIN modules m ON t.module_id = m.module_id 
                     JOIN levels l ON m.level_id = l.level_id 
                     WHERE t.task_id = ?", 
                    [$task_id]
                );
                $options = [];
                if ($task['task_type'] === 'multiple_choice') {
                    $options = $db->fetchAll(
                        "SELECT * FROM task_options WHERE task_id = ? ORDER BY order_number",
                        [$task_id]
                    );
                }
            }
            break;
    }
}
?>

<!-- меню для выюора чем управлять -->
<div class="admin-section">
  <h2>Управление учебным контентом</h2>
  <div style="margin-bottom: 30px;">
    <div style="display: flex; gap: 10px; border-bottom: 2px solid var(--light-gray);">
      <a href="?page=content&type=levels"
        class="btn <?php echo $content_type === 'levels' ? 'btn-primary' : 'btn-secondary'; ?>"
        style="border-radius: 5px 5px 0 0;">
        Уровни (A1-C2)
      </a>
      <a href="?page=content&type=modules"
        class="btn <?php echo $content_type === 'modules' ? 'btn-primary' : 'btn-secondary'; ?>"
        style="border-radius: 5px 5px 0 0;">
        Модули
      </a>
      <a href="?page=content&type=tasks"
        class="btn <?php echo $content_type === 'tasks' ? 'btn-primary' : 'btn-secondary'; ?>"
        style="border-radius: 5px 5px 0 0;">
        Задания
      </a>
    </div>
  </div>
<!-- если уровни - то предлагаем добавить один -->
  <?php if ($content_type === 'levels'): ?>
      <?php if ($action === 'list'): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Уровни обучения (A1-C2)</h3>
            <button type="button" data-modal="addLevelModal" class="btn btn-primary">
              Добавить уровень
            </button>
          </div>

          <!-- формируем все в таблицу -->
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Код уровня</th>
                  <th>Название</th>
                  <th>Описание</th>
                  <th>Баллы (мин-макс)</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($levels_data)): ?>
                    <?php foreach ($levels_data as $level): ?>
                        <tr>
                          <td><?php echo $level['level_id']; ?></td>
                          <td>
                            <span class="level-badge level-<?php echo strtolower($level['level_code']); ?>">
                              <?php echo $level['level_code']; ?>
                            </span>
                          </td>
                          <td style="font-weight: 600;"><?php echo htmlspecialchars($level['level_name']); ?></td>
                          <td><?php echo htmlspecialchars($level['description'] ?? ''); ?></td>
                          <td><?php echo $level['min_score']; ?> - <?php echo $level['max_score']; ?></td>
                          <td>
                            <div class="action-buttons">
                              <a href="?page=content&type=levels&action=edit&id=<?php echo $level['level_id']; ?>" class="btn-edit"
                                title="Редактировать">
                                Редактировать
                              </a>
                              <a href="?page=content&type=levels&action=delete&id=<?php echo $level['level_id']; ?>" 
   class="btn-delete" title="Удалить"
   data-confirm="Удалить уровень <?php echo htmlspecialchars($level['level_code']); ?>? Это также удалит все связанные модули и задания!">
    Удалить
</a>
                            </div>
                          </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                      <td colspan="6" style="text-align: center; padding: 40px;">
                        <div style="font-size: 48px; color: var(--medium-gray); margin-bottom: 20px;">📊</div>
                        <h3>Уровни не найдены</h3>
                        <p style="color: var(--medium-gray);">Добавьте первый уровень обучения</p>
                      </td>
                    </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

      <?php elseif ($action === 'edit' && isset($level)): ?>
          <div style="margin-bottom: 20px;">
            <a href="?page=content&type=levels" class="btn btn-secondary">← Назад к списку</a>
          </div>

          <h3>Редактирование уровня: <?php echo htmlspecialchars($level['level_code']); ?></h3>

          <form method="POST">
            <input type="hidden" name="update_level" value="1">
            <input type="hidden" name="level_id" value="<?php echo $level['level_id']; ?>">

            <div class="form-group">
              <label for="level_code">Код уровня *</label>
              <input type="text" id="level_code" name="level_code" class="form-control"
                value="<?php echo htmlspecialchars($level['level_code']); ?>" required>
            </div>

            <div class="form-group">
              <label for="level_name">Название уровня *</label>
              <input type="text" id="level_name" name="level_name" class="form-control"
                value="<?php echo htmlspecialchars($level['level_name']); ?>" required>
            </div>

            <div class="form-group">
              <label for="description">Описание</label>
              <textarea id="description" name="description" class="form-control"
                rows="3"><?php echo htmlspecialchars($level['description'] ?? ''); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
              <div class="form-group">
                <label for="min_score">Минимальный балл</label>
                <input type="number" id="min_score" name="min_score" class="form-control"
                  value="<?php echo $level['min_score']; ?>" min="0">
              </div>

              <div class="form-group">
                <label for="max_score">Максимальный балл</label>
                <input type="number" id="max_score" name="max_score" class="form-control"
                  value="<?php echo $level['max_score']; ?>" min="0">
              </div>
            </div>

            <div style="text-align: right; margin-top: 30px;">
              <a href="?page=content&type=levels" class="btn btn-secondary">Отмена</a>
              <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </div>
          </form>

      <?php elseif ($action === 'add'): ?>
      <?php endif; ?>

      <div id="addLevelModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Добавить новый уровень</h3>
            <button type="button" class="modal-close">×</button>
          </div>

          <form method="POST">
            <input type="hidden" name="add_level" value="1">

            <div class="admin-form">
              <div class="form-group">
                <label for="new_level_code">Код уровня *</label>
                <input type="text" id="new_level_code" name="level_code" class="form-control" placeholder="A1, A2, B1, B2, C1, C2..."
                  required>
              </div>

              <div class="form-group">
                <label for="new_level_name">Название уровня *</label>
                <input type="text" id="new_level_name" name="level_name" class="form-control"
                  placeholder="Beginner, Elementary, Intermediate, Upper Intermediate, Advanced, Mastery..." required>
              </div>

              <div class="form-group">
                <label for="new_description">Описание</label>
                <textarea id="new_description" name="description" class="form-control" rows="3"
                  placeholder="Краткое описание уровня..."></textarea>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                  <label for="new_min_score">Минимальный балл</label>
                  <input type="number" id="new_min_score" name="min_score" class="form-control" value="0" min="0">
                </div>

                <div class="form-group">
                  <label for="new_max_score">Максимальный балл</label>
                  <input type="number" id="new_max_score" name="max_score" class="form-control" value="100" min="0">
                </div>
              </div>
            </div>

            <div style="text-align: right; margin-top: 30px;">
              <button type="button" class="btn btn-secondary modal-close">Отмена</button>
              <button type="submit" class="btn btn-primary">Добавить уровень</button>
            </div>
          </form>
        </div>
      </div>

      <!-- формируем таблциу где будут модули -->
  <?php elseif ($content_type === 'modules'): ?>
      <?php if ($action === 'list'): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Учебные модули (A1-C2)</h3>
            <button type="button" data-modal="addModuleModal" class="btn btn-primary">
              Добавить модуль
            </button>
          </div>

          <div class="filter-section" style="margin-bottom: 20px;">
            <form method="GET" class="filter-form">
              <input type="hidden" name="page" value="content">
              <input type="hidden" name="type" value="modules">

              <div>
                <label>Фильтр по уровню</label>
                <select name="level" class="form-control" onchange="this.form.submit()">
                  <option value="all" <?php echo $level_filter === 'all' ? 'selected' : ''; ?>>Все уровни</option>
                  <?php foreach ($levels as $level): ?>
                      <option value="<?php echo $level['level_id']; ?>" <?php echo $level_filter == $level['level_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($level['level_code'] . ' - ' . $level['level_name']); ?>
                      </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="filter-actions">
                <a href="?page=content&type=modules" class="btn btn-secondary">Сбросить</a>
              </div>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Уровень</th>
                  <th>Название модуля</th>
                  <th>Тип</th>
                  <th>Описание</th>
                  <th>Порядок</th>
                  <th>Статус</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($modules_data)): ?>
                    <?php foreach ($modules_data as $module): ?>
                        <tr>
                          <td><?php echo $module['module_id']; ?></td>
                          <td>
                            <span class="level-badge level-<?php echo strtolower($module['level_code']); ?>">
                              <?php echo $module['level_code']; ?>
                            </span>
                          </td>
                          <td style="font-weight: 600;"><?php echo htmlspecialchars($module['module_name']); ?></td>
                          <td>
                            <?php
                            $type_labels = [
                              'grammar' => 'Грамматика',
                              'vocabulary' => 'Словарь',
                              'reading' => ' Чтение'
                            ];
                            echo $type_labels[$module['module_type']] ?? $module['module_type'];
                            ?>
                          </td>
                          <td><?php echo htmlspecialchars($module['description'] ?? ''); ?></td>
                          <td><?php echo $module['order_number'] ?? '-'; ?></td>
                          <td>
                            <?php if ($module['is_active']): ?>
                                <span class="status-badge status-active">Активен</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Неактивен</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="action-buttons">
                              <a href="?page=content&type=modules&action=edit&id=<?php echo $module['module_id']; ?>" class="btn-edit"
                                title="Редактировать">
                                Редактировать
                              </a>
                              <a href="?page=content&type=modules&action=delete&id=<?php echo $module['module_id']; ?>" 
   class="btn-delete" title="Удалить"
   data-confirm="Удалить модуль '<?php echo htmlspecialchars($module['module_name']); ?>'? Это также удалит все связанные задания!">
    Удалить
</a>
                            </div>
                          </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                      <td colspan="8" style="text-align: center; padding: 40px;">
                        <div style="font-size: 48px; color: var(--medium-gray); margin-bottom: 20px;">📚</div>
                        <h3>Модули не найдены</h3>
                        <p style="color: var(--medium-gray);">Добавьте первый модуль</p>
                      </td>
                    </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- модальное окно реадктирования  -->
      <?php elseif ($action === 'edit' && isset($module)): ?>
          <div style="margin-bottom: 20px;">
            <a href="?page=content&type=modules" class="btn btn-secondary">← Назад к списку</a>
          </div>

          <h3>Редактирование модуля: <?php echo htmlspecialchars($module['module_name']); ?></h3>

          <form method="POST">
            <input type="hidden" name="update_module" value="1">
            <input type="hidden" name="module_id" value="<?php echo $module['module_id']; ?>">

            <div class="form-group">
              <label for="module_name">Название модуля *</label>
              <input type="text" id="module_name" name="module_name" class="form-control"
                value="<?php echo htmlspecialchars($module['module_name']); ?>" required>
            </div>

            <div class="form-group">
              <label for="description">Описание</label>
              <textarea id="description" name="description" class="form-control"
                rows="3"><?php echo htmlspecialchars($module['description'] ?? ''); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
              <div class="form-group">
                <label for="level_id">Уровень *</label>
                <select id="level_id" name="level_id" class="form-control" required>
                  <?php foreach ($levels as $level): ?>
                      <option value="<?php echo $level['level_id']; ?>" <?php echo $module['level_id'] == $level['level_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($level['level_code'] . ' - ' . $level['level_name']); ?>
                      </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="module_type">Тип модуля *</label>
                <select id="module_type" name="module_type" class="form-control" required>
                  <option value="grammar" <?php echo $module['module_type'] === 'grammar' ? 'selected' : ''; ?>>Грамматика
                  </option>
                  <option value="vocabulary" <?php echo $module['module_type'] === 'vocabulary' ? 'selected' : ''; ?>>Словарь
                  </option>
                  <option value="reading" <?php echo $module['module_type'] === 'reading' ? 'selected' : ''; ?>>Чтение</option>
                </select>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
              <div class="form-group">
                <label for="order_number">Порядковый номер</label>
                <input type="number" id="order_number" name="order_number" class="form-control"
                  value="<?php echo $module['order_number'] ?? ''; ?>" min="1">
              </div>

              <div class="form-group" style="display: flex; align-items: center; margin-top: 25px;">
                <input type="checkbox" id="is_active" name="is_active" <?php echo $module['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active" style="margin-left: 10px; margin-bottom: 0;">Активный модуль</label>
              </div>
            </div>

            <div style="text-align: right; margin-top: 30px;">
              <a href="?page=content&type=modules" class="btn btn-secondary">Отмена</a>
              <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </div>
          </form>

      <?php endif; ?>

      <!-- модальное окно добавление модуля -->
      <div id="addModuleModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Добавить новый модуль</h3>
            <button type="button" class="modal-close">×</button>
          </div>

          <form method="POST">
              <input type="hidden" name="add_module" value="1">

            <div class="admin-form">
              <div class="form-group">
                <label for="new_module_name">Название модуля *</label>
                <input type="text" id="new_module_name" name="module_name" class="form-control" required>
              </div>

              <div class="form-group">
                <label for="new_description">Описание</label>
                <textarea id="new_description" name="description" class="form-control" rows="3"></textarea>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                  <label for="new_level_id">Уровень *</label>
                  <select id="new_level_id" name="level_id" class="form-control" required>
                    <?php foreach ($levels as $level): ?>
                        <option value="<?php echo $level['level_id']; ?>">
                          <?php echo htmlspecialchars($level['level_code'] . ' - ' . $level['level_name']); ?>
                        </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label for="new_module_type">Тип модуля *</label>
                  <select id="new_module_type" name="module_type" class="form-control" required>
                    <option value="grammar">Грамматика</option>
                    <option value="vocabulary">Словарь</option>
                    <option value="reading">Чтение</option>
                  </select>
                </div>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                  <label for="new_order_number">Порядковый номер</label>
                  <input type="number" id="new_order_number" name="order_number" class="form-control" min="1">
                </div>

                <div class="form-group" style="display: flex; align-items: center; margin-top: 25px;">
                  <input type="checkbox" id="new_is_active" name="is_active" checked>
                  <label for="new_is_active" style="margin-left: 10px; margin-bottom: 0;">Активный модуль</label>
                </div>
              </div>
            </div>

            <div style="text-align: right; margin-top: 30px;">
              <button type="button" class="btn btn-secondary modal-close">Отмена</button>
              <button type="submit" class="btn btn-primary">Добавить модуль</button>
            </div>
          </form>
        </div>
      </div>

      <!-- фомируем таблицу с всеми заданиями -->
  <?php elseif ($content_type === 'tasks'): ?>
      <?php if ($action === 'list'): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Учебные задания (A1-C2)</h3>
            <button type="button" data-modal="addTaskModal" class="btn btn-primary">
              Добавить задание
            </button>
          </div>

          <div class="filter-section" style="margin-bottom: 20px;">
            <form method="GET" class="filter-form">
              <input type="hidden" name="page" value="content">
              <input type="hidden" name="type" value="tasks">

              <div>
                <label>Модуль</label>
                <select name="module" class="form-control" onchange="this.form.submit()">
                  <option value="all" <?php echo $module_filter === 'all' ? 'selected' : ''; ?>>Все модули</option>
                  <?php 
                  // Загружаем все модули для фильтрации
                  $all_modules_for_filter = $db->fetchAll("
                      SELECT m.module_id, m.module_name, l.level_code 
                      FROM modules m 
                      JOIN levels l ON m.level_id = l.level_id 
                      WHERE m.is_active = 1
                      ORDER BY 
                          CASE l.level_code 
                              WHEN 'A1' THEN 1
                              WHEN 'A2' THEN 2
                              WHEN 'B1' THEN 3
                              WHEN 'B2' THEN 4
                              WHEN 'C1' THEN 5
                              WHEN 'C2' THEN 6
                              ELSE 7
                          END, 
                          m.order_number
                  ");
                  
                  foreach ($all_modules_for_filter as $module_item): ?>
                      <option value="<?php echo $module_item['module_id']; ?>" <?php echo $module_filter == $module_item['module_id'] ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($module_item['level_code'] . ' - ' . $module_item['module_name']); ?>
                      </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label>Тип задания</label>
                <select name="task_type" class="form-control" onchange="this.form.submit()">
                  <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Все типы</option>
                  <option value="multiple_choice" <?php echo $type_filter === 'multiple_choice' ? 'selected' : ''; ?>>Выбор ответа
                  </option>
                  <option value="fill_blank" <?php echo $type_filter === 'fill_blank' ? 'selected' : ''; ?>>Заполнение пропусков
                  </option>
                </select>
              </div>

              <div class="filter-actions">
                <a href="?page=content&type=tasks" class="btn btn-secondary">Сбросить</a>
              </div>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Уровень/Модуль</th>
                  <th>Текст задания</th>
                  <th>Тип</th>
                  <th>Сложность</th>
                  <th>Баллы</th>
                  <th>Статус</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($tasks_data)): ?>
                    <?php foreach ($tasks_data as $task): ?>
                        <tr>
                          <td><?php echo $task['task_id']; ?></td>
                          <td>
                            <div style="font-size: 12px;">
                              <span class="level-badge level-<?php echo strtolower($task['level_code']); ?>" 
                                    style="font-size: 10px; padding: 2px 6px; 
                                           <?php if ($task['level_code'] === 'C2'): ?>
                                           background-color: #2c3e50; color: white;
                                           <?php endif; ?>">
                                <?php echo $task['level_code']; ?>
                              </span>
                              <div style="margin-top: 5px; color: var(--medium-gray);">
                                <?php echo htmlspecialchars($task['module_name']); ?>
                              </div>
                            </div>
                          </td>
                          <td>
                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                              <?php echo htmlspecialchars($task['task_text']); ?>
                            </div>
                          </td>
                          <td>
                            <?php
                            $type_labels = [
                              'multiple_choice' => 'Выбор',
                              'fill_blank' => ' Заполнение'
                            ];
                            echo $type_labels[$task['task_type']] ?? $task['task_type'];
                            ?>
                          </td>
                          <td>
                            <span class="status-badge <?php echo strtolower($task['difficulty_level']); ?>"
                              style="background: rgba(217, 4, 41, 0.1); color: var(--primary-red);
                                     <?php if ($task['difficulty_level'] === 'C2'): ?>
                                     background: rgba(44, 62, 80, 0.1); color: #2c3e50; border: 1px solid #2c3e50;
                                     <?php endif; ?>">
                              <?php echo $task['difficulty_level']; ?>
                            </span>
                          </td>
                          <td><?php echo $task['points']; ?></td>
                          <td>
                            <?php if ($task['is_active']): ?>
                                <span class="status-badge status-active">Активно</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Неактивно</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="action-buttons">
                              <a href="?page=content&type=tasks&action=edit&id=<?php echo $task['task_id']; ?>" class="btn-edit"
                                title="Редактировать">
                                Редактировать
                              </a>
                              <a href="?page=content&type=tasks&action=delete&id=<?php echo $task['task_id']; ?>" 
   class="btn-delete" title="Удалить"
   data-confirm="Удалить задание? Это также удалит все ответы пользователей!">
    Удалить
</a>
                            </div>
                          </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                      <td colspan="8" style="text-align: center; padding: 40px;">
                        <h3>Задания не найдены</h3>
                        <p style="color: var(--medium-gray);">Добавьте первое задание</p>
                      </td>
                    </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
<!-- окно редактирования задания -->
      <?php elseif ($action === 'edit' && isset($task)): ?>
          <div style="margin-bottom: 20px;">
            <a href="?page=content&type=tasks" class="btn btn-secondary">← Назад к списку</a>
          </div>

          <h3>Редактирование задания</h3>

          <form method="POST">
            <input type="hidden" name="update_task" value="1">
            <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">

            <div class="form-group">
              <label for="task_text">Текст задания *</label>
              <textarea id="task_text" name="task_text" class="form-control" rows="3"
                required><?php echo htmlspecialchars($task['task_text']); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
              <div class="form-group">
                <label for="module_id">Модуль *</label>
                <select id="module_id" name="module_id" class="form-control" required>
                  <?php 
                  // Загружаем все модули для выпадающего списка при редактировании
                  $all_modules_for_edit = $db->fetchAll("
                      SELECT m.module_id, m.module_name, l.level_code, l.level_id
                      FROM modules m 
                      JOIN levels l ON m.level_id = l.level_id 
                      WHERE m.is_active = 1
                      ORDER BY 
                          CASE l.level_code 
                              WHEN 'A1' THEN 1
                              WHEN 'A2' THEN 2
                              WHEN 'B1' THEN 3
                              WHEN 'B2' THEN 4
                              WHEN 'C1' THEN 5
                              WHEN 'C2' THEN 6
                              ELSE 7
                          END, 
                          m.order_number
                  ");
                  
                  foreach ($all_modules_for_edit as $module_item): ?>
                      <option value="<?php echo $module_item['module_id']; ?>" <?php echo $task['module_id'] == $module_item['module_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($module_item['level_code'] . ' - ' . $module_item['module_name']); ?>
                      </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="task_type">Тип задания *</label>
                <select id="task_type" name="task_type" class="form-control" required onchange="toggleOptions(this.value)">
                  <option value="multiple_choice" <?php echo $task['task_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Выбор
                    ответа</option>
                  <option value="fill_blank" <?php echo $task['task_type'] === 'fill_blank' ? 'selected' : ''; ?>>Заполнение
                    пропусков</option>
                </select>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
              <div class="form-group">
                <label for="difficulty_level">Уровень сложности *</label>
                <select id="difficulty_level" name="difficulty_level" class="form-control" required>
                  <?php foreach ($levels as $level): ?>
                      <option value="<?php echo $level['level_code']; ?>" <?php echo $task['difficulty_level'] == $level['level_code'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($level['level_code']); ?>
                      </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="points">Баллы *</label>
                <input type="number" id="points" name="points" class="form-control" value="<?php echo $task['points']; ?>"
                  min="1" max="100" required>
              </div>

              <div class="form-group" style="display: flex; align-items: center; margin-top: 25px;">
                <input type="checkbox" id="is_active" name="is_active" <?php echo $task['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active" style="margin-left: 10px; margin-bottom: 0;">Активное задание</label>
              </div>
            </div>

            <div id="options-section" style="<?php echo $task['task_type'] === 'multiple_choice' ? '' : 'display: none;'; ?> 
                        margin-bottom: 20px; padding: 20px; background: var(--light-gray); border-radius: 8px;">
              <h4>Варианты ответов</h4>

              <div id="options-container">
                <?php if ($task['task_type'] === 'multiple_choice' && !empty($options)): ?>
                    <?php foreach ($options as $index => $option): ?>
                        <div class="form-group option-row" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                          <input type="radio" name="correct_option" value="<?php echo $index; ?>" <?php echo $option['is_correct'] ? 'checked' : ''; ?>>
                          <input type="text" name="options[<?php echo $index; ?>]" class="form-control"
                            value="<?php echo htmlspecialchars($option['option_text']); ?>" placeholder="Текст варианта ответа">
                          <button type="button" class="btn-delete" onclick="removeOption(this)" style="padding: 5px 10px;">Удалить</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="form-group option-row" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                      <input type="radio" name="correct_option" value="0" checked>
                      <input type="text" name="options[0]" class="form-control" placeholder="Текст варианта ответа">
                      <button type="button" class="btn-delete" onclick="removeOption(this)" style="padding: 5px 10px;">Удалить</button>
                    </div>
                <?php endif; ?>
              </div>

              <button type="button" class="btn btn-secondary" onclick="addOption()">+ Добавить вариант</button>
            </div>

            <div id="correct-answer-section" style="margin-bottom: 20px;">
    <div class="form-group">
        <label for="correct_answer">Правильный ответ *</label>
        <?php if ($task['task_type'] === 'essay'): ?>
            <textarea id="correct_answer" name="correct_answer" class="form-control" rows="4" 
                      placeholder="Пример правильного ответа..." required><?php echo htmlspecialchars($task['correct_answer']); ?></textarea>
        <?php else: ?>
            <input type="text" id="correct_answer" name="correct_answer" class="form-control" 
                   value="<?php echo htmlspecialchars($task['correct_answer']); ?>" required>
        <?php endif; ?>
    </div>
</div>

            <div class="form-group">
              <label for="explanation">Объяснение (показывается после ответа)</label>
              <textarea id="explanation" name="explanation" class="form-control"
                rows="3"><?php echo htmlspecialchars($task['explanation'] ?? ''); ?></textarea>
            </div>

            <div style="text-align: right; margin-top: 30px;">
              <a href="?page=content&type=tasks" class="btn btn-secondary">Отмена</a>
              <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </div>
          </form>

      <?php endif; ?>

      <!-- модалка на добавление заданий -->
      <div id="addTaskModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Добавить новое задание</h3>
            <button type="button" class="modal-close">×</button>
          </div>

          <form method="POST">
            <input type="hidden" name="add_task" value="1">

            <div class="admin-form">
              <div class="form-group">
                <label for="new_task_text">Текст задания *</label>
                <textarea id="new_task_text" name="task_text" class="form-control" rows="3" required></textarea>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                  <label for="new_module_id">Модуль *</label>
                  <select id="new_module_id" name="module_id" class="form-control" required>
                    <?php foreach ($modules as $module_item): ?>
                        <option value="<?php echo $module_item['module_id']; ?>">
                          <?php echo htmlspecialchars($module_item['level_code'] . ' - ' . $module_item['module_name']); ?>
                        </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label for="new_task_type">Тип задания *</label>
                  <select id="new_task_type" name="task_type" class="form-control" required
                    onchange="toggleNewOptions(this.value)">
                    <option value="multiple_choice">Выбор ответа</option>
                    <option value="fill_blank">Заполнение пропусков</option>
                  </select>
                </div>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                  <label for="new_difficulty_level">Уровень сложности *</label>
                  <select id="new_difficulty_level" name="difficulty_level" class="form-control" required>
                    <?php foreach ($levels as $level): ?>
                        <option value="<?php echo $level['level_code']; ?>">
                          <?php echo htmlspecialchars($level['level_code']); ?>
                        </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label for="new_points">Баллы *</label>
                  <input type="number" id="new_points" name="points" class="form-control" value="10" min="1" max="100"
                    required>
                </div>

                <div class="form-group" style="display: flex; align-items: center; margin-top: 25px;">
                  <input type="checkbox" id="new_is_active" name="is_active" checked>
                  <label for="new_is_active" style="margin-left: 10px; margin-bottom: 0;">Активное задание</label>
                </div>
              </div>

              <div id="new-options-section"
                style="display: none; margin-bottom: 20px; padding: 20px; background: var(--light-gray); border-radius: 8px;">
                <h4>Варианты ответов</h4>

                <div id="new-options-container">
                  <div class="form-group option-row"
                    style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <input type="radio" name="correct_option" value="0" checked>
                    <input type="text" name="options[0]" class="form-control" placeholder="Текст варианта ответа">
                    <button type="button" class="btn-delete" onclick="removeNewOption(this)"
                      style="padding: 5px 10px;">Удалить</button>
                  </div>
                </div>

                <button type="button" class="btn btn-secondary" onclick="addNewOption()">+ Добавить вариант</button>
              </div>

              <div id="new-correct-answer-section" style="margin-bottom: 20px;">
    <div class="form-group">
        <label for="new_correct_answer">Правильный ответ *</label>
        <input type="text" id="new_correct_answer" name="correct_answer" class="form-control" required>
    </div>
</div>

              <div class="form-group">
                <label for="new_explanation">Объяснение (показывается после ответа)</label>
                <textarea id="new_explanation" name="explanation" class="form-control" rows="3"></textarea>
              </div>
            </div>

            <div style="text-align: right; margin-top: 30px;">
              <button type="button" class="btn btn-secondary modal-close">Отмена</button>
              <button type="submit" class="btn btn-primary">Добавить задание</button>
            </div>
          </form>
        </div>
      </div>
<script>
  // функции для обработки дейтсвий над заданиями\модулями\уровнями
document.addEventListener('DOMContentLoaded', function() {
    const addLevelForm = document.querySelector('form[action="?page=content&type=levels&action=add"]');
    if (addLevelForm) {
        addLevelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, 'Уровень успешно добавлен');
        });
    }
    
    const addModuleForm = document.querySelector('form[action="?page=content&type=modules&action=add"]');
    if (addModuleForm) {
        addModuleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, 'Модуль успешно добавлен');
        });
    }
    
    const addTaskForm = document.querySelector('form[action="?page=content&type=tasks&action=add"]');
    if (addTaskForm) {
        addTaskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, 'Задание успешно добавлено');
        });
    }
    
    const editForms = document.querySelectorAll('form[method="POST"]:not([action])');
    editForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (this.querySelector('[name*="update_"]')) {
                e.preventDefault();
                submitForm(this, 'Изменения успешно сохранены');
            }
        });
    });
});

// функция для реализации удаления чего-либо на стороне клиента
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a.btn-delete').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const href = this.getAttribute('href');
            const confirmMessage = this.getAttribute('data-confirm') || 
                                  'Вы уверены, что хотите удалить этот элемент?';
            
            if (confirm(confirmMessage)) {
                const originalText = this.textContent;
                this.textContent = 'Удаление...';
                this.style.opacity = '0.5';
                
                fetch(href)
                    .then(response => response.text())
                    .then(html => {
                        window.location.reload();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Ошибка удаления', 'error');
                        this.textContent = originalText;
                        this.style.opacity = '1';
                    });
            }
        });
    });
});

// валидация и отправка
function submitForm(form, successMessage) {
    const taskType = form.querySelector('select[name="task_type"]');
    if (taskType && taskType.value === 'multiple_choice') {
        const optionInputs = form.querySelectorAll('input[name^="options["]');
        const hasOptions = Array.from(optionInputs).some(input => input.value.trim() !== '');
        
        if (!hasOptions) {
            showNotification('Для заданий с выбором ответа необходимо добавить хотя бы один вариант', 'error');
            return;
        }
        
        const correctOption = form.querySelector('input[name="correct_option"]:checked');
        if (!correctOption) {
            showNotification('Выберите правильный вариант ответа', 'error');
            return;
        }
    }

    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Сохранение...';
  
    fetch('', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || successMessage, 'success');
            
            const modal = form.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Ошибка сохранения', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Ошибка сети. Проверьте подключение к интернету.', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// функция для показа уведомления об успехе\ошибке
function showNotification(message, type) {
    const oldNotifications = document.querySelectorAll('.custom-notification');
    oldNotifications.forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = `custom-notification alert alert-${type === 'success' ? 'success' : 'error'}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10001;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    /* Стили для C2 уровня */
    .level-c2 {
        background-color: #2c3e50 !important;
        color: white !important;
        border: 1px solid #2c3e50;
    }
    
    .status-badge.c2 {
        background: rgba(44, 62, 80, 0.1) !important;
        color: #2c3e50 !important;
        border: 1px solid #2c3e50 !important;
    }
`;
document.head.appendChild(style);

document.querySelectorAll('.modal form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        if (this.querySelector('[name="add_level"]')) {
            submitForm(this, 'Уровень успешно добавлен');
        } else if (this.querySelector('[name="add_module"]')) {
            submitForm(this, 'Модуль успешно добавлен');
        } else if (this.querySelector('[name="add_task"]')) {
            submitForm(this, 'Задание успешно добавлено');
        }
    });
});

// функция для обработки действий при редекатировании\создании заданий
function toggleOptions(taskType) {
    const optionsSection = document.getElementById('options-section');
    const correctAnswerSection = document.getElementById('correct-answer-section');
    const correctAnswerInput = document.getElementById('correct_answer');
    
    if (taskType === 'multiple_choice') {
        optionsSection.style.display = 'block';
        correctAnswerSection.style.display = 'none';
        correctAnswerInput.required = false;
        
        document.querySelector('label[for="correct_answer"]').textContent = 'Правильный ответ *';
        correctAnswerInput.type = 'hidden';
    } else {
        optionsSection.style.display = 'none';
        correctAnswerSection.style.display = 'block';
        correctAnswerInput.required = true;
        
        if (taskType === 'essay') {
            const parent = correctAnswerInput.parentNode;
            const newTextarea = document.createElement('textarea');
            newTextarea.id = 'correct_answer';
            newTextarea.name = 'correct_answer';
            newTextarea.className = 'form-control';
            newTextarea.rows = 4;
            newTextarea.placeholder = 'Пример правильного ответа...';
            newTextarea.value = correctAnswerInput.value;
            newTextarea.required = true;
            
            parent.replaceChild(newTextarea, correctAnswerInput);
            document.querySelector('label[for="correct_answer"]').textContent = 'Пример правильного ответа *';
        } else {
            if (correctAnswerInput.tagName === 'TEXTAREA') {
                const parent = correctAnswerInput.parentNode;
                const newInput = document.createElement('input');
                newInput.id = 'correct_answer';
                newInput.name = 'correct_answer';
                newInput.type = 'text';
                newInput.className = 'form-control';
                newInput.value = correctAnswerInput.value;
                newInput.required = true;
                
                parent.replaceChild(newInput, correctAnswerInput);
            }
            document.querySelector('label[for="correct_answer"]').textContent = 'Правильный ответ *';
        }
    }
}

// // функция для обработки действий при редекатировании\создании заданий (новая вроде)
function toggleNewOptions(taskType) {
    const optionsSection = document.getElementById('new-options-section');
    const correctAnswerSection = document.getElementById('new-correct-answer-section');
    const correctAnswerInput = document.getElementById('new_correct_answer');
    const correctAnswerLabel = document.querySelector('label[for="new_correct_answer"]');
    
    if (taskType === 'multiple_choice') {
        optionsSection.style.display = 'block';
        correctAnswerSection.style.display = 'none';
        correctAnswerInput.required = false;
        correctAnswerLabel.textContent = 'Правильный ответ *';
    } else {
        optionsSection.style.display = 'none';
        correctAnswerSection.style.display = 'block';
        correctAnswerInput.required = true;
        
        if (taskType === 'essay') {
            const parent = correctAnswerInput.parentNode;
            const newTextarea = document.createElement('textarea');
            newTextarea.id = 'new_correct_answer';
            newTextarea.name = 'correct_answer';
            newTextarea.className = 'form-control';
            newTextarea.rows = 4;
            newTextarea.placeholder = 'Пример правильного ответа...';
            newTextarea.required = true;
            
            parent.replaceChild(newTextarea, correctAnswerInput);
            correctAnswerLabel.textContent = 'Пример правильного ответа *';
        } else {
            correctAnswerLabel.textContent = 'Правильный ответ *';
            if (correctAnswerInput.tagName === 'TEXTAREA') {
                const parent = correctAnswerInput.parentNode;
                const newInput = document.createElement('input');
                newInput.id = 'new_correct_answer';
                newInput.name = 'correct_answer';
                newInput.type = 'text';
                newInput.className = 'form-control';
                newInput.required = true;
                
                parent.replaceChild(newInput, correctAnswerInput);
            }
        }
    }
}

// доавбляем вариант ответа
function addOption() {
    const container = document.getElementById('options-container');
    const optionCount = container.querySelectorAll('.option-row').length;
    
    const newRow = document.createElement('div');
    newRow.className = 'form-group option-row';
    newRow.style.cssText = 'display: flex; align-items: center; gap: 10px; margin-bottom: 10px;';
    
    newRow.innerHTML = `
        <input type="radio" name="correct_option" value="${optionCount}" 
               ${optionCount === 0 ? 'checked' : ''}>
        <input type="text" name="options[${optionCount}]" class="form-control" 
               placeholder="Текст варианта ответа" required>
        <button type="button" class="btn-delete" onclick="removeOption(this)" 
                style="padding: 5px 10px;">Удалить</button>
    `;
    
    container.appendChild(newRow);
}

// добавление ещё вариантов ответа
function addNewOption() {
    const container = document.getElementById('new-options-container');
    const optionCount = container.querySelectorAll('.option-row').length;
    
    const newRow = document.createElement('div');
    newRow.className = 'form-group option-row';
    newRow.style.cssText = 'display: flex; align-items: center; gap: 10px; margin-bottom: 10px;';
    
    newRow.innerHTML = `
        <input type="radio" name="correct_option" value="${optionCount}" 
               ${optionCount === 0 ? 'checked' : ''}>
        <input type="text" name="options[${optionCount}]" class="form-control" 
               placeholder="Текст варианта ответа" required>
        <button type="button" class="btn-delete" onclick="removeNewOption(this)" 
                style="padding: 5px 10px;">Удалить</button>
    `;
    
    container.appendChild(newRow);
}

// удаляем вариант ответа
function removeOption(button) {
    const row = button.closest('.option-row');
    if (row) {
        row.remove();
        
        const container = document.getElementById('options-container');
        const rows = container.querySelectorAll('.option-row');
        rows.forEach((row, index) => {
            const radio = row.querySelector('input[type="radio"]');
            const input = row.querySelector('input[type="text"]');
            
            if (radio) {
                radio.value = index;
                radio.name = 'correct_option';
            }
            if (input) {
                input.name = `options[${index}]`;
            }
        });
    }
}

// убираем созданные нами варианты ответа
function removeNewOption(button) {
    const row = button.closest('.option-row');
    if (row) {
        row.remove();
        
        const container = document.getElementById('new-options-container');
        const rows = container.querySelectorAll('.option-row');
        rows.forEach((row, index) => {
            const radio = row.querySelector('input[type="radio"]');
            const input = row.querySelector('input[type="text"]');
            
            if (radio) {
                radio.value = index;
                radio.name = 'correct_option';
            }
            if (input) {
                input.name = `options[${index}]`;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($action === 'edit' && isset($task)): ?>
        const editTaskType = document.getElementById('task_type');
        if (editTaskType) {
            toggleOptions(editTaskType.value);
            editTaskType.addEventListener('change', function() {
                toggleOptions(this.value);
            });
        }
    <?php endif; ?>
    
    const newTaskType = document.getElementById('new_task_type');
    if (newTaskType) {
        toggleNewOptions(newTaskType.value);
        newTaskType.addEventListener('change', function() {
            toggleNewOptions(this.value);
        });
    }
    
    document.querySelectorAll('select[name="task_type"]').forEach(select => {
        if (!select.id.includes('new_')) {
            select.addEventListener('change', function() {
                toggleOptions(this.value);
            });
        }
    });
});
</script>
  <?php endif; ?>
</div>
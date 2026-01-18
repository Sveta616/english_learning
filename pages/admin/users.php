<?php
// переменные для данных
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

$sql = "SELECT u.*, c.city_name 
        FROM users u 
        LEFT JOIN cities c ON u.city_id = c.city_id 
        WHERE 1=1";

$params = [];
$types = "";

// фильтр и поиск юзеров
if ($search) {
  $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
  $search_param = "%$search%";
  $params = array_merge($params, [$search_param, $search_param, $search_param]);
  $types .= "sss";
}

if ($role_filter !== 'all') {
  $sql .= " AND u.user_type = ?";
  $params[] = $role_filter;
  $types .= "s";
}

if ($status_filter !== 'all') {
  $sql .= " AND u.is_active = ?";
  $params[] = ($status_filter === 'active') ? 1 : 0;
  $types .= "i";
}

$sql .= " ORDER BY u.registration_date DESC";
$users = $db->fetchAll($sql, $params);
$cities = $db->fetchAll("SELECT city_id, city_name FROM cities WHERE is_active = 1 ORDER BY city_name");
$levels = $db->fetchAll("SELECT level_id, level_code, level_name FROM levels ORDER BY level_id");
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// обновляем
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $response = ['success' => false, 'message' => 'Неизвестная ошибка'];

  try {
    if (isset($_POST['update_user'])) {
      $user_id = (int) $_POST['user_id'];

      $existing_user = $db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$user_id]);
      if (!$existing_user) {
        throw new Exception('Пользователь не найден');
      }

      $update_data = [
        'full_name' => trim($_POST['full_name']),
        'email' => trim($_POST['email']),
        'user_type' => $_POST['user_type'],
        'city_id' => !empty($_POST['city_id']) ? (int) $_POST['city_id'] : null,
        'current_level_id' => ($_POST['user_type'] === 'student' && !empty($_POST['current_level_id'])) ?
          (int) $_POST['current_level_id'] : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s')
      ];

      if ($_POST['user_type'] === 'tutor') {
        $tutor_data = [
          'full_name' => $update_data['full_name'],
          'email' => $update_data['email'],
          'city_id' => $update_data['city_id'],
          'updated_at' => date('Y-m-d H:i:s')
        ];

        $tutor = $db->fetchOne("SELECT tutor_id FROM tutors WHERE user_id = ?", [$user_id]);
        if ($tutor) {
          $db->update('tutors', $tutor_data, 'user_id = ?', [$user_id]);
        } else {
          $tutor_data['user_id'] = $user_id;
          $tutor_data['is_active'] = 0;
          $tutor_data['is_verified'] = 0;
          $tutor_data['created_at'] = date('Y-m-d H:i:s');
          $db->insert('tutors', $tutor_data);
        }
      }

      $db->update('users', $update_data, 'user_id = ?', [$user_id]);

      $response = [
        'success' => true,
        'message' => 'Пользователь успешно обновлен'
      ];
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

// удаляем или активируем\верефицируем юзера
if (isset($_GET['action'])) {
  $user_id = (int) ($_GET['id'] ?? 0);
  $action = $_GET['action'];

  if ($user_id && in_array($action, ['activate', 'deactivate', 'delete', 'make_admin', 'make_tutor', 'make_student'])) {
    if ($user_id === $currentUser['user_id'] && in_array($action, ['deactivate', 'delete'])) {
      echo '<div class="alert alert-error">Нельзя выполнить это действие над собой</div>';
    } else {
      switch ($action) {
        case 'activate':
          $db->update('users', ['is_active' => 1], 'user_id = ?', [$user_id]);
          echo '<div class="alert alert-success">Пользователь активирован</div>';
          break;

        case 'deactivate':
          $db->update('users', ['is_active' => 0], 'user_id = ?', [$user_id]);
          echo '<div class="alert alert-success">Пользователь деактивирован</div>';
          break;

        case 'delete':
          $user = $db->fetchOne("SELECT user_type FROM users WHERE user_id = ?", [$user_id]);

          if ($user && $user['user_type'] === 'tutor') {
            $db->delete('tutors', 'user_id = ?', [$user_id]);
          }

          $db->delete('users', 'user_id = ?', [$user_id]);
          echo '<div class="alert alert-success">Пользователь удален</div>';
          break;

        case 'make_admin':
          $db->update('users', ['user_type' => 'admin'], 'user_id = ?', [$user_id]);
          echo '<div class="alert alert-success">Пользователь назначен администратором</div>';
          break;

        case 'make_tutor':
          $db->update('users', ['user_type' => 'tutor'], 'user_id = ?', [$user_id]);

          $tutor = $db->fetchOne("SELECT tutor_id FROM tutors WHERE user_id = ?", [$user_id]);
          if (!$tutor) {
            $user_data = $db->fetchOne("SELECT full_name, email, city_id FROM users WHERE user_id = ?", [$user_id]);
            if ($user_data) {
              $tutor_data = [
                'user_id' => $user_id,
                'full_name' => $user_data['full_name'],
                'email' => $user_data['email'],
                'city_id' => $user_data['city_id'],
                'is_active' => 0,
                'is_verified' => 0,
                'created_at' => date('Y-m-d H:i:s')
              ];
              $db->insert('tutors', $tutor_data);
            }
          }
          echo '<div class="alert alert-success">Пользователь назначен репетитором</div>';
          break;

        case 'make_student':
          $db->update('users', ['user_type' => 'student'], 'user_id = ?', [$user_id]);
          echo '<div class="alert alert-success">Пользователь назначен студентом</div>';
          break;
      }

      $users = $db->fetchAll($sql, $params);
    }
  }
}
?>

<div class="admin-section">
  <h2>Управление пользователями</h2>

  <div class="filter-section">
    <!-- фильтры -->
    <form method="GET" class="filter-form">
      <input type="hidden" name="page" value="users">

      <div>
        <label>Поиск пользователей</label>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control"
          placeholder="Имя, email, логин...">
      </div>

      <div>
        <label>Роль</label>
        <select name="role" class="form-control">
          <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>Все роли</option>
          <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Студенты</option>
          <option value="tutor" <?php echo $role_filter === 'tutor' ? 'selected' : ''; ?>>Репетиторы</option>
          <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Администраторы</option>
        </select>
      </div>

      <div>
        <label>Статус</label>
        <select name="status" class="form-control">
          <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Все статусы</option>
          <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Активные</option>
          <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Неактивные</option>
        </select>
      </div>

      <div class="filter-actions">
        <button type="submit" class="btn btn-primary">Применить фильтры</button>
        <a href="?page=users" class="btn btn-secondary">Сбросить</a>
      </div>
    </form>
  </div>

  <div style="background: var(--light-gray); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    Найдено пользователей: <strong><?php echo count($users); ?></strong>
  </div>

  <!-- формируем таблицу всех юзеров с красивым указанием роли -->
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Имя</th>
          <th>Email</th>
          <th>Роль</th>
          <th>Город</th>
          <th>Дата регистрации</th>
          <th>Статус</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($users)): ?>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?php echo $user['user_id']; ?></td>
              <td>
                <div style="font-weight: 600;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div style="font-size: 12px; color: var(--medium-gray);">
                  @<?php echo htmlspecialchars($user['username']); ?>
                </div>
              </td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td>
                <?php
                $role_badges = [
                  'student' => ['class' => 'status-active', 'label' => 'Студент'],
                  'tutor' => ['class' => 'status-verified', 'label' => 'Репетитор'],
                  'admin' => ['class' => '', 'label' => 'Админ', 'style' => 'background: var(--dark-blue); color: white;']
                ];
                $role = $role_badges[$user['user_type']] ?? ['class' => '', 'label' => $user['user_type']];
                ?>
                <span class="status-badge <?php echo $role['class']; ?>" style="<?php echo $role['style'] ?? ''; ?>">
                  <?php echo $role['label']; ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($user['city_name'] ?? 'Не указан'); ?></td>
              <td><?php echo date('d.m.Y', strtotime($user['registration_date'])); ?></td>
              <td>
                <?php if ($user['is_active']): ?>
                  <span class="status-badge status-active">Активен</span>
                <?php else: ?>
                  <span class="status-badge status-inactive">Заблокирован</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="action-buttons">
                  <button type="button" class="btn-edit" onclick="editUser(<?php echo $user['user_id']; ?>)"
                    title="Редактировать">
                    Редактировать
                  </button>

                  <?php if ($user['user_type'] !== 'admin'): ?>
                    <a href="?page=users&action=make_admin&id=<?php echo $user['user_id']; ?>" class="btn-edit"
                      title="Сделать администратором" onclick="return confirm('Назначить пользователя администратором?')">
                      Сделать админом
                    </a>
                  <?php endif; ?>

                  <?php if ($user['user_type'] !== 'tutor'): ?>
                    <a href="?page=users&action=make_tutor&id=<?php echo $user['user_id']; ?>" class="btn-edit"
                      title="Сделать репетитором" onclick="return confirm('Назначить пользователя репетитором?')">
                      Сделать репетитором
                    </a>
                  <?php endif; ?>

                  <?php if ($user['user_type'] !== 'student'): ?>
                    <a href="?page=users&action=make_student&id=<?php echo $user['user_id']; ?>" class="btn-edit"
                      title="Сделать студентом" onclick="return confirm('Назначить пользователя студентом?')">
                      Сделать студентом
                    </a>
                  <?php endif; ?>

                  <?php if ($user['is_active']): ?>
                    <a href="?page=users&action=deactivate&id=<?php echo $user['user_id']; ?>" class="btn-deactivate"
                      title="Заблокировать" onclick="return confirm('Заблокировать пользователя?')">
                      Деактивировать
                    </a>
                  <?php else: ?>
                    <a href="?page=users&action=activate&id=<?php echo $user['user_id']; ?>" class="btn-activate"
                      title="Активировать">
                      Активировать
                    </a>
                  <?php endif; ?>

                  <?php if ($user['user_id'] !== $currentUser['user_id']): ?>
                    <a href="?page=users&action=delete&id=<?php echo $user['user_id']; ?>" class="btn-delete" title="Удалить"
                      onclick="return confirm('Удалить пользователя навсегда?')">
                      Удалить
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 40px;">
              <div style="font-size: 48px; color: var(--medium-gray); margin-bottom: 20px;">👥</div>
              <h3>Пользователи не найдены</h3>
              <p style="color: var(--medium-gray);">Попробуйте изменить параметры поиска</p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>


  <div style="text-align: center; margin-top: 30px;">
    <button type="button" data-modal="addUserModal" class="btn btn-primary">
      Добавить нового пользователя
    </button>
  </div>
</div>
<!-- модалка редактирования пользователя -->
<div id="editUserModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Редактирование пользователя</h3>
      <button type="button" class="modal-close">×</button>
    </div>

    <form id="editUserForm" method="POST">
      <input type="hidden" name="update_user" value="1">
      <input type="hidden" id="edit_user_id" name="user_id">

      <div class="admin-form">
        <div class="form-group">
          <label for="edit_full_name">ФИО *</label>
          <input type="text" id="edit_full_name" name="full_name" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="edit_email">Email *</label>
          <input type="email" id="edit_email" name="email" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="edit_username">Имя пользователя</label>
          <input type="text" id="edit_username" class="form-control" disabled>
          <small style="color: var(--medium-gray);">Имя пользователя нельзя изменить</small>
        </div>

        <div class="form-group">
          <label for="edit_user_type">Роль *</label>
          <select id="edit_user_type" name="user_type" class="form-control" required
            onchange="toggleLevelField(this.value)">
            <option value="student">Студент</option>
            <option value="tutor">Репетитор</option>
            <option value="admin">Администратор</option>
          </select>
        </div>

        <div class="form-group">
          <label for="edit_city_id">Город</label>
          <select id="edit_city_id" name="city_id" class="form-control">
            <option value="">Не указан</option>
            <?php foreach ($cities as $city): ?>
              <option value="<?php echo $city['city_id']; ?>">
                <?php echo htmlspecialchars($city['city_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" id="level-field-container" style="display: none;">
          <label for="edit_current_level_id">Текущий уровень</label>
          <select id="edit_current_level_id" name="current_level_id" class="form-control">
            <option value="">Не указан</option>
            <?php foreach ($levels as $level): ?>
              <option value="<?php echo $level['level_id']; ?>">
                <?php echo htmlspecialchars($level['level_code'] . ' - ' . $level['level_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="display: flex; align-items: center;">
          <input type="checkbox" id="edit_is_active" name="is_active">
          <label for="edit_is_active" style="margin-left: 10px; margin-bottom: 0;">Активный пользователь</label>
        </div>
      </div>

      <div style="text-align: right; margin-top: 30px;">
        <button type="button" class="btn btn-secondary modal-close">Отмена</button>
        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
      </div>
    </form>
  </div>
</div>

<!-- модалка добавления пользователя -->
<div id="addUserModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Добавить нового пользователя</h3>
      <button type="button" class="modal-close">×</button>
    </div>

    <form id="addUserForm" method="POST" action="../../api/admin/add_user.php">
      <div class="admin-form">
        <div class="form-group">
          <label for="new_full_name">ФИО *</label>
          <input type="text" id="new_full_name" name="full_name" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="new_email">Email *</label>
          <input type="email" id="new_email" name="email" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="new_username">Имя пользователя *</label>
          <input type="text" id="new_username" name="username" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="new_password">Пароль *</label>
          <input type="password" id="new_password" name="password" class="form-control" required minlength="6">
        </div>

        <div class="form-group">
          <label for="new_user_type">Роль *</label>
          <select id="new_user_type" name="user_type" class="form-control" required>
            <option value="student">Студент</option>
            <option value="tutor">Репетитор</option>
            <option value="admin">Администратор</option>
          </select>
        </div>

        <div class="form-group">
          <label for="new_city_id">Город</label>
          <select id="new_city_id" name="city_id" class="form-control">
            <option value="">Не указан</option>
            <?php foreach ($cities as $city): ?>
              <option value="<?php echo $city['city_id']; ?>">
                <?php echo htmlspecialchars($city['city_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="text-align: right; margin-top: 30px;">
        <button type="button" class="btn btn-secondary modal-close">Отмена</button>
        <button type="submit" class="btn btn-primary">Добавить пользователя</button>
      </div>
    </form>
  </div>
</div>

<script>
  function toggleLevelField(userType) {
    const levelField = document.getElementById('level-field-container');
    if (userType === 'student') {
      levelField.style.display = 'block';
    } else {
      levelField.style.display = 'none';
    }
  }

  // редактруем юзера и применяем действия через апи
  function editUser(userId) {
    fetch(`../../api/admin/get_user.php?id=${userId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const user = data.user;

          document.getElementById('edit_user_id').value = user.user_id;
          document.getElementById('edit_full_name').value = user.full_name || '';
          document.getElementById('edit_email').value = user.email || '';
          document.getElementById('edit_username').value = user.username || '';
          document.getElementById('edit_user_type').value = user.user_type || 'student';
          document.getElementById('edit_city_id').value = user.city_id || '';
          document.getElementById('edit_current_level_id').value = user.current_level_id || '';
          document.getElementById('edit_is_active').checked = user.is_active == 1;

          toggleLevelField(user.user_type || 'student');

          document.getElementById('editUserModal').style.display = 'flex';
        } else {
          alert('Ошибка загрузки данных: ' + data.message);
        }
      })
      .catch(error => {
        alert('Ошибка сети: ' + error.message);
      });
  }

  // обрабатываем форму редактирования юзера и в зависимости от результата выводим ошибку\успех
  document.getElementById('editUserForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
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
          showNotification(data.message || 'Пользователь успешно обновлен', 'success');

          document.getElementById('editUserModal').style.display = 'none';

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
  });

  // также обработка модалки добавления юзера
  document.getElementById('addUserForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Добавление...';

    fetch(this.action, {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showNotification('Пользователь успешно добавлен', 'success');

          document.getElementById('addUserModal').style.display = 'none';

          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          showNotification(data.message || 'Ошибка добавления', 'error');
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
  });

  // вывод уведомления(сообщения) об успехе\ошибке
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

  if (!document.querySelector('style[data-notifications]')) {
    const style = document.createElement('style');
    style.setAttribute('data-notifications', 'true');
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
    `;
    document.head.appendChild(style);
  }
</script>
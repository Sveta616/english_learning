<?php
// переменные под данные
$search = $_GET['search'] ?? '';
$verification_filter = $_GET['verification'] ?? 'all';
$city_filter = $_GET['city'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

$sql = "SELECT t.*, c.city_name, ts.name as specialization_name, u.is_active as user_active
        FROM tutors t
        LEFT JOIN cities c ON t.city_id = c.city_id
        LEFT JOIN tutor_specializations ts ON t.specialization_id = ts.specialization_id
        LEFT JOIN users u ON t.user_id = u.user_id
        WHERE 1=1";

$params = [];
$types = "";

// параметры и фильтры поиска
if ($search) {
    $sql .= " AND (t.full_name LIKE ? OR t.email LIKE ? OR t.bio LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if ($verification_filter !== 'all') {
    $sql .= " AND t.is_verified = ?";
    $params[] = ($verification_filter === 'verified') ? 1 : 0;
    $types .= "i";
}

if ($city_filter !== 'all' && is_numeric($city_filter)) {
    $sql .= " AND t.city_id = ?";
    $params[] = $city_filter;
    $types .= "i";
}

if ($status_filter !== 'all') {
    $sql .= " AND t.is_active = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
    $types .= "i";
}

$sql .= " ORDER BY t.is_verified DESC, t.rating DESC";

$tutors = $db->fetchAll($sql, $params);

$cities = $db->fetchAll("SELECT city_id, city_name FROM cities WHERE is_active = 1 ORDER BY city_name");

$specializations = $db->fetchAll("SELECT * FROM tutor_specializations ORDER BY name");

if (isset($_GET['action'])) {
    $tutor_id = (int) ($_GET['id'] ?? 0);
    $action = $_GET['action'];
    
    if ($tutor_id && in_array($action, ['verify', 'unverify', 'activate', 'deactivate', 'delete', 'edit'])) {
        $tutor = $db->fetchOne("SELECT * FROM tutors WHERE tutor_id = ?", [$tutor_id]);
        
        if ($tutor) {
            switch ($action) {
                case 'verify':
                    $db->update('tutors', ['is_verified' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'tutor_id = ?', [$tutor_id]);
                    echo '<div class="alert alert-success">Репетитор верифицирован</div>';
                    break;
                    
                case 'unverify':
                    $db->update('tutors', ['is_verified' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'tutor_id = ?', [$tutor_id]);
                    echo '<div class="alert alert-success">Верификация отменена</div>';
                    break;
                    
                case 'activate':
                    $db->update('tutors', ['is_active' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'tutor_id = ?', [$tutor_id]);
                    $db->update('users', ['is_active' => 1], 'user_id = ?', [$tutor['user_id']]);
                    echo '<div class="alert alert-success">Репетитор активирован</div>';
                    break;
                    
                case 'deactivate':
                    $db->update('tutors', ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'tutor_id = ?', [$tutor_id]);
                    echo '<div class="alert alert-success">Репетитор деактивирован</div>';
                    break;
                    
                case 'delete':
                    $db->delete('tutors', 'tutor_id = ?', [$tutor_id]);
                    $db->delete('users', 'user_id = ?', [$tutor['user_id']]);
                    echo '<div class="alert alert-success">Репетитор удален</div>';
                    break;
                    
                case 'edit':
                    break;
            }
            
            $tutors = $db->fetchAll($sql, $params);
        }
    }
}

// обновляем инфу о преподе
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tutor'])) {
    $tutor_id = (int) $_POST['tutor_id'];
    
    // Сначала получаем текущие данные репетитора
    $current_tutor = $db->fetchOne("SELECT * FROM tutors WHERE tutor_id = ?", [$tutor_id]);
    if (!$current_tutor) {
        echo '<div class="alert alert-error">Репетитор не найден</div>';
    } else {
        $update_data = [
            'full_name' => trim($_POST['full_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone'] ?? ''),
            'city_id' => !empty($_POST['city_id']) ? (int) $_POST['city_id'] : null,
            'bio' => trim($_POST['bio'] ?? ''),
            'experience_years' => !empty($_POST['experience_years']) ? (int) $_POST['experience_years'] : null,
            'hourly_rate' => !empty($_POST['hourly_rate']) ? floatval($_POST['hourly_rate']) : null,
            'specialization_id' => !empty($_POST['specialization_id']) ? (int) $_POST['specialization_id'] : null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Обновляем данные в таблице tutors
        $db->update('tutors', $update_data, 'tutor_id = ?', [$tutor_id]);
        
        // Обновляем соответствующие поля в таблице users
        $user_update_data = [
            'email' => $update_data['email'],
            'full_name' => $update_data['full_name'],
            'city_id' => $update_data['city_id'], // если город связан и с пользователем
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Обновляем пользователя
        $db->update('users', $user_update_data, 'user_id = ?', [$current_tutor['user_id']]);
        
        echo '<div class="alert alert-success">Профиль репетитора обновлен</div>';
        $tutors = $db->fetchAll($sql, $params);
    }
}
?>
<!-- вывод интерфейса -->
<div class="admin-section">
    <h2>Управление репетиторами</h2>
    <div class="filter-section">
      <!-- фильтры -->
        <form method="GET" class="filter-form">
            <input type="hidden" name="page" value="tutors">
            
            <div>
                <label>Поиск репетиторов</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-control" placeholder="Имя, email, биография...">
            </div>
            
            <div>
                <label>Город</label>
                <select name="city" class="form-control">
                    <option value="all">Все города</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo $city['city_id']; ?>" 
                                <?php echo $city_filter == $city['city_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($city['city_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label>Верификация</label>
                <select name="verification" class="form-control">
                    <option value="all" <?php echo $verification_filter === 'all' ? 'selected' : ''; ?>>Все</option>
                    <option value="verified" <?php echo $verification_filter === 'verified' ? 'selected' : ''; ?>>Верифицированы</option>
                    <option value="not_verified" <?php echo $verification_filter === 'not_verified' ? 'selected' : ''; ?>>Не верифицированы</option>
                </select>
            </div>
            
            <div>
                <label>Статус</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Все</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Активные</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Неактивные</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Применить фильтры</button>
                <a href="?page=tutors" class="btn btn-secondary">Сбросить</a>
            </div>
        </form>
    </div>
    <!-- микро статистика перед выводом -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div style="background: var(--light-gray); padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: var(--dark-blue);">
                <?php echo count($tutors); ?>
            </div>
            <div style="font-size: 14px; color: var(--medium-gray);">Всего репетиторов</div>
        </div>
        
        <div style="background: rgba(155, 89, 182, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #9b59b6;">
                <?php echo count(array_filter($tutors, fn($t) => $t['is_verified'])); ?>
            </div>
            <div style="font-size: 14px; color: var(--medium-gray);">Верифицированы</div>
        </div>
        
        <div style="background: rgba(46, 213, 115, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #2ed573;">
                <?php echo count(array_filter($tutors, fn($t) => $t['is_active'])); ?>
            </div>
            <div style="font-size: 14px; color: var(--medium-gray);">Активны</div>
        </div>
        
        <div style="background: rgba(52, 152, 219, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #3498db;">
                <?php 
                $average_rating = array_reduce($tutors, fn($carry, $t) => $carry + ($t['rating'] ?? 0), 0);
                $count_with_rating = count(array_filter($tutors, fn($t) => $t['rating'] > 0));
                echo $count_with_rating > 0 ? number_format($average_rating / $count_with_rating, 1) : '0.0';
                ?>
            </div>
            <div style="font-size: 14px; color: var(--medium-gray);">Средний рейтинг</div>
        </div>
    </div>
    <!-- формируем и выводим всех репетиторов в таблицу -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Репетитор</th>
                    <th>Контакты</th>
                    <th>Город / Специализация</th>
                    <th>Статистика</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tutors)): ?>
                    <?php foreach ($tutors as $tutor): ?>
                        <tr>
                            <td><?php echo $tutor['tutor_id']; ?></td>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($tutor['full_name']); ?></div>
                                <div style="font-size: 12px; color: var(--medium-gray);">
                                    <?php if ($tutor['experience_years']): ?>
                                        Опыт: <?php echo $tutor['experience_years']; ?> лет
                                    <?php endif; ?>
                                    <?php if ($tutor['hourly_rate']): ?>
                                        | <?php echo number_format($tutor['hourly_rate'], 0, ',', ' '); ?> ₽/час
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div>Email: <?php echo htmlspecialchars($tutor['email']); ?></div>
                                <?php if ($tutor['phone']): ?>
                                    <div>Телефон: <?php echo htmlspecialchars($tutor['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($tutor['city_name'] ?? 'Не указан'); ?></div>
                                <div style="font-size: 12px; color: var(--medium-gray);">
                                    <?php echo htmlspecialchars($tutor['specialization_name'] ?? 'Не указана'); ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div>
                                        <div style="font-weight: bold; color: var(--dark-blue);">
                                            ★ <?php echo number_format($tutor['rating'] ?? 0, 1); ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--medium-gray);">
                                            <?php echo $tutor['total_reviews'] ?? 0; ?> отзывов
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <?php if ($tutor['is_verified']): ?>
                                        <span class="status-badge status-verified">— Верифицирован</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">— На проверке</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($tutor['is_active']): ?>
                                        <span class="status-badge status-active">— Активен</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive"> — Неактивен</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn-edit" 
                                            onclick="editTutor(<?php echo $tutor['tutor_id']; ?>)"
                                            title="Редактировать">
                                        Редактировать
                                    </button>
                                    
                                    <?php if ($tutor['is_verified']): ?>
                                        <a href="?page=tutors&action=unverify&id=<?php echo $tutor['tutor_id']; ?>" 
                                           class="btn-deactivate" title="Снять верификацию"
                                           onclick="return confirm('Снять верификацию с репетитора?')">
                                            Снять верификацию
                                        </a>
                                    <?php else: ?>
                                        <a href="?page=tutors&action=verify&id=<?php echo $tutor['tutor_id']; ?>" 
                                           class="btn-verify" title="Верифицировать"
                                           onclick="return confirm('Верифицировать репетитора?')">
                                            Верефицировать
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($tutor['is_active']): ?>
                                        <a href="?page=tutors&action=deactivate&id=<?php echo $tutor['tutor_id']; ?>" 
                                           class="btn-deactivate" title="Деактивировать"
                                           onclick="return confirm('Деактивировать репетитора?')">
                                            Деактивировать
                                        </a>
                                    <?php else: ?>
                                        <a href="?page=tutors&action=activate&id=<?php echo $tutor['tutor_id']; ?>" 
                                           class="btn-activate" title="Активировать">
                                            Активировать
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?page=tutors&action=delete&id=<?php echo $tutor['tutor_id']; ?>" 
                                       class="btn-delete" title="Удалить"
                                       onclick="return confirm('Удалить репетитора навсегда?')">
                                        Удалить
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <h3>Репетиторы не найдены</h3>
                            <p style="color: var(--medium-gray);">Попробуйте изменить параметры поиска</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="?page=users&action=add&prefill_type=tutor" class="btn btn-primary">
            Добавить нового репетитора
        </a>
    </div>
</div>
<!-- модалка для редактирования инфы о преподе -->
<div id="editTutorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Редактирование репетитора</h3>
            <button type="button" class="modal-close">×</button>
        </div>
        
        <form id="editTutorForm" method="POST">
            <input type="hidden" name="update_tutor" value="1">
            <input type="hidden" id="edit_tutor_id" name="tutor_id">
            
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
                    <label for="edit_phone">Телефон</label>
                    <input type="text" id="edit_phone" name="phone" class="form-control" placeholder="+7 (XXX) XXX-XX-XX">
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
                
                <div class="form-group">
                    <label for="edit_specialization_id">Специализация</label>
                    <select id="edit_specialization_id" name="specialization_id" class="form-control">
                        <option value="">Не указана</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo $spec['specialization_id']; ?>">
                                <?php echo htmlspecialchars($spec['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_experience_years">Опыт работы (лет)</label>
                    <input type="number" id="edit_experience_years" name="experience_years" class="form-control" min="0" max="50">
                </div>
                
                <div class="form-group">
                    <label for="edit_hourly_rate">Стоимость часа (₽)</label>
                    <input type="number" id="edit_hourly_rate" name="hourly_rate" class="form-control" min="0" step="100">
                </div>
                
                <div class="form-group">
                    <label for="edit_bio">Биография</label>
                    <textarea id="edit_bio" name="bio" class="form-control" rows="4" 
                              placeholder="Расскажите о себе, образовании, методике преподавания..."></textarea>
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 30px;">
                <button type="button" class="btn btn-secondary modal-close">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </div>
        </form>
    </div>
</div>

<script>
  // получаем препода и инфу о нем через апи
function editTutor(tutorId) {
    fetch(`../../api/admin/get_tutor.php?id=${tutorId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tutor = data.tutor;
                
                document.getElementById('edit_tutor_id').value = tutor.tutor_id;
                document.getElementById('edit_full_name').value = tutor.full_name || '';
                document.getElementById('edit_email').value = tutor.email || '';
                document.getElementById('edit_phone').value = tutor.phone || '';
                document.getElementById('edit_city_id').value = tutor.city_id || '';
                document.getElementById('edit_specialization_id').value = tutor.specialization_id || '';
                document.getElementById('edit_experience_years').value = tutor.experience_years || '';
                document.getElementById('edit_hourly_rate').value = tutor.hourly_rate || '';
                document.getElementById('edit_bio').value = tutor.bio || '';
                
                document.getElementById('editTutorModal').style.display = 'flex';
            } else {
                alert('Ошибка загрузки данных: ' + data.message);
            }
        })
        .catch(error => {
            alert('Ошибка сети: ' + error.message);
        });
}

document.getElementById('editTutorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        location.reload();
    })
    .catch(error => {
        alert('Ошибка: ' + error.message);
    });
});
</script>
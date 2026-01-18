<?php
// загружаем ближайщие 
$upcoming_lessons = $db->fetchAll(
    "SELECT tr.*, u.full_name as student_name, u.email as student_email, 
            c.city_name as student_city_name,
            tr.student_contact_phone, tr.student_age, tr.social_media
     FROM tutor_requests tr 
     LEFT JOIN users u ON tr.student_id = u.user_id 
     LEFT JOIN cities c ON u.city_id = c.city_id 
     WHERE tr.tutor_id = ? AND tr.status = 'accepted'
     ORDER BY tr.request_date ASC",
    [$tutor['tutor_id']]
);

// если завершаем занятие
if (isset($_POST['complete_lesson'])) {
    $request_id = (int)$_POST['request_id'];
    
    $request = $db->fetchOne(
        "SELECT * FROM tutor_requests 
         WHERE request_id = ? AND tutor_id = ? AND status = 'accepted'",
        [$request_id, $tutor['tutor_id']]
    );
    
    if ($request) {
        $db->update('tutor_requests', 
            [
                'status' => 'completed',
                'response_date' => date('Y-m-d H:i:s')
            ],
            'request_id = ?',
            [$request_id]
        );
        
        $upcoming_lessons = $db->fetchAll(
            "SELECT tr.*, u.full_name as student_name, u.email as student_email, 
                    c.city_name as student_city_name,
                    tr.student_contact_phone, tr.student_age, tr.social_media
             FROM tutor_requests tr 
             LEFT JOIN users u ON tr.student_id = u.user_id 
             LEFT JOIN cities c ON u.city_id = c.city_id 
             WHERE tr.tutor_id = ? AND tr.status = 'accepted'
             ORDER BY tr.request_date ASC",
            [$tutor['tutor_id']]
        );
        
        echo '<div class="alert alert-success">Занятие отмечено как завершенное!</div>';
    }
}

// получаем завершенные занятия
$completed_lessons = $db->fetchAll(
    "SELECT tr.*, u.full_name as student_name, u.email as student_email, 
            c.city_name as student_city_name,
            tr.student_contact_phone, tr.student_age, tr.social_media,
            tr.response_date
     FROM tutor_requests tr 
     LEFT JOIN users u ON tr.student_id = u.user_id 
     LEFT JOIN cities c ON u.city_id = c.city_id 
     WHERE tr.tutor_id = ? AND tr.status = 'completed'
     ORDER BY tr.request_date DESC
     LIMIT 10",
    [$tutor['tutor_id']]
);

// форматируем дату для вывода
function formatScheduleDate($date_string) {
    $date = new DateTime($date_string);
    $now = new DateTime();

    $days_of_week = [
        'воскресенье', 'понедельник', 'вторник', 'среда', 
        'четверг', 'пятница', 'суббота'
    ];
    
    $day_of_week = $days_of_week[(int)$date->format('w')];
    $day_month = $date->format('d.m');
    $full_date = $date->format('d.m.Y H:i');
    
    $is_past = $date < $now;
    $is_today = $date->format('Y-m-d') === $now->format('Y-m-d');
    $is_ongoing = $date <= $now && $date->modify('+1 hour') > $now;

    $date = new DateTime($date_string);
    $time_until = '';
    
    if ($is_past) {
        $interval = $now->diff($date);
        if ($interval->days > 0) {
            $time_until = $interval->days . ' дн. назад';
        } elseif ($interval->h > 0) {
            $time_until = $interval->h . ' ч. назад';
        } else {
            $time_until = 'Недавно';
        }
    } else {
        $interval = $date->diff($now);
        if ($interval->days > 0) {
            $time_until = 'через ' . $interval->days . ' дн.';
        } elseif ($interval->h > 0) {
            $time_until = 'через ' . $interval->h . ' ч.';
        } else {
            $time_until = 'Скоро';
        }
    }
    
    return [
        'day_of_week' => $day_of_week,
        'day_month' => $day_month,
        'full_date' => $full_date,
        'is_past' => $is_past,
        'is_today' => $is_today,
        'is_ongoing' => $is_ongoing,
        'time_until' => $time_until,
        'date_object' => $date,
        'now_object' => $now
    ];
}

$active_lessons = $upcoming_lessons;
?>
<div class="tutor-section">
    <h2>Расписание занятий</h2>
    <div style="margin-bottom: 30px;">
        <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 15px; height: 15px; background: var(--primary-red); border-radius: 3px;"></div>
                <span style="font-size: 14px; color: var(--dark-blue);">Текущее/прошедшее занятие</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 15px; height: 15px; background: var(--dark-blue); border-radius: 3px;"></div>
                <span style="font-size: 14px; color: var(--dark-blue);">Будущие занятия</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 15px; height: 15px; background: #2ed573; border-radius: 3px;"></div>
                <span style="font-size: 14px; color: var(--dark-blue);">Сегодня</span>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <span class="badge" style="background: var(--dark-blue); color: white;">
                Запланировано: <?php echo count($active_lessons); ?>
            </span>
            <span class="badge" style="background: var(--medium-gray); color: white;">
                Завершено: <?php echo count($completed_lessons); ?>
            </span>
        </div>
    </div>
    
    <!-- выводим активные занятия -->
    <?php if (!empty($active_lessons)): ?>
        <h3 style="color: var(--dark-blue); margin-bottom: 20px;">Активные занятия</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <?php foreach ($active_lessons as $lesson): 
                $date_info = formatScheduleDate($lesson['request_date']);
                $is_ongoing = $date_info['is_ongoing'];
                $is_past = $date_info['is_past'];
                $is_today = $date_info['is_today'];
                
                $card_border = '';
                $card_bg = 'white';
                
                if ($is_ongoing) {
                    $card_border = 'var(--primary-red)';
                    $card_bg = 'rgba(217, 4, 41, 0.05)';
                } elseif ($is_past) {
                    $card_border = 'var(--primary-red)';
                    $card_bg = 'rgba(217, 4, 41, 0.02)';
                } elseif ($is_today) {
                    $card_border = '#2ed573';
                    $card_bg = 'rgba(46, 213, 115, 0.05)';
                } else {
                    $card_border = 'var(--dark-blue)';
                }
            ?>
                <div class="lesson-card" style="
                    background: <?php echo $card_bg; ?>;
                    border: 2px solid <?php echo $card_border; ?>;
                    border-radius: 10px;
                    padding: 20px;
                    transition: all 0.3s;
                    position: relative;
                    <?php echo $is_ongoing ? 'box-shadow: 0 0 15px rgba(217, 4, 41, 0.2);' : ''; ?>
                ">
                    <?php if ($is_ongoing): ?>
                        <div style="position: absolute; top: -10px; right: 20px; background: var(--red-dark); color: white; padding: 5px 15px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                            ИДЕТ СЕЙЧАС
                        </div>
                    <?php elseif ($is_past): ?>
                        <div style="position: absolute; top: -10px; right: 20px; background: var(--gray-medium); color: white; padding: 5px 15px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                            ПРОШЕЛ
                        </div>
                    <?php elseif ($is_today): ?>
                        <div style="position: absolute; top: -10px; right: 20px; background: #2ed573; color: white; padding: 5px 15px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                            СЕГОДНЯ
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--light-gray);">
                        <div>
                            <div style="font-size: 18px; font-weight: bold; color: var(--dark-blue); text-transform: capitalize;">
                                <?php echo $date_info['day_of_week']; ?>
                            </div>
                            <div style="font-size: 14px; color: var(--medium-gray); margin-top: 5px;">
                                <?php echo $date_info['full_date']; ?>
                            </div>
                            <div style="font-size: 12px; color: <?php echo $is_past ? 'var(--primary-red)' : ($is_today ? '#2ed573' : 'var(--dark-blue)'); ?>; margin-top: 5px;">
                                <?php echo $date_info['time_until']; ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 32px; font-weight: bold; color: var(--dark-blue);">
                                <?php echo $date_info['day_month']; ?>
                            </div>
                            <div style="font-size: 11px; color: var(--medium-gray);">
                                день\месяц
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--dark-blue); margin-bottom: 5px; font-size: 18px;">
                            <?php echo htmlspecialchars($lesson['student_name']); ?>
                        </h4>
                        
                        <?php if ($lesson['student_age']): ?>
                            <div style="font-size: 14px; color: var(--medium-gray); margin-bottom: 5px;">
                                Возраст: <?php echo $lesson['student_age']; ?> лет
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($lesson['student_city_name'])): ?>
                            <div style="font-size: 14px; color: var(--medium-gray); margin-bottom: 5px;">
                                Город: <?php echo htmlspecialchars($lesson['student_city_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--light-gray);">
                        <div style="font-size: 12px; color: var(--medium-gray); margin-bottom: 10px;">
                            Контактная информация:
                        </div>
                        <div style="display: grid; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <a href="mailto:<?php echo htmlspecialchars($lesson['student_email']); ?>" 
                                   style="color: var(--dark-blue); font-size: 14px; text-decoration: none;">
                                    <?php echo htmlspecialchars($lesson['student_email']); ?>
                                </a>
                            </div>
                            
                            <?php if ($lesson['student_contact_phone']): ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <a href="tel:<?php echo htmlspecialchars($lesson['student_contact_phone']); ?>" 
                                       style="color: var(--dark-blue); font-size: 14px; text-decoration: none;">
                                        <?php echo htmlspecialchars($lesson['student_contact_phone']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($lesson['social_media']): ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--dark-blue); font-size: 14px;">
                                        <?php echo htmlspecialchars($lesson['social_media']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($lesson['request_text']): ?>
                        <div style="margin-top: 15px; padding: 10px; background: var(--light-gray); border-radius: 5px;">
                            <div style="font-size: 12px; color: var(--medium-gray); margin-bottom: 5px;">
                                Сообщение от студента:
                            </div>
                            <div style="font-size: 14px; color: var(--dark-blue); font-style: italic;">
                                "<?php echo htmlspecialchars(mb_strimwidth($lesson['request_text'], 0, 100, '...')); ?>"
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <?php if ($is_past): ?>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="request_id" value="<?php echo $lesson['request_id']; ?>">
                                <button type="submit" name="complete_lesson" 
                                        class="btn btn-primary"
                                        style="width: 100%;">
                                    Завершить занятие
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="request_id" value="<?php echo $lesson['request_id']; ?>">
                                <button type="submit" name="complete_lesson" 
                                        class="btn <?php echo $is_ongoing ? 'btn-primary' : 'btn-secondary'; ?>"
                                        style="width: 100%;"
                                        <?php echo $is_ongoing ? '' : 'disabled'; ?>>
                                    <?php if ($is_ongoing): ?>
                                        Завершить занятие
                                    <?php else: ?>
                                        Занятие еще не началось
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="?page=requests&action=reject&id=<?php echo $lesson['request_id']; ?>" 
                           class="btn btn-outline" 
                           onclick="return confirm('Вы уверены, что хотите отменить это занятие?')">
                            Отменить
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; background: var(--light-gray); border-radius: 10px; margin-bottom: 30px;">
            <h3 style="color: var(--dark-blue); margin-bottom: 15px;">Нет активных занятий</h3>
            <p style="color: var(--medium-gray);">Принятые заявки от студентов будут отображаться здесь</p>
            <a href="?page=requests" class="btn btn-primary" style="margin-top: 20px;">
                Перейти к заявкам
            </a>
        </div>
    <?php endif; ?>
    
    <!-- выводим историю занятий -->
    <?php if (!empty($completed_lessons)): ?>
        <h3 style="color: var(--dark-blue); margin-bottom: 20px;">История занятий</h3>
        <p style="color: var(--medium-gray); margin-bottom: 20px; font-size: 14px;">
            Завершенные занятия (последние 10)
        </p>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Студент</th>
                        <th>Дата занятия</th>
                        <th>Завершено</th>
                        <th>Контактные данные</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completed_lessons as $lesson): 
                        $date_info = formatScheduleDate($lesson['request_date']);
                        $response_date = $lesson['response_date'] ? new DateTime($lesson['response_date']) : null;
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($lesson['student_name']); ?></div>
                                <?php if ($lesson['student_age']): ?>
                                    <div style="font-size: 12px; color: var(--medium-gray);">
                                        Возраст: <?php echo $lesson['student_age']; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo $date_info['full_date']; ?></div>
                                <div style="font-size: 12px; color: var(--medium-gray);">
                                    <?php echo $date_info['day_of_week']; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($response_date): ?>
                                    <div><?php echo $response_date->format('d.m.Y H:i'); ?></div>
                                    <div style="font-size: 12px; color: #2ed573; font-weight: 600;">
                                        Завершено
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 14px;"><?php echo htmlspecialchars($lesson['student_email']); ?></div>
                                <?php if ($lesson['student_contact_phone']): ?>
                                    <div style="font-size: 12px; color: var(--medium-gray);">
                                        <?php echo htmlspecialchars($lesson['student_contact_phone']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <!-- если ничего нет -->
    <?php if (empty($active_lessons) && empty($completed_lessons)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <h3 style="color: var(--dark-blue); margin-bottom: 15px;">Расписание пусто</h3>
            <p style="color: var(--medium-gray); margin-bottom: 25px; max-width: 400px; margin-left: auto; margin-right: auto;">
                Когда вы примете заявки от студентов, они появятся здесь в виде карточек с датами и временем занятий
            </p>
            <a href="?page=requests" class="btn btn-primary">
                Перейти к заявкам
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
  // фкнция для отображения если занятие идет прямо сейчас\прошло\будет
function updateOngoingLessons() {
    const now = new Date();
    const lessonCards = document.querySelectorAll('.lesson-card');
    
    lessonCards.forEach(card => {
        const completeBtn = card.querySelector('button[name="complete_lesson"]');
        if (completeBtn && completeBtn.disabled && completeBtn.textContent.includes('не началось')) {
            const lessonTimeText = card.querySelector('[style*="color: var(--dark-blue);"]')?.textContent;
            if (lessonTimeText) {
                setTimeout(() => {
                    window.location.reload();
                }, 30000);
            }
        }
    });
}

setInterval(updateOngoingLessons, 60000);

document.addEventListener('DOMContentLoaded', function() {
    const ongoingCards = document.querySelectorAll('.lesson-card');
    ongoingCards.forEach(card => {
        if (card.style.borderColor === 'var(--primary-red)') {
            card.style.animation = 'pulse 2s infinite';
        }
    });
});

const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(217, 4, 41, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(217, 4, 41, 0); }
        100% { box-shadow: 0 0 0 0 rgba(217, 4, 41, 0); }
    }
`;
document.head.appendChild(style);
</script>
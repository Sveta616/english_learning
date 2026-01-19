-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0
-- Время создания: Янв 19 2026 г., 22:01
-- Версия сервера: 8.0.43
-- Версия PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `english_learning`
--

-- --------------------------------------------------------

--
-- Структура таблицы `achievements`
--

CREATE TABLE `achievements` (
  `achievement_id` int NOT NULL,
  `user_id` int NOT NULL,
  `achievement_name` varchar(100) NOT NULL,
  `achievement_description` text,
  `badge_type` enum('level_completed','task_milestone','streak','first_login') NOT NULL,
  `points_awarded` int DEFAULT '0',
  `earned_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cities`
--

CREATE TABLE `cities` (
  `city_id` int NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'Россия',
  `is_active` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `cities`
--

INSERT INTO `cities` (`city_id`, `city_name`, `region`, `country`, `is_active`) VALUES
(1, 'Москва', 'Московская область', 'Россия', 1),
(2, 'Санкт-Петербург', 'Ленинградская область', 'Россия', 1),
(3, 'Екатеринбург', 'Свердловская область', 'Россия', 1),
(4, 'Новосибирск', 'Новосибирская область', 'Россия', 1),
(5, 'Казань', 'Татарстан', 'Россия', 1),
(6, 'Нижний Новгород', 'Нижегородская область', 'Россия', 1),
(7, 'Красноярск', 'Красноярский край', 'Россия', 1),
(8, 'Челябинск', 'Челябинская область', 'Россия', 1),
(9, 'Самара', 'Самарская область', 'Россия', 1),
(10, 'Уфа', 'Башкортостан', 'Россия', 1),
(11, 'Ростов-на-Дону', 'Ростовская область', 'Россия', 1),
(12, 'Краснодар', 'Краснодарский край', 'Россия', 1),
(13, 'Воронеж', 'Воронежская область', 'Россия', 1),
(14, 'Пермь', 'Пермский край', 'Россия', 1),
(15, 'Волгоград', 'Волгоградская область', 'Россия', 1),
(17, 'Уфалей', 'Волгоградская область', 'Россия', 1),
(18, 'Набережные Челны', 'Татарстан', 'Россия', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `levels`
--

CREATE TABLE `levels` (
  `level_id` int NOT NULL,
  `level_code` varchar(10) NOT NULL,
  `level_name` varchar(50) NOT NULL,
  `description` text,
  `min_score` int DEFAULT '0',
  `max_score` int DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `levels`
--

INSERT INTO `levels` (`level_id`, `level_code`, `level_name`, `description`, `min_score`, `max_score`) VALUES
(1, 'A1', 'Beginner', 'Elementary proficiency', 0, 100),
(2, 'A2', 'Elementary', 'Elementary proficiency', 0, 100),
(3, 'B1', 'Intermediate', 'Intermediate proficiency', 0, 100),
(4, 'B2', 'Upper Intermediate', 'Upper intermediate proficiency', 0, 100),
(5, 'C1', 'Advanced', 'Advanced proficiency', 0, 100),
(6, 'C2', 'Mastery', 'Mastery proficiency', 0, 100);

-- --------------------------------------------------------

--
-- Структура таблицы `modules`
--

CREATE TABLE `modules` (
  `module_id` int NOT NULL,
  `level_id` int NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `description` text,
  `module_type` enum('grammar','vocabulary','reading') NOT NULL,
  `order_number` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `modules`
--

INSERT INTO `modules` (`module_id`, `level_id`, `module_name`, `description`, `module_type`, `order_number`, `is_active`) VALUES
(1, 1, 'Основы грамматики A1', 'Базовые грамматические конструкции для начинающих', 'grammar', 1, 1),
(2, 1, 'Базовый словарный запас A1', '500 самых важных слов для повседневного общения', 'vocabulary', 2, 1),
(3, 1, 'Простое чтение A1', 'Короткие тексты и диалоги для начинающих', 'reading', 3, 1),
(4, 2, 'Грамматика A2', 'Past Simple и основы повествования', 'grammar', 1, 1),
(5, 2, 'Словарь A2', 'Расширенная лексика по ключевым темам', 'vocabulary', 2, 1),
(6, 2, 'Чтение A2', 'Рассказы и истории среднего уровня', 'reading', 3, 1),
(7, 3, 'Грамматика B1', 'Present Perfect и сложные конструкции', 'grammar', 1, 1),
(8, 3, 'Словарь B1', 'Деловой английский и специализированная лексика', 'vocabulary', 2, 1),
(9, 3, 'Чтение B1', 'Статьи и аналитические тексты', 'reading', 3, 1),
(10, 4, 'Грамматика B2', 'Условные предложения всех типов', 'grammar', 1, 1),
(11, 4, 'Словарь B2', 'Научная и академическая лексика', 'vocabulary', 2, 1),
(12, 4, 'Чтение B2', 'Художественная литература', 'reading', 3, 1),
(13, 5, 'Грамматика C1', 'Косвенная речь и стилистика', 'grammar', 1, 1),
(14, 5, 'Словарь C1', 'Идиомы и фразовые глаголы', 'vocabulary', 2, 1),
(15, 5, 'Чтение C1', 'Академические и научные тексты', 'reading', 3, 1),
(16, 6, 'Грамматика C2', 'Продвинутые синтаксические конструкции', 'grammar', 1, 1),
(17, 6, 'Словарь C2', 'Редкие и сложные слова английского', 'vocabulary', 2, 1),
(18, 6, 'Чтение C2', 'Философские и сложные тексты', 'reading', 3, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int NOT NULL,
  `module_id` int NOT NULL,
  `task_text` text NOT NULL,
  `task_type` enum('multiple_choice','fill_blank','essay') NOT NULL,
  `difficulty_level` varchar(10) NOT NULL,
  `correct_answer` text NOT NULL,
  `points` int DEFAULT '10',
  `explanation` text,
  `is_active` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`task_id`, `module_id`, `task_text`, `task_type`, `difficulty_level`, `correct_answer`, `points`, `explanation`, `is_active`, `updated_at`) VALUES
(1, 1, 'Выберите правильный вариант: \"Hello, ___ name is John.\"', 'multiple_choice', 'A1', 'my', 5, 'В английском языке используется притяжательное местоимение \"my\"', 1, '2025-12-09 12:07:53'),
(2, 1, 'Заполните пропуск: \"I ___ from London.\"', 'multiple_choice', 'A1', 'am', 5, 'С местоимением \"I\" используется глагол \"am\"', 1, '2025-12-09 12:07:53'),
(3, 1, 'Выберите правильный вопрос: \"___ are you?\"', 'multiple_choice', 'A1', 'How', 5, '\"How are you?\" - стандартный вопрос о самочувствии', 1, '2025-12-09 12:07:53'),
(4, 1, 'Заполните: \"What ___ your name?\"', 'multiple_choice', 'A1', 'is', 5, 'С существительным в единственном числе используется \"is\"', 1, '2025-12-09 12:07:53'),
(5, 2, 'Переведите слово \"house\"', 'multiple_choice', 'A1', 'дом', 5, 'House - отдельно стоящий дом', 1, '2025-12-09 12:07:53'),
(6, 2, 'Что означает \"book\"?', 'multiple_choice', 'A1', 'книга', 5, 'Book - книга для чтения', 1, '2025-12-09 12:07:53'),
(7, 2, 'Переведите \"семья\" на английский', 'multiple_choice', 'A1', 'family', 5, 'Family - семья, родственники', 1, '2025-12-09 12:07:53'),
(8, 2, 'Что означает \"water\"?', 'multiple_choice', 'A1', 'вода', 5, 'Water - вода, жидкость', 1, '2025-12-09 12:07:53'),
(9, 2, 'Переведите \"friend\"', 'multiple_choice', 'A1', 'друг', 5, 'Friend - друг или подруга', 1, '2025-12-09 12:07:53'),
(10, 2, 'Что означает \"school\"?', 'multiple_choice', 'A1', 'школа', 5, 'School - учебное заведение', 1, '2025-12-09 12:07:53'),
(11, 2, 'Переведите \"работа\" на английский', 'multiple_choice', 'A1', 'work', 5, 'Work - работа, трудовая деятельность', 1, '2025-12-09 12:07:53'),
(12, 2, 'Что означает \"city\"?', 'multiple_choice', 'A1', 'город', 5, 'City - крупный город', 1, '2025-12-09 12:07:53'),
(13, 3, 'Прочитайте: \"Tom has a cat. The cat is black.\" Какого цвета кошка?', 'multiple_choice', 'A1', 'black', 5, 'Из текста следует: \"The cat is black\"', 1, '2025-12-09 12:07:53'),
(14, 3, 'Заполните: \"I ___ breakfast at 8 AM.\"', 'fill_blank', 'A1', 'have', 5, 'Have breakfast - принимать завтрак', 1, '2025-12-09 12:07:53'),
(15, 3, 'Прочитайте: \"Anna is a teacher.\" Кто Анна?', 'multiple_choice', 'A1', 'a teacher', 5, 'Прямо указано в тексте', 1, '2025-12-09 12:07:53'),
(16, 3, 'Заполните: \"We ___ English in class.\"', 'fill_blank', 'A1', 'study', 5, 'Study English - изучать английский', 1, '2025-12-09 12:07:53'),
(17, 3, 'Прочитайте: \"The weather is sunny.\" Какая погода?', 'multiple_choice', 'A1', 'sunny', 5, 'Прямо указано в тексте', 1, '2025-12-09 12:07:53'),
(18, 3, 'Заполните: \"My brother ___ football.\"', 'fill_blank', 'A1', 'plays', 5, 'Plays football - играет в футбол', 1, '2025-12-09 12:07:53'),
(19, 3, 'Прочитайте: \"This is my house. It is big.\" Какой дом?', 'multiple_choice', 'A1', 'big', 5, 'Из описания: \"It is big\"', 1, '2025-12-09 12:07:53'),
(20, 3, 'Заполните: \"She ___ to music.\"', 'fill_blank', 'A1', 'listens', 5, 'Listens to music - слушает музыку', 1, '2025-12-09 12:07:53'),
(21, 4, 'Вчера я ___ в кино.', 'multiple_choice', 'A2', 'went', 5, 'Past simple от go - went', 1, '2025-12-09 12:07:53'),
(22, 4, 'Она ___ книгу на прошлой неделе.', 'multiple_choice', 'A2', 'read', 5, 'Read (прошлое) - read (произношение \"red\")', 1, '2025-12-09 12:07:53'),
(23, 4, 'Они ___ футбол вчера.', 'fill_blank', 'A2', 'played', 5, 'Regular verb + ed', 1, '2025-12-09 12:07:53'),
(24, 4, 'Я ___ телевизор вечером.', 'fill_blank', 'A2', 'watched', 5, 'Watch → watched', 1, '2025-12-09 12:07:53'),
(25, 4, 'Она не ___ вчера в школу.', 'fill_blank', 'A2', 'went', 5, 'Did not go → didn\'t go', 1, '2025-12-09 12:07:53'),
(26, 4, 'В прошлом году мы ___ в Париж.', 'multiple_choice', 'A2', 'traveled', 5, 'Travel → traveled (амер.) / travelled (брит.)', 1, '2025-12-09 12:07:53'),
(27, 5, 'Переведите \"environment\"', 'multiple_choice', 'A2', 'окружающая среда', 5, 'Environment - окружающая среда', 1, '2025-12-09 12:07:53'),
(28, 5, 'Что означает \"opportunity\"?', 'multiple_choice', 'A2', 'возможность', 5, 'Opportunity - шанс, возможность', 1, '2025-12-09 12:07:53'),
(29, 5, 'Переведите \"development\"', 'multiple_choice', 'A2', 'развитие', 5, 'Development - развитие, рост', 1, '2025-12-09 12:07:53'),
(30, 5, 'Что означает \"government\"?', 'multiple_choice', 'A2', 'правительство', 5, 'Government - государственная власть', 1, '2025-12-09 12:07:53'),
(31, 5, 'Переведите \"education\"', 'multiple_choice', 'A2', 'образование', 5, 'Education - обучение, образование', 1, '2025-12-09 12:07:53'),
(32, 5, 'Что означает \"information\"?', 'multiple_choice', 'A2', 'информация', 5, 'Information - сведения, данные', 1, '2025-12-09 12:07:53'),
(33, 5, 'Переведите \"technology\"', 'multiple_choice', 'A2', 'технология', 5, 'Technology - технические средства', 1, '2025-12-09 12:07:53'),
(34, 6, 'Прочитайте: \"Yesterday was Monday. Today is Tuesday.\" Какой сегодня день?', 'multiple_choice', 'A2', 'Tuesday', 5, 'Из текста: \"Today is Tuesday\"', 1, '2025-12-09 12:07:54'),
(35, 6, 'Заполните: \"Last summer we ___ to the sea.\"', 'fill_blank', 'A2', 'went', 5, 'Past simple: go → went', 1, '2025-12-09 12:07:54'),
(36, 6, 'Прочитайте: \"She usually reads books in the evening.\" Когда она читает?', 'multiple_choice', 'A2', 'in the evening', 5, 'Прямо указано в тексте', 1, '2025-12-09 12:07:54'),
(37, 6, 'Заполните: \"They ___ English for two years.\"', 'fill_blank', 'A2', 'have studied', 5, 'Present perfect для периода времени', 1, '2025-12-09 12:07:54'),
(38, 6, 'Прочитайте: \"If it rains, we will stay at home.\" Что произойдет если пойдет дождь?', 'multiple_choice', 'A2', 'stay at home', 5, 'Условное предложение первого типа', 1, '2025-12-09 12:07:54'),
(39, 6, 'Заполните: \"He ___ already ___ his homework.\"', 'fill_blank', 'A2', 'has finished', 5, 'Present perfect с already', 1, '2025-12-09 12:07:54'),
(40, 6, 'Прочитайте: \"The movie was interesting but too long.\" Каким был фильм?', 'multiple_choice', 'A2', 'interesting but long', 5, 'Из описания фильма', 1, '2025-12-09 12:07:54'),
(41, 7, 'Выберите правильный вариант: \"I ___ (live) here since 2010.\"', 'multiple_choice', 'B1', 'have lived', 10, 'Present Perfect используется для действия, начавшегося в прошлом и продолжающегося до сих пор', 1, '2026-01-18 12:00:00'),
(42, 7, 'Заполните пропуск: \"If I ___ (be) you, I would go there.\"', 'multiple_choice', 'B1', 'were', 10, 'В сослагательном наклонении (Second Conditional) используется form \"were\" для всех лиц', 1, '2026-01-18 12:00:00'),
(43, 7, 'Заполните: \"This house ___ (build) in 1995.\"', 'fill_blank', 'B1', 'was built', 10, 'Passive Voice в прошедшем времени (Past Simple Passive)', 1, '2026-01-18 12:00:00'),
(44, 7, 'Выберите: \"He told me that he ___ (finish) the work.\"', 'multiple_choice', 'B1', 'had finished', 10, 'Past Perfect используется в косвенной речи для предшествующего действия', 1, '2026-01-18 12:00:00'),
(45, 7, 'Заполните пропуск: \"I’m looking forward to ___ (meet) you.\"', 'multiple_choice', 'B1', 'meeting', 10, 'После фразы \"look forward to\" используется герундий (-ing)', 1, '2026-01-18 12:00:00'),
(46, 7, 'Заполните: \"She ___ (work) when the phone rang.\"', 'fill_blank', 'B1', 'was working', 10, 'Past Continuous для длительного процесса, прерванного другим действием', 1, '2026-01-18 12:00:00'),
(47, 8, 'Переведите слово \"entrepreneur\"', 'multiple_choice', 'B1', 'предприниматель', 10, 'Entrepreneur - человек, организующий бизнес', 1, '2026-01-18 12:00:00'),
(48, 8, 'Что означает фразовый глагол \"put off\"?', 'multiple_choice', 'B1', 'откладывать', 10, 'To put off - перенести встречу или событие на более поздний срок', 1, '2026-01-18 12:00:00'),
(49, 8, 'Переведите \"переговоры\" на английский', 'multiple_choice', 'B1', 'negotiations', 10, 'Negotiations - официальное обсуждение условий', 1, '2026-01-18 12:00:00'),
(50, 8, 'Что означает слово \"advantage\"?', 'multiple_choice', 'B1', 'преимущество', 10, 'Advantage - положительная сторона чего-либо', 1, '2026-01-18 12:00:00'),
(51, 8, 'Переведите \"ответственность\"', 'multiple_choice', 'B1', 'responsibility', 10, 'Responsibility - обязанность отвечать за что-либо', 1, '2026-01-18 12:00:00'),
(52, 8, 'Выберите синоним к слову \"decrease\"', 'multiple_choice', 'B1', 'reduce', 10, 'Reduce и decrease означают уменьшение', 1, '2026-01-18 12:00:00'),
(53, 9, 'Прочитайте: \"Despite the delay, we arrived on time.\" Мы опоздали?', 'multiple_choice', 'B1', 'no', 10, 'Despite the delay означает \"несмотря на задержку\"', 1, '2026-01-18 12:00:00'),
(54, 9, 'Заполните: \"The meeting was cancelled ___ (из-за) the rain.\"', 'multiple_choice', 'B1', 'due to', 10, 'Due to используется для указания причины', 1, '2026-01-18 12:00:00'),
(55, 9, 'Прочитайте: \"I managed to solve the problem.\" Я решил проблему?', 'multiple_choice', 'B1', 'yes', 10, 'Manage to - успешно справиться с чем-то сложным', 1, '2026-01-18 12:00:00'),
(56, 9, 'Заполните: \"She is ___ (такая) smart girl!\"', 'fill_blank', 'B1', 'such a', 10, 'Such a используется перед прилагательным с существительным в ед. числе', 1, '2026-01-18 12:00:00'),
(57, 9, 'Прочитайте: \"The shop is open daily except Sundays.\" Можно ли прийти в воскресенье?', 'multiple_choice', 'B1', 'no', 10, 'Except Sundays означает \"кроме воскресений\"', 1, '2026-01-18 12:00:00'),
(58, 9, 'Заполните: \"___ I was tired, I finished the report.\"', 'fill_blank', 'B1', 'Although', 10, 'Although (хотя) используется для противопоставления', 1, '2026-01-18 12:00:00'),
(59, 9, 'Прочитайте: \"You should avoid eating fast food.\" Рекомендуется ли есть фастфуд?', 'multiple_choice', 'B1', 'no', 10, 'Avoid - избегать', 1, '2026-01-18 12:00:00'),
(60, 9, 'Заполните: \"I ___ (привык) to getting up early.\"', 'fill_blank', 'B1', 'am used', 10, 'To be used to - иметь привычку к чему-либо', 1, '2026-01-18 12:00:00'),
(61, 10, 'Выберите вариант: \"I wish I ___ (know) about the meeting yesterday.\"', 'multiple_choice', 'B2', 'had known', 15, 'Regret about the past: wish + Past Perfect', 1, '2026-01-18 12:00:00'),
(62, 10, 'Заполните: \"By next year, I ___ (complete) my project.\"', 'multiple_choice', 'B2', 'will have completed', 15, 'Future Perfect для действия, которое завершится к сроку в будущем', 1, '2026-01-18 12:00:00'),
(63, 10, 'Заполните: \"It\'s about time you ___ (find) a job.\"', 'fill_blank', 'B2', 'found', 15, 'После \"It\'s about time\" используется Past Simple', 1, '2026-01-18 12:00:00'),
(64, 10, 'Выберите: \"He is said ___ (be) the best doctor in town.\"', 'multiple_choice', 'B2', 'to be', 15, 'Complex Nominative (Passive Reporting Structure)', 1, '2026-01-18 12:00:00'),
(65, 10, 'Заполните: \"I\'d rather you ___ (not/tell) anyone my secret.\"', 'fill_blank', 'B2', 'didn\'t tell', 15, 'I\'d rather someone did something - сослагательное наклонение', 1, '2026-01-18 12:00:00'),
(66, 10, 'Выберите: \"___ (have/finish) the report, he went home.\"', 'multiple_choice', 'B2', 'Having finished', 15, 'Perfect Participle для подчеркивания завершенности действия', 1, '2026-01-18 12:00:00'),
(67, 11, 'Переведите слово \"ambiguous\"', 'multiple_choice', 'B2', 'двусмысленный', 15, 'Ambiguous - неясный, имеющий два значения', 1, '2026-01-18 12:00:00'),
(68, 11, 'Что означает идиома \"to face the music\"?', 'multiple_choice', 'B2', 'принять последствия', 15, 'To face the music - нести ответственность за свои ошибки', 1, '2026-01-18 12:00:00'),
(69, 11, 'Переведите \"окружающая среда\" (более формально)', 'multiple_choice', 'B2', 'environment', 15, 'Environment - среда обитания, природа', 1, '2026-01-18 12:00:00'),
(70, 11, 'Что означает слово \"inevitable\"?', 'multiple_choice', 'B2', 'неизбежный', 15, 'Inevitable - то, чего нельзя избежать', 1, '2026-01-18 12:00:00'),
(71, 11, 'Переведите \"подчеркивать/акцентировать\"', 'multiple_choice', 'B2', 'emphasize', 15, 'To emphasize - выделять что-то важное', 1, '2026-01-18 12:00:00'),
(72, 11, 'Что означает фразовый глагол \"look down on\"?', 'multiple_choice', 'B2', 'смотреть свысока', 15, 'Считать кого-то ниже себя или хуже', 1, '2026-01-18 12:00:00'),
(73, 12, 'Прочитайте: \"The movie was highly acclaimed by critics.\" Понравился ли фильм критикам?', 'multiple_choice', 'B2', 'yes', 15, 'Acclaimed - получивший признание, одобрение', 1, '2026-01-18 12:00:00'),
(74, 12, 'Заполните: \"Hardly ___ (I/start) dinner when the guest arrived.\"', 'fill_blank', 'B2', 'had I started', 15, 'Инверсия после наречия Hardly для эмфазы', 1, '2026-01-18 12:00:00'),
(75, 12, 'Прочитайте: \"She was so engrossed in the book that she forgot to eat.\" Ей была интересна книга?', 'multiple_choice', 'B2', 'yes', 15, 'Engrossed in - поглощен чем-либо полностью', 1, '2026-01-18 12:00:00'),
(76, 12, 'Заполните: \"The car ___ (repair) for three hours before they finished.\"', 'fill_blank', 'B2', 'had been being repaired', 15, 'Past Perfect Continuous Passive (редкая форма, но верная для B2+)', 1, '2026-01-18 12:00:00'),
(77, 12, 'Прочитайте: \"Unless you study, you won\'t pass.\" Нужно ли учиться для сдачи?', 'multiple_choice', 'B2', 'yes', 15, 'Unless = if not (пока не / если не)', 1, '2026-01-18 12:00:00'),
(78, 12, 'Заполните: \"I suggest that he ___ (apply) for this job.\"', 'fill_blank', 'B2', 'apply', 15, 'Subjunctive mood после глагола suggest (без -s)', 1, '2026-01-18 12:00:00'),
(79, 12, 'Прочитайте: \"The results were consistent with our theory.\" Результаты совпали?', 'multiple_choice', 'B2', 'yes', 15, 'Consistent with - соответствующий чему-либо, согласующийся', 1, '2026-01-18 12:00:00'),
(80, 12, 'Заполните: \"No sooner ___ (we/leave) the house than it started to rain.\"', 'fill_blank', 'B2', 'had we left', 15, 'Инверсия с No sooner ... than', 1, '2026-01-18 12:00:00'),
(81, 13, 'Выберите правильную форму: \"Were it not for your help, I ___ (be) in trouble.\"', 'multiple_choice', 'C1', 'would be', 20, 'Инвертированный условный период (Conditionals inversion)', 1, '2026-01-18 12:00:00'),
(82, 13, 'Заполните пропуск: \"Never ___ (I/see) such a masterpiece.\"', 'fill_blank', 'C1', 'have I seen', 20, 'Отрицательная инверсия для усиления смысла предложения', 1, '2026-01-18 12:00:00'),
(83, 13, 'Выберите вариант: \"Such ___ (be) the force of the wind that trees fell.\"', 'multiple_choice', 'C1', 'was', 20, 'Эмфатическая структура с Such + inversion', 1, '2026-01-18 12:00:00'),
(84, 13, 'Заполните: \"Try ___ (he/might), he couldn\'t open the door.\"', 'fill_blank', 'C1', 'as he might', 20, 'Уступчивое предложение (Concession) с инверсией', 1, '2026-01-18 12:00:00'),
(85, 13, 'Заполните: \"Under no circumstances ___ (you/should) sign this.\"', 'fill_blank', 'C1', 'should you', 20, 'Инверсия после негативного выражения \"Under no circumstances\"', 1, '2026-01-18 12:00:00'),
(86, 13, 'Выберите: \"He acted as if he ___ (be) the boss.\"', 'multiple_choice', 'C1', 'were', 20, 'Unreal present после \"as if\"', 1, '2026-01-18 12:00:00'),
(87, 14, 'Что означает слово \"ubiquitous\"?', 'multiple_choice', 'C1', 'вездесущий', 20, 'Ubiquitous - находящийся везде одновременно', 1, '2026-01-18 12:00:00'),
(88, 14, 'Переведите слово \"resilient\"', 'multiple_choice', 'C1', 'стойкий', 20, 'Resilient - способный быстро восстанавливаться после трудностей', 1, '2026-01-18 12:00:00'),
(89, 14, 'Что означает идиома \"to take with a grain of salt\"?', 'multiple_choice', 'C1', 'относиться скептически', 20, 'Означает не верить информации на 100%', 1, '2026-01-18 12:00:00'),
(90, 14, 'Переведите \"осуществимый/выполнимый\"', 'multiple_choice', 'C1', 'feasible', 20, 'Feasible - то, что реально можно сделать', 1, '2026-01-18 12:00:00'),
(91, 14, 'Что означает слово \"mitigate\"?', 'multiple_choice', 'C1', 'смягчать', 20, 'To mitigate - уменьшать суровость или болезненность чего-либо', 1, '2026-01-18 12:00:00'),
(92, 14, 'Выберите синоним к слову \"perplexed\"', 'multiple_choice', 'C1', 'confused', 20, 'Обе формы означают замешательство или растерянность', 1, '2026-01-18 12:00:00'),
(93, 15, 'Прочитайте: \"The evidence was inconclusive.\" Помогли ли доказательства?', 'multiple_choice', 'C1', 'no', 20, 'Inconclusive - неубедительный, не дающий окончательного результата', 1, '2026-01-18 12:00:00'),
(94, 15, 'Заполните: \"But for his help, we ___ (not/succeed).\"', 'fill_blank', 'C1', 'would not have succeeded', 20, 'Структура \"But for\" (если бы не) для прошлых событий', 1, '2026-01-18 12:00:00'),
(95, 15, 'Прочитайте: \"The manager alluded to future changes.\" Менеджер прямо сказал о них?', 'multiple_choice', 'C1', 'no', 20, 'To allude to - намекать, косвенно упоминать', 1, '2026-01-18 12:00:00'),
(96, 15, 'Заполните: \"Only after ___ (hear) his voice did I relax.\"', 'multiple_choice', 'C1', 'hearing', 20, 'Использование герундия после предлога в структуре с инверсией', 1, '2026-01-18 12:00:00'),
(97, 15, 'Прочитайте: \"He is second to none in chemistry.\" Насколько он хорош?', 'multiple_choice', 'C1', 'the best', 20, 'Second to none - лучший, никому не уступающий', 1, '2026-01-18 12:00:00'),
(98, 15, 'Заполните: \"Little ___ (they/know) about the surprise.\"', 'fill_blank', 'C1', 'did they know', 20, 'Инверсия с наречием Little (почти не)', 1, '2026-01-18 12:00:00'),
(99, 15, 'Прочитайте: \"The law was repealed.\" Закон все еще действует?', 'multiple_choice', 'C1', 'no', 20, 'To repeal - аннулировать или отменить закон', 1, '2026-01-18 12:00:00'),
(100, 15, 'Заполните: \"Should you ___ (require) assistance, call us.\"', 'fill_blank', 'C1', 'require', 20, 'Inverted First Conditional для формального стиля', 1, '2026-01-18 12:00:00'),
(101, 16, 'Выберите форму: \"If the project ___ (be) to fail, the company would fold.\"', 'multiple_choice', 'C2', 'were', 25, 'Unreal future hypothesis (Was/Were to structure)', 1, '2026-01-18 12:00:00'),
(102, 16, 'Заполните: \"___ (be) it not for your intervention, the deal would have collapsed.\"', 'fill_blank', 'C2', 'Had', 25, 'Формальная инверсия Third Conditional', 1, '2026-01-18 12:00:00'),
(103, 16, 'Заполните: \"I would rather you ___ (not/mention) this at the gala yesterday.\"', 'fill_blank', 'C2', 'hadn\'t mentioned', 25, 'Would rather + Past Perfect для сожалений о прошлом', 1, '2026-01-18 12:00:00'),
(104, 16, 'Выберите: \"It is imperative that the CEO ___ (attend) the meeting.\"', 'multiple_choice', 'C2', 'attend', 25, 'Mandative Subjunctive (без окончания -s)', 1, '2026-01-18 12:00:00'),
(105, 16, 'Заполните: \"___ I to tell you the truth, you wouldn\'t believe me.\"', 'fill_blank', 'C2', 'Were', 25, 'Inversion of Second Conditional', 1, '2026-01-18 12:00:00'),
(106, 16, 'Выберите: \"He spoke as though he ___ (witness) the event himself.\"', 'multiple_choice', 'C2', 'had witnessed', 25, 'Unreal past after \"as though\"', 1, '2026-01-18 12:00:00'),
(107, 17, 'Что означает слово \"panacea\"?', 'multiple_choice', 'C2', 'универсальное средство', 25, 'Panacea - решение всех проблем или лекарство от всех болезней', 1, '2026-01-18 12:00:00'),
(108, 17, 'Переведите слово \"ephemeral\"', 'multiple_choice', 'C2', 'эфемерный', 25, 'Ephemeral - мимолетный, существующий очень короткое время', 1, '2026-01-18 12:00:00'),
(109, 17, 'Что означает слово \"cacophony\"?', 'multiple_choice', 'C2', 'какофония', 25, 'Cacophony - резкое, неприятное сочетание звуков', 1, '2026-01-18 12:00:00'),
(110, 17, 'Переведите \"quintessential\"', 'multiple_choice', 'C2', 'наиболее типичный', 25, 'Quintessential - представляющий наиболее совершенный пример чего-либо', 1, '2026-01-18 12:00:00'),
(111, 17, 'Что означает глагол \"to abdicate\"?', 'multiple_choice', 'C2', 'отрекаться', 25, 'To abdicate - официально отказаться от трона или власти', 1, '2026-01-18 12:00:00'),
(112, 17, 'Выберите синоним к слову \"serendipity\"', 'multiple_choice', 'C2', 'happy accident', 25, 'Serendipity - счастливая случайность, интуитивная прозорливость', 1, '2026-01-18 12:00:00'),
(113, 18, 'Прочитайте: \"The argument is flawed by a logical fallacy.\" Верно ли утверждение?', 'multiple_choice', 'C2', 'no', 25, 'Fallacy - заблуждение, ошибка в логике', 1, '2026-01-18 12:00:00'),
(114, 18, 'Заполните: \"Not until much later ___ (I/realize) the gravity of the situation.\"', 'fill_blank', 'C2', 'did I realize', 25, 'Инверсия с временным ограничением \"Not until\"', 1, '2026-01-18 12:00:00'),
(115, 18, 'Прочитайте: \"His remarks were rather facetious.\" Сказал ли он это серьезно?', 'multiple_choice', 'C2', 'no', 25, 'Facetious - шутливый, часто неуместно, несерьезный', 1, '2026-01-18 12:00:00'),
(116, 18, 'Заполните: \"So ___ (be) the demand that they ran out of stock.\"', 'fill_blank', 'C2', 'great was', 25, 'Эмфатическая инверсия So + adjective + inversion', 1, '2026-01-18 12:00:00'),
(117, 18, 'Прочитайте: \"The dichotomy between theory and practice is clear.\" Есть ли разница?', 'multiple_choice', 'C2', 'yes', 25, 'Dichotomy - дихотомия, резкое деление на две части', 1, '2026-01-18 12:00:00'),
(118, 18, 'Заполните: \"On no account ___ (this/door/be) left unlocked.\"', 'fill_blank', 'C2', 'must this door be', 25, 'Инверсия с модальным глаголом для строгого запрета', 1, '2026-01-18 12:00:00'),
(119, 18, 'Прочитайте: \"The company\'s assets are tangible.\" Можно ли их оценить/потрогать?', 'multiple_choice', 'C2', 'yes', 25, 'Tangible - материальный, ощутимый', 1, '2026-01-18 12:00:00'),
(120, 18, 'Заполните: \"Rarely ___ (we/encounter) such profound wisdom.\"', 'fill_blank', 'C2', 'do we encounter', 25, 'Инверсия после наречия частотности Rarely', 1, '2026-01-18 12:00:00');

-- --------------------------------------------------------

--
-- Структура таблицы `task_options`
--

CREATE TABLE `task_options` (
  `option_id` int NOT NULL,
  `task_id` int NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) DEFAULT '0',
  `order_number` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `task_options`
--

INSERT INTO `task_options` (`option_id`, `task_id`, `option_text`, `is_correct`, `order_number`) VALUES
(1, 1, 'my', 1, 1),
(2, 1, 'your', 0, 2),
(3, 1, 'his', 0, 3),
(4, 1, 'her', 0, 4),
(5, 2, 'am', 1, 1),
(6, 2, 'is', 0, 2),
(7, 2, 'are', 0, 3),
(8, 2, 'be', 0, 4),
(9, 3, 'How', 1, 1),
(10, 3, 'What', 0, 2),
(11, 3, 'Where', 0, 3),
(12, 3, 'When', 0, 4),
(13, 4, 'is', 1, 1),
(14, 4, 'am', 0, 2),
(15, 4, 'are', 0, 3),
(16, 4, 'be', 0, 4),
(17, 5, 'дом', 1, 1),
(18, 5, 'квартира', 0, 2),
(19, 5, 'здание', 0, 3),
(20, 5, 'комната', 0, 4),
(21, 6, 'книга', 1, 1),
(22, 6, 'журнал', 0, 2),
(23, 6, 'газета', 0, 3),
(24, 6, 'тетрадь', 0, 4),
(25, 7, 'family', 1, 1),
(26, 7, 'parents', 0, 2),
(27, 7, 'relatives', 0, 3),
(28, 7, 'home', 0, 4),
(29, 8, 'вода', 1, 1),
(30, 8, 'сок', 0, 2),
(31, 8, 'молоко', 0, 3),
(32, 8, 'чай', 0, 4),
(33, 9, 'друг', 1, 1),
(34, 9, 'враг', 0, 2),
(35, 9, 'знакомый', 0, 3),
(36, 9, 'коллега', 0, 4),
(37, 10, 'школа', 1, 1),
(38, 10, 'университет', 0, 2),
(39, 10, 'колледж', 0, 3),
(40, 10, 'институт', 0, 4),
(41, 11, 'work', 1, 1),
(42, 11, 'job', 0, 2),
(43, 11, 'career', 0, 3),
(44, 11, 'profession', 0, 4),
(45, 12, 'город', 1, 1),
(46, 12, 'деревня', 0, 2),
(47, 12, 'поселок', 0, 3),
(48, 12, 'мегаполис', 0, 4),
(49, 13, 'black', 1, 1),
(50, 13, 'white', 0, 2),
(51, 13, 'brown', 0, 3),
(52, 13, 'gray', 0, 4),
(53, 15, 'a teacher', 1, 1),
(54, 15, 'a doctor', 0, 2),
(55, 15, 'a student', 0, 3),
(56, 15, 'a manager', 0, 4),
(57, 17, 'sunny', 1, 1),
(58, 17, 'rainy', 0, 2),
(59, 17, 'cloudy', 0, 3),
(60, 17, 'windy', 0, 4),
(61, 19, 'big', 1, 1),
(62, 19, 'small', 0, 2),
(63, 19, 'old', 0, 3),
(64, 19, 'new', 0, 4),
(65, 21, 'went', 1, 1),
(66, 21, 'go', 0, 2),
(67, 21, 'going', 0, 3),
(68, 21, 'gone', 0, 4),
(69, 22, 'read', 1, 1),
(70, 22, 'reads', 0, 2),
(71, 22, 'reading', 0, 3),
(72, 22, 'readed', 0, 4),
(73, 26, 'traveled', 1, 1),
(74, 26, 'travel', 0, 2),
(75, 26, 'travelling', 0, 3),
(76, 26, 'travels', 0, 4),
(77, 27, 'окружающая среда', 1, 1),
(78, 27, 'экономика', 0, 2),
(79, 27, 'политика', 0, 3),
(80, 27, 'культура', 0, 4),
(81, 28, 'возможность', 1, 1),
(82, 28, 'проблема', 0, 2),
(83, 28, 'вопрос', 0, 3),
(84, 28, 'ответ', 0, 4),
(85, 29, 'развитие', 1, 1),
(86, 29, 'упадок', 0, 2),
(87, 29, 'стагнация', 0, 3),
(88, 29, 'рост', 0, 4),
(89, 30, 'правительство', 1, 1),
(90, 30, 'компания', 0, 2),
(91, 30, 'организация', 0, 3),
(92, 30, 'ассоциация', 0, 4),
(93, 31, 'образование', 1, 1),
(94, 31, 'воспитание', 0, 2),
(95, 31, 'обучение', 0, 3),
(96, 31, 'развитие', 0, 4),
(97, 32, 'информация', 1, 1),
(98, 32, 'знание', 0, 2),
(99, 32, 'данные', 0, 3),
(100, 32, 'сведения', 0, 4),
(101, 33, 'технология', 1, 1),
(102, 33, 'наука', 0, 2),
(103, 33, 'инженерия', 0, 3),
(104, 33, 'прогресс', 0, 4),
(105, 34, 'Tuesday', 1, 1),
(106, 34, 'Monday', 0, 2),
(107, 34, 'Wednesday', 0, 3),
(108, 34, 'Thursday', 0, 4),
(109, 36, 'in the evening', 1, 1),
(110, 36, 'in the morning', 0, 2),
(111, 36, 'at noon', 0, 3),
(112, 36, 'at night', 0, 4),
(113, 38, 'stay at home', 1, 1),
(114, 38, 'go for a walk', 0, 2),
(115, 38, 'visit friends', 0, 3),
(116, 38, 'go shopping', 0, 4),
(117, 40, 'interesting but long', 1, 1),
(118, 40, 'boring and short', 0, 2),
(119, 40, 'funny and exciting', 0, 3),
(120, 40, 'sad but beautiful', 0, 4),
(121, 14, 'have', 1, 1),
(122, 14, 'has', 0, 2),
(123, 14, 'had', 0, 3),
(124, 14, 'having', 0, 4),
(125, 16, 'study', 1, 1),
(126, 16, 'studies', 0, 2),
(127, 16, 'studying', 0, 3),
(128, 16, 'studied', 0, 4),
(129, 18, 'plays', 1, 1),
(130, 18, 'play', 0, 2),
(131, 18, 'playing', 0, 3),
(132, 18, 'played', 0, 4),
(133, 20, 'listens', 1, 1),
(134, 20, 'listen', 0, 2),
(135, 20, 'listening', 0, 3),
(136, 20, 'listened', 0, 4),
(137, 23, 'played', 1, 1),
(138, 23, 'plays', 0, 2),
(139, 23, 'play', 0, 3),
(140, 23, 'playing', 0, 4),
(141, 24, 'watched', 1, 1),
(142, 24, 'watch', 0, 2),
(143, 24, 'watches', 0, 3),
(144, 24, 'watching', 0, 4),
(145, 25, 'went', 1, 1),
(146, 25, 'go', 0, 2),
(147, 25, 'gone', 0, 3),
(148, 25, 'goes', 0, 4),
(149, 41, 'have lived', 1, 1),
(150, 41, 'live', 0, 2),
(151, 41, 'am living', 0, 3),
(152, 41, 'lived', 0, 4),
(153, 42, 'were', 1, 1),
(154, 42, 'was', 0, 2),
(155, 42, 'am', 0, 3),
(156, 42, 'be', 0, 4),
(157, 43, 'was built', 1, 1),
(158, 43, 'built', 0, 2),
(159, 43, 'has built', 0, 3),
(160, 43, 'is built', 0, 4),
(161, 44, 'had finished', 1, 1),
(162, 44, 'finished', 0, 2),
(163, 44, 'has finished', 0, 3),
(164, 44, 'was finishing', 0, 4),
(165, 45, 'meeting', 1, 1),
(166, 45, 'meet', 0, 2),
(167, 45, 'to meet', 0, 3),
(168, 45, 'met', 0, 4),
(169, 46, 'was working', 1, 1),
(170, 46, 'worked', 0, 2),
(171, 46, 'works', 0, 3),
(172, 46, 'had worked', 0, 4),
(173, 47, 'предприниматель', 1, 1),
(174, 47, 'учитель', 0, 2),
(175, 47, 'врач', 0, 3),
(176, 47, 'инженер', 0, 4),
(177, 48, 'откладывать', 1, 1),
(178, 48, 'терпеть', 0, 2),
(179, 48, 'надевать', 0, 3),
(180, 48, 'выключать', 0, 4),
(181, 49, 'negotiations', 1, 1),
(182, 49, 'conversations', 0, 2),
(183, 49, 'agreements', 0, 3),
(184, 49, 'meetings', 0, 4),
(185, 50, 'преимущество', 1, 1),
(186, 50, 'недостаток', 0, 2),
(187, 50, 'совет', 0, 3),
(188, 50, 'результат', 0, 4),
(189, 51, 'responsibility', 1, 1),
(190, 51, 'ability', 0, 2),
(191, 51, 'possibility', 0, 3),
(192, 51, 'opportunity', 0, 4),
(193, 52, 'reduce', 1, 1),
(194, 52, 'increase', 0, 2),
(195, 52, 'improve', 0, 3),
(196, 52, 'expand', 0, 4),
(197, 53, 'no', 1, 1),
(198, 53, 'yes', 0, 2),
(199, 54, 'due to', 1, 1),
(200, 54, 'because', 0, 2),
(201, 54, 'despite', 0, 3),
(202, 54, 'instead of', 0, 4),
(203, 55, 'yes', 1, 1),
(204, 55, 'no', 0, 2),
(205, 56, 'such a', 1, 1),
(206, 56, 'so', 0, 2),
(207, 56, 'very', 0, 3),
(208, 56, 'quite', 0, 4),
(209, 57, 'no', 1, 1),
(210, 57, 'yes', 0, 2),
(211, 58, 'Although', 1, 1),
(212, 58, 'Despite', 0, 2),
(213, 58, 'However', 0, 3),
(214, 58, 'Since', 0, 4),
(215, 59, 'no', 1, 1),
(216, 59, 'yes', 0, 2),
(217, 60, 'am used', 1, 1),
(218, 60, 'used', 0, 2),
(219, 60, 'get used', 0, 3),
(220, 60, 'was using', 0, 4),
(221, 61, 'had known', 1, 1),
(222, 61, 'knew', 0, 2),
(223, 61, 'have known', 0, 3),
(224, 61, 'would know', 0, 4),
(225, 62, 'will have completed', 1, 1),
(226, 62, 'will complete', 0, 2),
(227, 62, 'complete', 0, 3),
(228, 62, 'will be completing', 0, 4),
(229, 63, 'found', 1, 1),
(230, 63, 'find', 0, 2),
(231, 63, 'have found', 0, 3),
(232, 63, 'would find', 0, 4),
(233, 64, 'to be', 1, 1),
(234, 64, 'being', 0, 2),
(235, 64, 'be', 0, 3),
(236, 64, 'that he is', 0, 4),
(237, 65, 'didn\'t tell', 1, 1),
(238, 65, 'don\'t tell', 0, 2),
(239, 65, 'not tell', 0, 3),
(240, 65, 'won\'t tell', 0, 4),
(241, 66, 'Having finished', 1, 1),
(242, 66, 'Finished', 0, 2),
(243, 66, 'Finishing', 0, 3),
(244, 66, 'To finish', 0, 4),
(245, 67, 'двусмысленный', 1, 1),
(246, 67, 'амбициозный', 0, 2),
(247, 67, 'древний', 0, 3),
(248, 67, 'красивый', 0, 4),
(249, 68, 'принять последствия', 1, 1),
(250, 68, 'слушать музыку', 0, 2),
(251, 68, 'купить билеты', 0, 3),
(252, 68, 'начать петь', 0, 4),
(253, 69, 'environment', 1, 1),
(254, 69, 'surroundings', 0, 2),
(255, 69, 'nature', 0, 3),
(256, 69, 'location', 0, 4),
(257, 70, 'неизбежный', 1, 1),
(258, 70, 'невероятный', 0, 2),
(259, 70, 'невидимый', 0, 3),
(260, 70, 'неэффективный', 0, 4),
(261, 71, 'emphasize', 1, 1),
(262, 71, 'imagine', 0, 2),
(263, 71, 'improve', 0, 3),
(264, 71, 'ignore', 0, 4),
(265, 72, 'смотреть свысока', 1, 1),
(266, 72, 'заботиться', 0, 2),
(267, 72, 'искать информацию', 0, 3),
(268, 72, 'ожидать', 0, 4),
(269, 73, 'yes', 1, 1),
(270, 73, 'no', 0, 2),
(271, 74, 'had I started', 1, 1),
(272, 74, 'I had started', 0, 2),
(273, 74, 'did I start', 0, 3),
(274, 74, 'I started', 0, 4),
(275, 75, 'yes', 1, 1),
(276, 75, 'no', 0, 2),
(277, 76, 'had been being repaired', 1, 1),
(278, 76, 'was being repaired', 0, 2),
(279, 76, 'had been repaired', 0, 3),
(280, 76, 'has been repaired', 0, 4),
(281, 77, 'yes', 1, 1),
(282, 77, 'no', 0, 2),
(283, 78, 'apply', 1, 1),
(284, 78, 'applies', 0, 2),
(285, 78, 'applied', 0, 3),
(286, 78, 'should apply', 0, 4),
(287, 79, 'yes', 1, 1),
(288, 79, 'no', 0, 2),
(289, 80, 'had we left', 1, 1),
(290, 80, 'we had left', 0, 2),
(291, 80, 'did we leave', 0, 3),
(292, 80, 'we left', 0, 4),
(293, 81, 'would be', 1, 1),
(294, 81, 'will be', 0, 2),
(295, 81, 'had been', 0, 3),
(296, 81, 'am', 0, 4),
(297, 82, 'have I seen', 1, 1),
(298, 82, 'I have seen', 0, 2),
(299, 82, 'did I see', 0, 3),
(300, 82, 'I saw', 0, 4),
(301, 83, 'was', 1, 1),
(302, 83, 'were', 0, 2),
(303, 83, 'been', 0, 3),
(304, 83, 'is', 0, 4),
(305, 84, 'as he might', 1, 1),
(306, 84, 'though he might', 0, 2),
(307, 84, 'since he might', 0, 3),
(308, 84, 'because he might', 0, 4),
(309, 85, 'should you', 1, 1),
(310, 85, 'you should', 0, 2),
(311, 85, 'shall you', 0, 3),
(312, 85, 'do you', 0, 4),
(313, 86, 'were', 1, 1),
(314, 86, 'was', 0, 2),
(315, 86, 'is', 0, 3),
(316, 86, 'be', 0, 4),
(317, 87, 'вездесущий', 1, 1),
(318, 87, 'редкий', 0, 2),
(319, 87, 'опасный', 0, 3),
(320, 87, 'громкий', 0, 4),
(321, 88, 'стойкий', 1, 1),
(322, 88, 'хрупкий', 0, 2),
(323, 88, 'ленивый', 0, 3),
(324, 88, 'быстрый', 0, 4),
(325, 89, 'относиться скептически', 1, 1),
(326, 89, 'солить еду', 0, 2),
(327, 89, 'верить на слово', 0, 3),
(328, 89, 'быть злым', 0, 4),
(329, 90, 'feasible', 1, 1),
(330, 90, 'difficult', 0, 2),
(331, 90, 'unlikely', 0, 3),
(332, 90, 'optional', 0, 4),
(333, 91, 'смягчать', 1, 1),
(334, 91, 'ухудшать', 0, 2),
(335, 91, 'игнорировать', 0, 3),
(336, 91, 'праздновать', 0, 4),
(337, 92, 'confused', 1, 1),
(338, 92, 'excited', 0, 2),
(339, 92, 'angry', 0, 3),
(340, 92, 'bored', 0, 4),
(341, 93, 'no', 1, 1),
(342, 93, 'yes', 0, 2),
(343, 94, 'would not have succeeded', 1, 1),
(344, 94, 'had not succeeded', 0, 2),
(345, 94, 'did not succeed', 0, 3),
(346, 94, 'will not succeed', 0, 4),
(347, 95, 'no', 1, 1),
(348, 95, 'yes', 0, 2),
(349, 96, 'hearing', 1, 1),
(350, 96, 'heard', 0, 2),
(351, 96, 'to hear', 0, 3),
(352, 96, 'having heard', 0, 4),
(353, 97, 'the best', 1, 1),
(354, 97, 'the second', 0, 2),
(355, 97, 'average', 0, 3),
(356, 97, 'worst', 0, 4),
(357, 98, 'did they know', 1, 1),
(358, 98, 'they knew', 0, 2),
(359, 98, 'had they known', 0, 3),
(360, 98, 'they did know', 0, 4),
(361, 99, 'no', 1, 1),
(362, 99, 'yes', 0, 2),
(363, 100, 'require', 1, 1),
(364, 100, 'requires', 0, 2),
(365, 100, 'required', 0, 3),
(366, 100, 'should require', 0, 4),
(367, 101, 'were', 1, 1),
(368, 101, 'was', 0, 2),
(369, 101, 'be', 0, 3),
(370, 101, 'had been', 0, 4),
(371, 102, 'Had', 1, 1),
(372, 102, 'Were', 0, 2),
(373, 102, 'Should', 0, 3),
(374, 102, 'Been', 0, 4),
(375, 103, 'hadn\'t mentioned', 1, 1),
(376, 103, 'didn\'t mention', 0, 2),
(377, 103, 'not mention', 0, 3),
(378, 103, 'wouldn\'t mention', 0, 4),
(379, 104, 'attend', 1, 1),
(380, 104, 'attends', 0, 2),
(381, 104, 'attended', 0, 3),
(382, 104, 'should attend', 0, 4),
(383, 105, 'Were', 1, 1),
(384, 105, 'Was', 0, 2),
(385, 105, 'Should', 0, 3),
(386, 105, 'Had', 0, 4),
(387, 106, 'had witnessed', 1, 1),
(388, 106, 'witnessed', 0, 2),
(389, 106, 'witnesses', 0, 3),
(390, 106, 'would witness', 0, 4),
(391, 107, 'универсальное средство', 1, 1),
(392, 107, 'болезнь', 0, 2),
(393, 107, 'страх', 0, 3),
(394, 107, 'музыка', 0, 4),
(395, 108, 'эфемерный', 1, 1),
(396, 108, 'вечный', 0, 2),
(397, 108, 'красивый', 0, 3),
(398, 108, 'тяжелый', 0, 4),
(399, 109, 'какофония', 1, 1),
(400, 109, 'симфония', 0, 2),
(401, 109, 'тишина', 0, 3),
(402, 109, 'песня', 0, 4),
(403, 110, 'наиболее типичный', 1, 1),
(404, 110, 'редкий', 0, 2),
(405, 110, 'странный', 0, 3),
(406, 110, 'старый', 0, 4),
(407, 111, 'отрекаться', 1, 1),
(408, 111, 'соглашаться', 0, 2),
(409, 111, 'бороться', 0, 3),
(410, 111, 'помогать', 0, 4),
(411, 112, 'happy accident', 1, 1),
(412, 112, 'sad event', 0, 2),
(413, 112, 'planned trip', 0, 3),
(414, 112, 'hard work', 0, 4),
(415, 113, 'no', 1, 1),
(416, 113, 'yes', 0, 2),
(417, 114, 'did I realize', 1, 1),
(418, 114, 'I realized', 0, 2),
(419, 114, 'had I realized', 0, 3),
(420, 114, 'do I realize', 0, 4),
(421, 115, 'no', 1, 1),
(422, 115, 'yes', 0, 2),
(423, 116, 'great was', 1, 1),
(424, 116, 'was great', 0, 2),
(425, 116, 'is great', 0, 3),
(426, 116, 'greatness was', 0, 4),
(427, 117, 'yes', 1, 1),
(428, 117, 'no', 0, 2),
(429, 118, 'must this door be', 1, 1),
(430, 118, 'this door must be', 0, 2),
(431, 118, 'should this door be', 0, 3),
(432, 118, 'must be this door', 0, 4),
(433, 119, 'yes', 1, 1),
(434, 119, 'no', 0, 2),
(435, 120, 'do we encounter', 1, 1),
(436, 120, 'we encounter', 0, 2),
(437, 120, 'did we encounter', 0, 3),
(438, 120, 'we do encounter', 0, 4);

-- --------------------------------------------------------

--
-- Структура таблицы `tutors`
--

CREATE TABLE `tutors` (
  `tutor_id` int NOT NULL,
  `user_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `bio` text,
  `experience_years` int DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT '5.00',
  `total_reviews` int DEFAULT '0',
  `is_verified` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `specialization_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tutors`
--

INSERT INTO `tutors` (`tutor_id`, `user_id`, `full_name`, `email`, `phone`, `city_id`, `bio`, `experience_years`, `hourly_rate`, `rating`, `total_reviews`, `is_verified`, `is_active`, `specialization_id`, `created_at`, `updated_at`) VALUES
(4, 6, 'Екатеринбургская Репа Олеговна', 'ekb@rep.ru', '+7-902-902-90-22', 14, '', 7, 1300.00, 4.00, 2, 1, 1, 1, '2025-12-07 13:23:09', '2026-01-18 16:08:47'),
(6, 8, 'Московская Репа Ивановна', 'msk@rep.ru', '+7-900-22-22-22', 1, 'Имею большой опыт и знания!', 5, 1500.00, 3.50, 3, 1, 1, 2, '2025-12-07 13:26:49', '2026-01-18 15:17:30'),
(8, 14, '123123123', 'pe@ku.ru', NULL, 13, NULL, NULL, NULL, 5.00, 0, 1, 1, NULL, '2025-12-10 09:39:36', '2026-01-18 11:24:11');

-- --------------------------------------------------------

--
-- Структура таблицы `tutor_requests`
--

CREATE TABLE `tutor_requests` (
  `request_id` int NOT NULL,
  `student_id` int NOT NULL,
  `tutor_id` int NOT NULL,
  `request_text` text,
  `student_contact_name` varchar(100) DEFAULT NULL,
  `student_contact_email` varchar(100) DEFAULT NULL,
  `student_contact_phone` varchar(20) DEFAULT NULL,
  `student_age` int DEFAULT NULL,
  `social_media` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','completed') DEFAULT 'pending',
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `response_date` timestamp NULL DEFAULT NULL,
  `is_rated` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tutor_requests`
--

INSERT INTO `tutor_requests` (`request_id`, `student_id`, `tutor_id`, `request_text`, `student_contact_name`, `student_contact_email`, `student_contact_phone`, `student_age`, `social_media`, `status`, `request_date`, `response_date`, `is_rated`) VALUES
(1, 10, 6, 'Хотел бы записаться на 12.30 в среду 10 декабря', 'Выворотов Иван Алексеевич', 'stud@msk.ru', NULL, 20, '', 'completed', '2025-12-08 18:25:44', '2025-12-10 11:09:43', 0),
(2, 10, 8, 'Я так хочу заниматься с вами, можно пж!', 'Выворотов Иван Алексеевич', 'stud@msk.ru', '+7 990 909 09 90', 22, '@telegramm', 'completed', '2025-12-10 11:44:53', '2025-12-10 11:47:17', 0),
(3, 10, 8, 'Ку', 'Выворотов Иван Алексеевич', 'stud@msk.ru', NULL, 22, NULL, 'pending', '2025-12-10 11:50:32', NULL, 0),
(4, 10, 6, 'ку', 'Выворотов Иван Алексеевич', 'stud@msk.ru', NULL, 22, NULL, 'completed', '2025-12-10 11:51:24', '2025-12-22 19:58:00', 0),
(6, 10, 6, 'Очень хочу заниматься', 'Выворотов Иван Алексеевич', 'stud@msk.ru', '+7 123 123 12 31', 22, '@telega', 'completed', '2025-12-22 19:56:37', '2025-12-22 19:57:17', 0),
(7, 17, 6, 'Здравствуйте, хочу научится английскому как у Бенедикта Камбербетча!', 'Учениковый Тест Тестович', 'uch@uch.ru', '+7 532 423 42 34', 23, NULL, 'completed', '2026-01-09 23:13:57', '2026-01-09 23:24:15', 0),
(8, 17, 6, 'Хочу английским стать!', 'Учениковый Тест Тестович', 'uch@uch.ru', NULL, 23, NULL, 'completed', '2026-01-09 23:26:21', '2026-01-09 23:27:22', 0),
(9, 17, 6, 'r', 'Учениковый Тест Тестович', 'uch@uch.ru', NULL, 23, NULL, 'rejected', '2026-01-09 23:46:41', '2026-01-09 23:50:20', 0),
(12, 9, 4, 'примите', 'Иванов Олег Олегович', 'stud@ekb.ru', '+7 996 176 93 44', 19, NULL, 'rejected', '2026-01-18 15:19:19', NULL, 0),
(13, 9, 4, 'примите', 'Иванов Олег Олегович', 'stud@ekb.ru', '+7 996 176 93 44', 19, NULL, 'completed', '2026-01-18 15:19:27', '2026-01-18 15:20:48', 0),
(14, 10, 4, 'р', 'Выворотов Иван Алексеевич', 'stud@msk.ru', '+7 996 176 93 44', 19, NULL, 'completed', '2026-01-18 15:23:18', '2026-01-18 15:25:01', 0),
(15, 10, 6, 'ghgg', 'Выворотов Иван Алексеевич', 'stud@msk.ru', '+7 996 176 93 44', 19, NULL, 'completed', '2026-01-18 15:25:48', '2026-01-18 15:26:06', 0),
(16, 9, 6, 'gdg', 'Иванов Олег Олегович', 'stud@ekb.ru', '+7 996 176 93 44', 19, NULL, 'completed', '2026-01-18 15:27:48', '2026-01-18 15:28:21', 0),
(17, 9, 4, 'dfh', 'Иванов Олег Олегович', 'stud@ekb.ru', '+7 996 176 93 44', 19, NULL, 'completed', '2026-01-18 16:07:41', '2026-01-18 16:08:47', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `tutor_specializations`
--

CREATE TABLE `tutor_specializations` (
  `specialization_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tutor_specializations`
--

INSERT INTO `tutor_specializations` (`specialization_id`, `name`, `description`) VALUES
(1, 'General English', 'Общий английский язык'),
(2, 'Business English', 'Деловой английский'),
(3, 'Exam Preparation', 'Подготовка к экзаменам (IELTS, TOEFL)'),
(4, 'Conversational English', 'Разговорный английский'),
(5, 'English for Kids', 'Английский для детей'),
(6, 'Technical English', 'Технический английский');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `current_level_id` int DEFAULT '1',
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `user_type` enum('student','tutor','admin') DEFAULT 'student',
  `is_active` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `city_id`, `current_level_id`, `registration_date`, `last_login`, `user_type`, `is_active`, `updated_at`) VALUES
(6, 'Реп екб', 'ekb@rep.ru', '$2y$10$hi4pCvsE2roP/MlNBTipjOmVDToomNsqAsgv5wr0yHFonbC4pvFbq', 'Екатеринбургская Репа Олеговна', 14, 1, '2025-12-07 13:23:09', '2026-01-18 16:08:02', 'tutor', 1, '2026-01-18 16:08:02'),
(8, 'Реп мск', 'msk@rep.ru', '$2y$10$Qal/TQXYozI3akNKwmif9uliOWIGr3sjOSTsXdPFjFJEX/kUyBlZO', 'Московская Репа Ивановна', 1, NULL, '2025-12-07 13:26:49', '2026-01-18 15:27:59', 'tutor', 1, '2026-01-18 15:27:59'),
(9, 'СкуфГерой', 'stud@ekb.ru', '$2y$10$HvYlqQr.R4u.talrwh2g2ejFlOBH1bWcZ3MUIyZuM5dc0XWZkw.r2', 'Иванов Олег Олегович', NULL, 1, '2025-12-08 13:18:18', '2026-01-18 16:08:29', 'student', 1, '2026-01-18 16:08:29'),
(10, 'Крутой Игорь', 'stud@msk.ru', '$2y$10$DweE/T69dKLmcSymPIVDAOQ0jmr36BWqZt3IIAUKaQ03ZB6f9JDgK', 'Выворотов Иван Алексеевич', 13, 2, '2025-12-08 13:43:10', '2026-01-18 15:25:32', 'student', 1, '2026-01-18 15:25:32'),
(13, '123', 'ku@ma.ru', '$2y$10$0t17hNYP0PeJntj/KYc/5.5gos8rRlZ9TjIfE76L8zixks092AJ6i', '123', 1, 1, '2025-12-10 09:38:54', '2025-12-10 09:38:54', 'student', 1, '2025-12-10 09:38:54'),
(14, '123123', 'pe@ku.ru', '$2y$10$fKn1hSBjaujAOgfzllEgyeOJH1WxnFqRvXwnrKij8vh6z9bGd/RtO', '123123123', NULL, 1, '2025-12-10 09:39:36', '2025-12-10 11:45:44', 'tutor', 1, '2025-12-10 11:45:44'),
(15, 'admin', 'adm@adm.ru', '$2y$10$Z5jELBs8bBQZktv.kvQwN.rJL1LBA1mBc1SNbwNh6aIBiOmRnwkyy', 'admin', NULL, 1, '2025-12-10 12:37:48', '2026-01-19 16:45:15', 'admin', 1, '2026-01-19 16:45:15'),
(17, 'uchenik', 'uch@uch.ru', '$2y$10$iWpMPBhlsnHcsAWVLaIM.eeDHt9UeS3N50qJEthvFYj1s4ZkCuZ82', 'Учениковый Тест Тестович', 14, 2, '2026-01-09 23:09:05', '2026-01-09 23:51:03', 'student', 0, '2026-01-18 14:18:32');

-- --------------------------------------------------------

--
-- Структура таблицы `user_answers`
--

CREATE TABLE `user_answers` (
  `answer_id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `user_answer` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_earned` int DEFAULT '0',
  `attempt_number` int DEFAULT '1',
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_answers`
--

INSERT INTO `user_answers` (`answer_id`, `user_id`, `task_id`, `user_answer`, `is_correct`, `points_earned`, `attempt_number`, `answered_at`) VALUES
(9, 10, 1, '1', 1, 5, 1, '2025-12-09 13:16:47'),
(10, 10, 2, '5', 1, 5, 1, '2025-12-09 14:39:35'),
(11, 9, 1, '1', 1, 5, 1, '2025-12-09 15:52:36'),
(12, 9, 2, '5', 1, 5, 1, '2025-12-09 15:52:38'),
(13, 9, 3, '10', 0, 0, 1, '2025-12-09 15:52:39'),
(14, 9, 4, '13', 1, 5, 1, '2025-12-09 15:52:40'),
(15, 10, 5, '18', 0, 0, 1, '2025-12-11 11:09:05'),
(16, 10, 6, '23', 0, 0, 1, '2025-12-11 11:09:09'),
(17, 10, 7, '27', 0, 0, 1, '2025-12-11 11:09:12'),
(18, 10, 8, '29', 1, 5, 1, '2025-12-11 11:09:13'),
(19, 10, 9, '33', 1, 5, 1, '2025-12-11 11:09:14'),
(20, 10, 10, '37', 1, 5, 1, '2025-12-11 11:09:16'),
(21, 10, 11, '41', 1, 5, 1, '2025-12-11 11:09:17'),
(22, 10, 12, '45', 1, 5, 1, '2025-12-11 11:09:18'),
(27, 10, 3, '9', 1, 5, 1, '2025-12-20 17:03:24'),
(28, 10, 4, '13', 1, 5, 1, '2025-12-20 17:03:38'),
(29, 10, 13, '52', 0, 0, 1, '2025-12-20 17:03:54'),
(30, 10, 14, 'am', 0, 0, 1, '2025-12-20 17:04:03'),
(31, 10, 15, '54', 0, 0, 1, '2025-12-20 17:04:09'),
(32, 10, 16, 'have', 0, 0, 1, '2025-12-20 17:04:25'),
(33, 10, 17, '57', 1, 5, 1, '2025-12-20 17:04:35'),
(34, 10, 18, 'like', 0, 0, 1, '2025-12-20 17:04:41'),
(35, 10, 19, '61', 1, 5, 1, '2025-12-20 17:04:50'),
(36, 10, 20, 'likes', 0, 0, 1, '2025-12-20 17:05:13'),
(37, 17, 1, '3', 0, 0, 1, '2026-01-09 23:09:22'),
(38, 17, 2, '5', 1, 5, 1, '2026-01-09 23:09:27'),
(39, 17, 3, '9', 1, 5, 1, '2026-01-09 23:09:30'),
(40, 17, 4, '13', 1, 5, 1, '2026-01-09 23:09:32'),
(41, 17, 5, '17', 1, 5, 1, '2026-01-09 23:10:46'),
(42, 17, 6, '21', 1, 5, 1, '2026-01-09 23:10:48'),
(43, 17, 7, '27', 0, 0, 1, '2026-01-09 23:10:49'),
(44, 17, 8, '30', 0, 0, 1, '2026-01-09 23:10:50'),
(45, 17, 9, '33', 1, 5, 1, '2026-01-09 23:10:52'),
(46, 17, 10, '38', 0, 0, 1, '2026-01-09 23:10:54'),
(47, 17, 11, '41', 1, 5, 1, '2026-01-09 23:10:56'),
(48, 17, 12, '45', 1, 5, 1, '2026-01-09 23:10:58'),
(49, 17, 13, '49', 1, 5, 1, '2026-01-09 23:11:27'),
(50, 17, 14, 'am', 0, 0, 1, '2026-01-09 23:11:33'),
(51, 17, 15, '53', 1, 5, 1, '2026-01-09 23:11:35'),
(52, 17, 16, 'were', 0, 0, 1, '2026-01-09 23:11:46'),
(53, 17, 17, '57', 1, 5, 1, '2026-01-09 23:11:50'),
(54, 17, 18, 'play', 0, 0, 1, '2026-01-09 23:11:54'),
(55, 17, 19, '61', 1, 5, 1, '2026-01-09 23:11:57'),
(56, 17, 20, 'play', 0, 0, 1, '2026-01-09 23:12:05'),
(57, 17, 21, '66', 0, 0, 1, '2026-01-09 23:52:16'),
(58, 17, 22, '69', 1, 5, 1, '2026-01-09 23:52:33'),
(59, 17, 23, 'plays', 0, 0, 1, '2026-01-09 23:52:44'),
(60, 17, 24, 'plays', 0, 0, 1, '2026-01-09 23:52:47'),
(61, 17, 25, 'plays', 0, 0, 1, '2026-01-09 23:52:47'),
(62, 17, 26, '73', 1, 5, 1, '2026-01-09 23:52:49');

-- --------------------------------------------------------

--
-- Структура таблицы `user_progress`
--

CREATE TABLE `user_progress` (
  `progress_id` int NOT NULL,
  `user_id` int NOT NULL,
  `level_id` int NOT NULL,
  `tasks_completed` int DEFAULT '0',
  `total_tasks` int DEFAULT NULL,
  `current_score` int DEFAULT '0',
  `max_score` int DEFAULT '0',
  `completion_percentage` int DEFAULT '0',
  `last_activity_date` timestamp NULL DEFAULT NULL,
  `status` enum('not_started','in_progress','completed','certified') DEFAULT 'not_started',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_progress`
--

INSERT INTO `user_progress` (`progress_id`, `user_id`, `level_id`, `tasks_completed`, `total_tasks`, `current_score`, `max_score`, `completion_percentage`, `last_activity_date`, `status`, `updated_at`) VALUES
(5, 10, 1, 20, NULL, 55, 0, 100, '2025-12-20 17:05:13', 'completed', '2025-12-20 17:05:23'),
(6, 9, 1, 4, NULL, 15, 0, 20, '2025-12-09 15:52:40', 'in_progress', '2025-12-09 15:52:40'),
(8, 13, 1, 0, NULL, 0, 0, 0, NULL, 'not_started', '2025-12-10 09:38:54'),
(9, 15, 1, 0, NULL, 0, 0, 0, NULL, 'not_started', '2025-12-10 12:37:48'),
(11, 10, 2, 0, NULL, 0, 0, 0, '2025-12-20 17:05:23', 'not_started', '2025-12-20 17:05:23'),
(12, 17, 1, 20, NULL, 60, 0, 100, '2026-01-09 23:12:05', 'completed', '2026-01-09 23:12:28'),
(13, 17, 2, 6, NULL, 10, 0, 30, '2026-01-09 23:52:49', 'in_progress', '2026-01-09 23:52:49');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`achievement_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`city_id`),
  ADD UNIQUE KEY `city_name` (`city_name`);

--
-- Индексы таблицы `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`level_id`),
  ADD UNIQUE KEY `level_code` (`level_code`);

--
-- Индексы таблицы `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`),
  ADD KEY `level_id` (`level_id`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `idx_tasks_module` (`module_id`,`is_active`);

--
-- Индексы таблицы `task_options`
--
ALTER TABLE `task_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Индексы таблицы `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`tutor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_tutors_city` (`city_id`),
  ADD KEY `fk_tutors_specialization` (`specialization_id`);

--
-- Индексы таблицы `tutor_requests`
--
ALTER TABLE `tutor_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Индексы таблицы `tutor_specializations`
--
ALTER TABLE `tutor_specializations`
  ADD PRIMARY KEY (`specialization_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `current_level_id` (`current_level_id`);

--
-- Индексы таблицы `user_answers`
--
ALTER TABLE `user_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `idx_user_answers_user_task` (`user_id`,`task_id`);

--
-- Индексы таблицы `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD UNIQUE KEY `unique_user_level` (`user_id`,`level_id`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `idx_user_progress_user_level` (`user_id`,`level_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `achievement_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cities`
--
ALTER TABLE `cities`
  MODIFY `city_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `levels`
--
ALTER TABLE `levels`
  MODIFY `level_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT для таблицы `task_options`
--
ALTER TABLE `task_options`
  MODIFY `option_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=439;

--
-- AUTO_INCREMENT для таблицы `tutors`
--
ALTER TABLE `tutors`
  MODIFY `tutor_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `tutor_requests`
--
ALTER TABLE `tutor_requests`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `tutor_specializations`
--
ALTER TABLE `tutor_specializations`
  MODIFY `specialization_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `user_answers`
--
ALTER TABLE `user_answers`
  MODIFY `answer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT для таблицы `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `progress_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `task_options`
--
ALTER TABLE `task_options`
  ADD CONSTRAINT `task_options_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tutors`
--
ALTER TABLE `tutors`
  ADD CONSTRAINT `fk_tutors_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tutors_specialization` FOREIGN KEY (`specialization_id`) REFERENCES `tutor_specializations` (`specialization_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tutors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tutor_requests`
--
ALTER TABLE `tutor_requests`
  ADD CONSTRAINT `tutor_requests_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`tutor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tutor_requests_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`current_level_id`) REFERENCES `levels` (`level_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_answers`
--
ALTER TABLE `user_answers`
  ADD CONSTRAINT `user_answers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_answers_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

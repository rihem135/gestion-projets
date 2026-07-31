<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Étape 1 : récupérer les tâches assignées à cet utilisateur
$query = "
    SELECT t.title, t.deadline, p.name AS project_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    WHERE t.assigned_to = :user_id AND t.deadline IS NOT NULL
";
$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Couleurs aléatoires
$distinct_colors = [
    '#4392FD', '#A060B0', '#FFE066', '#FF5340', '#40CC80',
    '#FFB347', '#5C9AFF', '#C080C0', '#FF9999', '#80E0A0'
];

$calendar_events = [];

foreach ($tasks as $task) {
    $deadline = date('Y-m-d', strtotime($task['deadline']));
    $color = $distinct_colors[array_rand($distinct_colors)];

    $calendar_events[] = [
        'title' => $task['title'] . ' (Projet: ' . $task['project_name'] . ')',
        'start' => $deadline,
        'color' => $color
    ];
}

$events_json = json_encode($calendar_events);
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Calendrier des tâches</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/locales/fr.global.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            margin: 0;
        }

        .header-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .calendar-title {
            font-size: 2.5rem;
            color: #26474E;
            font-weight: bold;
            margin-top: 20px;
        }

        .close-icon {
            position: fixed;
            top: 20px;
            right: 20px;
            font-size: 24px;
            text-decoration: none;
            color: #26474E;
            background-color: #A7E0E0;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease;
            z-index: 999;
        }

        .close-icon:hover {
            background-color: #4AA3A2;
            color: white;
        }

        #calendar {
            max-width: 1400px;
            margin: 40px auto;
            height: 700px;
        }
    </style>
</head>
<body>

    <a href="dashboard_me.php" class="close-icon" title="Retour au Dashboard">&#10006;</a>

    <div class="header-container">
        <h2 class="calendar-title">Tasks Calendar</h2>
    </div>

    <div id='calendar'></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
        today: 'Today',
        month: 'Month',
        week: 'Week',
        day: 'Day'
    },
            events: <?php echo $events_json; ?>,
            eventDisplay: 'block'
        });

        calendar.render();
    });
    </script>
</body>
</html>


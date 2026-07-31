<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->query("SELECT id, name, created_at, deadline FROM projects");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$distinct_colors = [
    '#4392FD', // Bleu moyen (entre #0000FF et #87CEFA)
    '#A060B0', // Violet doux (entre #800080 et #D8BFD8)
    '#FFE066', // Jaune moyen (entre #FFD700 et #FFFACD)
    '#FF5340', // Rouge moyen (entre #FF0000 et #FFA07A)
    '#40CC80', // Vert moyen (entre #008000 et #00FF7F)
    '#FFB347', // Orange doux (entre #FFA500 et #FFE4B5)
    '#5C9AFF', // Bleu ciel clair (entre #26478E et #87CEFA)
    '#C080C0', // Mauve clair (entre #800080 et #E8AABE)
    '#FF9999', // Rose pastel (entre #FF0000 et #FFA07A)
    '#80E0A0'  // Vert menthe doux (entre #008000 et #C0FFC0)
];



$calendar_events = [];

foreach ($projects as $project) {
    $start = date('Y-m-d', strtotime($project['created_at']));
    $end = $project['deadline'] ? date('Y-m-d', strtotime($project['deadline'] . ' +1 day')) : null;

    if ($start && $end) {
        $color = $distinct_colors[array_rand($distinct_colors)];
        $calendar_events[] = [
            'title' => $project['name'],
            'start' => $start,
            'end' => $end,
            'color' => $color
        ];
    }
}

$events_json = json_encode($calendar_events);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Calendrier des projets</title>
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

    <!-- Bouton "X" vers le dashboard -->
    <a href="dashboard_ad.php" class="close-icon" title="Go to Dashboard">&#10006;</a>

    <!-- Titre centré -->
    <div class="header-container">
        <h2 class="calendar-title">Project Calendar</h2>
    </div>

    <!-- Calendrier -->
    <div id='calendar'></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        console.log("Événements :", <?php echo $events_json; ?>);

        var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    // locale: 'fr', ← supprimé pour revenir à l’anglais par défaut
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
    eventDisplay: 'block',
    eventTimeFormat: {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }
});


        calendar.render();
    });
    </script>
</body>
</html>

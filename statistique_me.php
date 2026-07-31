<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$project_id = $_GET['project_id'] ?? null;

if ($role !== 'membre') {
    echo "This page is only for members.";
    exit;
}

if ($project_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM tasks WHERE project_id = ? AND (assigned_to = ? OR (assigned_to IS NULL AND is_public = 1)) LIMIT 1");
    $stmt->execute([$project_id, $user_id]);
    if (!$stmt->fetch()) {
        echo "You don't have access to this project.";
        exit;
    }
} else {
    $stmt = $pdo->prepare("SELECT DISTINCT project_id FROM tasks WHERE assigned_to = ? OR (assigned_to IS NULL AND is_public = 1) LIMIT 1");
    $stmt->execute([$user_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project) {
        echo "No project found for this member.";
        exit;
    }
    $project_id = $project['project_id'];
}

$return_link = "membre/dashboard_me.php?project_id=" . urlencode($project_id);

$stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
$project_name = $project ? $project['name'] : 'Unknown Project';

$stmt = $pdo->prepare("SELECT status, COUNT(*) AS count FROM tasks WHERE project_id = ? AND assigned_to = ? GROUP BY status");
$stmt->execute([$project_id, $user_id]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data = [];
$colorsMap = ["Not Started" => "#e0e0e0", "In Progress" => "#fff9c4", "Stuck" => "#ffcdd2", "Done" => "#c8e6c9"];
$colors = [];

foreach ($stats as $row) {
    $labels[] = $row['status'];
    $data[] = (int)$row['count'];
    $colors[] = $colorsMap[$row['status']] ?? '#cccccc';
}

$stmt = $pdo->prepare("SELECT SUM(CASE WHEN status = 'Done' THEN 1 ELSE 0 END) AS done_tasks, COUNT(*) AS total_tasks FROM tasks WHERE project_id = ? AND assigned_to = ?");
$stmt->execute([$project_id, $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$done = (int)$row['done_tasks'];
$total = (int)$row['total_tasks'];
$progress_percent = $total > 0 ? round(($done / $total) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Progress - <?= htmlspecialchars($project_name) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
            background-color: #f9f9f9;
        }
        .btn-back, .btn-download {
            display: inline-block;
            margin-bottom: 1.5rem;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn-back {
            background-color: rgb(161, 165, 187);
        }
        .btn-download {
            background-color: rgb(161, 165, 187);
            margin-left: 1rem;
        }
        .btn-back:hover {
            background-color: #45a049;
        }
        .btn-download:hover {
            background-color: #45a049;
        }
        h2 {
            margin-bottom: 1.5rem;
        }
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            flex: 1 1 350px;
            max-width: 500px;
        }
        canvas {
            width: 100% !important;
            height: auto !important;
        }
    </style>
</head>
<body>
<a href="<?= htmlspecialchars($return_link) ?>" class="btn-back">⬅️ Back to Dashboard</a>
<a href="#" class="btn-download" onclick="downloadPDF()">📥 Download PDF</a>
<h2>📊 My Task Statistics: <?= htmlspecialchars($project_name) ?></h2>

<?php if (empty($labels)) : ?>
    <p>You have no tasks in this project.</p>
<?php else: ?>
<div class="chart-container">
    <div class="card">
        <canvas id="taskPieChart"></canvas>
    </div>
    <div class="card">
        <canvas id="taskBarChart"></canvas>
    </div>
</div>
<script>
    const labels = <?= json_encode($labels) ?>;
    const data = <?= json_encode($data) ?>;
    const colors = <?= json_encode($colors) ?>;
    const progressPercent = <?= json_encode($progress_percent) ?>;

    new Chart(document.getElementById('taskPieChart').getContext('2d'), {
        type: 'pie',
        data: { labels: labels, datasets: [{ data: data, backgroundColor: colors }] },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    formatter: (value, ctx) => ((value / ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0)) * 100).toFixed(1) + '%',
                    color: '#000',
                    font: { weight: 'bold' }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    new Chart(document.getElementById('taskBarChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Progress'],
            datasets: [{ label: 'Your Task Completion', data: [progressPercent], backgroundColor: ['#81c784'] }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100, title: { display: true, text: 'Completion %' } } },
            plugins: {
                datalabels: {
                    anchor: 'end', align: 'end',
                    formatter: value => value + '%', color: '#000', font: { weight: 'bold' }
                },
                legend: { display: false }
            }
        },
        plugins: [ChartDataLabels]
    });

    async function downloadPDF() {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();
        const pageWidth = pdf.internal.pageSize.getWidth();
        const margin = 15;
        const lineHeight = 7;
        let y = margin;

        const titleColor = [0, 51, 102];
        const textColor = [0, 0, 0];
        const subtitleColor = [100, 100, 100];

        pdf.setFontSize(20);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(...titleColor);
        pdf.text("Project Progress Report", pageWidth / 2, y, { align: 'center' });
        y += lineHeight * 2;

        pdf.setFontSize(16);
        pdf.setTextColor(...textColor);
        pdf.text("Project: <?= addslashes($project_name) ?>", margin, y);
        y += lineHeight * 2;

        pdf.setFontSize(12);
        const date = new Date().toLocaleDateString();
        pdf.text(`Report generated on: ${date}`, margin, y);
        y += lineHeight * 3;

        pdf.setFontSize(14);
        pdf.setTextColor(...titleColor);
        pdf.text("Task Distribution by Status", margin, y);
        y += lineHeight;

        pdf.setFontSize(10);
        pdf.setTextColor(...subtitleColor);
        pdf.text("Overview of tasks categorized by current status", margin, y);
        y += lineHeight * 2;

        const pieImg = document.getElementById('taskPieChart').toDataURL('image/png');
        pdf.addImage(pieImg, 'PNG', (pageWidth - 140) / 2, y, 140, 84);
        y += 84 + lineHeight * 2;

        pdf.setFontSize(14);
        pdf.setTextColor(...titleColor);
        pdf.text("Your Completion Progress", margin, y);
        y += lineHeight;

        pdf.setFontSize(10);
        pdf.setTextColor(...subtitleColor);
        pdf.text("Percentage of your tasks completed in this project", margin, y);
        y += lineHeight * 2;

        const barImg = document.getElementById('taskBarChart').toDataURL('image/png');
        pdf.addImage(barImg, 'PNG', (pageWidth - 150) / 2, y, 150, 60);

        const fileName = `progress_report_<?= strtolower(preg_replace('/\s+/', '_', $project_name)) ?>_${new Date().toISOString().split('T')[0]}.pdf`;
        pdf.save(fileName);
    }
</script>
<?php endif; ?>
</body>
</html>

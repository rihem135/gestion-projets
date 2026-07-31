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

if ($role === 'admin') {
    if (!$project_id) {
        echo "Missing project ID.";
        exit;
    }
    $return_link = "admin/dashboard_ad.php?project_id=" . urlencode($project_id);
} elseif ($role === 'membre') {
    if ($project_id) {
        // Check if user has access to this project
        $stmt = $pdo->prepare("
            SELECT 1 FROM tasks 
            WHERE project_id = ? AND (assigned_to = ? OR (assigned_to IS NULL AND is_public = 1))
            LIMIT 1
        ");
        $stmt->execute([$project_id, $user_id]);
        if (!$stmt->fetch()) {
            echo "You don't have access to this project.";
            exit;
        }
    } else {
        // Get the first accessible project for the member
        $stmt = $pdo->prepare("
            SELECT DISTINCT project_id FROM tasks 
            WHERE assigned_to = ? OR (assigned_to IS NULL AND is_public = 1) 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$project) {
            echo "No project found for this member.";
            exit;
        }
        $project_id = $project['project_id'];
    }

    $return_link = "membre/dashboard_me.php?project_id=" . urlencode($project_id);
} else {
    echo "Invalid role.";
    exit;
}


// Fetch project name
$stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
$project_name = $project ? $project['name'] : 'Unknown Project';

// Retrieve task stats by status
function getTaskStatsByStatus($pdo, $project_id) {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS count FROM tasks WHERE project_id = ? GROUP BY status");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stats = getTaskStatsByStatus($pdo, $project_id);
$labels = [];
$data = [];

foreach ($stats as $row) {
    $labels[] = $row['status'];
    $data[] = $row['count'];
}

$colorsMap = [
    "Not Started" => "#e0e0e0",
    "In Progress" => "#fff9c4",
    "Stuck" => "#ffcdd2",
    "Done" => "#c8e6c9"
];

$colors = [];
foreach ($labels as $label) {
    $colors[] = $colorsMap[$label] ?? '#cccccc';
}

// Retrieve number of tasks per member
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(u.name, 'Public') AS member_name, 
        COUNT(*) AS task_count 
    FROM tasks t 
    LEFT JOIN users u ON t.assigned_to = u.id 
    WHERE t.project_id = ? 
    GROUP BY u.name
");
$stmt->execute([$project_id]);
$member_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$member_labels = [];
$member_data = [];

foreach ($member_stats as $row) {
    $member_labels[] = $row['member_name'];
    $member_data[] = $row['task_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Progress Statistics - <?= htmlspecialchars($project_name) ?></title>
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
            background-color: rgb(161, 165, 187);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-download {
            margin-left: 1rem;
        }
        .btn-back:hover, .btn-download:hover {
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
        @media (max-width: 600px) {
            .chart-container {
                flex-direction: column;
                align-items: center;
            }
            .card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<a href="<?= htmlspecialchars($return_link) ?>" class="btn-back">⬅️ Back to Dashboard</a>
<a href="#" class="btn-download" onclick="downloadPDF()">📥 Download PDF</a>

<h2>📊 Progress Statistics : <?= htmlspecialchars($project_name) ?></h2>

<?php if (empty($labels)) : ?>
    <p>No tasks available for this project.</p>
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

    const memberLabels = <?= json_encode($member_labels) ?>;
    const memberData = <?= json_encode($member_data) ?>;

    const memberColors = [];
    const colorPalette = [
  '#FF99AA', '#6FBFFF', '#FFE98C', '#85E5E5', 
  '#C4A3FF', '#FFC88C', '#AEDB73', '#F79A93',
  '#F9A8C0', '#FFD580', '#FFE87C', '#B7E28C',
  '#66CCFF', '#D28BE2', '#FFB399', '#66D9CC',
  '#A0AAB5', '#C0A7FF', '#88F788', '#DDA0FF'
];

    for (let i = 0; i < memberLabels.length; i++) {
        memberColors.push(colorPalette[i % colorPalette.length]);
    }

    const pieChart = new Chart(document.getElementById('taskPieChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: colors }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    formatter: (value, context) => {
                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        return ((value / total) * 100).toFixed(1) + '%';
                    },
                    color: '#000',
                    font: { weight: 'bold' }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    const barChart = new Chart(document.getElementById('taskBarChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: memberLabels,
            datasets: [{
                label: 'Tasks per Member',
                data: memberData,
                backgroundColor: memberColors
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => `${context.parsed.y} task(s)`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Number of Tasks' }
                }
            }
        }
    });

    async function downloadPDF() {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();

        const pageWidth = pdf.internal.pageSize.getWidth();
        const margin = 15;
        const lineHeight = 7;
        let yPosition = margin;

        const titleColor = [0, 51, 102];
        const textColor = [0, 0, 0];
        const subtitleColor = [100, 100, 100];

        pdf.setFontSize(20);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(...titleColor);
        pdf.text("Project Progress Report", pageWidth / 2, yPosition, { align: 'center' });
        yPosition += lineHeight * 2;

        pdf.setFontSize(16);
        pdf.setTextColor(...textColor);
        pdf.text("Project: <?= addslashes($project_name) ?>", margin, yPosition);
        yPosition += lineHeight * 2;

        pdf.setFontSize(12);
        const date = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        pdf.text(`Report generated on: ${date}`, margin, yPosition);
        yPosition += lineHeight * 3;

        pdf.setFontSize(14);
        pdf.setTextColor(...titleColor);
        pdf.text("Task Distribution by Status", margin, yPosition);
        yPosition += lineHeight;

        pdf.setFontSize(10);
        pdf.setTextColor(...subtitleColor);
        pdf.text("Overview of tasks categorized by their current status", margin, yPosition);
        yPosition += lineHeight * 2;

        const pieCanvas = document.getElementById('taskPieChart');
        const pieImage = pieCanvas.toDataURL('image/png', 1.0);
        const pieWidth = pageWidth * 0.7;
        const pieHeight = pieWidth * 0.6;
        pdf.addImage(pieImage, 'PNG', (pageWidth - pieWidth)/2, yPosition, pieWidth, pieHeight);
        yPosition += pieHeight + lineHeight * 2;

        pdf.setFontSize(14);
        pdf.setTextColor(...titleColor);
        pdf.text("Tasks Assigned per Member", margin, yPosition);
        yPosition += lineHeight;

        pdf.setFontSize(10);
        pdf.setTextColor(...subtitleColor);
        pdf.text("Breakdown of task assignments across team members", margin, yPosition);
        yPosition += lineHeight * 2;

        const barCanvas = document.getElementById('taskBarChart');
        const barImage = barCanvas.toDataURL('image/png', 1.0);
        const barWidth = pageWidth * 0.8;
        const barHeight = barWidth * 0.35;
        pdf.addImage(barImage, 'PNG', (pageWidth - barWidth)/2, yPosition, barWidth, barHeight);

        const fileName = `progress_report_<?= strtolower(preg_replace('/\s+/', '_', $project_name)) ?>_${new Date().toISOString().split('T')[0]}.pdf`;
        pdf.save(fileName);
    }
</script>
<?php endif; ?>
</body>
</html>


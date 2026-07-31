<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$message = '';
$current_project_id = isset($_GET['current_project_id']) ? intval($_GET['current_project_id']) : 0;

// Handle project creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_name'])) {
    $name = $_POST['project_name'];
    $description = $_POST['project_description'];
    $deadline = $_POST['project_deadline'];

    $stmt = $pdo->prepare("INSERT INTO projects (name, description, deadline, created_by) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$name, $description, $deadline, $admin_id]);

    if ($success) {
        // Récupérer l'ID du nouveau projet
        $new_project_id = $pdo->lastInsertId();

        // Redirection vers try.php avec le nouvel ID
        header("Location: ../dashboard_ad.php?project_id=$new_project_id");

        exit();
    } else {
        $message = 'Error adding the project.';
    }
}

// Fetch admin projects
$projects = $pdo->prepare("SELECT * FROM projects WHERE created_by = ?");
$projects->execute([$admin_id]);
$projects = $projects->fetchAll(PDO::FETCH_ASSOC);

// Fetch members
$members = $pdo->query("SELECT id, name FROM users WHERE role = 'membre'")->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Add Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #cbd7fd;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .add-box {
            position: relative;
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            text-decoration: none;
            font-size: 24px;
            color: #333;
        }
        .close-btn:hover {
            color: #ff0000;
        }
    </style>
</head>
<body>

<div class="add-box">
    <!-- X button -->
    <a href="../dashboard_ad.php" class="close-btn">&times;</a>

    <h2 class="mb-4 text-center">Add a Project</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Add Project Form -->
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Project Name</label>
            <input type="text" name="project_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="project_description" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Deadline</label>
            <input type="date" name="project_deadline" class="form-control" required>
        </div>
        <div class="text-end">
            <button type="submit" class="btn btn-success">Add</button>
        </div>
    </form>
</div>

</body>
</html>

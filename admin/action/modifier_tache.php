<?php
session_start();
require '../../db.php';

//corrcetion de deadline cote task tous ce code 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$task_id = $_GET['id'] ?? null;
$project_id = $_GET['project_id'] ?? null;

// Liste des membres
$members = $pdo->query("SELECT id, name FROM users WHERE role = 'membre'")->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
$project_deadline = null;
$error_message = '';

if ($task_id && $project_id) {
    // Récupérer les infos de la tâche
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo "Task not found.";
        exit();
    }

    // Récupérer la deadline du projet
    $stmt = $pdo->prepare("SELECT deadline FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    $project_deadline = $project ? $project['deadline'] : null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = $_POST['task_title'];
        $description = $_POST['task_description'];
        $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
        $is_public = ($assigned_to === null) ? 1 : 0;
        $deadline = $_POST['deadline'];

        // Validation de la deadline
        if ($deadline >= $today && $deadline <= $project_deadline) {
            $stmt = $pdo->prepare("UPDATE tasks 
                                   SET title = :title, 
                                       description = :description, 
                                       assigned_to = :assigned_to, 
                                       is_public = :is_public, 
                                       deadline = :deadline 
                                   WHERE id = :id");

            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':assigned_to', $assigned_to, $assigned_to === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':is_public', $is_public, PDO::PARAM_INT);
            $stmt->bindValue(':deadline', $deadline);
            $stmt->bindValue(':id', $task_id, PDO::PARAM_INT);

            $stmt->execute();

            header("Location: ../dashboard_ad.php?project_id=" . $project_id);
            exit();
        } else {
            $error_message = "❌ La date limite doit être comprise entre aujourd’hui ($today) et la date limite du projet ($project_deadline).";
        }
    }
} else {
    echo "Missing task ID or project ID.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Task</title>
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
        .edit-task-box {
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

<div class="edit-task-box">
    <!-- X button avec project_id pour revenir au bon projet -->
    <a href="../dashboard_ad.php?project_id=<?= htmlspecialchars($project_id) ?>" class="close-btn">&times;</a>

    <h2 class="mb-4 text-center">Edit Task</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger text-center"><?= $error_message ?></div>
    <?php endif; ?>

    <!-- Le formulaire POST -->
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="task_title" class="form-control" value="<?= htmlspecialchars($task['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="task_description" class="form-control" required><?= htmlspecialchars($task['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Assign to</label>
            <select name="assigned_to" class="form-select">
                <option value=""> -- Public --</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $task['assigned_to'] == $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Deadline</label>
            <input type="date" name="deadline" class="form-control"
                   value="<?= htmlspecialchars($task['deadline']) ?>"
                   min="<?= $today ?>" max="<?= $project_deadline ?>">
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>

</body>
</html>

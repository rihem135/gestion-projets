<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['project_name'];
        $description = $_POST['project_description'];
        $deadline = $_POST['project_deadline']; // 🔹 Nouvelle donnée

        $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, deadline = ? WHERE id = ? AND created_by = ?");
        $stmt->execute([$name, $description, $deadline, $project_id, $admin_id]);

        header("Location: ../dashboard_ad.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND created_by = ?");
    $stmt->execute([$project_id, $admin_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo "Project not found.";
        exit();
    }
} else {
    echo "Missing project ID.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #cbd7fd;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Pour occuper toute la hauteur de la fenêtre */
            margin: 0;
        }
        .edit-box {
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

<div class="edit-box">
    <!-- X button -->
    <a href="../dashboard_ad.php" class="close-btn">&times;</a>

    <h2 class="mb-4 text-center">Edit Project</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Project Name</label>
            <input type="text" name="project_name" class="form-control" value="<?= htmlspecialchars($project['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="project_description" class="form-control" required><?= htmlspecialchars($project['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Deadline</label>
            <input type="date" name="project_deadline" class="form-control" value="<?= htmlspecialchars($project['deadline']) ?>">
        </div>

        <!-- Update button only -->
        <div class="text-end mt-4">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>

</body>
</html>


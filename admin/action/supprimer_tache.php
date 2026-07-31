<?php
// supprimer_tache.php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;

if (isset($_GET['id'])) {
    $task_id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
}

if ($project_id) {
    header("Location: ../dashboard_ad.php?project_id=" . $project_id);
} else {
    header("Location: ../dashboard_ad.php");
}
exit();
?>
<?php
// supprimer_projet.php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];

    // Supprimer d'abord les tâches du projet
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE project_id = ?");
    $stmt->execute([$project_id]);

    // Puis supprimer le projet
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND created_by = ?");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
}

header("Location: ../dashboard_ad.php");
exit();
?>
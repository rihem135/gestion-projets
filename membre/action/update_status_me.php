<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'membre') {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? null;
    $status = $_POST['status'] ?? null;

    $valid_status = ['Not Started', 'In Progress', 'Stuck', 'Done'];

    if ($task_id && in_array($status, $valid_status)) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$status, $task_id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Requête invalide']);
    }
}

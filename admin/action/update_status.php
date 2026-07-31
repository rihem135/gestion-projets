<?php
require '../../db.php';

$task_id = $_POST['task_id'] ?? null;
$status = $_POST['status'] ?? null;

if ($task_id && $status) {
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->execute([$status, $task_id]);

    echo "success";
} else {
    echo "error";
}
?>

<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'membre') {
    header("Location: ../../login.php");
    exit();
}

$member_id = $_SESSION['user_id'];
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    echo "Missing task ID.";
    exit();
}

// Check if task is public and unassigned
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND is_public = 1 AND assigned_to IS NULL");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo "Task not found or already claimed.";
    exit();
}

// Assign the task to the user
$stmt = $pdo->prepare("UPDATE tasks SET assigned_to = ? WHERE id = ?");
$stmt->execute([$member_id, $task_id]);


//********************** nouveau code ********************************************* */
// Redirect to dashboard
$project_id = $_GET['project_id'] ?? null;
$redirect_url = '../dashboard_me.php';
if ($project_id) {
    $redirect_url .= '?project_id=' . $project_id;
}
header("Location: $redirect_url");
exit();
//*********************************************************************** */
?>


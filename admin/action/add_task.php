<?php
require '../../db.php';

//corrcetion de deadline cote task tous ce code 

// Récupération des données du formulaire
$title = $_POST['task_title'] ?? '';
$description = $_POST['task_description'] ?? '';
$status = $_POST['status'] ?? 'Not Started';
$deadline = $_POST['deadline'] ?? null;
$is_public = isset($_POST['is_public']) ? 1 : 0;
$project_id = $_POST['project_id'] ?? null;
$assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;

// Si aucun membre n’est assigné, on force is_public à 1
if ($assigned_to === null) {
    $is_public = 1;
}

// Vérification simple des champs requis
if ($title && $description && $deadline && $project_id) {
    // Étape 1 : Récupérer la deadline du projet
    $stmt = $pdo->prepare("SELECT deadline FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($project) {
        $project_deadline = $project['deadline'];
        $today = date('Y-m-d');

        // Étape 2 : Vérifier si la deadline de la tâche est entre aujourd'hui et la deadline du projet
        if ($deadline >= $today && $deadline <= $project_deadline) {
            // Deadline valide, on peut insérer la tâche
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, status, deadline, is_public, project_id) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");

            $stmt->bindValue(1, $title);
            $stmt->bindValue(2, $description);
            $stmt->bindValue(3, $assigned_to, $assigned_to === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(4, $status);
            $stmt->bindValue(5, $deadline);
            $stmt->bindValue(6, $is_public, PDO::PARAM_INT);
            $stmt->bindValue(7, $project_id, PDO::PARAM_INT);

            $stmt->execute();

            header("Location: ../dashboard_ad.php?project_id=" . $project_id);
            exit;
        } else {
            echo "❌ La date limite de la tâche doit être comprise entre aujourd’hui ($today) et le deadline du projet ($project_deadline).";
        }
    } else {
        echo "❌ Projet introuvable.";
    }
} else {
    echo "❌ Tous les champs requis n'ont pas été remplis.";
}
?>

<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer l'admin (supposons qu'il y a un seul admin)
$stmt = $pdo->prepare("SELECT id, email FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin not found.");
}

// Récupérer tous les messages entre l'utilisateur et l'admin
$messages = $pdo->prepare("
    SELECT m.*, u1.email AS sender_email, u2.email AS receiver_email
    FROM messages m
    JOIN users u1 ON m.sender_id = u1.id
    JOIN users u2 ON m.receiver_id = u2.id
    WHERE (m.sender_id = :user_id AND m.receiver_id = :admin_id)
       OR (m.sender_id = :admin_id AND m.receiver_id = :user_id)
    ORDER BY m.sent_at DESC
");
$messages->execute(['user_id' => $user_id, 'admin_id' => $admin['id']]);
$allMessages = $messages->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_POST['sender_id'];
    $receiver_id = $_POST['receiver_id'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $file = null;

    if (!empty($_FILES['file']['name'])) {
        $upload_dir = 'fichiers/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES['file']['name']);
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
        $file = $file_name;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, file) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $subject, $message, $file]);

    header("Location: messaging_user.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Messaging</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: stretch;
            overflow: hidden;
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1600px;
            margin: 20px;
            gap: 20px;
        }

        /* Left form area */
        .formulaire {
            position: relative;
            flex: 3;
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            overflow-y: auto;
        }

        .formulaire h2 {
            text-align: center;
            margin-top: 2px;
            margin-bottom: 50px;
        }

        .formulaire label {
            font-weight: 600;
            display: block;
            margin-top: 16px;
        }

        .formulaire input[type="text"],
        .formulaire select,
        .formulaire textarea,
        .formulaire input[type="file"] {
            width: 100%;
            padding: 8px 12px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .formulaire textarea {
            resize: vertical;
        }

        .formulaire button {
            margin-top: 50px;
            width: 100%;
            padding: 12px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .formulaire button:hover {
            background-color: #0b5ed7;
        }

        /* Right messages box */
        .boite {
            flex: 2;
            background-color: #f1f3f5;
            padding: 30px;
            border-radius: 12px;
            overflow-y: auto;
            max-height: 90vh;
        }

        .boite h2 {
            margin-top: 0;
            margin-bottom: 30px;
            text-align: center;
        }

        .message {
            background-color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 6px solid #6c757d;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            word-wrap: break-word;
        }

        .message.envoye {
            border-left-color: #28a745;
        }

        .message.recu {
            border-left-color: #dc3545;
        }

        .message strong {
            display: inline-block;
            min-width: 60px;
        }

        .message a {
            display: inline-block;
            margin-top: 6px;
            text-decoration: none;
            color: #0d6efd;
        }

        .message a:hover {
            text-decoration: underline;
        }

        .message em {
            display: block;
            margin-top: 8px;
            font-size: 0.85rem;
            color: #555;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-decoration: none;
            cursor: pointer;
            user-select: none;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Left form -->
    <div class="formulaire">
        <a href="dashboard_me.php" class="close-btn" title="Back to dashboard">&times;</a>
        <h2>Send a Message to Admin</h2>
        <form action="messaging_user.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="sender_id" value="<?= $user_id ?>">
            <input type="hidden" name="receiver_id" value="<?= $admin['id'] ?>">

            <label for="subject">Subject:</label>
            <input type="text" name="subject" id="subject" required>

            <label for="message">Message:</label>
            <textarea name="message" id="message" rows="5" required></textarea>

            <label for="file">Attachment (optional):</label>
            <input type="file" name="file" id="file">

            <button type="submit">Send</button>
        </form>
    </div>

    <!-- Right messages box -->
    <div class="boite">
        <h2>Inbox & Sent Messages</h2>
        <?php foreach ($allMessages as $msg): ?>
            <div class="message <?= $msg['sender_id'] == $user_id ? 'envoye' : 'recu' ?>">
                <strong>From:</strong> <?= htmlspecialchars($msg['sender_email']) ?><br>
                <strong>To:</strong> <?= htmlspecialchars($msg['receiver_email']) ?><br>
                <strong>Subject:</strong> <?= htmlspecialchars($msg['subject']) ?><br>
                <strong>Message:</strong> <?= nl2br(htmlspecialchars($msg['message'])) ?><br>
                <?php if ($msg['file']): ?>
                    <a href="fichiers/<?= htmlspecialchars($msg['file']) ?>" download>📎 Attachment</a><br>
                <?php endif; ?>
                <em>Sent on <?= $msg['sent_at'] ?></em>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>

<?php
session_start();
require 'db.php';

$error = '';
$success = '';

// Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $error = 'Tous les champs sont requis';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        // Vérifier si l'email existe
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Mettre à jour le mot de passe
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
            $update_stmt->bindParam(':password', $hashed_password);
            $update_stmt->bindParam(':email', $email);

            if ($update_stmt->execute()) {
                $success = 'Mot de passe mis à jour avec succès!';
            } else {
                $error = 'Une erreur est survenue lors de la mise à jour';
            }
        } else {
            $error = 'Aucun compte trouvé avec cet email';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Réinitialiser le mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(90deg, #e2e2e2, #c9d6ff);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            color: #333;
        }
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn-submit {
            background: #7494ec;
            color: white;
            border: none;
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-submit:hover {
            background: #5a76d9;
        }
        .text-center a {
            color: #7494ec;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Réinitialiser le mot de passe</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="input-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-submit mt-3">Reset</button>
        </form>

        <div class="text-center mt-3">
            <a href="login.php">Return to login</a>
        </div>
    </div>

</body>
</html>

<?php
session_start();
require 'db.php';

// Initialisation variables d'erreur/messages
$login_error = $register_error = '';
$register_success = '';

// Traitement du login
if (isset($_POST['login'])) {
    $name = trim($_POST['login_name']);
    $password = $_POST['login_password'];

    if ($name === '' || $password === '') {
        $login_error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name = :name");
        $stmt->execute(['name' => $name]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Stockage en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Charger last_project_id en session
            if (!empty($user['last_project_id'])) {
                $_SESSION['current_project_id'] = $user['last_project_id'];
            } else {
                unset($_SESSION['current_project_id']);
            }

            // Redirection selon rôle
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard_ad.php"); // ou admin/dashboard_ad.php
            } elseif ($user['role'] === 'membre') {
                header("Location: membre/dashboard_me.php");
            } else {
                // Si rôle inconnu, rediriger vers page d'erreur ou accueil
                header("Location: register.php");
            }
            exit();
        } else {
            $login_error = "Invalid username or password.";
        }
    }
}

// Traitement du register
if (isset($_POST['register'])) {
    $name = trim($_POST['register_name']);
    $email = trim($_POST['register_email']);
    $password_raw = $_POST['register_password'];

    if ($name === '' || $email === '' || $password_raw === '') {
        $register_error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Invalid email format.";
    } else {
        // Vérifier doublon nom ou email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = :name OR email = :email");
        $stmt->execute(['name' => $name, 'email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $register_error = "Username or email already exists.";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
            if ($stmt->execute(['name' => $name, 'email' => $email, 'password' => $password])) {
                $_SESSION['register_success'] = "Registration successful. Please login.";
                header("Location: login.php");
                exit();
            } else {
                $register_error = "Registration failed. Please try again.";
            }
        }
    }
}

// Afficher message succès inscrit (puis le supprimer de session)
if (isset($_SESSION['register_success'])) {
    $register_success = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Responsive Register and Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet' />
    <link href='https://cdn.boxicons.com/fonts/brands/boxicons-brands.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">
    <div class="form-box login">
        <form method="POST" novalidate>
            <h1>Login</h1>

            <?php if($register_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($register_success); ?></div>
            <?php endif; ?>

            <?php if($login_error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>

            <div class="input-box">
                <input type="text" name="login_name" placeholder="Username" required />
                <i class="bxr bxs-user"></i>
            </div>
            <div class="input-box">
                <input type="password" name="login_password" placeholder="Password" required />
                <i class="bxr bxs-lock"></i>
            </div>
            <div class="forgot-link">
                <a href="reset_password.php">Forget password?</a>
            </div>
            <button type="submit" name="login" class="btn">Login</button>
        </form>
    </div>

    <div class="form-box register">
        <form method="POST" novalidate>
            <h1>Registration</h1>

            <?php if($register_error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($register_error); ?></div>
            <?php endif; ?>

            <div class="input-box">
                <input type="text" name="register_name" placeholder="Username" required />
                <i class="bxr bxs-user"></i>
            </div>
            <div class="input-box">
                <input type="email" name="register_email" placeholder="Email" required />
                <i class="bxr bxs-envelope"></i>
            </div>
            <div class="input-box">
                <input type="password" name="register_password" placeholder="Password" required />
                <i class="bxr bxs-lock"></i>
            </div>
            <button type="submit" name="register" class="btn">Register</button>
        </form>
    </div>

    <div class="toggle-box">
        <div class="toggle-panel toggle-left">
            <h1>Hello, Welcome!</h1>
            <p>Don't have an account?</p>
            <button class="btn register-btn">Register</button>
        </div>
        <div class="toggle-panel toggle-right">
            <h1>Welcome Back!</h1>
            <p>Already have an account?</p>
            <button class="btn login-btn">Login</button>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>

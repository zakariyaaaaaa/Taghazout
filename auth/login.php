<?php
session_start();
require '../includes/config.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email)) $errors[] = "L'email est requis.";
    if (empty($password)) $errors[] = "Le mot de passe est requis.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];

            // redirect حسب role
            if($user['role'] === 'admin'){

                header("Location: ../admin/dashboard.php");
                exit;

            }else{

                header("Location: ../index.php");
                exit;

            }

        } else {

            $errors[] = "Identifiants invalides.";

        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Events</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="icon" type="image/png" href="../img/logo.png.jpg">

</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <span class="navbar-icon">✦</span>
            Login
        </a>
                <button class="theme-toggle" id="toggleBtn">🌙 Dark</button>

    </nav>

    <div class="login-wrap" >
        <div class="login-card">
            <p class="login-eyebrow">Bienvenue</p>
            <h1>Login</h1>
            <p class="login-sub">Connectez-vous pour réserver vos événements</p>
            

            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           placeholder="exemple@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password"
                           placeholder="••••••••">
                </div>
                <button type="submit">Se connecter</button>
            </form>

            <div class="divider"></div>

            <span class="signup-link">
                Pas encore inscrit ? <a href="signup.php">Créer un compte</a>
            </span>
        </div>
    </div>
<script src="script.js"></script>
</body>
</html>
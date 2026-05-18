<?php
session_start(); // ← دايما فوق!
require '../includes/config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name']);
    $email            = trim($_POST['email']);
    $password         = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($name))                                   $errors[] = "Le nom est requis.";
    if (empty($email))                                  $errors[] = "L'email est requis.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = "L'email n'est pas valide.";
    if (empty($password))                               $errors[] = "Le mot de passe est requis.";
    if (strlen($password) < 6)                          $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    if (!preg_match('/[A-Z]/', $password))              $errors[] = "Au moins une lettre majuscule.";
    if (!preg_match('/[a-z]/', $password))              $errors[] = "Au moins une lettre minuscule.";
    if (!preg_match('/[0-9]/', $password))              $errors[] = "Au moins un chiffre.";
    if (!preg_match('/[@$!%_?&]/', $password))          $errors[] = "Au moins un caractère spécial.";
    if ($password !== $confirm_password)                $errors[] = "Les mots de passe ne correspondent pas.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé.";
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hashed]);

        // ✅ session بعد INSERT مباشرة
        $_SESSION['user_id']   = $pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        $_SESSION['role']      = 'user';

        header('Location: ../index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Events</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/signup.css">
    <link rel="icon" type="image/png" href="../img/logo.png.jpg">

</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <span class="navbar-icon">✦</span>
            Events
        </a>
        <button class="theme-toggle" id="toggleBtn">🌙 Dark</button>
    </nav>

    <div class="signup-wrap">
        <div class="signup-card">
            <p class="signup-eyebrow">Rejoignez-nous</p>
            <h1>Inscription</h1>
            <p class="signup-sub">Créez votre compte pour réserver des événements</p>

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
                    <label for="name">Nom</label>
                    <input type="text" id="name" name="name"
                           placeholder="Votre nom"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
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
                <div>
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="••••••••">
                </div>
                <button type="submit">S'inscrire</button>
            </form>

            <div class="divider"></div>

            <span class="login-link">
                Déjà inscrit ? <a href="login.php">Se connecter</a>
            </span>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
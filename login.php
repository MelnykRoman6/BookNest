<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utente WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];

        header("Location: index.php");
        exit;
    } else {
        $error = "Incorrect email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - BookNest</title>
    <link rel="stylesheet" href="styles/stile_login.css">
</head>
<body>

<div class="auth-container">
    <form method="POST" class="auth-form">
        <h2>Login BookNest</h2>

        <?php if(isset($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <label>Email:</label>
        <input type="email" name="email" required placeholder="Enter your email">

        <label>Password:</label>
        <input type="password" name="password" required placeholder="Enter your password">

        <button type="submit" class="btn-auth">Log in</button>

        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </form>
</div>

</body>
</html>
<?php
session_start();
require_once 'db.php';
if (isset($pdo)) {
    //echo "Connessione OK";
} else {
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
        $error = "Email o password sbagliati!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - BookNest</title>
</head>
<body style="font-family: Arial; text-align: center; padding-top: 50px;">
<form method="POST" style="display: inline-block; text-align: left; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
    <h2>Login BookNest</h2>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <label>Email:</label><br>
    <input type="email" name="email" required style="margin-bottom: 10px; width: 250px;"><br>
    <label>Password:</label><br>
    <input type="password" name="password" required style="margin-bottom: 20px; width: 250px;"><br>
    <button type="submit" style="width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer;">Accedi</button>
    <p>Non hai un account? <a href="register.php">Registrati</a></p>
</form>
</body>
</html>
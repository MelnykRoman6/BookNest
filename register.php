<?php
session_start();
require_once 'db.php';
if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}

require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//verifica mail
if (isset($_POST['send_code'])) {
    $email = $_POST['email'];
    $code = rand(100000, 999999);

    $_SESSION['temp_email'] = $email;
    $_SESSION['temp_code'] = $code;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'melnykromandev@gmail.com';
        $mail->Password   = 'qwnrxlyrkqqgiwqh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('melnykromandev@gmail.com', 'BookNest');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your BookNest verification code';
        $mail->Body    = "Your verification code is: <b>$code</b>";

        $mail->send();
        $msg = "Code sent to $email";
    } catch (Exception $e) {
        $msg = "Sending error: {$mail->ErrorInfo}";
    }
}

if (isset($_POST['register'])) {
    $input_code = $_POST['ver_code'];
    $password = $_POST['password'];

    if ($input_code == $_SESSION['temp_code']) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $email = $_SESSION['temp_email'];

        try {
            $pdo->beginTransaction();

            $sqlUser = "INSERT INTO utente (email, password_hash, is_verified, data_reg) VALUES (?, ?, 1, NOW())";
            $stmtUser = $pdo->prepare($sqlUser);
            $stmtUser->execute([$email, $password_hash]);

            $new_user_id = $pdo->lastInsertId();

            $base_collections = [
                    'Reading',
                    'Finished',
                    'I want to read',
                    'Favorites'
            ];
            //creazione delle collezioni
            $sqlCol = "INSERT INTO collezione (id_utente, nome, data_crea) VALUES (?, ?, NOW())";
            $stmtCol = $pdo->prepare($sqlCol);

            foreach ($base_collections as $col_name) {
                $stmtCol->execute([$new_user_id, $col_name]);
            }

            $pdo->commit();

            unset($_SESSION['temp_code']);
            unset($_SESSION['temp_email']);
            header("Location: login.php?success=1");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error during registration: " . $e->getMessage();
        }
    } else {
        $error = "Incorrect code!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registration - BookNest</title>
    <link rel="stylesheet" href="styles/stile_login.css">
</head>
<body>

<div class="auth-container">
    <h2>Registration</h2>

    <form method="POST" class="auth-form">
        <label>Email:</label>
        <div class = "email">
            <input type="email" name="email"
                   value="<?php echo $_SESSION['temp_email'] ?? ''; ?>"
                   required placeholder="Email"
                   > <button type="submit" name="send_code" class="btn-auth" style="width: auto; padding: 0 15px;">Send</button>
        </div>
        <?php if(isset($msg)) echo "<div class = 'sent-conf'>$msg</div>"; ?>
    </form>

    <hr class = "hr">

    <form method="POST" class="auth-form">
        <label>Code received:</label>
        <input type="text" name="ver_code" placeholder="123456" required>

        <label>Select password:</label>
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit" name="register" class="btn-auth" style="background: #28a745;">Create account</button>

        <?php if(isset($error)): ?>
            <div class="error-msg" style="margin-top: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </form>
</div>

</body>
</html>
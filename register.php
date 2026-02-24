<?php
session_start();
require_once 'db.php';
if (isset($pdo)) {
    //echo "Connessione OK";
} else {
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
        $mail->Subject = 'Il tuo codice di verifica BookNest';
        $mail->Body    = "Il tuo codice di verifica è: <b>$code</b>";

        $mail->send();
        $msg = "Codice inviato a $email";
    } catch (Exception $e) {
        $msg = "Errore invio: {$mail->ErrorInfo}";
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
                'Sto leggendo',
                'Letto',
                'Voglio leggere',
                'Preferiti'
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
            $error = "Errore durante la registrazione: " . $e->getMessage();
        }
    } else {
        $error = "Codice errato!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione - BookNest</title>
    <link rel="stylesheet" href="styles/stile_login.css">
</head>
<body>

<div class="auth-container">
    <h2>Registrazione</h2>

    <form method="POST" class="auth-form">
        <label>Email:</label>
        <div style="display: flex; gap: 8px;">
            <input type="email" name="email"
                   value="<?php echo $_SESSION['temp_email'] ?? ''; ?>"
                   required placeholder="Email"
                   style="margin-bottom: 0;"> <button type="submit" name="send_code" class="btn-auth" style="width: auto; padding: 0 15px;">Invia</button>
        </div>
        <?php if(isset($msg)) echo "<div style='color: #17a2b8; font-size: 0.8em; margin-top: 5px;'>$msg</div>"; ?>
    </form>

    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #444;">

    <form method="POST" class="auth-form">
        <label>Codice ricevuto:</label>
        <input type="text" name="ver_code" placeholder="123456" required>

        <label>Scegli Password:</label>
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit" name="register" class="btn-auth" style="background: #28a745;">Crea Account</button>

        <?php if(isset($error)): ?>
            <div class="error-msg" style="margin-top: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="auth-footer">
            <p>Hai già un account? <a href="login.php">Accedi</a></p>
        </div>
    </form>
</div>

</body>
</html>
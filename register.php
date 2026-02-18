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
</head>
<body style="font-family: Arial; padding: 50px; text-align: center;">
<div style="display: inline-block; text-align: left; background: #f9f9f9; padding: 30px; border-radius: 10px; border: 1px solid #ddd;">
    <h2>Registrazione</h2>

    <form method="POST">
        <label>Email:</label><br>
        <div style="display: flex; gap: 5px;">
            <input type="email" name="email" value="<?php echo $_SESSION['temp_email'] ?? ''; ?>" required style="padding: 8px;">
            <button type="submit" name="send_code" style="background: #007bff; color: white; border: none; padding: 8px; cursor: pointer;">Invia Codice</button>
        </div>
        <?php if(isset($msg)) echo "<small style='color: blue;'>$msg</small>"; ?>
    </form>

    <hr style="margin: 20px 0;">

    <form method="POST">
        <label>Codice ricevuto:</label><br>
        <input type="text" name="ver_code" placeholder="123456" required style="width: 100%; padding: 8px; margin-bottom: 10px;"><br>

        <label>Scegli Password:</label><br>
        <input type="password" name="password" required style="width: 100%; padding: 8px; margin-bottom: 20px;"><br>

        <button type="submit" name="register" style="width: 100%; background: #28a745; color: white; border: none; padding: 10px; cursor: pointer;">Crea Account</button>
        <?php if(isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
    </form>
</div>
</body>
</html>
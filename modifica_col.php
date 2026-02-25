<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}

if (!isset($_SESSION['user_id'])) {
    die("Errore: Devi effettuare il login per creare nuove collezioni.");
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";
$id_col = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');

    if (empty($nome)) {
        $error = "Il nome della collezione non può essere vuoto.";
    } elseif (strlen($nome) > 255) {
        $error = "Il nome è troppo lungo (max 255 caratteri).";
    } else {
        //per controllare se esiste già una collezione con lo stesso nome per l'utente
        $stmtCheck = $pdo->prepare("SELECT id FROM collezione WHERE id_utente = ? AND nome = ?");
        $stmtCheck->execute([$user_id, $nome]);

        if ($stmtCheck->fetch()) {
            $error = "Hai già una collezione con questo nome.";
        } else {
            $stmt = $pdo->prepare("update collezione set nome = ? where id_utente = ? and id = ?");
            $stmt->execute([$nome, $user_id, $id_col]);

            $success = "Nome cambiata con successo!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo nome</title>
    <link rel="stylesheet" href="styles/stile_nuova_col.css">
</head>
<body>

<div class="box">
    <a href="profilo.php" style="text-decoration:none;">← Torna al Profilo</a>
    <h2>Cambia nome</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Nome Collezione:</label>
        <input type="text" name="nome" placeholder="Es. Fantascienza 2024" required>
        <button type="submit">Rinomina</button>
    </form>
</div>

</body>
</html>
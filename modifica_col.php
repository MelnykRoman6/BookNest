<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}

if (!isset($_SESSION['user_id'])) {
    die("Error: You must log in to create new collections.");
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";
$id_col = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');

    if (empty($nome)) {
        $error = "The collection name cannot be empty";
    } elseif (strlen($nome) > 255) {
        $error = "The name is too long (max 255 characters)";
    } else {
        //per controllare se esiste già una collezione con lo stesso nome per l'utente
        $stmtCheck = $pdo->prepare("SELECT id FROM collezione WHERE id_utente = ? AND nome = ?");
        $stmtCheck->execute([$user_id, $nome]);

        if ($stmtCheck->fetch()) {
            $error = "You already have a collection with this name";
        } else {
            $stmt = $pdo->prepare("update collezione set nome = ? where id_utente = ? and id = ?");
            $stmt->execute([$nome, $user_id, $id_col]);

            $success = "Name changed successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>New name</title>
    <link rel="stylesheet" href="styles/stile_nuova_col.css">
</head>
<body>

<div class="box">
    <a href="profilo.php">← Back to profile</a>
    <h2>Change name</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Collection name:</label>
        <input type="text" name="nome" placeholder="Ex. Science Fiction 2024" required>
        <button type="submit">Rename</button>
    </form>
</div>

</body>
</html>
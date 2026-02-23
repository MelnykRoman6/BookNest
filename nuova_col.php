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
            $stmt = $pdo->prepare("INSERT INTO collezione (id_utente, nome) VALUES (?, ?)");
            $stmt->execute([$user_id, $nome]);

            $success = "Collezione creata con successo!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuova Collezione</title>
    <style>
        body { font-family: Arial; background: #f4f4f9; padding: 40px; }
        .box { max-width: 500px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px; }
        button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; }
        button:hover { background: #218838; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="box">
    <a href="profilo.php" style="text-decoration:none;">← Torna al Profilo</a>
    <h2>Crea Nuova Collezione</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Nome Collezione:</label>
        <input type="text" name="nome" placeholder="Es. Fantascienza 2024" required>
        <button type="submit">Crea</button>
    </form>
</div>

</body>
</html>
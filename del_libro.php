<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $id_libro = $_POST['id_libro'];
    $id_collezione = $_POST['id_collezione'];
    $user_id = $_SESSION['user_id'];

    $stmtCheck = $pdo->prepare("SELECT id FROM collezione WHERE id = ? AND id_utente = ?");
    $stmtCheck->execute([$id_collezione, $user_id]);

    if ($stmtCheck->fetch()) {
        $stmtDelete = $pdo->prepare("DELETE FROM aggiungere WHERE id_libro = ? AND id_collezione = ?");
        $stmtDelete->execute([$id_libro, $id_collezione]);
    }
}

header("Location: profilo.php");
exit;
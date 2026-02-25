<?php

session_start();
require_once "db.php";

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

$id_libro = $_POST['id_libro'] ?? null;
$id_vecchia_col = $_POST['id_vecchia_col'] ?? null;
$id_nuova_col = $_POST['id_nuova_col'] ?? null;

if (!$id_libro || !$id_vecchia_col || !$id_nuova_col) {
    die("Dati mancanti");
}

try {
    $stmt = $pdo->prepare("
        UPDATE aggiungere
        SET id_collezione = ?
        WHERE id_libro = ?
        AND id_collezione = ?
    ");
    $stmt->execute([$id_nuova_col, $id_libro, $id_vecchia_col]);

    header("Location: profilo.php");
    exit;

} catch (PDOException $e) {
    die($e->getMessage());
}
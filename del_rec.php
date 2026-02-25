<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_libro = trim($_POST['id_rec_libro'] ?? '');
    $id = trim($_POST['id_rec'] ?? '');
    $ia = trim($_POST['id_ia'] ?? '');
    $id_oid = trim($_POST['id_oid'] ?? '');
    if (!empty($id) and !empty($id_libro)) {
        $stmtDelete = $pdo->prepare("DELETE FROM recensione WHERE id = ? AND id_utente = ? and id_libro = ?");
        $stmtDelete->execute([$id, $user_id, $id_libro]);
    }
}
header("Location: libro.php?id=" . $id_oid . "&ia=" . $ia);
exit;
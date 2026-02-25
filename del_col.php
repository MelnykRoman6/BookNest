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
    $id_col = trim($_POST['id_collezione'] ?? '');
    if (!empty($id_col)) {
        $stmtDelete = $pdo->prepare("DELETE FROM collezione WHERE id = ? AND id_utente = ?");
        $stmtDelete->execute([$id_col, $user_id]);
    }
}
header("Location: profilo.php");
exit;
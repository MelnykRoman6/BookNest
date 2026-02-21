<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}

if (!isset($_SESSION['user_id'])) {
    die("Errore: Devi effettuare il login per salvare i libri.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_book_id    = $_POST['db_book_id'] ?? null;
    $collection_id = $_POST['collection_id'] ?? null;
    $ol_id         = $_POST['book_id'] ?? '';

    $stmtCol = $pdo->prepare("SELECT ia_id FROM libro WHERE open_library_id = ?");
    $stmtCol->execute([$ol_id]);
    $row = $stmtCol->fetch();
    $ia_id = ($row) ? $row['ia_id'] : '';

    if (!$db_book_id || !$collection_id) {
        die("Errore: Dati mancanti per il salvataggio.");
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT IGNORE INTO aggiungere (id_libro, id_collezione) VALUES (?, ?)");
        $stmt->execute([$db_book_id, $collection_id]);

        $pdo->commit();

        header("Location: libro.php?id=" . urlencode($ol_id) . "&ia=" . urlencode($ia_id));
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Errore critico durante il salvataggio in collezione: " . $e->getMessage());
    }
}
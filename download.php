<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
if (!isset($_GET['file_url'])) {
    die("File non valido.");
}

$url = $_GET['file_url'];
$cleanName = isset($_GET['file_name'])
    ? preg_replace('/[^a-zA-Z0-9\s]/', '', $_GET['file_name'])
    : 'documento';
$fileName = $cleanName . ".pdf";

//salvataggio nel db
if (isset($_GET['id_formato']) && isset($_SESSION['user_id'])) {
    $id_formato = $_GET['id_formato'];
    $stmt = $pdo->prepare("
        INSERT INTO Download (id_formato, id_utente)
        VALUES (?, ?)");
    $stmt->execute([$id_formato, $_SESSION['user_id']]);
}

//parte download
$options = [
    "http" => [
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
        "follow_location" => 1
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false
    ]
];

$context = stream_context_create($options);
$content = @file_get_contents($url, false, $context);

if ($content !== false) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
} else {
    echo "<script>
            alert('Spiacenti, il file PDF non è disponibile.');
            window.location.href='index.php';
          </script>";
}
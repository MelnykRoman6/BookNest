<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
if (!isset($_GET['file_url'])) {
    die("File not valid");
}

$url = $_GET['file_url'];
$id_formato = $_GET['id_formato'] ?? null;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
$filesize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
curl_close($ch);

$cleanName = isset($_GET['file_name'])
    ? preg_replace('/[^a-zA-Z0-9\s]/', '', $_GET['file_name'])
    : 'documento';
$fileName = $cleanName . ".pdf";

if ($id_formato && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("INSERT INTO Download (id_formato, id_utente) VALUES (?, ?)");
    $stmt->execute([$id_formato, $_SESSION['user_id']]);
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
if ($filesize > 0) {
    header('Content-Length: ' . $filesize);
}

readfile($url);
exit;
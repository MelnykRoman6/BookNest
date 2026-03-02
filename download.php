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

//crea una risorsa per URL
$ch = curl_init($url);
//restituisce la risposta come stringa invece di stamparla
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//include header nella risposta
curl_setopt($ch, CURLOPT_HEADER, true);
//non scarica il corpo della risposta (solo info, non file)
curl_setopt($ch, CURLOPT_NOBODY, true);
//segue eventuali redirect (nuovi indirizzi in cui si trovano le info)
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//invia la richiesta
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
//attachment -> forza il download (invece di aprirlo nel browser)
header('Content-Disposition: attachment; filename="' . $fileName . '"');
if ($filesize > 0) {
    header('Content-Length: ' . $filesize);
}
//legge il file dall’URL e lo invia al browser
readfile($url);
exit;
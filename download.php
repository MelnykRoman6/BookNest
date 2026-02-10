<?php
if (isset($_GET['file_url'])) {
    $url = $_GET['file_url'];
    $name = isset($_GET['file_name']) ? preg_replace('/[^a-zA-Z0-0\s]/', '', $_GET['file_name']) : 'book';
    $fileName = $name . ".txt";

    $content = file_get_contents($url);

    if ($content !== false) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    } else {
        echo "Errore durante il download del file.";
    }
}
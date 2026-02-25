<?php
$url = $_GET['url'] ?? '';

if (!$url) {
    http_response_code(400);
    exit("URL mancante");
}

header("Content-Type: application/pdf");
readfile($url);
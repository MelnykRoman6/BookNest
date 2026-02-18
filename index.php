<?php
session_start();
require_once 'db.php';
if (isset($pdo)) {
    //echo "Connessione OK";
} else {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
echo "<div style='position: fixed; top: 10px; right: 20px; z-index: 1000; display: flex; gap: 10px;'>";

if (isset($_SESSION['user_id'])) {
    echo "";
    echo "<a href='profilo.php' style='text-decoration: none;'>
            <button style='padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Profilo</button>
          </a>";
    echo "<a href='logout.php' style='text-decoration: none;'>
            <button style='padding: 8px 15px; background-color: darkred; color: white; border: none; border-radius: 4px; cursor: pointer;'>Logout</button>
          </a>";
} else {
    echo "<a href='login.php' style='text-decoration: none;'>
            <button style='padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Accedi</button>
          </a>";
    echo "<a href='register.php' style='text-decoration: none;'>
            <button style='padding: 8px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;'>Registrati</button>
          </a>";
}
echo "</div>";
echo "<h2>Libreria Digitale PDF (Open Library)</h2>";
echo "<form method='GET' action=''>
        <input type='text' name='search' placeholder='Cerca un libro in PDF...' 
               value='" . htmlspecialchars($_GET['search'] ?? '') . "' 
               style='padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 4px;'>
        <button type='submit' style='padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>
            Cerca
        </button>
      </form>";
echo "<br><hr><br>";

$query = isset($_GET['search']) ? trim($_GET['search']) : "";
if (isset($_SESSION['user_id']) and $query != "") {
    $stmt = $pdo->prepare("insert into cronologia (id_utente, criterio_ricerca, filtro_genere, filtro_autore, data_ricerca) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $query, $query, $query]);
    $user = $stmt->fetch();
}

if ($query !== "") {
    $url = "https://openlibrary.org/search.json?q=" . urlencode($query) . "&limit=10";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MyStudentApp/1.0');
    $response = curl_exec($ch);
    $data = json_decode($response, true);

    if (!empty($data['docs'])) {
        foreach ($data['docs'] as $book) {

            $iaId = $book['ia'][0] ?? null;

            if (!$iaId) continue;

            echo "<div style='border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: flex-start; gap: 20px;'>";

            $coverId = $book['cover_i'] ?? null;
            $image = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg" : "https://via.placeholder.com/100x150?text=No+Cover";
            echo "<img src='$image' alt='Cover' style='width: 100px;'>";

            echo "<div>";
            echo "<strong style='font-size: 1.2em;'>" . htmlspecialchars($book['title']) . "</strong><br>";
            echo "Autore: " . ($book['author_name'][0] ?? 'Sconosciuto') . "<br><br>";



            if ($iaId) {
                $pdfUrl = "https://archive.org/download/{$iaId}/{$iaId}.pdf";
                $encodedUrl = urlencode($pdfUrl);
                $encodedTitle = urlencode($book['title']);

                echo "<div style='display: flex; gap: 10px; margin-top: 10px;'>";

                $downloadParams = http_build_query(['file_url' => $pdfUrl, 'file_name' => $book['title']]);
                echo "<a href='download.php?$downloadParams'>
            <button style='padding: 8px 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;'>Scarica PDF</button>
          </a>";

                echo "<a href='reader.php?file=$encodedUrl&title=$encodedTitle' target='_blank'>
            <button style='padding: 8px 12px; background-color: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer;'>Leggi Online</button>
          </a>";

                echo "</div>";
            }
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<p>Nessun libro trovato con PDF disponibile.</p>";
    }
    curl_close($ch);
}
<?php
session_start();
require_once 'db.php';
if (isset($pdo)) {
    //echo "Connessione OK";
} else {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
$bookId = $_GET['id'] ?? null;
$iaId = $_GET['ia'] ?? null;

if (!$bookId) die("ID libro mancante.");

$apiUrl = "https://openlibrary.org/works/{$bookId}.json";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'BookNestApp/1.0');
$response = curl_exec($ch);
$book = json_decode($response, true);
curl_close($ch);

$title = $book['title'] ?? 'Titolo Sconosciuto';
$description = "Descrizione non disponibile.";
if (isset($book['description'])) {
    $description = is_array($book['description']) ? $book['description']['value'] : $book['description'];
}

try {
    $stmtCheck = $pdo->prepare("SELECT id FROM libro WHERE open_library_id = ?");
    $stmtCheck->execute([$bookId]);
    $existingBook = $stmtCheck->fetch();

    if (!$existingBook) {
        $sqlInsert = "INSERT INTO libro (open_library_id, ia_id, titolo, descrizione, lingua) VALUES (?, ?, ?, ?, ?)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([$bookId, $iaId, $title, $description, 'en']);
        $db_book_id = $pdo->lastInsertId();
    } else {
        $db_book_id = $existingBook['id'];
    }
} catch (PDOException $e) {

}

$collezioni = [];
if (isset($_SESSION['user_id'])) {
    $stmtCol = $pdo->prepare("SELECT id, nome FROM collezione WHERE id_utente = ?");
    $stmtCol->execute([$_SESSION['user_id']]);
    $collezioni = $stmtCol->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?> - Dettagli</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; padding: 40px; line-height: 1.6; margin: 0; }
        .details-wrapper { display: flex; gap: 40px; max-width: 1200px; margin: 0; align-items: flex-start; }
        .side-panel { width: 300px; flex-shrink: 0; display: flex; flex-direction: column; gap: 15px; }
        .book-cover { width: 100%; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; color: white; font-weight: bold; }
        .btn-red { background: #dc3545; }
        .btn-cyan { background: #17a2b8; }
        .collection-form { background: #2d2d2d; padding: 15px; border-radius: 8px; margin-top: 10px; }
        .select-wrapper { display: flex; gap: 8px; margin-top: 10px; }
        select { background: #3d3d3d; color: #fff; border: 1px solid #444; padding: 8px; border-radius: 4px; flex-grow: 1; }
        .main-content { flex: 1; }
        .tag { background: #333; padding: 6px 12px; border-radius: 20px; font-size: 12px; margin: 0 5px 5px 0; display: inline-block; border: 1px solid #444; }
    </style>
</head>
<body>

<a href="javascript:history.back()" style="color: #17a2b8; text-decoration: none;">← Torna ai risultati</a>

<div class="details-wrapper" style="margin-top: 20px;">

    <div class="side-panel">
        <?php
        $coverId = $book['covers'][0] ?? null;
        $coverUrl = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg" : "https://via.placeholder.com/300x450?text=No+Cover";
        ?>
        <img src="<?php echo $coverUrl; ?>" class="book-cover" alt="Cover">

        <?php if ($iaId): ?>
            <div class="action-btns" style="display: flex; flex-direction: column; gap: 10px;">
                <?php
                $pdfUrl = "https://archive.org/download/{$iaId}/{$iaId}.pdf";
                $downloadParams = http_build_query(['file_url' => $pdfUrl, 'file_name' => $title]);
                ?>
                <a href='download.php?<?php echo $downloadParams; ?>'><button class="btn btn-red">Scarica PDF</button></a>
                <a href='reader.php?file=<?php echo urlencode($pdfUrl); ?>&title=<?php echo urlencode($title); ?>' target='_blank'><button class="btn btn-cyan">Leggi Online</button></a>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="collection-form">
                <form action="salva_in_col.php" method="POST">
                    <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($bookId); ?>">
                    <input type="hidden" name="db_book_id" value="<?php echo $db_book_id; ?>">
                    <label style="font-size: 13px; color: #bbb;">Aggiungi a una collezione:</label>
                    <div class="select-wrapper">
                        <select name="collection_id">
                            <?php foreach ($collezioni as $col): ?>
                                <option value="<?php echo $col['id']; ?>"><?php echo htmlspecialchars($col['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Salva</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <h1 style="margin-top: 0;"><?php echo htmlspecialchars($title); ?></h1>
        <h3>Descrizione:</h3>
        <p style="color: #ccc;"><?php echo nl2br(htmlspecialchars($description)); ?></p>

        <?php if (isset($book['subjects'])): ?>
            <h3>Soggetti:</h3>
            <?php foreach (array_slice($book['subjects'], 0, 12) as $subject): ?>
                <span class="tag"><?php echo htmlspecialchars($subject); ?></span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
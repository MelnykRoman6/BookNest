<?php
session_start();
require_once 'db.php';
if (isset($pdo)) {
    //echo "Connessione OK";
} else {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
$bookId = $_GET['id'] ?? null;
if (!$bookId) die("ID libro mancante.");

$apiUrl = "https://openlibrary.org/works/{$bookId}.json";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'BookNestApp/1.0');
$response = curl_exec($ch);
$book = json_decode($response, true);
curl_close($ch);

$description = "Descrizione non disponibile.";
if (isset($book['description'])) {
    $description = is_array($book['description']) ? $book['description']['value'] : $book['description'];
}
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("select c.nome from utente u inner join collezione c on u.id = c.id_utente where u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $collezioni = $stmt->fetch();

}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($book['title']); ?> - Dettagli</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 40px; line-height: 1.6; }
        .details-container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: flex; gap: 30px; }
        .book-info { flex: 1; }
        .book-cover { width: 250px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .tag { background: #eee; padding: 5px 10px; border-radius: 15px; font-size: 0.8em; margin-right: 5px; display: inline-block; margin-bottom: 5px; }
    </style>
</head>
<body>

<div style="max-width: 800px; margin: 0 auto 20px;">
    <a href="javascript:history.back()" style="text-decoration: none; color: #007bff;">← Torna ai risultati</a>
</div>

<div class="details-container">
    <div>
        <?php
        $coverId = $book['covers'][0] ?? null;
        $coverUrl = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg" : "https://via.placeholder.com/250x380?text=No+Cover";
        ?>
        <img src="<?php echo $coverUrl; ?>" class="book-cover" alt="Cover">
    </div>

    <div class="book-info">
        <h1><?php echo htmlspecialchars($book['title']); ?></h1>

        <h3>Descrizione:</h3>
        <p><?php echo nl2br(htmlspecialchars($description)); ?></p>

        <?php if (isset($book['subjects'])): ?>
            <h3>Soggetti:</h3>
            <?php foreach (array_slice($book['subjects'], 0, 10) as $subject): ?>
                <span class="tag"><?php echo htmlspecialchars($subject); ?></span>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <form action="save_to_collection.php" method="POST">
                <input type="hidden" name="book_id" value="<?php echo $bookId; ?>">
                <label>Aggiungi a una collezione:</label><br>
                <?php
                for ($i = 0; $i < count($collezioni); $i++) {
                    echo count($collezioni);
                }
                ?>
                <select name="collection_id" style="padding: 10px; width: 200px; margin-top: 10px;">
                </select>
                <button type="submit" style="padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor:pointer;">Salva</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
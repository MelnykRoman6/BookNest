<?php
session_start();
require_once 'db.php';
if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
?>

<!-- BOTTONI LOGIN / PROFILO -->
<div style="position: fixed; top: 10px; right: 20px; z-index: 1000; display: flex; gap: 10px;">

    <?php if (isset($_SESSION['user_id'])): ?>

        <a href="profilo.php"><button style="padding:8px 15px; background:#007bff; color:white; border:none; border-radius:4px;">Profilo</button></a>
        <a href="logout.php"><button style="padding:8px 15px; background:darkred; color:white; border:none; border-radius:4px;">Logout</button></a>

    <?php else: ?>

        <a href="login.php"><button style="padding:8px 15px; background:#007bff; color:white; border:none; border-radius:4px;">Accedi</button></a>
        <a href="register.php"><button style="padding:8px 15px; background:#28a745; color:white; border:none; border-radius:4px;">Registrati</button></a>

    <?php endif; ?>

</div>

<?php
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
$coverId = $book['covers'][0] ?? null;

try {
    $stmtCheck = $pdo->prepare("SELECT id FROM libro WHERE open_library_id = ?");
    $stmtCheck->execute([$bookId]);
    $existingBook = $stmtCheck->fetch();

    if (!$existingBook) {
        //per eseguire più operazioni e le modifiche restano in sospeso finché non viene fatto il commit
        $pdo->beginTransaction();

        //per inserire libro
        $sqlInsert = "INSERT INTO libro (open_library_id, ia_id, titolo, descrizione, lingua, cover_id)
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([$bookId, $iaId, $title, $description, 'en', $coverId]);
        $db_book_id = $pdo->lastInsertId();

        //per inserire autori
        if (!empty($book['authors'])) {
            foreach ($book['authors'] as $authorData) {
                $authorKey = $authorData['author']['key'];
                $authorUrl = "https://openlibrary.org{$authorKey}.json";

                $authorJson = json_decode(file_get_contents($authorUrl), true);
                $authorName = $authorJson['name'] ?? null;

                if (!$authorName) continue;

                //per controllare esistenza autore
                $stmtCheckAuthor = $pdo->prepare("SELECT id FROM Autore WHERE nome = ?");
                $stmtCheckAuthor->execute([$authorName]);
                $existingAuthor = $stmtCheckAuthor->fetch();

                if ($existingAuthor) {
                    $author_id = $existingAuthor['id'];
                } else {
                    $stmtInsertAuthor = $pdo->prepare("INSERT INTO Autore (nome) VALUES (?)");
                    $stmtInsertAuthor->execute([$authorName]);
                    $author_id = $pdo->lastInsertId();
                }

                //relazione Scrivere
                $stmtRel = $pdo->prepare("INSERT IGNORE INTO Scrivere (id_libro, id_autore) VALUES (?, ?)");
                $stmtRel->execute([$db_book_id, $author_id]);
            }
        }

        //per inserire generi
        if (!empty($book['subjects'])) {
            //prende al massimo 5 generi
            foreach (array_slice($book['subjects'], 0, 5) as $subject) {
                $stmtCheckGenre = $pdo->prepare("SELECT id FROM Genere WHERE nome = ?");
                $stmtCheckGenre->execute([$subject]);
                $existingGenre = $stmtCheckGenre->fetch();

                if ($existingGenre) {
                    $genre_id = $existingGenre['id'];
                } else {
                    $stmtInsertGenre = $pdo->prepare("INSERT INTO Genere (nome) VALUES (?)");
                    $stmtInsertGenre->execute([$subject]);
                    $genre_id = $pdo->lastInsertId();
                }
                $stmtRel = $pdo->prepare("INSERT IGNORE INTO Appartenere (id_libro, id_genere) VALUES (?, ?)");
                $stmtRel->execute([$db_book_id, $genre_id]);
            }
        }
        $pdo->commit();
    } else {
        $db_book_id = $existingBook['id'];
    }
} catch (PDOException $e) {
    die("Errore salvataggio libro: " . $e->getMessage());
}

if (isset($_POST['invia_recensione']) && isset($_SESSION['user_id'])) {
    $rating = $_POST['rating'];
    $commento = trim($_POST['commento'] ?? '');

    //controllo se ha già recensito
    $stmtCheck = $pdo->prepare("
        SELECT id FROM recensione
        WHERE id_libro = ? AND id_utente = ?
    ");
    $stmtCheck->execute([$db_book_id, $_SESSION['user_id']]);

    if (!$stmtCheck->fetch()) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO recensione
            (id_libro, id_utente, rating, commento, data_rec)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmtInsert->execute([
                $db_book_id,
                $_SESSION['user_id'],
                $rating,
                $commento
        ]);
    }

    //per evitare reinvio form
    header("Location: libro.php?id=" . $_GET['id'] . "&ia=" . $_GET['ia']);
    exit;
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

<a href="javascript:history.back()" style="color: #17a2b8; text-decoration: none;">← Torna alla pagina precedente</a>

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

<hr>

<?php if (isset($_SESSION['user_id'])):
    $stmtCheckUserReview = $pdo->prepare("
        SELECT id FROM recensione
        WHERE id_libro = ? AND id_utente = ?
    ");
    $stmtCheckUserReview->execute([$db_book_id, $_SESSION['user_id']]);
    $recensito = $stmtCheckUserReview->fetch();
    if (!$recensito) :
    ?>

    <h3>Scrivi una recensione</h3>

    <form method="POST">

        <label>Valutazione:</label>
        <select name="rating" required>
            <option value="">-- Voto --</option>
            <option value="1">⭐</option>
            <option value="2">⭐⭐</option>
            <option value="3">⭐⭐⭐</option>
            <option value="4">⭐⭐⭐⭐</option>
            <option value="5">⭐⭐⭐⭐⭐</option>
        </select>

        <br><br>

        <textarea name="commento"
                  placeholder="Scrivi la tua recensione..."
                  rows="4"
                  cols="50"></textarea>

        <br><br>

        <button type="submit" name="invia_recensione">
            Pubblica
        </button>
    </form>

    <?php else: ?>
        <p><b>Hai già recensito questo libro.</b></p>
    <?php endif; ?>
<?php else: ?>
    <p><b>Effettua il login per scrivere una recensione.</b></p>
<?php endif; ?>

<hr>
<h3>Recensioni</h3>

<?php
$stmtRec = $pdo->prepare("
    SELECT r.*, u.email
    FROM recensione r
    JOIN utente u ON r.id_utente = u.id
    WHERE r.id_libro = ?
    ORDER BY r.data_rec DESC
");

$stmtRec->execute([$db_book_id]);
$recensioni = $stmtRec->fetchAll();

if ($recensioni):
    foreach ($recensioni as $rec):
        ?>

        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <strong><?= htmlspecialchars($rec['email']) ?></strong>
            — <?= str_repeat("⭐", $rec['rating']) ?>
            <br><br>
            <?= nl2br(htmlspecialchars($rec['commento'])) ?>
            <br><small><?= $rec['data_rec'] ?></small>
        </div>

    <?php
    endforeach;
else:
    echo "<p>Nessuna recensione ancora.</p>";
endif;
?>
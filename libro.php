<?php
session_start();
require_once 'db.php';
if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Book details</title>
    <link rel="stylesheet" href="styles/stile_libro.css">
</head>
<body>

<div id="progress-container">
    <div class="progress-bar-bg">
        <div id="pb-fill" class="progress-bar-fill"></div>
    </div>
    <div id="pb-text" class="progress-text">0%</div>
    <p style="color: #888; margin-top: 10px;">Salvataggio dei dati in corso...</p>
</div>

<div id="loader-overlay">
    <div class="spinner"></div>
    <div class="loader-text">Downloading...</div>
    <p style="color: #888; font-size: 0.9em;">Please wait, we are downloading the author and cover data</p>
</div>
<div class="user-menu">

    <?php if (isset($_SESSION['user_id'])): ?>

        <a href="profilo.php" class="btn-menu btn-blue">Profile</a>
        <a href="logout.php" class="btn-menu btn-red-dark">Logout</a>

    <?php else: ?>

        <a href="login.php" class="btn-menu btn-blue">Log in</a>
        <a href="register.php" class="btn-menu btn-green-reg">Register</a>

    <?php endif; ?>

</div>

<?php
$bookId = $_GET['id'] ?? null;
$iaId = $_GET['ia'] ?? null;

if (!$bookId) die("Book ID missing");

$apiUrl = "https://openlibrary.org/works/{$bookId}.json";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'BookNestApp/1.0');
$response = curl_exec($ch);
$book = json_decode($response, true);
curl_close($ch);

$title = $book['title'] ?? 'Title non known';
$description = "Description not available";
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

        //inserimento formato pdf
        $stmtFormato = $pdo->prepare("
            INSERT INTO Formato (id_libro, tipo, url)
            VALUES (?, ?, ?)
        ");

        $stmtFormato->execute([
                $db_book_id,
                'pdf',
                "https://archive.org/download/{$iaId}/{$iaId}.pdf"
        ]);
    } else {
        $db_book_id = $existingBook['id'];
    }
} catch (PDOException $e) {
    die("Error during downloading: " . $e->getMessage());
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

<a href="javascript:history.back()" class="back-link">← Return to the previous page</a>

<div class="details-wrapper">

    <div class="side-panel">
        <?php
        $coverId = $book['covers'][0] ?? null;
        $coverUrl = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg" : "https://via.placeholder.com/300x450?text=No+Cover";
        ?>
        <img src="<?php echo $coverUrl; ?>" class="book-cover" alt="Cover">

        <?php if ($iaId): ?>
            <div class="action-btns">
                <?php
                $pdfUrl = "https://archive.org/download/{$iaId}/{$iaId}.pdf";

                $stmtFormato = $pdo->prepare("SELECT id FROM Formato WHERE id_libro = ? AND tipo = 'pdf'");
                $stmtFormato->execute([$db_book_id]);
                $formato = $stmtFormato->fetch();

                $downloadParams = http_build_query([
                        'file_url' => $pdfUrl,
                        'file_name' => $title,
                        'id_formato' => $formato['id'] ?? null   // 👈 IMPORTANTE
                ]);
                ?>
                <a href='download.php?<?php echo $downloadParams; ?>'><button class="btn-action btn-red">Download PDF</button></a>
                <a href='reader.php?file=<?php echo urlencode($pdfUrl); ?>&title=<?php echo urlencode($title); ?>&id_libro=<?php echo $db_book_id; ?>' target='_blank'><button class="btn-action btn-cyan">Read Online</button></a>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="collection-form">
                <form action="salva_in_col.php" method="POST">
                    <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($bookId); ?>">
                    <input type="hidden" name="db_book_id" value="<?php echo $db_book_id; ?>">
                    <label class="collection-label">Add to a collection:</label>
                    <div class="select-wrapper">
                        <select name="collection_id" class="select-dark">
                            <?php foreach ($collezioni as $col): ?>
                                <option value="<?php echo $col['id']; ?>"><?php echo htmlspecialchars($col['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-save">Save</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <h3>Description:</h3>
        <p class="description-text"><?php echo nl2br(htmlspecialchars($description)); ?></p>

        <?php if (isset($book['subjects'])): ?>
            <h3>Subjects:</h3>
            <?php foreach (array_slice($book['subjects'], 0, 12) as $subject): ?>
                <span class="tag"><?php echo htmlspecialchars($subject); ?></span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

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

        <h3>Write a review</h3>

        <form method="POST">
            <label>Rating:</label>
            <select name="rating" required>
                <option value="">-- Vote --</option>
                <option value="1">⭐</option>
                <option value="2">⭐⭐</option>
                <option value="3">⭐⭐⭐</option>
                <option value="4">⭐⭐⭐⭐</option>
                <option value="5">⭐⭐⭐⭐⭐</option>
            </select>
            <br><br>
            <textarea name="commento" placeholder="Write your review..." rows="4" cols="50"></textarea>
            <br><br>
            <button type="submit" name="invia_recensione">Publish</button>
        </form>

    <?php else: ?>
        <p><b>You have already reviewed this book</b></p>
    <?php endif; ?>
<?php else: ?>
    <p><b>Log in to write a review</b></p>
<?php endif; ?>

<hr>
<h3>Reviews</h3>

<?php
$stmtRec = $pdo->prepare("
    SELECT r.*, u.email, l.open_library_id, l.ia_id
    FROM recensione r
    INNER JOIN utente u ON r.id_utente = u.id
    INNER JOIN libro l on l.id = r.id_libro
    WHERE r.id_libro = ?
    ORDER BY r.data_rec DESC
");

$stmtRec->execute([$db_book_id]);
$recensioni = $stmtRec->fetchAll();

if ($recensioni):
    foreach ($recensioni as $rec):
        ?>
        <div class="review-card">
            <strong><?= htmlspecialchars($rec['email']) ?></strong>
            — <?= str_repeat("⭐", $rec['rating']) ?>
            <br>
            <?= nl2br(htmlspecialchars($rec['commento'])) ?>
            <br>
            <form action="del_rec.php" method="POST" class="delete-form" onsubmit="return confirm('Are you sure?');">
                <input type="hidden" name="id_rec" value="<?php echo $rec['id']; ?>">
                <input type="hidden" name="id_rec_libro" value="<?php echo $rec['id_libro']; ?>">
                <input type="hidden" name="id_ia" value="<?php echo $rec['ia_id']; ?>">
                <input type="hidden" name="id_oid" value="<?php echo $rec['open_library_id']; ?>">
                <small><button type="submit" class="link-delete" onclick="event.stopPropagation();">
                    Eliminate
                </button> </small> <br>
            </form>
            <small><?= $rec['data_rec'] ?></small>
        </div>
    <?php
    endforeach;
else:
    echo "<p>No reviews yet</p>";
endif;
?>

</body>
</html>
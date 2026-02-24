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
    <link rel="stylesheet" href="styles/stile_index.css">
    <title>BookNest - Home</title>
</head>
<body>

<div class="user-menu">
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="profilo.php" class="btn btn-primary">Profilo</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    <?php else: ?>
        <a href="login.php" class="btn btn-primary">Accedi</a>
        <a href="register.php" class="btn btn-success">Registrati</a>
    <?php endif; ?>
</div>

<h2 class="main-title">BookNest</h2>

<form method="GET" class="search-form">
    <input type="text"
           name="search"
           placeholder="Cerca..."
           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">

    <?php
    $countAutori = $pdo->query("SELECT COUNT(*) FROM Autore")->fetchColumn();
    if ($countAutori > 0):
        $autori = $pdo->query("SELECT id, nome FROM Autore ORDER BY nome ASC")->fetchAll();
        ?>
        <select name="autore">
            <option value="">-- Autore --</option>
            <?php foreach ($autori as $a): ?>
                <option value="<?= $a['id'] ?>"
                        <?php if (isset($_GET['autore']) && $_GET['autore'] == $a['id']) echo "selected"; ?>>
                    <?= htmlspecialchars($a['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <?php
    $countGeneri = $pdo->query("SELECT COUNT(*) FROM Genere")->fetchColumn();
    if ($countGeneri > 0):
        $generi = $pdo->query("SELECT id, nome FROM Genere ORDER BY nome ASC")->fetchAll();
        ?>
        <select name="genere">
            <option value="">-- Genere --</option>
            <?php foreach ($generi as $g): ?>
                <option value="<?= $g['id'] ?>"
                        <?php if (isset($_GET['genere']) && $_GET['genere'] == $g['id']) echo "selected"; ?>>
                    <?= htmlspecialchars($g['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <button type="submit">Cerca</button>
</form>

<br><hr><br>

<?php
$titolo = trim($_GET['search'] ?? '');
$autore = $_GET['autore'] ?? '';
$genere = $_GET['genere'] ?? '';

// salvataggio in cronologia
if (isset($_SESSION['user_id']) && $titolo !== '') {
    $stmtCheck = $pdo->prepare("
        SELECT id FROM cronologia
        WHERE id_utente = ?
        AND criterio_ricerca = ?
        AND DATE(data_ricerca) = CURDATE()
        LIMIT 1
    ");
    $stmtCheck->execute([$_SESSION['user_id'], $titolo]);
    $exists = $stmtCheck->fetch();

    if (!$exists) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO cronologia 
            (id_utente, criterio_ricerca, data_ricerca)
            VALUES (?, ?, NOW())
        ");
        $stmtInsert->execute([$_SESSION['user_id'], $titolo]);
    }
}

$libri_db = [];

// ricerca nel DB
if ($titolo !== '' || $autore !== '' || $genere !== '') {
    $sql = "
        SELECT 
            l.*,
            AVG(r.rating) as media_rating,
            COUNT(r.id) as totale_recensioni
        FROM Libro l
        LEFT JOIN Scrivere s ON l.id = s.id_libro
        LEFT JOIN Autore a ON s.id_autore = a.id
        LEFT JOIN Appartenere ap ON l.id = ap.id_libro
        LEFT JOIN Genere g ON ap.id_genere = g.id
        LEFT JOIN recensione r ON l.id = r.id_libro
        WHERE 1=1
    ";
    $params = [];
    if (!empty($titolo)) { $sql .= " AND l.titolo LIKE ?"; $params[] = "%$titolo%"; }
    if (!empty($autore)) { $sql .= " AND a.id = ?"; $params[] = $autore; }
    if (!empty($genere)) { $sql .= " AND g.id = ?"; $params[] = $genere; }
    $sql .= " GROUP BY l.id ORDER BY l.titolo ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $libri_db = $stmt->fetchAll();

    if(empty($libri_db)) {
        $sql = "
            SELECT 
                l.*,
                AVG(r.rating) as media_rating,
                COALESCE((SELECT count(*) FROM recensione r1 WHERE r1.id_libro = l.id), 0) as totale_recensioni,
                a.nome
            FROM Libro l
            LEFT JOIN Scrivere s ON l.id = s.id_libro
            LEFT JOIN Autore a ON s.id_autore = a.id
            LEFT JOIN Appartenere ap ON l.id = ap.id_libro
            LEFT JOIN Genere g ON ap.id_genere = g.id
            LEFT JOIN recensione r ON l.id = r.id_libro
            WHERE 1=1
        ";
        $params = [];
        if (!empty($titolo)) { $sql .= " AND a.nome LIKE ?"; $params[] = "%$titolo%"; }
        if (!empty($autore)) { $sql .= " AND a.id = ?"; $params[] = $autore; }
        if (!empty($genere)) { $sql .= " AND g.id = ?"; $params[] = $genere; }
        $sql .= " GROUP BY l.id ORDER BY l.titolo ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $libri_db = $stmt->fetchAll();
    }
}

// mostra risultati DB
if (!empty($libri_db)) {
    echo "<h3>Risultati dal Database</h3>";

    foreach ($libri_db as $libro) {
        $media = $libro['media_rating'];
        $totaleRec = $libro['totale_recensioni'];
        $stelle = str_repeat("⭐", floor($media)) . str_repeat("☆", 5 - floor($media));

        $stmtAut = $pdo->prepare("SELECT a.nome FROM Autore a JOIN Scrivere s ON a.id = s.id_autore WHERE s.id_libro = ? LIMIT 1");
        $stmtAut->execute([$libro['id']]);
        $autoreNome = $stmtAut->fetchColumn() ?? "Sconosciuto";

        $coverUrl = !empty($libro['cover_id'])
                ? "https://covers.openlibrary.org/b/id/" . $libro['cover_id'] . "-M.jpg"
                : "https://via.placeholder.com/100x150?text=No+Cover";

        echo "<div class='book-card'>";
        echo "<a href='libro.php?id=" . $libro['open_library_id'] . "&ia=" . $libro['ia_id'] . "'>";
        echo "<img src='$coverUrl' class='book-cover'>";
        echo "</a>";

        echo "<div class='book-details'>";
        echo "<a href='libro.php?id=" . $libro['open_library_id'] . "&ia=" . $libro['ia_id'] . "' class='book-title'>";
        echo "<strong>" . htmlspecialchars($libro['titolo']) . "</strong>";
        echo "</a>";
        echo "<span class='book-author'>Autore: " . htmlspecialchars($autoreNome) . "</span>";
        echo "</div>";

        if ($totaleRec > 0) {
            echo "<div class='rating-box'>";
            echo "<div class='stars'>$stelle</div>";
            $testoRecensioni = ($totaleRec == 1) ? "recensione" : "recensioni";
            echo "<div class='rating-info'>" . round($media, 1) . " / 5<br>" . $totaleRec . " " . $testoRecensioni . "</div>";
            echo "</div>";
        }
        echo "</div>";
    }
}

// se не trova usa API
if ($titolo !== '') {
    echo "<h3>Risultati da OpenLibrary</h3>";
    $url = "https://openlibrary.org/search.json?q=" . urlencode($titolo) . "&limit=10";
    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        if (!empty($data['docs'])) {
            $idsPresenti = array_column($libri_db, 'open_library_id');

            foreach ($data['docs'] as $book) {
                $bookKey = str_replace('/works/', '', $book['key']);
                if (in_array($bookKey, $idsPresenti)) continue;

                $titleApi = $book['title'] ?? 'Senza titolo';
                $authorApi = $book['author_name'][0] ?? 'Sconosciuto';
                $coverId = $book['cover_i'] ?? null;
                $iaId = $book['ia'][0] ?? null;
                $image = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg" : "https://via.placeholder.com/100x150?text=No+Cover";

                echo "<div class='book-card'>";
                echo "<a href='libro.php?id=$bookKey&ia=$iaId'><img src='$image' class='book-cover'></a>";
                echo "<div class='book-details'>";
                echo "<a href='libro.php?id=$bookKey&ia=$iaId' class='book-title'><strong>" . htmlspecialchars($titleApi) . "</strong></a>";
                echo "<span class='book-author'>Autore: " . htmlspecialchars($authorApi) . "</span>";
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p>Nessun risultato trovato.</p>";
        }
    } else {
        echo "<p>Errore nella chiamata API.</p>";
    }
}
?>
</body>
</html>
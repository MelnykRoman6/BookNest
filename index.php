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

    <h2>BookNest</h2>

    <!-- FORM RICERCA -->
    <form method="GET">

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

//salvataggio in cronologia
if (isset($_SESSION['user_id']) && $titolo !== '') {
    //evita duplicati identici nello stesso giorno
    $stmtCheck = $pdo->prepare("
        SELECT id FROM cronologia
        WHERE id_utente = ?
        AND criterio_ricerca = ?
        AND DATE(data_ricerca) = CURDATE()
        LIMIT 1
    ");

    $stmtCheck->execute([
            $_SESSION['user_id'],
            $titolo
    ]);

    $exists = $stmtCheck->fetch();

    if (!$exists) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO cronologia 
            (id_utente, criterio_ricerca, data_ricerca)
            VALUES (?, ?, NOW())
        ");

        $stmtInsert->execute([
                $_SESSION['user_id'],
                $titolo
        ]);
    }
}

$libri_db = [];

//ricerca nel DB
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

    if (!empty($titolo)) {
        $sql .= " AND l.titolo LIKE ?";
        $params[] = "%$titolo%";
    }

    if (!empty($autore)) {
        $sql .= " AND a.id = ?";
        $params[] = $autore;
    }

    if (!empty($genere)) {
        $sql .= " AND g.id = ?";
        $params[] = $genere;
    }

    $sql .= " GROUP BY l.id ORDER BY l.titolo ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $libri_db = $stmt->fetchAll();

    if(empty($libri_db)) {
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

        if (!empty($titolo)) {
            $sql .= " AND a.nome LIKE ?";
            $params[] = "%$titolo%";
        }

        if (!empty($autore)) {
            $sql .= " AND a.id = ?";
            $params[] = $autore;
        }

        if (!empty($genere)) {
            $sql .= " AND g.id = ?";
            $params[] = $genere;
        }

        $sql .= " GROUP BY l.id ORDER BY l.titolo ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $libri_db = $stmt->fetchAll();
    }
}

//mostra risultati DB
if (!empty($libri_db)) {
    echo "<h3>Risultati dal Database</h3>";

    foreach ($libri_db as $libro) {
        $media = $libro['media_rating'];
        $totaleRec =  $libro['totale_recensioni'];

        $stelle = '';

        if ($totaleRec > 0) {
            $stellePiene = floor($media);

            for ($i = 0; $i < $stellePiene; $i++) {
                $stelle .= "⭐";
            }

            // stelle vuote fino a 5
            for ($i = $stellePiene; $i < 5; $i++) {
                $stelle .= "☆";
            }
        }

        //autore
        $stmtAut = $pdo->prepare("
        SELECT a.nome
        FROM Autore a
        JOIN Scrivere s ON a.id = s.id_autore
        WHERE s.id_libro = ?
        LIMIT 1
    ");
        $stmtAut->execute([$libro['id']]);
        $autoreNome = $stmtAut->fetchColumn() ?? "Sconosciuto";

        //cover
        $coverUrl = !empty($libro['cover_id'])
                ? "https://covers.openlibrary.org/b/id/" . $libro['cover_id'] . "-M.jpg"
                : "https://via.placeholder.com/100x150?text=No+Cover";

        echo "<div style='border:1px solid #ddd; padding:15px; margin-bottom:20px; border-radius:8px; display:flex; gap:20px;'>";

        echo "<a href='libro.php?id=" . $libro['open_library_id'] . "&ia=" . $libro['ia_id'] . "'>";
        echo "<img src='$coverUrl' style='width:100px;'>";
        echo "</a>";

        echo "<div style='flex:1;'>";

        echo "<a href='libro.php?id=" . $libro['open_library_id'] . "&ia=" . $libro['ia_id'] . "' style='text-decoration:none; color:black;'>";
        echo "<strong style='font-size:1.2em;'>" . htmlspecialchars($libro['titolo']) . "</strong>";
        echo "</a><br>";
        echo "Autore: " . htmlspecialchars($autoreNome);

        echo "</div>";

        // BLOCCO RATING A DESTRA
        if ($totaleRec > 0) {
            echo "<div style='min-width:150px; text-align:right;'>";

            echo "<div style='color:#f5b301; font-size:1.1em; letter-spacing:2px;'>";
            echo $stelle;
            echo "</div>";

            echo "<div style='font-size:0.9em; color:#666;'>";
            echo round($media,1) . " / 5<br>";
            echo "$totaleRec recensioni";
            echo "</div>";

            echo "</div>";
        }

        echo "</div>";
    }
}

//se non trova, usa API
if ($titolo !== '' || empty($libri_db)) {
    echo "<h3>Risultati da OpenLibrary</h3>";

    $url = "https://openlibrary.org/search.json?q=" . urlencode($titolo) . "&limit=10";
    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);

        if (!empty($data['docs'])) {
            foreach ($data['docs'] as $book) {
                $bookKey = str_replace('/works/', '', $book['key']);
                $titleApi = $book['title'] ?? 'Senza titolo';
                $authorApi = $book['author_name'][0] ?? 'Sconosciuto';
                $coverId = $book['cover_i'] ?? null;
                $iaId = $book['ia'][0] ?? null;

                if(!empty($libri_db)){
                    foreach ($libri_db as $libro) {
                        if ($titleApi != $libro['titolo']) {
                            $image = $coverId
                                    ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg"
                                    : "https://via.placeholder.com/100x150?text=No+Cover";

                            echo "<div style='border:1px solid #ddd; padding:15px; margin-bottom:20px; border-radius:8px; display:flex; gap:20px;'>";

                            echo "<a href='libro.php?id=$bookKey&ia=$iaId'>";
                            echo "<img src='$image' style='width:100px;'>";
                            echo "</a>";

                            echo "<div>";
                            echo "<a href='libro.php?id=$bookKey&ia=$iaId' style='text-decoration:none; color:black;'>";
                            echo "<strong style='font-size:1.2em;'>" . htmlspecialchars($titleApi) . "</strong>";
                            echo "</a><br>";
                            echo "Autore: " . htmlspecialchars($authorApi);
                            echo "</div>";

                            echo "</div>";
                        }
                    }
                }
                else {
                    $image = $coverId
                            ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg"
                            : "https://via.placeholder.com/100x150?text=No+Cover";

                    echo "<div style='border:1px solid #ddd; padding:15px; margin-bottom:20px; border-radius:8px; display:flex; gap:20px;'>";

                    echo "<a href='libro.php?id=$bookKey&ia=$iaId'>";
                    echo "<img src='$image' style='width:100px;'>";
                    echo "</a>";

                    echo "<div>";
                    echo "<a href='libro.php?id=$bookKey&ia=$iaId' style='text-decoration:none; color:black;'>";
                    echo "<strong style='font-size:1.2em;'>" . htmlspecialchars($titleApi) . "</strong>";
                    echo "</a><br>";
                    echo "Autore: " . htmlspecialchars($authorApi);
                    echo "</div>";

                    echo "</div>";
                }
            }
        } else {
            echo "<p>Nessun risultato trovato.</p>";
        }

    } else {
        echo "<p>Errore nella chiamata API.</p>";
    }
}
?>
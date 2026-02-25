<?php
session_start();
require_once 'db.php';

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmtUser = $pdo->prepare("SELECT email, data_reg FROM utente WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch();

    $stmtCol = $pdo->prepare("
        SELECT 
            c.id, 
            c.nome, 
            c.data_crea, 
            COUNT(a.id_libro) AS total_libri,
            GROUP_CONCAT(l.titolo SEPARATOR '||') as titoli_libri,
            GROUP_CONCAT(l.open_library_id SEPARATOR '||') as ids_libri,
            GROUP_CONCAT(l.ia_id SEPARATOR '||') as ia_ids_libri
        FROM collezione c
        LEFT JOIN aggiungere a ON c.id = a.id_collezione
        LEFT JOIN libro l ON l.id = a.id_libro
        WHERE c.id_utente = ?
        GROUP BY c.id
        ORDER BY c.data_crea DESC
    ");
    $stmtCol->execute([$user_id]);
    $collezioni = $stmtCol->fetchAll();

    $stmtCron = $pdo->prepare("SELECT criterio_ricerca, data_ricerca FROM cronologia WHERE id_utente = ? ORDER BY data_ricerca DESC LIMIT 10");
    $stmtCron->execute([$user_id]);
    $cronologia = $stmtCron->fetchAll();

    $stmtDownload = $pdo->prepare("
        SELECT l.titolo, f.tipo, d.data_download
        FROM Download d
        INNER JOIN Formato f ON d.id_formato = f.id
        INNER JOIN Libro l ON f.id_libro = l.id
        WHERE d.id_utente = ?
        ORDER BY d.data_download DESC
        LIMIT 20
    ");
    $stmtDownload->execute([$user_id]);
    $downloadHistory = $stmtDownload->fetchAll();

} catch (PDOException $e) {
    die("Data recovery error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profile - BookNest</title>
    <link rel="stylesheet" href="styles/stile_profilo.css">
</head>
<body>

<div class="container">
    <a href="index.php" class="back-link">← Back to search</a>

    <div class="section">
        <h2>My profile</h2>
        <div class="info-box">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Member from:</strong> <?php echo date("d/m/Y", strtotime($user['data_reg'])); ?></p>
        </div>
    </div>

    <div class="section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>My collections</h3>
            <a href="nuova_col.php" class="btn-create">+ New collection</a>
        </div>

        <!-- se ci sono collezioni -->
        <?php if ($collezioni): ?>
            <?php foreach ($collezioni as $col): ?>
                <div style="margin-bottom: 15px; border: 1px solid #444; border-radius: 8px; overflow: hidden;">

                    <div class="list-item collapsible-header" onclick="toggleCollection(<?php echo $col['id']; ?>)">
                        <div>
                            <strong><?php echo htmlspecialchars($col['nome']); ?></strong><br>
                            <span class="date">Created at: <?php echo date("d/m/Y", strtotime($col['data_crea'])); ?></span>
                        </div>

                        <div class="actions-wrapper">
                            <div class="badge-count">
                                <?php echo $col['total_libri']; ?> books
                            </div>

                            <div class="buttons-stack">
                                <form action="modifica_col.php" method="GET" style="margin: 0;">
                                    <input type="hidden" name="id" value="<?php echo $col['id']; ?>">
                                    <button type="submit" class="btn-manage btn-green" onclick="event.stopPropagation();"> <!-- evita di aprire/chiudere collezione -->
                                        Modify
                                    </button>
                                </form>

                                <form action="del_col.php" method="POST" style="margin: 0;" onsubmit="return confirm('Delete the collection?');">
                                    <input type="hidden" name="id_collezione" value="<?php echo $col['id']; ?>">
                                    <button type="submit" class="btn-manage btn-red" onclick="event.stopPropagation();">
                                        Eliminate
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div id="col-<?php echo $col['id']; ?>" class="books-content">
                        <?php
                        if ($col['total_libri'] > 0):
                            //stringa in array
                            $titoli = explode('||', $col['titoli_libri']);
                            $ol_ids = explode('||', $col['ids_libri']);
                            $ia_ids = explode('||', $col['ia_ids_libri']);

                            for($i = 0; $i < count($titoli); $i++):
                                ?>
                                <div class="book-item">
                                    <a href="libro.php?id=<?php echo $ol_ids[$i]; ?>&ia=<?php echo $ia_ids[$i]; ?>" class="book-link">
                                        <?php echo htmlspecialchars($titoli[$i]); ?>
                                    </a>
                                    <span class="date">PDF available</span>
                                </div>
                            <?php endfor; ?>
                        <?php else: ?>
                            <p style="color: #777; font-size: 0.9em;">No books in this collection</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven't created any collections yet</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Search history</h3>
        <?php if ($cronologia): ?>
            <?php foreach ($cronologia as $item): ?>
                <div class="list-item">
                    <span class="search-term">"<?php echo htmlspecialchars($item['criterio_ricerca']); ?>"</span>
                    <span class="date"><?php echo date("d/m/Y H:i", strtotime($item['data_ricerca'])); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Your history is empty</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Download history</h3>
        <?php if ($downloadHistory): ?>
            <?php foreach ($downloadHistory as $dl): ?>
                <div class="list-item">
                    <div>
                        <strong><?= htmlspecialchars($dl['titolo']) ?></strong>
                        <br>
                        <span class="date"><?= htmlspecialchars($dl['tipo']) ?></span>
                    </div>
                    <span class="date"><?= date("d/m/Y H:i", strtotime($dl['data_download'])) ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven't downloaded any books yet</p>
        <?php endif; ?>
    </div>

</div>

<script>
    //click collezione -> chiusura, o apertura e chiusura altre
    function toggleCollection(id) {
        const content = document.getElementById('col-' + id);
        if (content.style.display == "block") {
            content.style.display = "none";
        } else {
            document.querySelectorAll('.books-content').forEach(el => el.style.display = 'none');
            content.style.display = "block";
        }
    }
</script>
</body>
</html>
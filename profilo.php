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
        JOIN Formato f ON d.id_formato = f.id
        JOIN Libro l ON f.id_libro = l.id
        WHERE d.id_utente = ?
        ORDER BY d.data_download DESC
        LIMIT 20
    ");
    $stmtDownload->execute([$user_id]);
    $downloadHistory = $stmtDownload->fetchAll();

} catch (PDOException $e) {
    die("Errore nel recupero dati: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profilo - BookNest</title>
    <link rel="stylesheet" href="styles/stile_profilo.css">
</head>
<body>

<div class="container">
    <a href="index.php" class="back-link">← Torna alla Ricerca</a>

    <div class="section">
        <h2>Il Mio Profilo</h2>
        <div class="info-box">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Membro dal:</strong> <?php echo date("d/m/Y", strtotime($user['data_reg'])); ?></p>
        </div>
    </div>

    <div class="section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Le Mie Collezioni</h3>
            <a href="nuova_col.php" class="btn-create">+ Nuova Collezione</a>
        </div>

        <?php if ($collezioni): ?>
            <?php foreach ($collezioni as $col): ?>
                <div style="margin-bottom: 15px; border: 1px solid #444; border-radius: 8px; overflow: hidden;">

                    <div class="list-item collapsible-header" onclick="toggleCollection(<?php echo $col['id']; ?>)">
                        <div>
                            <strong><?php echo htmlspecialchars($col['nome']); ?></strong><br>
                            <span class="date">Creato il: <?php echo date("d/m/Y", strtotime($col['data_crea'])); ?></span>
                        </div>

                        <div class="actions-wrapper">
                            <div class="badge-count">
                                <?php echo $col['total_libri']; ?> libri
                            </div>

                            <div class="buttons-stack">
                                <form action="modifica_col.php" method="GET" style="margin: 0;">
                                    <input type="hidden" name="id" value="<?php echo $col['id']; ?>">
                                    <button type="submit" class="btn-manage btn-green" onclick="event.stopPropagation();">
                                        Modifica
                                    </button>
                                </form>

                                <form action="del_col.php" method="POST" style="margin: 0;" onsubmit="return confirm('Eliminare la collezione?');">
                                    <input type="hidden" name="id_collezione" value="<?php echo $col['id']; ?>">
                                    <button type="submit" class="btn-manage btn-red" onclick="event.stopPropagation();">
                                        Elimina
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div id="col-<?php echo $col['id']; ?>" class="books-content">
                        <?php
                        if ($col['total_libri'] > 0):
                            $titoli = explode('||', $col['titoli_libri']);
                            $ol_ids = explode('||', $col['ids_libri']);
                            $ia_ids = explode('||', $col['ia_ids_libri']);

                            for($i = 0; $i < count($titoli); $i++):
                                ?>
                                <div class="book-item">
                                    <a href="libro.php?id=<?php echo $ol_ids[$i]; ?>&ia=<?php echo $ia_ids[$i]; ?>" class="book-link">
                                        <?php echo htmlspecialchars($titoli[$i]); ?>
                                    </a>
                                    <span class="date">PDF Disponibile</span>
                                </div>
                            <?php endfor; ?>
                        <?php else: ?>
                            <p style="color: #777; font-size: 0.9em;">Nessun libro in questa collezione.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Non hai ancora creato nessuna collezione.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Cronologia delle ricerche</h3>
        <?php if ($cronologia): ?>
            <?php foreach ($cronologia as $item): ?>
                <div class="list-item">
                    <span class="search-term">"<?php echo htmlspecialchars($item['criterio_ricerca']); ?>"</span>
                    <span class="date"><?php echo date("d/m/Y H:i", strtotime($item['data_ricerca'])); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>La tua cronologia è vuota.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Cronologia Download</h3>
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
            <p>Non hai ancora scaricato nessun libro.</p>
        <?php endif; ?>
    </div>

</div>

<script>
    function toggleCollection(id) {
        const content = document.getElementById('col-' + id);
        if (content.style.display === "block") {
            content.style.display = "none";
        } else {
            // Сначала закрываем все остальные (аккордеон)
            document.querySelectorAll('.books-content').forEach(el => el.style.display = 'none');
            content.style.display = "block";
        }
    }
</script>
</body>
</html>
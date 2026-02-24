<?php
session_start();
require_once 'db.php';
if (isset($pdo)) {
    //echo "Connessione OK";
} else {
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

} catch (PDOException $e) {
    die("Errore nel recupero dati: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profilo - BookNest</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 40px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2, h3 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .section { margin-bottom: 40px; }
        .info-box { background: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .list-item { padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        .btn-create { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-create:hover { background: #218838; }
        .search-term { font-style: italic; color: #007bff; }
        .date { font-size: 0.85em; color: #888; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" style="text-decoration: none; color: #007bff;">← Torna alla Ricerca</a>

    <div class="section">
        <h2>Il Mio Profilo</h2>
        <div class="info-box">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Membro dal:</strong> <?php echo date("d/m/Y", strtotime($user['data_reg'])); ?></p>
        </div>
    </div>

    <div class="section">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Le Mie Collezioni</h3>
            <a href="nuova_col.php" class="btn-create">+ Nuova Collezione</a>
        </div>

        <?php if ($collezioni): ?>
            <style>
                .collapsible-header { cursor: pointer; transition: background 0.3s; }
                .collapsible-header:hover { background: #333; }

                .books-content {
                    display: none;
                    background: #252525;
                    padding: 10px 20px;
                    border-radius: 0 0 8px 8px;
                    border-top: 1px solid #444;
                }

                .book-item {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                    border-bottom: 1px solid #333;
                }

                .book-item:last-child { border-bottom: none; }

                .btn-read-small {
                    font-size: 12px;
                    color: #17a2b8;
                    text-decoration: none;
                    border: 1px solid #17a2b8;
                    padding: 2px 8px;
                    border-radius: 4px;
                }
            </style>

            <?php foreach ($collezioni as $col): ?>
                <div style="margin-bottom: 15px; border: 1px solid #444; border-radius: 8px; overflow: hidden;">

                    <div class="list-item collapsible-header" onclick="toggleCollection(<?php echo $col['id']; ?>)" style="margin-bottom: 0; border-bottom: none;">
                        <div>
                            <strong style="font-size: 1.1em; color: #fff;"><?php echo htmlspecialchars($col['nome']); ?></strong>
                            <br>
                            <span class="date">Creato il: <?php echo date("d/m/Y", strtotime($col['data_crea'])); ?></span>
                        </div>
                        <div style="background: #007bff; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.9em; font-weight: bold;">
                            <?php echo $col['total_libri']; ?> libri
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
                                    <a href="libro.php?id=<?php echo $ol_ids[$i]; ?>&ia=<?php echo $ia_ids[$i]; ?>" style="color: #ccc; text-decoration: none;">
                                        <?php echo htmlspecialchars($titoli[$i]); ?>
                                    </a>
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

</div>
<script>
    function toggleCollection(id) {
        const content = document.getElementById('col-' + id);

        if (content.style.display === "block") {
            content.style.display = "none";
        } else {
            document.querySelectorAll('.books-content').forEach(el => el.style.display = 'none');
            content.style.display = "block";
        }
    }
</script>
</body>
</html>
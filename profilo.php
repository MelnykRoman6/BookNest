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

    $stmtCol = $pdo->prepare("SELECT 
                                        c.id, 
                                        c.nome, 
                                        c.data_crea, 
                                        COUNT(a.id_libro) AS total_libri
                                    FROM collezione c
                                    LEFT JOIN aggiungere a ON c.id = a.id_collezione
                                    WHERE c.id_utente = ?
                                    GROUP BY c.id
                                    ORDER BY c.data_crea DESC");
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
            <a href="create_collection.php" class="btn-create">+ Nuova Collezione</a>
        </div>

        <?php if ($collezioni): ?>
            <?php foreach ($collezioni as $col): ?>
                <div class="list-item" style="align-items: center;">
                    <div>
                        <strong><?php echo htmlspecialchars($col['nome']); ?></strong>
                        <br>
                        <span class="date">Creato il: <?php echo date("d/m/Y", strtotime($col['data_crea'])); ?></span>
                    </div>

                    <div style="background: #007bff; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.9em; font-weight: bold;">
                        <?php echo $col['total_libri']; ?> libri
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

</body>
</html>
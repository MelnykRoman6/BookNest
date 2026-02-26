<?php
session_start();
require_once 'db.php';

$fileUrl = $_GET['file'] ?? '';
$title = $_GET['title'] ?? 'PDF Reading';
$id_libro = $_GET['id_libro'] ?? null;
$id_utente = $_SESSION['user_id'] ?? null;

if (!isset($pdo)) {
    die("Errore: la variabile \$pdo non è definita in db.php");
}
if (!$fileUrl || !$id_libro) {
    die("Dati mancanti.");
}

//salvataggio progresso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $id_utente) {
    $pagina = $_POST['pagina'] ?? 1;
    $id_libro_post = $_POST['id_libro'] ?? null;

    if (!$id_libro_post) {
        die("Libro mancante.");
    }

    $stmt = $pdo->prepare("
        SELECT id FROM salvare
        WHERE id_libro = ? AND id_utente = ?
    ");
    $stmt->execute([$id_libro_post, $id_utente]);

    if ($stmt->fetch()) {
        $update = $pdo->prepare("
            UPDATE salvare
            SET progresso = ?
            WHERE id_libro = ? AND id_utente = ?
        ");

        $update->execute([$pagina, $id_libro_post, $id_utente]);
    } else {
        $insert = $pdo->prepare("
            INSERT INTO salvare (id_libro, id_utente, progresso)
            VALUES (?, ?, ?)
        ");

        $insert->execute([$id_libro_post, $id_utente, $pagina]);
    }

    header("Location: reader.php?file=" . urlencode($_POST['file']) .
            "&title=" . urlencode($_POST['title']) .
            "&id_libro=" . $id_libro_post);

    exit;
}

//recupero progresso
$pagina_salvata = 1;

if ($id_utente) {
    $stmt = $pdo->prepare("SELECT progresso FROM salvare WHERE id_libro = ? AND id_utente = ?");
    $stmt->execute([$id_libro, $id_utente]);
    $row = $stmt->fetch();

    if ($row && is_numeric($row['progresso'])) {
        $pagina_salvata = (int)$row['progresso'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="styles/stile_reader.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
    </script>
</head>
<body>

<form method="POST" id="progressForm">
    <div class="top-bar">
        <p class="page-info" id="pageInfo">
            Pagina <span id="current_page"><?= $pagina_salvata ?></span> / <span id="total_pages">...</span>
        </p>

        <input type="hidden" name="pagina" id="paginaInput">
        <input type="hidden" name="id_libro" value="<?= $id_libro ?>">
        <input type="hidden" name="file" value="<?= htmlspecialchars($fileUrl) ?>">
        <input type="hidden" name="title" value="<?= htmlspecialchars($title) ?>">

        <button type="submit" class="btn-save-progress">Save progress</button>
    </div>

    <div class="viewer-container">
        <button type="button" class="side-btn" onclick="prevPage()">&#8592;</button>

        <canvas id="pdfCanvas"></canvas>

        <button type="button" class="side-btn" onclick="nextPage()">&#8594;</button>
    </div>
</form>

<script>
    const pdfUrl = "proxy_pdf.php?url=<?= urlencode($fileUrl) ?>";
    const savedPage = <?= $pagina_salvata ?>;
</script>

</body>
</html>

<!-- JS esterno -->
<script src="js/reader.js"></script>

</body>
</html>
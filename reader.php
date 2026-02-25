<?php
$fileUrl = $_GET['file'] ?? '';
$title = $_GET['title'] ?? 'PDF Reading';

if (empty($fileUrl)) {
    die("Error: File URL missing");
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?> - Reader</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background-color: #333;
            font-family: Arial, sans-serif;
        }
        .header {
            height: 50px;
            background: #222;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            justify-content: space-between;
        }
        .header a {
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
        }
        .header a:hover { color: white; }

        .pdf-container {
            height: calc(100% - 50px);
            width: 100%;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>

<div class="header">
    <span><?php echo htmlspecialchars($title); ?></span>
    <a href="javascript:window.close();">Close reader ✕</a>
</div>

<div class="pdf-container">
    <iframe src="<?php echo htmlspecialchars($fileUrl); ?>#toolbar=1" type="application/pdf">
        <p>Your browser does not support viewing PDFs
            <a href="<?php echo htmlspecialchars($fileUrl); ?>">Click here to download</a>
        </p>
    </iframe>
</div>

</body>
</html>
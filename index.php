<?php
$query = "dickens";
$url = "https://gutendex.com/books/?search=" . urlencode($query);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'MyStudentApp/1.0'); // Добавь это, чтобы API не блокировало

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    $data = json_decode($response, true);
    echo "Found: " . $data['count'] . "<br><br>";

    foreach (array_slice($data['results'], 0, 3) as $book) {
        echo "Title: " . $book['title'] . "<br>";
        echo "<img src='{$book['formats']['image/jpeg']}' alt='Book Cover' style='width:100px;'><br>";
        echo "Author: " . ($book['authors'][0]['name'] ?? 'Unknown') . "<br>";

        $downloadUrl = $book['formats']['text/plain; charset=utf-8'] ?? $book['formats']['text/plain'] ?? '#';

        if ($downloadUrl !== '#'):

            $params = http_build_query([
                'file_url' => $downloadUrl,
                'file_name' => $book['title']
            ]);
            echo "<a href='download.php?$params'>" .
                "<button type='button'>Scarica libro</button>" .
                "</a>";
        else:
            echo "<p>File non è disponibile</p>";
        endif;
        echo "<hr>";
    }
}
curl_close($ch);
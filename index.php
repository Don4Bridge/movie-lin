<?php
function normalize_lin_preserving_order($lin) {
    $parts = explode('|', $lin);
    $rawPairs = [];
    $boardNumber = 'unknown';

    // Parse all tag-value pairs in order
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $value = $parts[$i + 1];
        $rawPairs[] = [$tag, $value];

        if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $value, $matches)) {
            $boardNumber = 'board-' . $matches[1];
        }
    }

    // Rebuild LIN string exactly as authored
    $normalized = '';
    foreach ($rawPairs as [$tag, $value]) {
        $normalized .= $tag . '|' . $value . '|';
    }

    return [$normalized, $boardNumber];
}}
// Serve download if requested
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = __DIR__ . '/' . $filename;

    if (!file_exists($filepath)) {
        http_response_code(404);
        echo "File not found.";
        exit;
    }

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = trim($_POST['url']);
    $parsed = parse_url($url);
    parse_str($parsed['query'] ?? '', $query);

    if (!isset($query['lin'])) {
        echo "<p>❌ Invalid BBO movie URL. LIN string not found.</p>";
        echo "<p><a href=''>🔁 Try again</a></p>";
        exit;
    }

    $rawLin = $query['lin'];
    list($normalized, $boardNumber) = normalize_lin_preserving_order($rawLin);
    $filename = $boardNumber . '.lin';
    file_put_contents($filename, $normalized);

    $viewerUrl = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . urlencode($normalized);

    echo "<h2>✅ LIN Converted</h2>";
    echo "<p><strong>Handviewer:</strong> <a href='$viewerUrl' target='_blank'>🔗 Handviewer Link</a></p>";
    echo "<p><a href='?download=$filename'>📥 Download LIN File</a></p>";
    echo "<pre style='white-space:pre-wrap;background:#f0f0f0;padding:1em;border-radius:5px;'>$normalized</pre>";
    echo "<p><a href=''>🔁 Convert another</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>BBO Movie → Handviewer</title>
    <style>
        body { font-family: sans-serif; padding: 2em; max-width: 700px; margin: auto; }
        input[type="text"] { width: 100%; padding: 0.5em; font-size: 1em; }
        button { padding: 0.5em 1em; font-size: 1em; margin-top: 1em; }
    </style>
</head>
<body>
    <h1>🎬 Convert BBO Movie to Handviewer</h1>
    <form method="post">
        <label for="url">Paste BBO movie URL:</label><br>
        <input type="text" name="url" required placeholder="https://www.bridgebase.com/tools/movie.html?lin=..."><br>
        <button type="submit">Convert</button>
    </form>
</body>
</html>

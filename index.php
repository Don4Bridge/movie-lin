<?php
function normalize_lin_preserving_order($lin) {
    if (!is_string($lin) || trim($lin) === '') {
        error_log("❌ Empty or invalid LIN string.");
        return ['', 'unknown'];
    }

    $parts = explode('|', $lin);
    $rawPairs = [];
    $boardNumber = 'unknown';

    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $value = $parts[$i + 1];
        $rawPairs[] = [$tag, $value];

        error_log("Parsed tag: $tag | value: $value");

        if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $value, $matches)) {
            $boardNumber = 'board-' . $matches[1];
            error_log("✅ Board number detected: $boardNumber");
        }
    }

    $normalized = '';
    foreach ($rawPairs as [$tag, $value]) {
        $normalized .= $tag . '|' . $value . '|';
    }

    error_log("✅ Normalized LIN preview: " . substr($normalized, 0, 200));

    return [$normalized, $boardNumber];
}

// Handle form submission
$handviewerLink = '';
$linDownloadLink = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = $_POST['url'];
    $lin = '';

    // Extract LIN from URL
    if (preg_match('/[?&]lin=([^&]+)/', $url, $matches)) {
        $lin = urldecode($matches[1]);
        list($normalizedLin, $boardId) = normalize_lin_preserving_order($lin);

        // Handviewer link
        $handviewerLink = 'https://tinyurl.com/lin2viewer?lin=' . urlencode($normalizedLin);

        // LIN file download (data URI)
        $linFilename = $boardId . '.lin';
        $linDownloadLink = 'data:text/plain;charset=utf-8,' . urlencode($normalizedLin);
    }
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
        .output { margin-top: 2em; padding: 1em; background: #f9f9f9; border: 1px solid #ccc; }
        a.download { display: inline-block; margin-top: 0.5em; padding: 0.3em 0.6em; background: #0077cc; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🎬 Convert BBO Movie to Handviewer</h1>
    <form method="post">
        <label for="url">Paste BBO movie URL:</label><br>
        <input type="text" name="url" required placeholder="https://www.bridgebase.com/tools/movie.html?lin=..."><br>
        <button type="submit">Convert</button>
    </form>

    <?php if ($handviewerLink): ?>
    <div class="output">
        <h2>✅ Conversion Results</h2>
        <p><strong>Handviewer Link:</strong><br>
            <a href="<?= htmlspecialchars($handviewerLink) ?>" target="_blank"><?= htmlspecialchars($handviewerLink) ?></a>
        </p>
        <p><strong>Download LIN File:</strong><br>
            <a class="download" href="<?= htmlspecialchars($linDownloadLink) ?>" download="<?= htmlspecialchars($linFilename) ?>">Download <?= htmlspecialchars($linFilename) ?></a>
        </p>
    </div>
    <?php endif; ?>
</body>
</html>

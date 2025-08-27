<?php
function normalize_lin($lin) {
    $parts = explode('|', $lin);
    $normalized = '';
    $boardId = 'unknown';

    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $val = $parts[$i + 1];
        $normalized .= $tag . '|' . $val . '|';

        if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $val, $m)) {
            $boardId = 'board-' . $m[1];
        }
    }

    return [$normalized, $boardId];
}

function convert_lin_to_pbn($lin) {
    $lines = explode('|', $lin);
    $meta = [
        'Event' => 'BBO Movie',
        'Site' => 'Bridge Base Online',
        'Date' => date('Y.m.d'),
        'Board' => '1',
        'Auction' => '',
        'Play' => ''
    ];

    for ($i = 0; $i < count($lines) - 1; $i += 2) {
        $tag = $lines[$i];
        $val = $lines[$i + 1];

        if ($tag === 'mb') $meta['Auction'] .= $val . ' ';
        if ($tag === 'pc') $meta['Play'] .= $val . ' ';
    }

    $pbn = '';
    foreach ($meta as $key => $val) {
        if (in_array($key, ['Auction', 'Play'])) continue;
        $pbn .= "[$key \"$val\"]\n";
    }

    $pbn .= "\nAuction \"\"\n" . trim($meta['Auction']) . "\n\n";
    $pbn .= "Play \"\"\n" . trim($meta['Play']) . "\n";

    return $pbn;
}

$handviewerLink = '';
$linDownloadLink = '';
$pbnDownloadLink = '';
$linFilename = '';
$pbnFilename = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = $_POST['url'];

    if (preg_match('/[?&]lin=([^&]+)/', $url, $matches)) {
        $lin = urldecode($matches[1]);
        list($normalizedLin, $boardId) = normalize_lin($lin);

        $linFilename = $boardId . '.lin';
        $pbnFilename = $boardId . '.pbn';

        $pbnContent = convert_lin_to_pbn($normalizedLin);

        $linDownloadLink = 'data:text/plain;charset=utf-8,' . urlencode($normalizedLin);
        $pbnDownloadLink = 'data:text/plain;charset=utf-8,' . urlencode($pbnContent);

        $handviewerLink = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . urlencode($normalizedLin);
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
    <script>
        function revealExtra() {
            document.getElementById('extra').style.display = 'block';
        }
    </script>
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
            <a href="<?= htmlspecialchars($handviewerLink) ?>" target="_blank" onclick="revealExtra();">
                <?= htmlspecialchars($handviewerLink) ?>
            </a>
        </p>

        <div id="extra" style="display:none; margin-top:1em;">
            <p><strong>Download LIN File:</strong><br>
                <a class="download" href="<?= htmlspecialchars($linDownloadLink) ?>" download="<?= htmlspecialchars($linFilename) ?>">
                    Download <?= htmlspecialchars($linFilename) ?>
                </a>
            </p>
            <p><strong>Download PBN File:</strong><br>
                <a class="download" href="<?= htmlspecialchars($pbnDownloadLink) ?>" download="<?= htmlspecialchars($pbnFilename) ?>">
                    Download <?= htmlspecialchars($pbnFilename) ?>
                </a>
            </p>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>

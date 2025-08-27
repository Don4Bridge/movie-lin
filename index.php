<?php
require_once 'includes/lin_parser.php';
require_once 'includes/pbn_generator.php';

$handviewerLink = '';
$linDownloadLink = '';
$pbnDownloadLink = '';
$linFilename = '';
$pbnFilename = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = $_POST['url'];

    if (preg_match('/[?&]lin=([^&]+)/', $url, $matches)) {
        $lin = urldecode($matches[1]);
        list($normalizedLin, $boardId, $tags) = parse_lin($lin);

        $linFilename = $boardId . '.lin';
        $pbnFilename = $boardId . '.pbn';

        $pbnContent = generate_pbn($tags);

        $linDownloadLink = 'data:text/plain;charset=utf-8,' . urlencode($normalizedLin);
        $pbnDownloadLink = 'data:text/plain;charset=utf-8,' . urlencode($pbnContent);

        $handviewerLink = 'redirect.php?b=' . urlencode($boardId);
        $cacheDir = __DIR__ . "/lin_cache";
        if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0775, true); // Creates directory if missing
}
        file_put_contents("$cacheDir/$boardId.lin", $normalizedLin);    }
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

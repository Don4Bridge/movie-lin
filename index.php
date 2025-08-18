<?php
function extractValidLin($lin) {
    // Remove HTML tags and annotation markers
    $lin = strip_tags($lin);
    $lin = str_replace('!', '', $lin);

    // Extract only supported LIN tags
    preg_match_all('/(?:pn|md|sv|mb|pc)\|[^|]+(?:\|[^|]+)*/', $lin, $matches);
    return implode('|', $matches[0]);
}

function isValidLin($lin) {
    return strpos($lin, 'pn|') !== false &&
           strpos($lin, 'md|') !== false &&
           strpos($lin, 'mb|') !== false &&
           strpos($lin, 'pc|') !== false;
}

$cleanedLin = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawLin = trim($_POST['lin'] ?? '');

    // Decode if URL-encoded
    $decodedLin = urldecode($rawLin);

    // Extract and clean
    $cleanedLin = extractValidLin($decodedLin);

    if (isValidLin($cleanedLin)) {
        $encoded = urlencode($cleanedLin);
        header("Location: https://www.bridgebase.com/tools/handviewer.html?lin=$encoded");
        exit;
    } else {
        $error = "Invalid LIN format. Please check for missing tags like pn|, md|, mb|, pc|.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bridge Hand Viewer Redirect</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        textarea { width: 100%; font-family: monospace; }
        .error { color: red; }
        .preview { background: #f9f9f9; padding: 10px; border: 1px solid #ccc; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Bridge Hand Viewer</h1>
    <form method="post">
        <label for="lin">Paste your LIN string (raw or URL-encoded):</label><br>
        <textarea name="lin" id="lin" rows="10" placeholder="pn|...md|...mb|...pc|... or URL-encoded LIN"></textarea><br><br>
        <button type="submit">View Hand</button>
    </form>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($cleanedLin)): ?>
        <div class="preview">
            <strong>Cleaned LIN Preview:</strong><br>
            <pre><?= htmlspecialchars($cleanedLin) ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>

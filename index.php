<?php
function cleanLin($lin) {
    // Remove HTML tags
    $lin = strip_tags($lin);

    // Remove unsupported or empty tags
    $lin = preg_replace('/\|st\|\|?/', '', $lin);
    $lin = preg_replace('/\|rh\|\|?/', '', $lin);
    $lin = preg_replace('/\|ah\|.*?\|/', '', $lin);
    $lin = preg_replace('/\|an\|.*?\|/', '', $lin);

    // Remove any double pipes
    $lin = str_replace('||', '|', $lin);

    // Remove whitespace
    $lin = trim($lin);
    $lin = preg_replace('/\s+/', '', $lin);

    return $lin;
}

function isValidLin($lin) {
    // Must contain essential tags
    return strpos($lin, 'pn|') !== false &&
           strpos($lin, 'md|') !== false &&
           strpos($lin, 'mb|') !== false &&
           strpos($lin, 'pc|') !== false;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawLin = trim($_POST['lin'] ?? '');
    $cleanedLin = cleanLin($rawLin);

    if (isValidLin($cleanedLin)) {
        $encoded = urlencode($cleanedLin);
        $target = "https://www.bridgebase.com/tools/handviewer.html?lin=$encoded";
        header("Location: $target");
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
    </style>
</head>
<body>
    <h1>Bridge Hand Viewer</h1>
    <form method="post">
        <label for="lin">Paste your LIN string:</label><br>
        <textarea name="lin" id="lin" rows="10" placeholder="pn|...md|...mb|...pc|..."></textarea><br><br>
        <button type="submit">View Hand</button>
    </form>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</body>
</html>

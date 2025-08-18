<?php
function cleanLin($lin) {
    // Remove unsupported or empty tags
    $lin = preg_replace('/\|st\|\|?/', '', $lin);  // Remove st||
    $lin = preg_replace('/\|rh\|\|?/', '', $lin);  // Remove rh||
    $lin = preg_replace('/\|ah\|.*?\|/', '', $lin); // Remove ah|...|

    // Remove any double pipes || that may remain
    $lin = str_replace('||', '|', $lin);

    // Trim whitespace and ensure basic structure
    $lin = trim($lin);

    return $lin;
}

function isValidLin($lin) {
    // Basic validation: must contain key tags
    return strpos($lin, 'pn|') !== false &&
           strpos($lin, 'md|') !== false &&
           strpos($lin, 'mb|') !== false &&
           strpos($lin, 'pc|') !== false;
}

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
</head>
<body>
    <h1>Bridge Hand Viewer</h1>
    <form method="post">
        <label for="lin">Paste your LIN string:</label><br>
        <textarea name="lin" id="lin" rows="10" cols="100" placeholder="pn|...md|...mb|...pc|..."></textarea><br><br>
        <button type="submit">View Hand</button>
    </form>
    <?php if (!empty($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</body>
</html>

<?php
function isValidLin($lin) {
    // Basic check: LIN strings usually contain "pn|" and "md|" tags
    return strpos($lin, 'pn|') !== false && strpos($lin, 'md|') !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lin = trim($_POST['lin'] ?? '');
    if (isValidLin($lin)) {
        $encoded = urlencode($lin);
        $target = "https://www.bridgebase.com/tools/handviewer.html?lin=$encoded";
        header("Location: $target");
        exit;
    } else {
        $error = "Invalid LIN format.";
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
        <textarea name="lin" id="lin" rows="6" cols="80" placeholder="pn|...md|..."></textarea><br><br>
        <button type="submit">View Hand</button>
    </form>
    <?php if (!empty($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</body>
</html>

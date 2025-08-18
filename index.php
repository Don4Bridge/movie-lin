<?php
function normalize_lin($lin) {
    $parts = explode('|', $lin);
    $tagMap = [];
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $value = $parts[$i + 1];
        if (!isset($tagMap[$tag])) {
            $tagMap[$tag] = [];
        }
        $tagMap[$tag][] = $value;
    }

    $fallbacks = [
        'pn' => ['North,East,South,West'],
        'rh' => ['N,E,S,W'],
        'st' => ['BBO Tournament']
    ];

    foreach (['pn', 'rh', 'st'] as $tag) {
        if (!isset($tagMap[$tag]) || !array_filter($tagMap[$tag])) {
            $tagMap[$tag] = $fallbacks[$tag];
        }
    }

    $ordered = [];
    foreach (['pn', 'rh', 'st'] as $tag) {
        foreach ($tagMap[$tag] as $value) {
            $ordered[] = $tag . '|' . $value;
        }
        unset($tagMap[$tag]);
    }

    foreach ($tagMap as $tag => $values) {
        foreach ($values as $value) {
            $ordered[] = $tag . '|' . $value;
        }
    }

    return implode('|', $ordered);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lin'])) {
    $raw = trim($_POST['lin']);
    $normalized = normalize_lin($raw);
    $filename = 'converted.lin';

    // Save file
    file_put_contents($filename, $normalized);

    // Viewer link
    $viewerUrl = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . urlencode($normalized);

    echo "<h2>✅ LIN Converted</h2>";
    echo "<p><a href='$viewerUrl' target='_blank'>🔍 View in BBO Handviewer</a></p>";
    echo "<p><a href='$filename' download>📥 Download LIN File</a></p>";
    echo "<pre style='white-space:pre-wrap;background:#f0f0f0;padding:1em;border-radius:5px;'>$normalized</pre>";
    echo "<p><a href=''>🔁 Convert another</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>LIN Converter</title>
    <style>
        body { font-family: sans-serif; padding: 2em; max-width: 700px; margin: auto; }
        textarea { width: 100%; height: 150px; font-family: monospace; }
        button { padding: 0.5em 1em; font-size: 1em; }
    </style>
</head>
<body>
    <h1>🃏 LIN Converter for BBO Handviewer</h1>
    <form method="post">
        <label for="lin">Paste your LIN string:</label><br>
        <textarea name="lin" required></textarea><br><br>
        <button type="submit">Convert & Download</button>
    </form>
</body>
</html>

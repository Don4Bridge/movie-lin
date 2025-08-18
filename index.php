<!DOCTYPE html>
<html>
<head>
    <title>Bridge LIN Converter</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 800px; margin: auto; }
        textarea { width: 100%; height: 200px; font-family: monospace; }
        pre { background: #f4f4f4; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap; word-wrap: break-word; }
        input[type="submit"] { padding: 10px 20px; font-size: 16px; }
        a { font-weight: bold; color: #0066cc; }
    </style>
</head>
<body>
    <h2>Bridge LIN Converter</h2>
    <form method="post">
        <textarea name="lin" placeholder="Paste your LIN string here..."><?php echo isset($_POST['lin']) ? htmlspecialchars($_POST['lin']) : ''; ?></textarea><br><br>
        <input type="submit" value="Convert">
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lin'])) {
        $lin = urldecode($_POST['lin']); // Decode URL-encoded input if needed

        echo "<h3>🔍 Raw Input:</h3><pre>" . htmlspecialchars($lin) . "</pre>";

        $converted = extractValidLin($lin);

        echo "<h3>✅ Converted LIN:</h3><pre>" . htmlspecialchars($converted) . "</pre>";

        $encoded = urlencode($converted);
        $viewerUrl = "https://www.bridgebase.com/tools/handviewer.html?lin=" . $encoded;

        echo "<h3>🔗 Handviewer Link:</h3><p><a href=\"$viewerUrl\" target=\"_blank\">View in BBO Handviewer</a></p>";
    }

    function extractValidLin($lin) {
        $validTags = ['pn', 'rh', 'st', 'md', 'sv', 'ah', 'an', 'mb', 'pc', 'pg', 'mc', 'qx', 'nt', 'px'];
        $parts = explode('|', $lin);
        $tagMap = [];

        for ($i = 0; $i < count($parts) - 1; $i += 2) {
            $tag = $parts[$i];
            $value = $parts[$i + 1];
            if (in_array($tag, $validTags)) {
                if (!isset($tagMap[$tag])) {
                    $tagMap[$tag] = [];
                }
                $tagMap[$tag][] = $value;
            }
        }

        // Ensure pn| is present and first
        if (!isset($tagMap['pn'])) {
            $tagMap['pn'] = ['North,East,South,West'];
        }

        // Ensure rh| is present
        if (!isset($tagMap['rh'])) {
            $tagMap['rh'] = ['N,E,S,W'];
        }

        // Ensure st| is present
        if (!isset($tagMap['st'])) {
            $tagMap['st'] = ['BBO Tournament'];
        }

        // Build ordered output
        $ordered = [];
        foreach (['pn', 'rh', 'st'] as $tag) {
            foreach ($tagMap[$tag] as $value) {
                $ordered[] = $tag . '|' . $value;
            }
            unset($tagMap[$tag]);
        }

        foreach ($validTags as $tag) {
            if (isset($tagMap[$tag])) {
                foreach ($tagMap[$tag] as $value) {
                    $ordered[] = $tag . '|' . $value;
                }
            }
        }

        return implode('|', $ordered);
    }
    ?>
</body>
</html>

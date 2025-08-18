<!DOCTYPE html>
<html>
<head>
    <title>LIN Converter</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        textarea { width: 100%; height: 200px; }
        pre { background: #f4f4f4; padding: 10px; border: 1px solid #ccc; }
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
        $lin = $_POST['lin'];
        $converted = extractValidLin($lin);
        echo "<h3>Converted LIN:</h3><pre>" . htmlspecialchars($converted) . "</pre>";
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

        if (!isset($tagMap['pn'])) {
            $tagMap['pn'] = ['North,East,South,West'];
        }
        if (!isset($tagMap['rh'])) {
            $tagMap['rh'] = ['N,E,S,W'];
        }
        if (!isset($tagMap['st'])) {
            $tagMap['st'] = ['BBO Tournament'];
        }

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

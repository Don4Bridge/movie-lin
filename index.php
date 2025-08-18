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

function lin_to_pbn($lin) {
    $parts = explode('|', $lin);
    $tags = [];
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tags[$parts[$i]][] = $parts[$i + 1];
    }

    $players = isset($tags['pn']) ? explode(',', $tags['pn'][0]) : ['North', 'East', 'South', 'West'];

    // Validate dealer
    $validDealers = ['N', 'E', 'S', 'W'];
    $dealer = 'N';
    if (isset($tags['rh']) && strlen($tags['rh'][0]) > 0) {
        $candidate = strtoupper($tags['rh'][0][0]);
        if (in_array($candidate, $validDealers)) {
            $dealer = $candidate;
        }
    }

    $auction = isset($tags['mb']) ? $tags['mb'] : [];
    $play = isset($tags['pc']) ? $tags['pc'] : [];

    $pbn = "[Event \"BBO Tournament\"]\n";
    $pbn .= "[Site \"Bridge Base Online\"]\n";
    $pbn .= "[Board \"1\"]\n";
    $pbn .= "[Dealer \"$dealer\"]\n";
    $pbn .= "[West \"{$players[3]}\"]\n";
    $pbn .= "[North \"{$players[0]}\"]\n";
    $pbn .= "[East \"{$players[1]}\"]\n";
    $pbn .= "[South \"{$players[2]}\"]\n";

    // Auction with rotation labels
    $pbn .= "\nAuction \"$dealer\"\n";
    $rotation = ['N', 'E', 'S', 'W'];
    $startIndex = array_search($dealer, $rotation);
    $currentIndex = $startIndex;

    foreach ($auction as $bid) {
        $pbn .= $rotation[$currentIndex] . " " . $bid . "\n";
        $currentIndex = ($currentIndex + 1) % 4;
    }

    // Play with rotation labels and trick grouping
    if (!empty($play)) {
        $pbn .= "\nPlay \"$dealer\"\n";
        $currentIndex = $startIndex;

        foreach (array_chunk($play, 4) as $trick) {
            foreach ($trick as $card) {
                $pbn .= $rotation[$currentIndex] . " " . $card . "\n";
                $currentIndex = ($currentIndex + 1) % 4;
            }
            $currentIndex = $startIndex;
        }

        error_log("✅ Parsed " . count($play) . " play cards starting from dealer $dealer");
    }

    return $pbn;
}

if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = __DIR__ . '/' . $filename;

    if (!file_exists($filepath)) {
        http_response_code(404);
        echo "File not found.";
        exit;
    }

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = trim($_POST['url']);
    $parsed = parse_url($url);
    parse_str($parsed['query'] ?? '', $query);

    if (!isset($query['lin'])) {
        echo "<p>❌ Invalid BBO movie URL. LIN string not found.</p>";
        echo "<p><a href=''>🔁 Try again</a></p>";
        exit;
    }

    $rawLin = $query['lin'];
    list($normalized, $boardNumber) = normalize_lin_preserving_order($rawLin);
    $linFilename = $boardNumber . '.lin';
    file_put_contents($linFilename, $normalized);

    $pbnText = lin_to_pbn($normalized);
    $pbnFilename = $boardNumber . '.pbn';
    file_put_contents($pbnFilename, $pbnText);

    $viewerUrl = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . urlencode($normalized);

    echo "<h2>✅ LIN Converted</h2>";
    echo "<p><strong>Board:</strong> $boardNumber</p>";
    echo "<p><strong>Handviewer:</strong> <a href='$viewerUrl' target='_blank'>🔗 Handviewer Link</a></p>";
    echo "<p><a href='?download=$linFilename'>📥 Download LIN File</a></p>";
    echo "<p><a href='?download=$pbnFilename'>📥 Download PBN File</a></p>";
    echo "<pre style='white-space:pre-wrap;background:#f0f0f0;padding:1em;border-radius:5px;'>$normalized</pre>";
    echo "<p><a href=''>🔁 Convert another</a></p>";
    exit;
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
    </style>
</head>
<body>
    <h1>🎬 Convert BBO Movie to Handviewer</h1>
   <form method="post">
    <label for="url"><strong>Paste BBO movie URL:</strong></label><br>
    <input type="text" id="url" name="url" required
           placeholder="https://www.bridgebase.com/tools/movie.html?lin=..."
           style="width: 100%; padding: 0.5em; font-size: 1em; margin-top: 0.5em;"><br>
    <button type="submit" style="margin-top: 1em;">Convert</button>
    </form>
</body>
</html>

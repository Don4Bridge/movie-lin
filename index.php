<?php

function normalizeLin($lin) {
    $lin = preg_replace('/pn\|[^|]+/', 'pn|North,East,South,West', $lin);

    $lin = preg_replace_callback('/md\|([1-4])([^|]*)/', function($matches) {
        $dealer = $matches[1];
        $hands = explode(',', $matches[2]);

        $fixedHands = array_map(function($hand) {
            preg_match_all('/([SHDC])([^SHDC]*)/', $hand, $parts, PREG_SET_ORDER);
            $suits = ['S' => '', 'H' => '', 'D' => '', 'C' => ''];
            foreach ($parts as $part) {
                $suits[$part[1]] = $part[2];
            }
            return 'S' . $suits['S'] . 'H' . $suits['H'] . 'D' . $suits['D'] . 'C' . $suits['C'];
        }, $hands);

        return 'md|' . $dealer . implode(',', $fixedHands);
    }, $lin);

    $lin = preg_replace('/\s+/', '', $lin);
    return $lin;
}

function extractValidLin($lin) {
    $validTags = ['pn', 'md', 'sv', 'mb', 'pc', 'pg', 'mc', 'qx', 'nt', 'ah', 'an', 'px'];
    $parts = explode('|', $lin);
    $cleaned = [];

    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $value = $parts[$i + 1];
        if (in_array($tag, $validTags)) {
            $cleaned[] = $tag . '|' . $value;
        }
    }

    return implode('|', $cleaned);
}

// Entry point
$rawLin = $_GET['lin'] ?? $_POST['lin'] ?? '';
$decodedLin = urldecode($rawLin);

if (!$rawLin) {
    // Show input form
    echo '<!DOCTYPE html><html><head><title>Bridge LIN Cleaner</title></head><body>';
    echo '<h2>Bridge LIN Cleaner</h2>';
    echo '<form method="post">';
    echo '<label for="lin">Paste LIN string or URL:</label><br>';
    echo '<textarea name="lin" rows="6" cols="80"></textarea><br>';
    echo '<button type="submit">Clean LIN</button>';
    echo '</form>';
    echo '</body></html>';
    exit;
}

$normalizedLin = normalizeLin($decodedLin);
$cleanedLin = extractValidLin($normalizedLin);

// Handle download request
if (isset($_GET['download'])) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="hand.lin"');
    echo $cleanedLin;
    exit;
}

// Show preview and download link
$encodedCleaned = urlencode($cleanedLin);
$handviewerUrl = "https://www.bridgebase.com/tools/handviewer.html?lin=" . $encodedCleaned;

echo '<!DOCTYPE html><html><head><title>Cleaned LIN Output</title></head><body>';
echo '<h2>Cleaned LIN Output</h2>';
echo '<pre>' . htmlspecialchars($cleanedLin) . '</pre>';
echo '<p><a href="?download=1&lin=' . urlencode($rawLin) . '">⬇️ Download LIN File</a></p>';
echo '<p><a href="' . $handviewerUrl . '" target="_blank">🔍 View in BBO Handviewer</a></p>';
echo '</body></html>';

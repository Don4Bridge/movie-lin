<?php

function normalizeLin($lin) {
    // Ensure pn| is present and valid
    if (!preg_match('/pn\|([^|]+)/', $lin)) {
        $lin = 'pn|North,East,South,West|' . $lin;
    }

    // Ensure st| is present and non-empty
    if (strpos($lin, 'st|') !== false) {
        $lin = preg_replace('/st\|\|/', 'st|BBO Tournament|', $lin);
    } else {
        $lin = preg_replace('/pn\|([^|]+)\|/', 'pn|$1|st|BBO Tournament|', $lin);
    }

    // Ensure rh| is present and valid
    if (strpos($lin, 'rh|') !== false) {
        $lin = preg_replace('/rh\|\|/', 'rh|N,E,S,W|', $lin);
    } else {
        $lin = preg_replace('/st\|[^|]+\|/', '$0rh|N,E,S,W|', $lin);
    }

    // Normalize md| tag
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
    $validTags = ['pn', 'st', 'rh', 'md', 'sv', 'ah', 'an', 'mb', 'pc', 'pg', 'mc', 'qx', 'nt', 'px'];
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

    // Force pn, st, rh to be first
    $ordered = [];
    foreach (['pn', 'st', 'rh'] as $tag) {
        if (isset($tagMap[$tag])) {
            foreach ($tagMap[$tag] as $value) {
                $ordered[] = $tag . '|' . $value;
            }
            unset($tagMap[$tag]);
        }
    }

    // Add remaining tags
    foreach ($validTags as $tag) {
        if (isset($tagMap[$tag])) {
            foreach ($tagMap[$tag] as $value) {
                $ordered[] = $tag . '|' . $value;
            }
        }
    }

    return implode('|', $ordered);
}

function ensureFullPlay($lin) {
    $playCount = substr_count($lin, 'pc|');
    if ($playCount < 52) {
        $missing = 52 - $playCount;
        for ($i = 0; $i < $missing; $i++) {
            $lin .= '|pc|XX';
        }
    }
    if (substr($lin, -1) !== '|') {
        $lin .= '|';
    }
    return $lin;
}

// Entry point
$rawLin = $_GET['lin'] ?? $_POST['lin'] ?? '';
$decodedLin = urldecode($rawLin);

if (!$rawLin) {
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
$finalLin = ensureFullPlay($cleanedLin);

// Handle download
if (isset($_GET['download'])) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="hand.lin"');
    echo $finalLin;
    exit;
}

// Show preview and links
$encodedFinal = urlencode($finalLin);
$handviewerUrl = "https://www.bridgebase.com/tools/handviewer.html?lin=" . $encodedFinal;

echo '<!DOCTYPE html><html><head><title>Cleaned LIN Output</title></head><body>';
echo '<h2>Cleaned LIN Output</h2>';
echo '<pre>' . htmlspecialchars($finalLin) . '</pre>';
echo '<p><a href="?download=1&lin=' . urlencode($rawLin) . '">⬇️ Download LIN File</a></p>';
echo '<p><a href="' . $handviewerUrl . '" target="_blank">🔍 View in BBO Handviewer</a></p>';
echo '</body></html>';

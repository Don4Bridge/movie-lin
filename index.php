<?php

// Normalize player names and md| tag
function normalizeLin($lin) {
    // Step 1: Normalize player names
    $lin = preg_replace('/pn\|[^|]+/', 'pn|North,East,South,West', $lin);

    // Step 2: Normalize md| tag
    $lin = preg_replace_callback('/md\|([1-4])([^|]*)/', function($matches) {
        $dealer = $matches[1];
        $hands = explode(',', $matches[2]);

        $fixedHands = array_map(function($hand) {
            // Extract suit segments
            preg_match_all('/([SHDC])([^SHDC]*)/', $hand, $parts, PREG_SET_ORDER);

            // Initialize all suits
            $suits = ['S' => '', 'H' => '', 'D' => '', 'C' => ''];

            foreach ($parts as $part) {
                $suits[$part[1]] = $part[2];
            }

            // Reconstruct hand in correct order
            return 'S' . $suits['S'] . 'H' . $suits['H'] . 'D' . $suits['D'] . 'C' . $suits['C'];
        }, $hands);

        return 'md|' . $dealer . implode(',', $fixedHands);
    }, $lin);

    // Step 3: Optional cleanup
    $lin = preg_replace('/\s+/', '', $lin);

    return $lin;
}

// Optional: Strip unsupported tags or sanitize further
function extractValidLin($lin) {
    // Keep only known tags
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
$normalizedLin = normalizeLin($decodedLin);
$cleanedLin = extractValidLin($normalizedLin);

// Output as plain text
header('Content-Type: text/plain');
echo $cleanedLin;

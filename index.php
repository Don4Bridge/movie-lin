<?php
// Input LIN string (can be replaced with $_GET['lin'] or file input)
$lin = "pn|klegro,DonFree,larcher95,martfree|st||md|3SAHJ53DQ8532CQJ72,SK5HKQ98DJT4CK964,SQT972HAT2D976C83,SJ8643H764DAKCAT5|sv|o|rh||ah|Board";

// Step 1: Parse LIN into tag-value pairs
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

// Step 2: Ensure pn, rh, st exist and are non-empty
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

// Step 3: Reorder tags: pn, rh, st first, then rest
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

// Step 4: Output normalized LIN string
$normalized = implode('|', $ordered);
echo $normalized;

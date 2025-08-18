function normalize_lin_with_board($lin) {
    $parts = explode('|', $lin);
    $boardNumber = 'unknown';
    $rawPairs = [];

    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $value = $parts[$i + 1];
        $rawPairs[] = [$tag, $value];

        if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $value, $matches)) {
            $boardNumber = 'board-' . $matches[1];
        }
    }

    // Fallbacks for pn, rh, st
    $fallbacks = [
        'pn' => ['North,East,South,West'],
        'rh' => ['N,E,S,W'],
        'st' => ['BBO Tournament']
    ];

    $tagsToInject = [];
    foreach (['pn', 'rh', 'st'] as $tag) {
        $found = false;
        foreach ($rawPairs as [$t, $v]) {
            if ($t === $tag && trim($v) !== '') {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $tagsToInject[] = [$tag, $fallbacks[$tag][0]];
        }
    }

    // Inject pn, rh, st at the beginning
    $finalPairs = array_merge($tagsToInject, $rawPairs);

    // Rebuild LIN string
    $normalized = '';
    foreach ($finalPairs as [$tag, $value]) {
        $normalized .= $tag . '|' . $value . '|';
    }

    return [$normalized, $boardNumber];
}

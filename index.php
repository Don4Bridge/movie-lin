function normalize_lin_preserving_order($lin) {
    $parts = explode('|', $lin);
    $rawPairs = [];
    $boardNumber = 'unknown';

    // Parse into tag-value pairs
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $value = $parts[$i + 1];
        $rawPairs[] = [$tag, $value];

        if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $value, $matches)) {
            $boardNumber = 'board-' . $matches[1];
        }
    }

    // Inject missing pn, rh, st tags at the top
    $fallbacks = [
        'pn' => 'North,East,South,West',
        'rh' => 'N,E,S,W',
        'st' => 'BBO Tournament'
    ];

    $existingTags = array_column($rawPairs, 0);
    $tagsToInject = [];

    foreach ($fallbacks as $tag => $defaultValue) {
        if (!in_array($tag, $existingTags)) {
            $tagsToInject[] = [$tag, $defaultValue];
        }
    }

    // Combine injected tags + original pairs
    $finalPairs = array_merge($tagsToInject, $rawPairs);

    // Rebuild LIN string
    $normalized = '';
    foreach ($finalPairs as [$tag, $value]) {
        $normalized .= $tag . '|' . $value . '|';
    }

    return [$normalized, $boardNumber];
}

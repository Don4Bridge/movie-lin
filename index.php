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

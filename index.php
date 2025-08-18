function normalizeLin($lin) {
    // Preserve pn| if it has 4 names, else fallback
    $lin = preg_replace_callback('/pn\|([^|]+)/', function($matches) {
        $names = explode(',', $matches[1]);
        return count($names) === 4
            ? 'pn|' . implode(',', $names)
            : 'pn|North,East,South,West';
    }, $lin);

    // Ensure st| tag is present and non-empty
    if (strpos($lin, 'st|') !== false) {
        $lin = preg_replace('/st\|\|/', 'st|BBO Tournament|', $lin);
    } else {
        $lin = 'st|BBO Tournament|' . $lin;
    }

    // Ensure rh| tag is present
    if (strpos($lin, 'rh|') === false) {
        $lin .= '|rh|N,E,S,W|';
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

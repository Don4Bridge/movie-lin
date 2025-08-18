function normalizeLin($lin) {
    // Remove whitespace
    $lin = preg_replace('/\s+/', '', $lin);

    // Extract pn| if present
    preg_match('/pn\|([^|]+)/', $lin, $pnMatch);
    $pn = isset($pnMatch[1]) ? $pnMatch[1] : 'North,East,South,West';

    // Extract rh| if present
    preg_match('/rh\|([^|]+)/', $lin, $rhMatch);
    $rh = isset($rhMatch[1]) && $rhMatch[1] !== '' ? $rhMatch[1] : 'N,E,S,W';

    // Extract st| if present
    preg_match('/st\|([^|]*)/', $lin, $stMatch);
    $st = isset($stMatch[1]) && $stMatch[1] !== '' ? $stMatch[1] : 'BBO Tournament';

    // Remove existing pn|, rh|, st| to avoid duplicates
    $lin = preg_replace('/pn\|[^|]+/', '', $lin);
    $lin = preg_replace('/rh\|[^|]*/', '', $lin);
    $lin = preg_replace('/st\|[^|]*/', '', $lin);

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

    // Rebuild header
    $header = 'pn|' . $pn . '|rh|' . $rh . '|st|' . $st . '|';

    return $header . $lin;
}

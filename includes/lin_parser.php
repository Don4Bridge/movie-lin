<?php
function parse_lin($lin) {
    $parts = explode('|', $lin);
    $tags = [];
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tags[] = [$parts[$i], $parts[$i + 1]];
    }

    $board = 'unknown';
    foreach ($tags as [$tag, $val]) {
        if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $val, $m)) {
            $board = 'board-' . $m[1];
            break;
        }
    }

    $normalized = '';
    foreach ($tags as [$tag, $val]) {
        $normalized .= $tag . '|' . $val . '|';
    }

    return [$normalized, $board, $tags];
}

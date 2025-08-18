<?php

function normalize_lin_with_board($lin) {
    // Split LIN string into tag-value pairs
    $parts = explode('|', $lin);
    $rawPairs = [];
    $boardNumber = 'unknown';

    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $value = $parts[$i + 1];
        $rawPairs[] = [$tag, $value];

        // Extract board number from ah|Board X
        if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $value, $matches)) {
            $boardNumber = 'board-' . $matches[1];
        }
    }

    // Define fallback values for required tags
    $fallbacks = [
        'pn' => 'North,East,South,West',
        'rh' => 'N,E,S,W',
        'st' => 'BBO Tournament'
    ];

    // Check for missing tags and prepare injections
    $existingTags = array_column($rawPairs, 0);
    $tagsToInject = [];

    foreach ($fallbacks as $tag => $defaultValue) {
        if (!in_array($tag, $existingTags)) {
            $tagsToInject[] = [$tag, $defaultValue];
        }
    }

    // Inject missing tags at the beginning
    $finalPairs = array_merge($tagsToInject, $rawPairs);

    // Rebuild normalized LIN string
    $normalized = '';
    foreach ($finalPairs as [$tag, $value]) {
        $normalized .= $tag . '|' . $value . '|';
    }

    return [$normalized, $boardNumber];
}

// Example usage
$lin = 'pn|dgdd1452,DonFree,Artemis825,martfree|st||md|4SA95HA83DQ98542CJ,ST8HKQ42D3CKT7543,SKQ72H9765DJ6C982,SJ643HJTDAKT7CAQ6|sv|e|rh||ah|Board 6|mb|1N|mb|2C|an|maj-min or long D|mb|P|mb|2D|mb|P|mb|P|mb|2N|mb|P|mb|P|mb|P|...';

list($normalizedLin, $boardId) = normalize_lin_with_board($lin);

echo "Board ID: $boardId\n";
echo "Normalized LIN:\n$normalizedLin\n";

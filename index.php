<?php
function patchLinAfterLastCard($lin) {
    $parts = explode('|', $lin);
    $patchedParts = [];
    $lastPcIndex = -1;

    // Track positions of pc| tags
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $patchedParts[] = $parts[$i];
        $patchedParts[] = $parts[$i + 1];
        if ($parts[$i] === 'pc') {
            $lastPcIndex = $i;
        }
    }

    // If no pc| found, return original
    if ($lastPcIndex === -1) {
        echo "No pc| tags found.\n";
        return $lin;
    }

    // Remove anything after last pc| tag
    $endIndex = $lastPcIndex + 2;
    $patchedParts = array_slice($patchedParts, 0, $endIndex);

    // Append pg| and mc| if not already present
    $endingTags = array_slice($parts, $endIndex);
    $hasPg = in_array('pg', $endingTags);
    $hasMc = in_array('mc', $endingTags);

    if (!$hasPg) {
        $patchedParts[] = 'pg';
        $patchedParts[] = '';
    }
    if (!$hasMc) {
        $patchedParts[] = 'mc';
        $patchedParts[] = '';
    }

    // Rebuild LIN string
    $patchedLin = implode('|', $patchedParts);
    return $patchedLin;
}

// Example usage
$rawLin = 'pn|N,S,E,W|md|1SKQJHKTDC9876,SA987H5432DQT5C4,S5432H987D32C32,S6H6DAKJACAKQJ|mb|1S|mb|2S|mb|3S|mb|ap|pc|C4|pc|C2|pc|C3|pc|C6|pc|D5|pc|D2|pc|D3|pc|D6|pc|H2|pc|H3|pc|H4|pc|H5|pc|S2|pc|S3|pc|S4|pc|S5|pc|C5|pc|C7|pc|C8|pc|C9|pc|D7|pc|D8|pc|D9|pc|DT|pc|H6|pc|H7|pc|H8|pc|H9|pc|S6|pc|S7|pc|S8|pc|S9|pc|CA|pc|CK|pc|CQ|pc|CJ|pc|DA|pc|DK|pc|DQ|pc|DJ|pc|HA|pc|HK|pc|HQ|pc|HJ|pc|SA|pc|SK|pc|SQ|pc|SJ';

$patchedLin = patchLinAfterLastCard($rawLin);
echo "Patched LIN:\n$patchedLin\n";

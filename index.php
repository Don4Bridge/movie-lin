<?php
function patchLinEnding($lin) {
    $parts = explode('|', $lin);
    $pcCount = 0;
    $hasPg = false;
    $hasMc = false;

    // Scan for pc| tags and ending tags
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        if ($parts[$i] === 'pc') {
            $pcCount++;
        } elseif ($parts[$i] === 'pg') {
            $hasPg = true;
        } elseif ($parts[$i] === 'mc') {
            $hasMc = true;
        }
    }

    echo "Cards played: $pcCount\n";

    // Append missing ending tags
    if (!$hasPg) {
        $lin .= '|pg|';
        echo "Appended pg|\n";
    }
    if (!$hasMc) {
        $lin .= '|mc|';
        echo "Appended mc|\n";
    }

    return $lin;
}

// Example usage
$rawLin = 'pn|N,S,E,W|md|1SKQJHKTDC9876,SA987H5432DQT5C4,S5432H987D32C32,S6H6DAKJACAKQJ|mb|1S|mb|2S|mb|3S|mb|ap|pc|C4|pc|C2|pc|C3|pc|C6|pc|D5|pc|D2|pc|D3|pc|D6|pc|H2|pc|H3|pc|H4|pc|H5|pc|S2|pc|S3|pc|S4|pc|S5|pc|C5|pc|C7|pc|C8|pc|C9|pc|D7|pc|D8|pc|D9|pc|DT|pc|H6|pc|H7|pc|H8|pc|H9|pc|S6|pc|S7|pc|S8|pc|S9|pc|CA|pc|CK|pc|CQ|pc|CJ|pc|DA|pc|DK|pc|DQ|pc|DJ|pc|HA|pc|HK|pc|HQ|pc|HJ|pc|SA|pc|SK|pc|SQ|pc|SJ';

$patchedLin = patchLinEnding($rawLin);
echo "\nPatched LIN:\n$patchedLin\n";

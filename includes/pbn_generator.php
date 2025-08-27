<?php
function generate_pbn($tags) {
    $meta = [
        'Event' => 'BBO Movie',
        'Site' => 'Bridge Base Online',
        'Date' => date('Y.m.d'),
        'Board' => '1',
        'West' => '', 'North' => '', 'East' => '', 'South' => '',
        'Dealer' => '', 'Vulnerable' => '',
        'Auction' => '', 'Play' => ''
    ];

    foreach ($tags as [$tag, $val]) {
        switch ($tag) {
            case 'md':
                $hands = explode(',', $val);
                foreach ($hands as $hand) {
                    $seat = substr($hand, 0, 1);
                    $cards = substr($hand, 1);
                    switch ($seat) {
                        case '1': $meta['South'] = $cards; break;
                        case '2': $meta['West'] = $cards; break;
                        case '3': $meta['North'] = $cards; break;
                        case '4': $meta['East'] = $cards; break;
                    }
                }
                break;
            case 'sv': $meta['Vulnerable'] = $val; break;
            case 'pc': $meta['Play'] .= $val . ' '; break;
            case 'mb': $meta['Auction'] .= $val . ' '; break;
            case 'md': $meta['Dealer'] = 'N'; break; // Placeholder
        }
    }

    $pbn = '';
    foreach ($meta as $key => $val) {
        if (in_array($key, ['Auction', 'Play'])) continue;
        $pbn .= "[$key \"$val\"]\n";
    }

    $pbn .= "\nAuction \"\"\n" . trim($meta['Auction']) . "\n\n";
    $pbn .= "Play \"\"\n" . trim($meta['Play']) . "\n";

    return $pbn;
}

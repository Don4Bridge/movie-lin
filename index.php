<?php
function normalize_lin_preserving_order($lin) {
    if (!is_string($lin) || trim($lin) === '') {
        error_log("❌ Empty or invalid LIN string.");
        return ['', 'unknown'];
    }

    $parts = explode('|', $lin);
    $rawPairs = [];
    $boardNumber = 'unknown';

    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tag = $parts[$i];
        $value = $parts[$i + 1];
        $rawPairs[] = [$tag, $value];

        error_log("Parsed tag: $tag | value: $value");

        if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $value, $matches)) {
            $boardNumber = 'board-' . $matches[1];
            error_log("✅ Board number detected: $boardNumber");
        }
    }

    $normalized = '';
    foreach ($rawPairs as [$tag, $value]) {
        $normalized .= $tag . '|' . $value . '|';
    }

    error_log("✅ Normalized LIN preview: " . substr($normalized, 0, 200));

    return [$normalized, $boardNumber];
}
function lin_to_pbn(string $lin): string {
    $tags = parse_lin_tags($lin); // Assumes you have a robust parser
    $rotation = ['N', 'E', 'S', 'W'];

    // 🔍 Board Info
    $boardTitle = $tags['ah'][0] ?? 'Board 1';
    preg_match('/Board\s+(\d+)/i', $boardTitle, $matches);
    $boardNum = $matches[1] ?? '1';

    // 🔍 Players
    $players = isset($tags['pn']) ? explode(',', $tags['pn'][0]) : ['North', 'East', 'South', 'West'];

    // 🔍 Deal
    $mdLine = isset($tags['md'][0]) ? 'md|' . $tags['md'][0] : '';
    [$dealer, $dealTag] = parse_md_to_pbn_deal($mdLine);

    // 🔍 Vulnerability
    $vulMap = ['o' => 'None', 'b' => 'Both', 'n' => 'NS', 'e' => 'EW'];
    $vulCode = $tags['sv'][0] ?? 'o';
    $vul = $vulMap[$vulCode] ?? 'None';

    // 🔍 Auction
    $auctionRaw = $tags['mb'] ?? [];
    $auction = array_map(fn($mb) => str_starts_with($mb, 'mb|') ? substr($mb, 3) : $mb, $auctionRaw);
    $contractBid = get_final_contract($auction);
    $contractBid = preg_replace('/^(\d)N$/', '$1NT', $contractBid);
    $declarer = determine_declarer($dealer, $auction, $contractBid);

    // 🔍 Play
    $play = $tags['pc'] ?? [];

    // 🔍 Result (placeholder)
    $result = 7;

    // 🧾 PBN Header
    $pbn = "[Event \"BBO Tournament\"]\n";
    $pbn .= "[Site \"Bridge Base Online\"]\n";
    $pbn .= "[Date \"" . date('Y.m.d') . "\"]\n";
    $pbn .= "[Board \"$boardNum\"]\n";
    $pbn .= "[Dealer \"$dealer\"]\n";
    $pbn .= "[Vulnerable \"$vul\"]\n";
    $pbn .= "[Contract \"$contractBid\"]\n";
    $pbn .= "[Declarer \"$declarer\"]\n";
    $pbn .= "[Result \"$result\"]\n";
    $pbn .= "[West \"{$players[3]}\"]\n";
    $pbn .= "[North \"{$players[0]}\"]\n";
    $pbn .= "[East \"{$players[1]}\"]\n";
    $pbn .= "[South \"{$players[2]}\"]\n";
    $pbn .= $dealTag . "\n";

    // 🧾 Auction Block
    $pbn .= "\nAuction \"$dealer\"\n";
    foreach ($auction as $i => $bid) {
        $pbn .= $bid;
        $pbn .= ($i + 1) % 4 === 0 ? "\n" : " ";
    }

    // 🧾 Play Block
    $pbn .= "\nPlay \"$declarer\"\n";
    $leadIndex = (array_search($dealer, $rotation) + 1) % 4;
    for ($i = 0; $i < count($play); $i += 4) {
        for ($j = 0; $j < 4; $j++) {
            $seat = $rotation[($leadIndex + $j) % 4];
            $card = $play[$i + $j] ?? '';
            if ($card !== '') {
                $pbn .= "$seat $card\n";
            }
        }
    }

    return $pbn;
}?>

<!DOCTYPE html>
<html>
<head>
    <title>BBO Movie → Handviewer</title>
    <style>
        body { font-family: sans-serif; padding: 2em; max-width: 700px; margin: auto; }
        input[type="text"] { width: 100%; padding: 0.5em; font-size: 1em; }
        button { padding: 0.5em 1em; font-size: 1em; margin-top: 1em; }
    </style>
</head>
<body>
    <h1>🎬 Convert BBO Movie to Handviewer</h1>
    <form method="post">
        <label for="url">Paste BBO movie URL:</label><br>
        <input type="text" name="url" required placeholder="https://www.bridgebase.com/tools/movie.html?lin=..."><br>
        <button type="submit">Convert</button>
    </form>
</body>
</html>

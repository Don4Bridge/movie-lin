    <?php
    function normalize_lin($lin) {
        $parts = explode('|', $lin);
        $normalized = '';
        $boardId = 'unknown';
    
        for ($i = 0; $i < count($parts) - 1; $i += 2) {
            $tag = $parts[$i];
            $val = $parts[$i + 1];
            $normalized .= $tag . '|' . $val . '|';
    
            if ($tag === 'ah' && preg_match('/Board\s+(\d+)/i', $val, $m)) {
                $boardId = 'board-' . $m[1];
            }
        }
    
        return [$normalized, $boardId];
    }
    
    function convert_lin_to_pbn($lin) {
        $lines = explode('|', $lin);
        $auction = [];
        $play = [];
        $dealer = 'N';
        $vul = 'None';
        $board = '1';
        $deal = '';
    
        foreach ($lines as $i => $tag) {
            $next = $lines[$i + 1] ?? '';
    
            switch ($tag) {
                case 'mb':
                    $bid = strtoupper($next);
    
                    if ($bid === 'D') {
                        $bid = 'X';
                    }
    
                    if (preg_match('/^[1-7]N$/', $bid)) {
                        $bid = str_replace('N', 'NT', $bid);
                    }
    
                    $auction[] = $bid;
                    break;
    
                case 'pc':
                    $play[] = strtoupper($next);
                    break;
    
                case 'ah':
                    if (preg_match('/Board\s+(\d+)/i', $next, $m)) {
                        $board = $m[1];
                    }
                    break;
    
                case 'sv':
                    $vulMap = ['n' => 'NS', 'e' => 'EW', 'b' => 'Both', 'o' => 'None', '-' => 'None'];
                    $vul = $vulMap[strtolower($next)] ?? 'None';
                    break;
    
                case 'md':
                    $dealerMap = ['1' => 'S', '2' => 'W', '3' => 'N', '4' => 'E'];
                    $dealerCode = substr($next, 0, 1);
                    $dealer = $dealerMap[$dealerCode] ?? 'N';
    
                    $hands = explode(',', substr($next, 1));
                    while (count($hands) < 4) {
                        $hands[] = '';
                    }
    
                    $seatOrder = ['N', 'E', 'S', 'W'];
                    $linOrder = ['S', 'W', 'N', 'E'];
                    $handsBySeat = array_combine($linOrder, $hands);
                    // Fill missing hand if only 3 are present
                    $nonEmptyHands = array_filter($handsBySeat, fn($h) => trim($h) !== '');
                    if (count($nonEmptyHands) === 3) {
                        $suits = ['S', 'H', 'D', 'C'];
                        $deck = [];
                        foreach ($suits as $suit) {
                            foreach (str_split('AKQJT98765432') as $rank) {
                                $deck[] = $rank . $suit;
                            }
                        }
    
                        $knownCards = [];
                        foreach ($nonEmptyHands as $hand) {
                            $currentSuit = null;
                            foreach (str_split($hand) as $char) {
                                if (in_array($char, $suits)) {
                                    $currentSuit = $char;
                                } elseif ($currentSuit) {
                                    $knownCards[] = $char . $currentSuit;
                                }
                            }
                        }
    
                        $missingCards = array_diff($deck, $knownCards);
                        $missingHand = [];
                        foreach ($suits as $suit) {
                            $missingHand[$suit] = '';
                        }
                        foreach ($missingCards as $card) {
                            $rank = substr($card, 0, 1);
                            $suit = substr($card, 1, 1);
                            $missingHand[$suit] .= $rank;
                        }
    
                        $missingHandStr = implode('', array_map(fn($suit) => $suit . $missingHand[$suit], $suits));
                        $missingSeat = array_diff(['S', 'W', 'N', 'E'], array_keys($nonEmptyHands));
                        if (count($missingSeat) === 1) {
                            $handsBySeat[array_values($missingSeat)[0]] = $missingHandStr;
                        }
                    }
    
                    $dealerIndex = array_search($dealer, $seatOrder);
                    $rotated = [];
                    for ($j = 0; $j < 4; $j++) {
                        $seat = $seatOrder[($dealerIndex + $j) % 4];
                        $rotated[] = $handsBySeat[$seat] ?? '';
                    }
    
                    function format_hand($hand) {
                        if (trim($hand) === '') {
                            return '-';
                        }
    
                        $suits = ['S' => '', 'H' => '', 'D' => '', 'C' => ''];
                        $currentSuit = null;
    
                        foreach (str_split($hand) as $char) {
                            if (isset($suits[$char])) {
                                $currentSuit = $char;
                            } elseif ($currentSuit) {
                                $suits[$currentSuit] .= $char;
                            }
                        }
    
                        return implode('.', [$suits['S'], $suits['H'], $suits['D'], $suits['C']]);
                    }
    
                    $formatted = array_map('format_hand', $rotated);
                    $deal = $dealer . ':' . implode(' ', $formatted);
                    break;
            }
        }
    
        // Extract player names from pn| tag
        $names = ['North' => '', 'East' => '', 'South' => '', 'West' => ''];
        foreach ($lines as $i => $tag) {
            if ($tag === 'pn') {
                $rawNames = explode('^', $lines[$i + 1] ?? '');
                if (count($rawNames) === 4) {
                    $names = [
                        'North' => $rawNames[0],
                        'East'  => $rawNames[1],
                        'South' => $rawNames[2],
                        'West'  => $rawNames[3],
                    ];
                }
                break;
            }
        }
    
        $contractBid = '';
        $contractIndex = -1;
        for ($i = count($auction) - 1; $i >= 0; $i--) {
            if (!in_array($auction[$i], ['P', 'X', 'XX'])) {
                $contractBid = $auction[$i];
                $contractIndex = $i;
                break;
            }
        }
    
        $declarer = '';
        if ($contractBid !== '') {
            $strain = preg_replace('/^[1-7]/', '', $contractBid);
            $seatOrder = ['W', 'N', 'E', 'S'];
            $dealerIndex = array_search($dealer, $seatOrder);
            $seats = [];
            for ($i = 0; $i < count($auction); $i++) {
                $seats[] = $seatOrder[($dealerIndex + $i) % 4];
            }
    
            $declaringSide = in_array($seats[$contractIndex], ['N', 'S']) ? ['N', 'S'] : ['E', 'W'];
            for ($i = 0; $i <= $contractIndex; $i++) {
                if (strpos($auction[$i], $strain) !== false && in_array($seats[$i], $declaringSide)) {
                    $declarer = $seats[$i];
                    break;
                }
            }
        }
    
        // Determine opening leader
        $openingLeader = '';
        if ($declarer !== '') {
            $seatOrder = ['N', 'E', 'S', 'W'];
            $leaderIndex = (array_search($declarer, $seatOrder) + 1) % 4;
            $openingLeader = $seatOrder[$leaderIndex];
        }
    
        $pbn = "[Event \"BBO Movie\"]\n";
        $pbn .= "[Site \"Bridge Base Online\"]\n";
        $pbn .= "[Date \"" . date('Y.m.d') . "\"]\n";
        $pbn .= "[Board \"$board\"]\n";
        $pbn .= "[Dealer \"$dealer\"]\n";
        $pbn .= "[Vulnerable \"$vul\"]\n";
        if ($deal) {
            $pbn .= "[Deal \"$deal\"]\n";
        }
        if ($contractBid) {
            $pbn .= "[Contract \"$contractBid\"]\n";
        }
        if ($declarer) {
            $pbn .= "[Declarer \"$declarer\"]\n";
        }
    
        // Add player names
        $pbn .= "[North \"{$names['North']}\"]\n";
        $pbn .= "[East \"{$names['East']}\"]\n";
        $pbn .= "[South \"{$names['South']}\"]\n";
        $pbn .= "[West \"{$names['West']}\"]\n";
    
        $pbn .= "[Auction \"$dealer\"]\n";
        for ($i = 0; $i < count($auction); $i += 4) {
            $pbn .= implode(' ', array_slice($auction, $i, 4)) . "\n";
        }
    
        // Group play into tricks of 4 cards each
        $tricks = [];
        for ($i = 0; $i < count($play); $i += 4) {
            $trick = array_slice($play, $i, 4);
            $tricks[] = implode(' ', $trick);
        }
    
     $pbn .= "[Play \"$openingLeader\"]\n";
foreach ($tricks as $trick) {
    $pbn .= "$trick\n";
}

return $pbn;
}


   
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
        $url = $_POST['url'];
    
        if (preg_match('/[?&]lin=([^&]+)/', $url, $matches)) {
            $lin = urldecode($matches[1]);
            list($normalizedLin, $boardId) = normalize_lin($lin);
             $linContent = '';
            $pbnContent = '';
            $linFilename = '';
            $pbnFilename = '';
            $linFilename = $boardId . '.lin';
            $pbnFilename = $boardId . '.pbn';
    
            $linContent = $normalizedLin;
            $pbnContent = convert_lin_to_pbn($normalizedLin);
    
            $handviewerLink = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . rawurlencode($normalizedLin);
        }
    }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>BBO Movie → Handviewer</title>
        <style>
            body { font-family: sans-serif; padding: 2em; max-width: 800px; margin: auto; }
            input[type="text"] { width: 100%; padding: 0.5em; font-size: 1em; }
            button { padding: 0.5em 1em; font-size: 1em; margin-top: 1em; }
            .output { margin-top: 2em; padding: 1em; background: #f9f9f9; border: 1px solid #ccc; }
            textarea { width: 100%; height: 200px; font-family: monospace; margin-top: 1em; }
            a.download { display: inline-block; margin-top: 0.5em; padding: 0.3em 0.6em; background: #0077cc; color: white; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <h1>🎬 Convert BBO Movie to Handviewer</h1>
        <form method="post">
            <label for="url">Paste BBO movie URL:</label><br>
            <input type="text" name="url" required placeholder="https://www.bridgebase.com/tools/movie.html?lin=..."><br>
            <button type="submit">Convert</button>
        </form>
    
        <?php if ($handviewerLink): ?>
        <div class="output">
            <h2>✅ Conversion Results</h2>
            <p><strong>Handviewer Link:</strong><br>
                <a href="<?= htmlspecialchars($handviewerLink) ?>" target="_blank">
                    <?= htmlspecialchars($handviewerLink) ?>
                </a>
            </p>
    
            <h3>📥 LIN File: <?= htmlspecialchars($linFilename) ?></h3>
            <textarea readonly><?= htmlspecialchars($linContent) ?></textarea><br>
            <a class="download" href="data:text/plain;charset=utf-8,<?= rawurlencode($linContent) ?>" download="<?= htmlspecialchars($linFilename) ?>">Download LIN</a>
    
            <h3>📥 PBN File: <?= htmlspecialchars($pbnFilename) ?></h3>
            <textarea readonly><?= htmlspecialchars($pbnContent) ?></textarea><br>
            <a class="download" id="downloadPBN" href="#" download="<?= htmlspecialchars($pbnFilename) ?>">Download PBN</a>
        <script>
            const pbnContent = <?= json_encode($pbnContent) ?>;
            const pbnBlob = new Blob([pbnContent], { type: 'text/plain;charset=utf-8' });
            const pbnUrl = URL.createObjectURL(pbnBlob);
            document.getElementById('downloadPBN').href = pbnUrl;
    </script>  </div>
        <?php endif; ?>
    </body>
    </html>

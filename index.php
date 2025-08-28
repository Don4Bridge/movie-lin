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

function extract_names_from_lin($normalizedLin) {
    $parts = explode('|', $normalizedLin);
    $names = ['North' => '', 'East' => '', 'South' => '', 'West' => ''];

    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        if ($parts[$i] === 'pn') {
            $raw = str_replace('+', ' ', $parts[$i + 1]);
            $raw = urldecode($raw);

            // Try both delimiters
            $rawNames = strpos($raw, '^') !== false ? explode('^', $raw) : explode(',', $raw);

            if (count($rawNames) === 4) {
                $names = [
                    'North' => trim($rawNames[0]),
                    'East'  => trim($rawNames[1]),
                    'South' => trim($rawNames[2]),
                    'West'  => trim($rawNames[3]),
                ];
            }
            break;
        }
    }

    return $names;
}

   function format_hand($hand) {
    $hand = str_replace('+', '', $hand);
    $hand = trim($hand);
    if ($hand === '') return '. . .';

    $suits = ['S' => '', 'H' => '', 'D' => '', 'C' => ''];
    $currentSuit = null;

    foreach (str_split($hand) as $char) {
        if (isset($suits[$char])) {
            $currentSuit = $char;
        } elseif ($currentSuit !== null) {
            $suits[$currentSuit] .= $char;
        }
    }

    return "{$suits['S']}.{$suits['H']}.{$suits['D']}.{$suits['C']}";
}
    

function convert_lin_to_pbn($lin) {
    $lin = urldecode($lin);
    $lines = explode('|', $lin);

    foreach ($lines as &$segment) {
        $segment = str_replace('+', ' ', $segment);
    }
    unset($segment);

    $auction = [];
    $play = [];
    $dealer = 'N';
    $vul = 'None';
    $board = '1';
    $deal = '';
    $contractBid = '';
    $declarer = '';
    $seatOrder = ['N', 'E', 'S', 'W'];

    for ($i = 0; $i < count($lines) - 1; $i += 2) {
        $tag = strtolower($lines[$i]);
        $next = $lines[$i + 1] ?? '';

        switch ($tag) {
            case 'mb':
                $bid = strtoupper($next);
                if ($bid === 'D') $bid = 'X';
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
                $suits = ['S', 'H', 'D', 'C'];
                $fullDeck = [];
                foreach ($suits as $suit) {
                    foreach (str_split('AKQJT98765432') as $rank) {
                        $fullDeck[] = $suit . $rank;
                    }
                }

                $linOrder = ['S', 'W', 'N', 'E'];
                $dealerMap = ['1' => 'S', '2' => 'W', '3' => 'N', '4' => 'E'];
                $dealerCode = substr($next, 0, 1);
                $dealer = $dealerMap[$dealerCode] ?? 'N';

                $hands = explode(',', substr($next, 1));
                $hands = array_pad($hands, 4, '');

                $knownCards = [];
                foreach ($hands as $hand) {
                    $currentSuit = '';
                    foreach (str_split($hand) as $char) {
                        if (in_array($char, $suits)) {
                            $currentSuit = $char;
                        } elseif ($currentSuit) {
                            $knownCards[] = $currentSuit . $char;
                        }
                    }
                }

                $missingCards = array_diff($fullDeck, $knownCards);
                $missingHand = '';
                $currentSuit = '';
                foreach ($missingCards as $card) {
                    $suit = $card[0];
                    $rank = $card[1];
                    if ($suit !== $currentSuit) {
                        $missingHand .= $suit;
                        $currentSuit = $suit;
                    }
                    $missingHand .= $rank;
                }

                for ($j = 0; $j < 4; $j++) {
                    if (trim($hands[$j]) === '') {
                        $hands[$j] = $missingHand;
                        break;
                    }
                }

                $handsBySeat = array_combine($linOrder, $hands);
                $dealerIndex = array_search($dealer, $seatOrder);
                $rotated = [];
                for ($j = 0; $j < 4; $j++) {
                    $seat = $seatOrder[($dealerIndex + $j) % 4];
                    $rotated[] = $handsBySeat[$seat] ?? '';
                }

                $formatted = array_map('format_hand', $rotated);
                $deal = $dealer . ':' . implode(' ', $formatted);
                break;
        }
    }

    for ($i = count($auction) - 1; $i >= 0; $i--) {
        if (!in_array($auction[$i], ['P', 'X', 'XX'])) {
            $contractBid = $auction[$i];
            $contractIndex = $i;
            break;
        }
    }

    if (!empty($contractBid)) {
        $strain = preg_replace('/^[1-7]/', '', $contractBid);
        $dealerIndex = array_search($dealer, ['W', 'N', 'E', 'S']);
        $seats = [];
        for ($i = 0; $i < count($auction); $i++) {
            $seats[] = ['W', 'N', 'E', 'S'][($dealerIndex + $i) % 4];
        }

        $declaringSide = in_array($seats[$contractIndex], ['N', 'S']) ? ['N', 'S'] : ['E', 'W'];
        for ($i = 0; $i <= $contractIndex; $i++) {
            if (strpos($auction[$i], $strain) !== false && in_array($seats[$i], $declaringSide)) {
                $declarer = $seats[$i];
                break;
            }
        }
    }

    $openingLeader = '';
    if ($declarer !== '') {
        $leaderIndex = (array_search($declarer, $seatOrder) + 1) % 4;
        $openingLeader = $seatOrder[$leaderIndex];
    }

    $names = extract_names_from_lin($lin);

    $pbn = "[Event \"BBO Movie\"]\n";
    $pbn .= "[Site \"Bridge Base Online\"]\n";
    $pbn .= "[Date \"" . date('Y.m.d') . "\"]\n";
    $pbn .= "[Board \"$board\"]\n";
    $pbn .= "[Dealer \"$dealer\"]\n";
    $pbn .= "[Vulnerable \"$vul\"]\n";
    if ($deal) $pbn .= "[Deal \"$deal\"]\n";
    if ($contractBid) $pbn .= "[Contract \"$contractBid\"]\n";
    if ($declarer) $pbn .= "[Declarer \"$declarer\"]\n";

    $pbn .= "[North \"{$names['North']}\"]\n";
    $pbn .= "[East \"{$names['East']}\"]\n";
    $pbn .= "[South \"{$names['South']}\"]\n";
    $pbn .= "[West \"{$names['West']}\"]\n";

    if (!empty($auction)) {
        $pbn .= "[Auction \"$dealer\"]\n";
        for ($i = 0; $i < count($auction); $i += 4) {
            $pbn .= implode(' ', array_slice($auction, $i, 4)) . "\n";
        }
    }

    if (!empty($play)) {
        $pbn .= "[Play \"$openingLeader\"]\n";
        for ($i = 0; $i < count($play); $i += 4) {
            $pbn .= implode(' ', array_slice($play, $i, 4)) . "\n";
        }
    }

    return $pbn;
}
        function format_hand($hand) {
    $hand = str_replace('+', '', $hand);
    $hand = trim($hand);
    if ($hand === '') return '. . .';

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

// ✅ POST handler
$handviewerLink = '';
$linContent = '';
$pbnContent = '';
$linFilename = '';
$pbnFilename = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = $_POST['url'];

    if (preg_match('/[?&]lin=([^&]+)/', $url, $matches)) {
        $lin = urldecode($matches[1]);
        list($normalizedLin, $boardId) = normalize_lin($lin);

        $linFilename = $boardId . '.lin';
        $pbnFilename = $boardId . '.pbn';

        $linContent = $normalizedLin;
        $pbnContent = convert_lin_to_pbn($lin); // ✅ correct

        $handviewerLink = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . urlencode($normalizedLin);
    }
}t = $char;
        } elseif ($currentSuit) {
            $suits[$currentSuit] .= $char;
        }
    }

    return implode('.', [$suits['S'], $suits['H'], $suits['D'], $suits['C']]);
}

// ✅ POST handler
$handviewerLink = '';
$linContent = '';
$pbnContent = '';
$linFilename = '';
$pbnFilename = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = $_POST['url'];

    if (preg_match('/[?&]lin=([^&]+)/', $url, $matches)) {
        $lin = urldecode($matches[1]);
        list($normalizedLin, $boardId) = normalize_lin($lin);

        $linFilename = $boardId . '.lin';
        $pbnFilename = $boardId . '.pbn';

        $linContent = $normalizedLin;
        $pbnContent = convert_lin_to_pbn($lin); // ✅ correct

        $handviewerLink = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . urlencode($normalizedLin);
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
    <h1>ߎ Convert BBO Movie to Handviewer</h1>
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

        <h3>ߓ LIN File: <?= htmlspecialchars($linFilename) ?></h3>
        <textarea readonly><?= htmlspecialchars($linContent) ?></textarea><br>
        <a class="download" href="data:text/plain;charset=utf-8,<?= urlencode($linContent) ?>" download="<?= htmlspecialchars($linFilename) ?>">Download LIN</a>

        <h3>ߓ PBN File: <?= htmlspecialchars($pbnFilename) ?></h3>
        <textarea readonly><?= htmlspecialchars($pbnContent) ?></textarea><br>
        <a class="download" href="data:text/plain;charset=utf-8,<?= rawurlencode($pbnContent) ?>" download="<?= htmlspecialchars($pbnFilename) ?>">Download PBN</a>
    </div>
    <?php endif; ?>
</body>
</html>

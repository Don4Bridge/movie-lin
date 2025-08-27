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
                // Normalize bid: uppercase and fix "N" → "NT"
                $bid = strtoupper($next);
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
                    $hands[] = ''; // pad missing hands
                }

                $seatOrder = ['N', 'E', 'S', 'W'];
                $linOrder = ['S', 'W', 'N', 'E'];
                $handsBySeat = array_combine($linOrder, $hands);

                $dealerIndex = array_search($dealer, $seatOrder);
                $rotated = [];
                for ($j = 0; $j < 4; $j++) {
                    $seat = $seatOrder[($dealerIndex + $j) % 4];
                    $rotated[] = $handsBySeat[$seat] ?? '';
                }

               function format_hand($hand) {
    $suits = explode('.', $hand);
    while (count($suits) < 4) {
        $suits[] = ''; // pad missing suits
    }
    return implode('.', $suits);
}

$formatted = array_map('format_hand', $rotated);
$deal = $dealer . ':' . implode(' ', $formatted);
                break;
        }
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

    $pbn .= "\nAuction \"$dealer\"\n" . implode(' ', $auction) . "\n\n";
    $pbn .= "Play \"$dealer\"\n" . implode(' ', $play) . "\n";

    return $pbn;
}
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
        $pbnContent = convert_lin_to_pbn($normalizedLin);

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
        <a class="download" href="data:text/plain;charset=utf-8,<?= urlencode($linContent) ?>" download="<?= htmlspecialchars($linFilename) ?>">Download LIN</a>

        <h3>📥 PBN File: <?= htmlspecialchars($pbnFilename) ?></h3>
        <textarea readonly><?= htmlspecialchars($pbnContent) ?></textarea><br>
        <a class="download" href="data:text/plain;charset=utf-8,<?= urlencode($pbnContent) ?>" download="<?= htmlspecialchars($pbnFilename) ?>">Download PBN</a>
    </div>
    <?php endif; ?>
</body>
</html>

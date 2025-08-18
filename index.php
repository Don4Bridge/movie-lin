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
function parse_md_to_pbn_deal($mdLine) {
    $dealerMap = ['1' => 'S', '2' => 'W', '3' => 'N', '4' => 'E'];
    $rotation = ['S', 'W', 'N', 'E'];

    // Extract dealer and hands from md| line
    if (strpos($mdLine, 'md|') !== 0) {
        error_log("❌ md line does not start with 'md|'");
        return ['N', '[Deal "N:S. H. D. C."]'];
    }

    $mdContent = substr($mdLine, 3); // Remove 'md|'
    $dealerCode = substr($mdContent, 0, 1); // First character is dealer code
    $dealer = $dealerMap[$dealerCode] ?? 'N';

    $handString = substr($mdContent, 1); // Remaining string is hands
    $rawHands = explode(',', $handString);
    if (count($rawHands) !== 4) {
        error_log("❌ Expected 4 hands, got " . count($rawHands));
        return [$dealer, '[Deal "' . $dealer . ':S. H. D. C."]'];
    }

    // LIN rotation: South, West, North, East
    $seatOrder = ['S', 'W', 'N', 'E'];
    $dealParts = [];

    foreach ($seatOrder as $i => $seat) {
        $hand = $rawHands[$i];
        $suitMap = ['S' => '', 'H' => '', 'D' => '', 'C' => ''];
        preg_match_all('/([SHDC])([^SHDC]*)/', $hand, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $suitMap[$match[1]] = $match[2];
        }
        $formatted = "S{$suitMap['S']}.H{$suitMap['H']}.D{$suitMap['D']}.C{$suitMap['C']}";
        $dealParts[] = "$seat.$formatted";
    }

    return [$dealer, '[Deal "' . $dealer . ':' . implode(' ', $dealParts) . '"]'];
}
function lin_to_pbn($lin) {
    $parts = explode('|', $lin);
    $tags = [];
    $pbn = '';
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $tags[$parts[$i]][] = $parts[$i + 1];
    }

    $players = isset($tags['pn']) ? explode(',', $tags['pn'][0]) : ['North', 'East', 'South', 'West'];
    $boardTitle = isset($tags['ah']) ? $tags['ah'][0] : 'Board';
    preg_match('/Board\s+(\d+)/i', $boardTitle, $matches);
    $boardNum = isset($matches[1]) ? $matches[1] : '1';

    $auction = isset($tags['mb']) ? $tags['mb'] : [];
    $play = isset($tags['pc']) ? $tags['pc'] : [];

    $md = isset($tags['md']) ? 'md|' . $tags['md'][0] : '';
    list($mdDealer, $dealTag) = parse_md_to_pbn_deal($md);
    $dealer = $mdDealer;

    /**
 * Maps each bid in the LIN auction to its correct seat based on dealer.
 *
 * @param string $dealerSeat One of 'N', 'E', 'S', 'W'
 * @param array $auction Array of mb| bids (e.g. ['1N', '2C', 'P', '2D', ...])
 * @return array Array of ['seat' => 'N', 'bid' => '1N'] entries
 */
function mapAuctionSeats(string $dealerSeat, array $auction): array {
 $rotation = ['N', 'E', 'S', 'W']; // ✅ define before use

echo "<p>Dealer: $dealer</p>";
echo "<p>Rotation count: " . count($rotation) . "</p>";

$startIndex = array_search($dealer, $rotation);
    $mapped = [];

    foreach ($auction as $i => $rawBid) {
        $seatIndex = ($startIndex + $i) % 4;
        $seat = $rotation[$seatIndex];
        $cleanBid = explode('|', $rawBid)[0]; // strip annotations if present
        $mapped[] = ['seat' => $seat, 'bid' => $cleanBid];
    }

    return $mapped;
}

    // 🔍 Vulnerability
    $vulMap = ['o' => 'None', 'b' => 'Both', 'n' => 'NS', 'e' => 'EW'];
    $vulCode = $tags['sv'][0] ?? 'o';
    $vul = $vulMap[$vulCode] ?? 'None';

    // ✅ Extract Contract from LIN
    $contractBid = null;
    foreach ($auction as $bid) {
        if (!in_array(strtolower($bid), ['p', 'ap'])) {
            $contractBid = $bid;
        }
    }
    $lastBid = $contractBid ?? 'Pass';
    $lastBid = preg_replace('/^(\d)N$/', '$1NT', $lastBid);
    error_log("🧪 Auction passed to determineDeclarer: " . json_encode($auction));
   /**
 * Determines the declarer seat from dealer, auction, and final contract strain.
 *
 * @param string $dealer One of 'N', 'E', 'S', 'W'
 * @param array $auction Array of mb| bids (e.g. ['1N', '2C', 'P', '2D', ...])
 * @return string Declarer seat ('N', 'E', 'S', 'W')
 */
 echo "<pre>";
var_dump($dealer);
var_dump(count($rotation));
echo "</pre>";
   
function determineDeclarer(string $dealer, array $auction): string {
    $rotation = ['N', 'E', 'S', 'W'];
    $startIndex = array_search($dealer, $rotation);
    if ($startIndex === false) {
        error_log("❌ Invalid dealer seat: $dealer");
        return 'N'; // fallback
    }
    $auctionRaw = $tags['mb'] ?? [];
    $auction = array_map(function($bid) {
    return str_starts_with($bid, 'mb|') ? substr($bid, 3) : $bid;
    }, $auctionRaw);

$declarer = determineDeclarer($dealer, $auction);

    // Extract final contract bid
    $finalBidIndex = -1;
    $strain = null;
    for ($i = count($auction) - 1; $i >= 0; $i--) {
        $bid = explode('|', $auction[$i])[0];
        if (preg_match('/[1-7](NT|[SHDC])/', $bid, $matches)) {
            $finalBidIndex = $i;
            $strain = $matches[1];
            break;
        }
    }

    if ($finalBidIndex === -1 || !$strain) {
        error_log("❌ No valid contract bid found.");
        return 'N'; // fallback
    }

    $finalBidderIndex = ($startIndex + $finalBidIndex) % 4;
    $finalBidderSeat = $rotation[$finalBidderIndex];
    $partnership = in_array($finalBidderSeat, ['N', 'S']) ? ['N', 'S'] : ['E', 'W'];

    // Find first strain bid by partnership
    for ($i = 0; $i < count($auction); $i++) {
        $bid = explode('|', $auction[$i])[0];
        if (preg_match('/[1-7]' . preg_quote($strain, '/') . '/', $bid)) {
            $seatIndex = ($startIndex + $i) % 4;
            $seat = $rotation[$seatIndex];
            if (in_array($seat, $partnership)) {
                return $seat;
            }
        }
    }

    return $finalBidderSeat; // fallback
}
       $result = 7; // declarer took all 13 tricks

    // 🧾 PBN Header
    $auctionRaw = $tags['mb'] ?? [];
    $auction = array_map(function($bid) {
    return explode('|', $bid)[0];
    }, $auctionRaw);

$declarer = determineDeclarer($dealer, $auction);
    $rotation = ['N', 'E', 'S', 'W'];
    $pbn = "[Event \"BBO Tournament\"]\n";
    $pbn .= "[Site \"Bridge Base Online\"]\n";
    $pbn .= "[Date \"" . date('Y.m.d') . "\"]\n";
    $pbn .= "[Board \"$boardNum\"]\n";
    $pbn .= "[Dealer \"$dealer\"]\n";
    $pbn .= "[Vulnerable \"$vul\"]\n";
    $pbn .= "[Contract \"$lastBid\"]\n";
    $pbn .= "[Declarer \"$declarer\"]\n";
    $pbn .= "[Result \"$result\"]\n";
    $pbn .= "[West \"{$players[3]}\"]\n";
    $pbn .= "[North \"{$players[0]}\"]\n";
    $pbn .= "[East \"{$players[1]}\"]\n";
    $pbn .= "[South \"{$players[2]}\"]\n";
    $pbn .= $dealTag . "\n";

    // 🧾 Auction Block
    $pbn .= "\nAuction \"$dealer\"\n";
    $currentIndex = array_search($dealer, $rotation);
    foreach ($auction as $i => $bid) {
        $pbn .= $bid;
        $currentIndex = ($currentIndex + 1) % 4;
        $pbn .= ($i + 1) % 4 === 0 ? "\n" : " ";
    }

    // 🧾 Play Block
$pbn .= "\nPlay \"$declarer\"\n";
$playRotation = ['N', 'E', 'S', 'W'];
$leadIndex = (array_search($dealer, $playRotation) + 1) % 4;

for ($i = 0; $i < count($play); $i += 4) {
    for ($j = 0; $j < 4; $j++) {
        $seat = $playRotation[($leadIndex + $j) % 4];
        $card = $play[$i + $j] ?? '';
        if ($card !== '') {
            $pbn .= "$seat $card\n";
        }
    }
}

    return $pbn;
}
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = __DIR__ . '/' . $filename;

    if (!file_exists($filepath)) {
        http_response_code(404);
        echo "File not found.";
        exit;
    }

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = trim($_POST['url']);
    $parsed = parse_url($url);
    parse_str($parsed['query'] ?? '', $query);

    if (!isset($query['lin'])) {
    echo "<p>❌ Invalid BBO movie URL. LIN string not found.</p>";
    echo "<p><a href=''>🔁 Try again</a></p>";
    exit;
}
    $rawLin = $query['lin']; // safe now
    list($normalized, $boardNumber) = normalize_lin_preserving_order($rawLin);
    $linFilename = $boardNumber . '.lin';
    file_put_contents($linFilename, $normalized);

  $pbnText = lin_to_pbn($normalized);
  error_log("✅ PBN text preview:\n" . substr($pbnText, 0, 300));
  $pbnFilename = $boardNumber . '.pbn';
  error_log("✅ Writing to file: $pbnFilename");
  file_put_contents($pbnFilename, $pbnText);

    $viewerUrl = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . urlencode($normalized);

    echo "<h2>✅ LIN Converted</h2>";
    echo "<p><strong>Board:</strong> $boardNumber</p>";
    echo "<p><strong>Handviewer:</strong> <a href='$viewerUrl' target='_blank'>🔗 Handviewer Link</a></p>";
    echo "<p><a href='?download=$linFilename'>📥 Download LIN File</a></p>";
    echo "<p><a href='?download=$pbnFilename'>📥 Download PBN File</a></p>";
    echo "<pre style='white-space:pre-wrap;background:#f0f0f0;padding:1em;border-radius:5px;'>$normalized</pre>";
    echo "<p><a href=''>🔁 Convert another</a></p>";
    exit;
}
?>

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

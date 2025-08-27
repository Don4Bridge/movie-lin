<?php
ob_start(); // Prevent output before headers

$board = $_GET['b'] ?? '';
$cachePath = __DIR__ . "/lin_cache/$board.lin";

if (!file_exists($cachePath)) {
    http_response_code(404);
    echo "❌ LIN file not found for board: $board";
    exit;
}

$lin = file_get_contents($cachePath);
if (!$lin || strlen(trim($lin)) < 10) {
    http_response_code(400);
    echo "❌ LIN file is empty or malformed.";
    exit;
}

$url = 'https://www.bridgebase.com/tools/handviewer.html?lin=' . urlencode($lin);
header("Location: $url");
exit;

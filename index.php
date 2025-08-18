<?php
$url = $_GET['url'] ?? '';
if (!$url) {
    echo "Usage: /?url=https://...bridgebase.com/tools/handviewer.html?lin=...";
    exit;
}

$parts = parse_url($url);
parse_str($parts['query'], $query);
$lin = urldecode($query['lin'] ?? '');
$formatted = str_replace('|', "|\n", $lin);

// Send as downloadable file
header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=hand.lin");
echo $formatted;

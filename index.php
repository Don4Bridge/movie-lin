<?php
if (isset($_GET['url'])) {
    $target = $_GET['url'];
    // Basic validation to prevent open redirect abuse
    if (strpos($target, 'bridgebase.com/tools/handviewer.html?lin=') !== false) {
        header("Location: $target");
        exit;
    } else {
        echo "Invalid URL.";
    }
} else {
    echo "Usage: /?url=https://www.bridgebase.com/tools/handviewer.html?lin=...";
}

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

// Handle form submission
$handviewerLink = '';
$linDownloadLink = '';
$linFilename = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = $_POST['url'];
    $lin = '';

    // Extract LIN from URL
    if (preg_match('/[?&]lin=([^&]+)/', $url, $matches)) {
        $lin = urldecode($matches[1]);
        list($normalizedLin, $boardId) = normalize_lin_preserving_order($lin);

        // Handviewer link (direct BBO endpoint)
        $handviewerLink

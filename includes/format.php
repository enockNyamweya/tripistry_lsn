<?php
/**
 * Compact stat display for large currency values (avoids ellipsis truncation in stat cards).
 */
function formatStatRevenue($amount): array {
    $amount = (float)$amount;
    // Full value for display (CSS handles sizing); compact for very tight layouts via 'short'
    $full = 'R' . number_format($amount, 2, '.', ',');

    if ($amount >= 1000000) {
        $short = 'R' . number_format($amount / 1000000, 2, '.', ',') . 'M';
    } elseif ($amount >= 1000) {
        $short = 'R' . number_format($amount / 1000, 1, '.', ',') . 'K';
    } else {
        $short = 'R' . number_format($amount, 0, '.', ',');
    }

    return [
        'display' => $full,
        'full' => $full,
        'short' => $short,
    ];
}

<?php
/**
 * Compact stat display for large currency values (avoids ellipsis truncation in stat cards).
 */
function formatStatRevenue($amount): array {
    $amount = (float)$amount;
    $full = 'R' . number_format($amount, 2, '.', ',');

    if ($amount >= 1000000) {
        $display = 'R' . number_format($amount / 1000000, 2, '.', ',') . 'M';
    } elseif ($amount >= 1000) {
        $display = 'R' . number_format($amount / 1000, 1, '.', ',') . 'K';
    } else {
        $display = 'R' . number_format($amount, 0, '.', ',');
    }

    return ['display' => $display, 'full' => $full];
}

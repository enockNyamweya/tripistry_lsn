<?php
// Group Matching Algorithm (Bonus Task 3)
// Matches solo/small-party travellers into compatible groups for agency group trips
// based on destination preference, budget, and travel patterns

function match_traveller_to_groups($pdo, $travellerId) {
    // Find traveller's booking history to infer preferences
    $stmt = $pdo->prepare('
        SELECT d.City, d.Country, d.DestinationID,
               AVG(p.Price) as avgBudget,
               AVG(p.DurationDays) as avgDuration,
               COUNT(*) as tripCount
        FROM BOOKING b
        JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID
        JOIN HAS_DESTINATION hd ON p.PackageID = hd.PackageID
        JOIN DESTINATION d ON hd.DestinationID = d.DestinationID
        WHERE b.UserID = ?
        GROUP BY d.DestinationID
        ORDER BY tripCount DESC
        LIMIT 5
    ');
    $stmt->execute([$travellerId]);
    $preferences = $stmt->fetchAll();

    if (empty($preferences)) {
        // No history — recommend popular group trips
        $stmt = $pdo->prepare('
            SELECT g.*, p.Title, p.Price, p.DurationDays,
                   d.City, d.Country,
                   (SELECT COUNT(*) FROM ENROLS e WHERE e.GroupTripID = g.GroupTripID) as currentEnrolment
            FROM GROUP_TRIP g
            JOIN TRAVEL_PACKAGE p ON g.PackageID = p.PackageID
            JOIN HAS_DESTINATION hd ON p.PackageID = hd.PackageID
            JOIN DESTINATION d ON hd.DestinationID = d.DestinationID
            WHERE g.Status = "Open" AND p.Status = "Active"
            ORDER BY (SELECT COUNT(*) FROM ENROLS e WHERE e.GroupTripID = g.GroupTripID) DESC
            LIMIT 10
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Build destination-aware match scores for all open group trips
    $stmt = $pdo->prepare('
        SELECT g.*, p.Title, p.Price, p.DurationDays,
               d.City, d.Country, d.DestinationID,
               (SELECT COUNT(*) FROM ENROLS e WHERE e.GroupTripID = g.GroupTripID) as currentEnrolment
        FROM GROUP_TRIP g
        JOIN TRAVEL_PACKAGE p ON g.PackageID = p.PackageID
        JOIN HAS_DESTINATION hd ON p.PackageID = hd.PackageID
        JOIN DESTINATION d ON hd.DestinationID = d.DestinationID
        WHERE g.Status = "Open" AND p.Status = "Active"
    ');
    $stmt->execute();
    $allGroups = $stmt->fetchAll();

    if (empty($allGroups)) return [];

    // Score each group against traveller preferences
    $scored = [];
    foreach ($allGroups as $group) {
        $score = 0;
        foreach ($preferences as $pref) {
            // Destination match (highest weight)
            if ($pref['DestinationID'] == $group['DestinationID']) {
                $score += 10 * $pref['tripCount'];
            }
            // Same country
            if ($pref['Country'] == $group['Country']) {
                $score += 5 * $pref['tripCount'];
            }
            // Budget compatibility (price within 30% of preferred)
            $budgetDiff = abs($group['Price'] - $pref['avgBudget']);
            if ($pref['avgBudget'] > 0 && $budgetDiff / $pref['avgBudget'] < 0.3) {
                $score += 4;
            }
            // Duration match
            if (abs($group['DurationDays'] - $pref['avgDuration']) <= 2) {
                $score += 3;
            }
        }
        // Bonus for popular groups close to capacity
        $capacityRatio = $group['MaxCapacity'] > 0
            ? $group['currentEnrolment'] / $group['MaxCapacity']
            : 0;
        if ($capacityRatio > 0.5 && $capacityRatio < 0.95) {
            $score += 5; // Almost full — join now
        }
        // Penalty for full groups
        if ($group['currentEnrolment'] >= $group['MaxCapacity']) {
            $score = -1;
        }

        $group['matchScore'] = round($score, 1);
        $scored[] = $group;
    }

    // Sort by match score descending
    usort($scored, function($a, $b) {
        return $b['matchScore'] <=> $a['matchScore'];
    });

    // Return top matches (score > 0)
    return array_filter($scored, function($g) {
        return $g['matchScore'] > 0;
    });
}

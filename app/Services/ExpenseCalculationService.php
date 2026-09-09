<?php

namespace App\Services;

class ExpenseCalculationService
{
    /**
     * Calculate equal splits for an expense amount among given member IDs.
     * Handles rounding so splits always add up to the exact total.
     *
     * @param float $amount
     * @param array $memberIds
     * @return array [memberId => splitAmount]
     */
    public function calculateEqualSplits(float $amount, array $memberIds): array
    {
        $count = count($memberIds);
        if ($count === 0) return [];

        $baseShare = floor(($amount / $count) * 100) / 100;
        $remainder = round($amount - ($baseShare * $count), 2);

        $splits = [];
        foreach ($memberIds as $index => $memberId) {
            $splits[$memberId] = $baseShare;
        }

        // Distribute remainder to first member(s)
        $remainderCents = (int) round($remainder * 100);
        $ids = array_values($memberIds);
        for ($i = 0; $i < $remainderCents; $i++) {
            $splits[$ids[$i % $count]] += 0.01;
            $splits[$ids[$i % $count]] = round($splits[$ids[$i % $count]], 2);
        }

        return $splits;
    }
}

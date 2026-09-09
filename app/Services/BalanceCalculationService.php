<?php

namespace App\Services;

class BalanceCalculationService
{
    /**
     * Calculate minimal settlements using greedy algorithm.
     * Minimizes the number of transactions needed.
     *
     * @param array $balances  [id => ['id', 'name', 'avatar_color', 'initials', 'balance']]
     * @return array  List of settlement transactions
     */
    public function calculateSettlements(array $balances): array
    {
        $creditors = [];
        $debtors = [];

        foreach ($balances as $item) {
            if ($item['balance'] > 0.01) {
                $creditors[] = $item;
            } elseif ($item['balance'] < -0.01) {
                $debtors[] = ['id' => $item['id'], 'name' => $item['name'],
                    'avatar_color' => $item['avatar_color'], 'initials' => $item['initials'],
                    'balance' => abs($item['balance'])];
            }
        }

        $settlements = [];

        while (!empty($creditors) && !empty($debtors)) {
            // Sort by balance descending
            usort($creditors, fn($a, $b) => $b['balance'] <=> $a['balance']);
            usort($debtors, fn($a, $b) => $b['balance'] <=> $a['balance']);

            $creditor = &$creditors[0];
            $debtor = &$debtors[0];

            $amount = min($creditor['balance'], $debtor['balance']);
            $amount = round($amount, 2);

            if ($amount > 0.01) {
                $settlements[] = [
                    'from' => [
                        'id' => $debtor['id'],
                        'name' => $debtor['name'],
                        'avatar_color' => $debtor['avatar_color'],
                        'initials' => $debtor['initials'],
                    ],
                    'to' => [
                        'id' => $creditor['id'],
                        'name' => $creditor['name'],
                        'avatar_color' => $creditor['avatar_color'],
                        'initials' => $creditor['initials'],
                    ],
                    'amount' => $amount,
                ];
            }

            $creditor['balance'] = round($creditor['balance'] - $amount, 2);
            $debtor['balance'] = round($debtor['balance'] - $amount, 2);

            if ($creditor['balance'] <= 0.01) array_shift($creditors);
            if ($debtor['balance'] <= 0.01) array_shift($debtors);
        }

        return $settlements;
    }
}

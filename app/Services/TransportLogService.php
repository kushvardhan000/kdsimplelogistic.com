<?php

namespace App\Services;

class TransportLogService
{
    public function computeTotals(array $data): array
    {
        $data['total_sale'] = round(
            (float) ($data['to_bb_sale'] ?? 0)
            + (float) ($data['paid_sale'] ?? 0)
            + (float) ($data['to_pay'] ?? 0),
            2
        );

        $data['total_advance'] = round(
            (float) ($data['diesel_advance'] ?? 0)
            + (float) ($data['cash_advance'] ?? 0),
            2
        );

        $data['total_expense'] = round(
            (float) ($data['freight'] ?? 0)
            + (float) ($data['loading'] ?? 0)
            + (float) ($data['unloading'] ?? 0)
            + (float) ($data['dd'] ?? 0)
            + (float) ($data['tempu_expense'] ?? 0)
            + (float) ($data['commission'] ?? 0)
            + (float) ($data['dtg_office_expense'] ?? 0),
            2
        );

        $data['profit'] = round($data['total_sale'] - $data['total_expense'], 2);

        $data['balance_vehicle_payment'] = round($data['total_sale'] - $data['payment'], 2);

        return $data;
    }
}

<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransportLogsExport implements FromQuery, WithHeadings, WithEvents
{
    private Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->select([
            'id',
            'date',
            'vehicle_no',
            'company',
            'transport_name',
            'logsheet_no',
            'destination',
            'km',
            'weight',
            'to_bb_sale',
            'paid_sale',
            'to_pay',
            'total_sale',
            'freight',
            'loading',
            'unloading',
            'dd',
            'tempu_expense',
            'commission',
            'dtg_office_expense',
            'total_expense',
            'profit',
            'diesel_advance',
            'cash_advance',
            'total_advance',
            'payment',
            'fuel_station_name',
            'fuel_station_balance',
            'balance_vehicle_payment',
            'clearing_date',
            'detail',
            'remarks',
        ]);
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Date',
            'Vehicle No',
            'Company',
            'Transport Name',
            'Logsheet No',
            'Destination',
            'Km',
            'Weight',
            'To BB Sale',
            'Paid Sale',
            'To Pay',
            'Total Sale',
            'Freight',
            'Loading',
            'Unloading',
            'DD',
            'Tempu Expense',
            'Commission',
            'DTG Office Expense',
            'Total Expense',
            'Profit',
            'Diesel Advance',
            'Cash Advance',
            'Total Advance',
            'Payment',
            'Fuel Station Name',
            'Fuel Station Balance',
            'Balance Vehicle Payment',
            'Clearing Date',
            'Detail',
            'Remarks',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $headerSales   = ['4472C4', 'FFFFFF'];
                $headerExp     = ['C65911', 'FFFFFF'];
                $headerAdv     = ['375623', 'FFFFFF'];
                $headerPayment = ['BF8F00', 'FFFFFF'];
                $headerDefault = ['D9D9D9', '000000'];

                $colGroups = [
                    'J' => $headerSales,
                    'K' => $headerSales,
                    'L' => $headerSales,
                    'M' => $headerSales,
                    'N' => $headerExp,
                    'O' => $headerExp,
                    'P' => $headerExp,
                    'Q' => $headerExp,
                    'R' => $headerExp,
                    'S' => $headerExp,
                    'T' => $headerExp,
                    'U' => $headerExp,
                    'W' => $headerAdv,
                    'X' => $headerAdv,
                    'Y' => $headerAdv,
                    'Z' => $headerPayment,
                ];

                foreach ($colGroups as $col => [$bg, $fg]) {
                    $sheet->getStyle($col.'1')->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $bg],
                        ],
                        'font' => [
                            'color' => ['rgb' => $fg],
                            'bold' => true,
                        ],
                    ]);
                }

                $otherCols = ['A','B','C','D','E','F','G','H','I','AA','AB','AC','AD','AE','AF'];
                foreach ($otherCols as $col) {
                    $sheet->getStyle($col.'1')->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D9D9D9'],
                        ],
                        'font' => ['bold' => true],
                    ]);
                }

                $formulaComments = [
                    'M' => '= To BB Sale + Paid Sale + To Pay',
                    'U' => '= Freight + Loading + Unloading + DD + Tempu Expense + Commission + DTG Office Expense',
                    'V' => '= Total Sale - Total Expense',
                    'Y' => '= Diesel Advance + Cash Advance',
                    'AC' => '= Total Sale - Payment',
                ];

                foreach ($formulaComments as $col => $formula) {
                    $comment = $sheet->getComment($col . '1');
                    $richText = new RichText();
                    $richText->createText($formula);
                    $comment->setAuthor('Transport System');
                    $comment->setText($richText);
                }

                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->setCellValue("M{$row}", "=J{$row}+K{$row}+L{$row}");
                    $sheet->setCellValue("U{$row}", "=N{$row}+O{$row}+P{$row}+Q{$row}+R{$row}+S{$row}+T{$row}");
                    $sheet->setCellValue("V{$row}", "=M{$row}-U{$row}");
                    $sheet->setCellValue("Y{$row}", "=W{$row}+X{$row}");
                    $sheet->setCellValue("AC{$row}", "=M{$row}-Z{$row}");

                    $formulaCols = [
                        'M' => ['C6EFCE', '006100'],
                        'U' => ['FFC7CE', '9C0006'],
                        'V' => ['FFEB9C', '9C6500'],
                        'Y' => ['BDD7EE', '1F497D'],
                        'AC' => ['F4B084', '843C0C'],
                    ];

                    foreach ($formulaCols as $col => [$bg, $fg]) {
                        $sheet->getStyle($col.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $bg],
                            ],
                            'font' => [
                                'color' => ['rgb' => $fg],
                            ],
                        ]);
                    }
                }

                $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF'];
                foreach ($columns as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TemplateClientExport implements WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'client_name',
            'email',
            'phone_number',
            'person_in_charge',
            'address',
            'sales_code'
        ];
    }
}
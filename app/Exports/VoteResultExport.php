<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;


class VoteResultExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $voteResults = $this->query;
        return $voteResults;
    }

    public function map($voteResults): array
    {
        return [
            $voteResults->forum_name,
            $voteResults->type == 'for' ? $voteResults->value : '',
            $voteResults->type == 'against' ? $voteResults->value : '',
            $voteResults->created_at->format('m-d-Y  H:i A')
        ];
    }
    public function headings(): array
    {
        return [
            'Forum Name',
            'Stake For',
            'Stake Against',
            'Time of Vote',
        ];
    }
}

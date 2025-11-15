<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GiftCardsExport implements FromCollection, WithHeadings
{
    protected $cards;

    public function __construct(Collection $cards)
    {
        $this->cards = $cards;
    }

    public function collection()
    {
        
        return $this->cards->map(function ($card) {
            return [
                'code' => $card->code,
                'shopify_giftcard_id' => $card->shopify_giftcard_id,
                'status' => ucfirst($card->status),
                'balance' => $card->balance ?? 'N/A',
                'error_message' => $card->error_message ? strip_tags($card->error_message) : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Shopify GiftCard ID',
            'Status',
            'Balance',
            'Error Message'
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class QuotePdfService
{
    public function render(Quote $quote): \Barryvdh\DomPDF\PDF
    {
        $quote->loadMissing([
            'client',
            'companyProfile',
            'contact',
            'site',
            'lead',
            'creator',
            'items',
        ]);

        $company = $quote->companyProfile
            ?? \App\Models\CompanyProfile::where('is_default', true)->first();

        $groupedItems = $quote->items->groupBy('section_name');
        $currencySymbol = $quote->currency === 'USD' ? '$' : 'S/';

        return Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $company,
            'groupedItems' => $groupedItems,
            'currencySymbol' => $currencySymbol,
        ])->setPaper('a4', 'portrait');
    }

    public function download(Quote $quote): Response
    {
        $filename = sprintf('%s-v%d.pdf', $quote->quote_number, $quote->version);

        return $this->render($quote)->download($filename);
    }

    public function output(Quote $quote): string
    {
        return $this->render($quote)->output();
    }
}

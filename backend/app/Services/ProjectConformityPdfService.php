<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ProjectConformityPdfService
{
    public function download(Project $project): Response
    {
        $project->loadMissing([
            'client',
            'site',
            'quote',
            'companyProfile',
            'leadTechnician',
            'tasks',
            'materials',
        ]);

        $company = $project->companyProfile
            ?? CompanyProfile::where('is_default', true)->first();

        $pdf = Pdf::loadView('pdf.conformity', [
            'project' => $project,
            'company' => $company,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Acta-Conformidad-{$project->code}.pdf");
    }
}

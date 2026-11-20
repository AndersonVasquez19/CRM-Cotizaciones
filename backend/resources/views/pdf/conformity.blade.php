<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Conformidad {{ $project->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; padding: 20px 24px; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 16px; font-weight: bold; color: #0f172a; }
        .sub { font-size: 9.5px; color: #475569; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        th { background-color: #1e293b; color: #ffffff; font-size: 9.5px; text-transform: uppercase; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; margin-bottom: 14px; }
        .sig-table td { border: none; padding-top: 40px; text-align: center; width: 45%; }
        .sig-line { border-top: 1px solid #64748b; padding-top: 4px; font-size: 9.5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">ACTA DE CONFORMIDAD DE SERVICIO E INSTALACIÓN — {{ $project->code }}</div>
        <div class="sub">{{ $company?->name ?? 'TECHQUOTE INTEGRACIONES S.A.C.' }} | RUC: {{ $company?->ruc ?? '20601234567' }}</div>
    </div>

    <div class="box">
        <strong>Cliente:</strong> {{ $project->client->business_name }} ({{ $project->client->document_type }} {{ $project->client->document_number }})<br>
        <strong>Proyecto:</strong> {{ $project->title }}<br>
        <strong>Sede:</strong> {{ $project->site?->site_name ?? 'Dirección Principal' }} {{ $project->site?->address ? '— '.$project->site->address : '' }}<br>
        <strong>Cotización Origen:</strong> {{ $project->quote?->quote_number }} v{{ $project->quote?->version }} | <strong>Avance:</strong> {{ $project->progress_percent }}%
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Sección</th>
                <th>Actividad / Entregable</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->tasks as $i => $task)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $task->section_name }}</td>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->is_completed ? 'Completado' : 'Pendiente' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Equipamiento / Material</th>
                <th>Und.</th>
                <th>Cant. Planificada</th>
                <th>Números de Serie / Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->materials as $mat)
                <tr>
                    <td>{{ $mat->description }}</td>
                    <td>{{ $mat->unit }}</td>
                    <td>{{ number_format((float) $mat->planned_qty, 2) }}</td>
                    <td>{{ $mat->serial_numbers ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($project->conformity_notes)
        <div class="box">
            <strong>Observaciones Finales:</strong> {{ $project->conformity_notes }}
        </div>
    @endif

    <table class="sig-table">
        <tr>
            <td><div class="sig-line"><strong>{{ $project->leadTechnician?->name ?? 'Especialista Técnico' }}</strong><br>Por el Integrador</div></td>
            <td style="width: 10%;"></td>
            <td><div class="sig-line"><strong>Conformidad del Cliente</strong><br>{{ $project->client->business_name }}</div></td>
        </tr>
    </table>
</body>
</html>

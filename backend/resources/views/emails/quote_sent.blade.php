<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.5; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #1e3a8a; margin-bottom: 8px;">Cotización Técnica {{ $quote->quote_number }} (v{{ $quote->version }})</h2>
    <p>Estimado(a) {{ $quote->contact?->full_name ?? $quote->client->business_name }},</p>
    <p>Adjuntamos nuestra propuesta técnica y económica por un total de <strong>{{ $quote->currency === 'USD' ? '$' : 'S/' }} {{ number_format((float) $quote->total, 2) }}</strong> (incluido IGV), válida hasta el <strong>{{ $quote->valid_until?->format('d/m/Y') }}</strong>.</p>
    <p style="margin: 24px 0;">
        <a href="{{ $portalUrl }}" style="background-color: #4f46e5; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;">
            Ver y Aprobar Cotización en Línea
        </a>
    </p>
    <p style="font-size: 12px; color: #64748b;">
        Si tienes alguna consulta técnica, responde a este correo o comunícate con {{ $quote->creator?->name ?? 'nuestro equipo comercial' }}.
    </p>
</body>
</html>

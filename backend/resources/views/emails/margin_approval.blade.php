<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.5; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #b45309; margin-bottom: 8px;">Solicitud de Aprobación de Margen</h2>
    <p>La cotización <strong>{{ $quote->quote_number }} (v{{ $quote->version }})</strong> para <strong>{{ $quote->client->business_name }}</strong> requiere aprobación gerencial.</p>
    <ul>
        <li><strong>Ejecutivo:</strong> {{ $quote->creator?->name }}</li>
        <li><strong>Margen Bruto Calculado:</strong> {{ $marginPercent }}% (Mínimo requerido: {{ $minMarginPercent }}%)</li>
        <li><strong>Total Cotizado:</strong> {{ $quote->currency === 'USD' ? '$' : 'S/' }} {{ number_format((float) $quote->total, 2) }}</li>
    </ul>
    <p style="margin: 24px 0;">
        <a href="{{ $quoteUrl }}" style="background-color: #0f172a; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;">
            Revisar y Aprobar en TechQuote CRM
        </a>
    </p>
</body>
</html>

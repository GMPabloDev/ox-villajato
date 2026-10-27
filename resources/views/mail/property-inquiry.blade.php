<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva consulta</title>
</head>
<body style="font-family: sans-serif; color: #171717; line-height: 1.5;">
    <h1 style="font-size: 20px;">Nueva consulta</h1>

    <p>Has recibido una consulta desde la ficha de una propiedad.</p>

    <table style="border-collapse: collapse;">
        <tr>
            <th style="padding: 4px 12px 4px 0; text-align: left;">Nombre</th>
            <td style="padding: 4px 0;">{{ $visitorName }}</td>
        </tr>
        <tr>
            <th style="padding: 4px 12px 4px 0; text-align: left;">Email</th>
            <td style="padding: 4px 0;">{{ $visitorEmail }}</td>
        </tr>
        @if ($visitorPhone)
            <tr>
                <th style="padding: 4px 12px 4px 0; text-align: left;">Teléfono</th>
                <td style="padding: 4px 0;">{{ $visitorPhone }}</td>
            </tr>
        @endif
        <tr>
            <th style="padding: 4px 12px 4px 0; text-align: left;">Propiedad</th>
            <td style="padding: 4px 0;">
                <a href="{{ route('properties.show', $property) }}">{{ $property->title }}</a>
            </td>
        </tr>
    </table>

    <h2 style="font-size: 16px;">Mensaje</h2>
    <p>{!! nl2br(e($visitorMessage)) !!}</p>
</body>
</html>

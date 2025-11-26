<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Tratamiento</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #007bff; /* Color temático de tu app */
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
        }
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            text-transform: uppercase;
        }
        .meta-info {
            font-size: 12px;
            text-align: right;
            color: #666;
        }
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 5px solid #007bff;
        }
        .patient-info h3 { margin: 0 0 10px 0; font-size: 16px; }
        .patient-info p { margin: 5px 0; font-size: 14px; }

        .rx-section {
            margin-bottom: 20px;
        }
        .rx-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        /* Tabla estilo receta */
        .med-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .med-table th {
            background-color: #eee;
            text-align: left;
            padding: 10px;
            font-size: 12px;
            text-transform: uppercase;
        }
        .med-table td {
            border-bottom: 1px solid #ddd;
            padding: 12px 10px;
            vertical-align: top;
        }
        .med-name {
            font-weight: bold;
            font-size: 14px;
        }
        .med-detail {
            font-size: 12px;
            color: #555;
            font-style: italic;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>

    <div class="header">
        <div style="float:left">
            <div class="logo-text">MiApp Médica</div> <div style="font-size: 12px;">Historial Clínico Digital</div>
        </div>
        <div style="float:right; text-align:right">
            <div class="meta-info">
                <strong>Fecha de emisión:</strong> {{ date('d/m/Y') }}<br>
                <strong>Folio Tratamiento:</strong> #{{ str_pad($tratamiento->id, 6, '0', STR_PAD_LEFT) }}
            </div>
        </div>
        <div style="clear:both"></div>
    </div>

    <div class="patient-info">
        <h3>Detalles del Tratamiento</h3>
        <p><strong>Nombre del Tratamiento:</strong> {{ $tratamiento->nombre_tratamiento }}</p>
        <p>
            <strong>Estado:</strong> 
            <span class="status-badge">{{ ucfirst($tratamiento->estado) }}</span>
        </p>
        <p><strong>Duración:</strong> Del {{ \Carbon\Carbon::parse($tratamiento->fecha_inicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($tratamiento->fecha_fin)->format('d/m/Y') }}</p>
        @if($tratamiento->notas)
            <p><strong>Notas generales:</strong> {{ $tratamiento->notas }}</p>
        @endif
    </div>

    <div class="rx-section">
        <div class="rx-header">💊 Esquema de Medicación (Rx)</div>
        
        <table class="med-table">
            <thead>
                <tr>
                    <th width="40%">Medicamento / Presentación</th>
                    <th width="20%">Dosis</th>
                    <th width="40%">Frecuencia e Instrucciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tratamiento->detalleTratamientos as $detalle)
                <tr>
                    <td>
                        <div class="med-name">{{ $detalle->medicamento->nombre }}</div>
                        <div class="med-detail">
                            {{ $detalle->medicamento->presentacion ?? 'Sin presentación' }} - 
                            {{ $detalle->medicamento->via_administracion ?? 'Oral' }}
                        </div>
                    </td>
                    <td>
                        {{ $detalle->cantidad_por_toma }}
                    </td>
                    <td>
                        @if($detalle->tipo_frecuencia == 'horas')
                            Cada {{ $detalle->valor_frecuencia }} horas
                        @elseif($detalle->tipo_frecuencia == 'dias')
                            Cada {{ $detalle->valor_frecuencia }} días
                        @elseif($detalle->tipo_frecuencia == 'semanal')
                            Días específicos: 
                            @php 
                                $dias = json_decode($detalle->dias_semana) ?? [];
                                echo implode(', ', array_map('ucfirst', $dias));
                            @endphp
                        @elseif($detalle->tipo_frecuencia == 'horarios_fijos')
                            Horarios fijos: 
                            @php 
                                $horas = json_decode($detalle->horarios_fijos) ?? [];
                                echo implode(' - ', $horas);
                            @endphp
                        @endif
                        
                        @if($detalle->instrucciones)
                            <br><small style="color:#666">Nota: {{ $detalle->instrucciones }}</small>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Este documento es un historial generado automáticamente por la aplicación. No sustituye una receta médica oficial firmada por un doctor.</p>
    </div>

</body>
</html>
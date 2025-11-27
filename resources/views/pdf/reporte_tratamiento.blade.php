<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Tratamiento</title>
    <style>
        /* === FUENTES Y GENERALES === */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif; /* Poppins no siempre carga bien en PDF, Helvetica es segura y se ve limpia */
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        /* === BARRA SUPERIOR DECORATIVA (Brand Identity) === */
        .brand-bar {
            height: 10px;
            background: #013f4a; /* Tu color oscuro principal */
            width: 100%;
        }

        .container {
            padding: 40px;
        }

        /* === ENCABEZADO === */
        .header {
            margin-bottom: 40px;
            border-bottom: 2px solid #e2e8e9; /* Tu color de bordes del login */
            padding-bottom: 20px;
        }
        
        .logo-section {
            float: left;
            width: 50%;
        }

        .logo-text {
            font-size: 26px;
            font-weight: bold;
            color: #013f4a; /* Azul oscuro */
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sub-logo {
            font-size: 11px;
            color: #00a6a6; /* Tu turquesa de acento */
            font-weight: bold;
            margin-top: 2px;
        }

        .meta-info {
            float: right;
            width: 40%;
            text-align: right;
            font-size: 11px;
            color: #555;
        }

        .meta-item {
            margin-bottom: 4px;
        }

        /* === CAJA DE INFORMACIÓN (Estilo Card) === */
        .info-card {
            background-color: #f9fafa; /* Tu color de fondo de inputs */
            border: 1px solid #e2e8e9;
            border-left: 5px solid #00a6a6; /* Acento turquesa a la izquierda */
            border-radius: 8px; /* Ligeramente redondeado */
            padding: 20px;
            margin-bottom: 35px;
        }

        .info-title {
            color: #013f4a;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-row {
            margin-bottom: 8px;
            font-size: 13px;
        }

        .info-label {
            font-weight: bold;
            color: #013f4a;
            margin-right: 5px;
        }

        /* === BADGE DE ESTADO === */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px; /* Redondeado total como tus botones */
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #ffffff;
            background-color: #00a6a6; /* Tu color de botón primario */
        }

        /* === TABLA DE MEDICAMENTOS === */
        .section-title {
            font-size: 18px;
            color: #013f4a;
            font-weight: bold;
            margin-bottom: 15px;
            padding-left: 10px;
            border-left: 4px solid #013f4a;
        }

        .med-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .med-table th {
            background-color: #013f4a; /* Cabecera oscura */
            color: #ffffff;
            text-align: left;
            padding: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .med-table tr:nth-child(even) {
            background-color: #f9fafa; /* Zebra striping con tu color de fondo */
        }

        .med-table td {
            border-bottom: 1px solid #e2e8e9;
            padding: 12px;
            vertical-align: top;
            color: #444;
        }

        .med-name {
            font-size: 14px;
            font-weight: bold;
            color: #005a6b; /* Un tono medio entre tus dos colores */
        }

        .med-detail {
            font-size: 11px;
            color: #777;
            margin-top: 2px;
            font-style: italic;
        }

        /* === FOOTER === */
        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #e2e8e9;
            padding-top: 15px;
        }

        .footer p {
            margin: 2px 0;
        }

        /* Utility para limpiar floats */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>

    <div class="brand-bar"></div>

    <div class="container">
        
        <div class="header clearfix">
            <div class="logo-section">
                <div class="logo-text">VIDOMEDI</div> 
                <div class="sub-logo">Centro Médico Digital</div>
            </div>
            
            <div class="meta-info">
                <div class="meta-item"><strong>Fecha de emisión:</strong> {{ date('d/m/Y') }}</div>
                <div class="meta-item"><strong>Folio:</strong> #{{ str_pad($tratamiento->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="meta-item" style="margin-top:5px; color: #00a6a6;">Original para Paciente</div>
            </div>
        </div>

        <div class="info-card">
            <div class="info-title">Información Clínica</div>
            
            <div class="info-row">
                <span class="info-label">Tratamiento:</span> 
                {{ $tratamiento->nombre_tratamiento }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Estado:</span> 
                <span class="status-badge">{{ ucfirst($tratamiento->estado) }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Vigencia:</span> 
                Del {{ \Carbon\Carbon::parse($tratamiento->fecha_inicio)->format('d/m/Y') }} 
                al {{ \Carbon\Carbon::parse($tratamiento->fecha_fin)->format('d/m/Y') }}
            </div>

            @if($tratamiento->notas)
            <div class="info-row" style="margin-top: 10px; border-top: 1px dashed #ddd; padding-top:10px;">
                <span class="info-label">Observaciones:</span> 
                {{ $tratamiento->notas }}
            </div>
            @endif
        </div>

        <div class="section-title">Esquema Terapéutico</div>

        <table class="med-table">
            <thead>
                <tr>
                    <th width="40%">Medicamento</th>
                    <th width="20%">Dosis</th>
                    <th width="40%">Indicaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tratamiento->detalleTratamientos as $detalle)
                <tr>
                    <td>
                        <div class="med-name">{{ $detalle->medicamento->nombre }}</div>
                        <div class="med-detail">
                            {{ $detalle->medicamento->presentacion ?? '' }} • 
                            {{ $detalle->medicamento->via_administracion ?? 'Oral' }}
                        </div>
                    </td>
                    <td style="font-weight: bold; color: #333;">
                        {{ $detalle->cantidad_por_toma }}
                    </td>
                    <td>
                        <div style="color: #013f4a; font-weight: 500;">
                        @if($detalle->tipo_frecuencia == 'horas')
                            Cada {{ $detalle->valor_frecuencia }} horas
                        @elseif($detalle->tipo_frecuencia == 'dias')
                            Cada {{ $detalle->valor_frecuencia }} días
                        @elseif($detalle->tipo_frecuencia == 'semanal')
                            Días: 
                            @php 
                                $dias = json_decode($detalle->dias_semana) ?? [];
                                echo implode(', ', array_map('ucfirst', $dias));
                            @endphp
                        @elseif($detalle->tipo_frecuencia == 'horarios_fijos')
                            Horarios: 
                            @php 
                                $horas = json_decode($detalle->horarios_fijos) ?? [];
                                echo implode(' - ', $horas);
                            @endphp
                        @endif
                        </div>

                        @if($detalle->instrucciones)
                            <div style="color:#d50f25; font-size: 10px; margin-top: 4px;">
                                ⚠️ {{ $detalle->instrucciones }}
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <p><strong>VIDOMEDI - Gestión Médica Inteligente</strong></p>
            <p>Este documento es informativo. Ante cualquier duda o reacción adversa, consulte a su médico inmediatamente.</p>
        </div>

    </div>
</body>
</html>
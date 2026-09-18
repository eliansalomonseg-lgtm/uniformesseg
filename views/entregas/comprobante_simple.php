<?php
$esEscuela = ($entrega['tipo_destino'] === 'ESCUELA');
$totalPrendas = (int)$entrega['total_piezas'];
?>

<!-- Barra de Botones en Pantalla (oculta al imprimir) -->
<div class="no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
    <a href="<?= escapar(url('entregas')) ?>" class="button button-outline" style="font-size:14px;">
        ← Volver a Entregas
    </a>
    <div style="display:flex; gap:10px;">
        <button onclick="window.print()" class="button" style="font-size:14px; background:var(--wine); color:#fff; display:flex; align-items:center; gap:6px;">
            🖨️ Imprimir Comprobante Oficial
        </button>
    </div>
</div>

<?php if (isset($_GET['creada'])): ?>
    <div class="no-print request-success" style="background:#eaf6ef; border-color:#b9e2cb; color:#1e6a3d; margin-bottom:20px; padding:12px 16px; border-radius:8px;">
        ✓ <strong>¡Entrega registrada exitosamente!</strong> Folio: <strong><?= escapar($entrega['folio']) ?></strong>. Ya puedes imprimir el comprobante para recabar las firmas correspondientes.
    </div>
<?php endif; ?>

<!-- Contenedor del Comprobante (optimizado para hoja tamaño carta) -->
<div class="comprobante-hoja" style="background:#ffffff; border:1px solid #dee2e6; border-radius:8px; padding:35px 40px; max-width:850px; margin:0 auto; box-shadow:0 3px 6px rgba(0,0,0,0.05); color:#212529;">
    
    <!-- Membrete Institucional -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px double #333; padding-bottom:15px; margin-bottom:20px;">
        <div>
            <h1 style="margin:0; font-size:18px; font-weight:800; color:#4a1525; text-transform:uppercase; letter-spacing:0.5px;">
                Gobierno del Estado de Guerrero
            </h1>
            <h2 style="margin:4px 0 0 0; font-size:14px; font-weight:600; color:#555; text-transform:uppercase;">
                Secretaría de Educación Guerrero • Programa de Uniformes Escolares
            </h2>
            <div style="font-size:13px; font-weight:700; color:#222; margin-top:6px; text-transform:uppercase;">
                Acta y Comprobante de Entrega - Recepción
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:12px; color:#666; font-weight:600;">FOLIO OFICIAL:</div>
            <div style="font-size:18px; font-weight:800; color:#4a1525; letter-spacing:0.5px;"><?= escapar($entrega['folio']) ?></div>
            <div style="font-size:13px; color:#444; margin-top:4px;">
                Fecha: <strong><?= date('d/m/Y', strtotime($entrega['fecha_entrega'])) ?></strong>
            </div>
        </div>
    </div>

    <!-- Datos del Destino / Receptor -->
    <div style="background:#f8f9fa; border:1px solid #e9ecef; border-radius:6px; padding:14px 18px; margin-bottom:22px;">
        <div style="font-size:12px; font-weight:700; color:#4a1525; text-transform:uppercase; margin-bottom:8px; border-bottom:1px solid #dee2e6; padding-bottom:4px;">
            <?= $esEscuela ? 'Datos del Plantel Educativo Beneficiario' : 'Datos del Servicio Regional' ?>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; font-size:13px;">
            <?php if ($esEscuela): ?>
                <div>
                    <span style="color:#6c757d;">Nombre de la Escuela:</span><br>
                    <strong style="font-size:14px;"><?= escapar($entrega['escuela_nombre'] ?: 'N/D') ?></strong>
                </div>
                <div>
                    <span style="color:#6c757d;">Clave de CCT:</span><br>
                    <strong style="font-size:14px; color:#1971c2;"><?= escapar($entrega['escuela_cct'] ?: 'N/D') ?></strong>
                </div>
                <div>
                    <span style="color:#6c757d;">Municipio / Localidad:</span><br>
                    <strong><?= escapar($entrega['escuela_municipio'] ?: '') ?> <?= !empty($entrega['escuela_localidad']) ? ' / ' . escapar($entrega['escuela_localidad']) : '' ?></strong>
                </div>
                <div>
                    <span style="color:#6c757d;">Nivel Educativo:</span><br>
                    <strong><?= escapar($entrega['escuela_nivel'] ?: 'Primaria') ?></strong>
                </div>
            <?php else: ?>
                <div>
                    <span style="color:#6c757d;">Coordinación Regional:</span><br>
                    <strong style="font-size:14px;"><?= escapar($entrega['servicio_nombre'] ?: 'N/D') ?></strong>
                </div>
                <div>
                    <span style="color:#6c757d;">Clave Regional:</span><br>
                    <strong style="font-size:14px; color:#6741d9;"><?= escapar($entrega['servicio_clave'] ?: 'N/D') ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Desglose de Uniformes Entregados -->
    <div style="margin-bottom:22px;">
        <div style="font-size:13px; font-weight:700; color:#333; margin-bottom:10px; text-transform:uppercase;">
            Relación de Uniformes Entregados
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:center;">
            <thead>
                <tr style="background:#e9ecef; border:1px solid #ced4da;">
                    <th style="padding:8px 12px; text-align:left; border:1px solid #ced4da;">Talla</th>
                    <th style="padding:8px 12px; width:130px; border:1px solid #ced4da; color:#1971c2;">Niño</th>
                    <th style="padding:8px 12px; width:130px; border:1px solid #ced4da; color:#d6336c;">Niña</th>
                    <th style="padding:8px 12px; width:140px; text-align:right; border:1px solid #ced4da;">Total Talla</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totNino = 0; 
                $totNina = 0; 
                ?>
                <?php foreach ($tablaTallas as $talla => $datos): ?>
                    <?php 
                    $totNino += $datos['nino']; 
                    $totNina += $datos['nina']; 
                    ?>
                    <tr style="border:1px solid #dee2e6;">
                        <td style="padding:7px 12px; text-align:left; font-weight:700; border:1px solid #dee2e6;">
                            Talla <?= escapar($talla) ?>
                        </td>
                        <td style="padding:7px 12px; border:1px solid #dee2e6;">
                            <?= $datos['nino'] > 0 ? number_format($datos['nino']) . ' pzs' : '-' ?>
                        </td>
                        <td style="padding:7px 12px; border:1px solid #dee2e6;">
                            <?= $datos['nina'] > 0 ? number_format($datos['nina']) . ' pzs' : '-' ?>
                        </td>
                        <td style="padding:7px 12px; text-align:right; font-weight:700; border:1px solid #dee2e6;">
                            <?= number_format($datos['total']) ?> pzs
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f1f3f5; font-weight:800; border:2px solid #adb5bd;">
                    <td style="padding:10px 12px; text-align:left; border:1px solid #adb5bd;">GRAN TOTAL:</td>
                    <td style="padding:10px 12px; color:#1971c2; border:1px solid #adb5bd;"><?= number_format($totNino) ?> pzs</td>
                    <td style="padding:10px 12px; color:#d6336c; border:1px solid #adb5bd;"><?= number_format($totNina) ?> pzs</td>
                    <td style="padding:10px 12px; text-align:right; color:#4a1525; font-size:14px; border:1px solid #adb5bd;">
                        <?= number_format($totalPrendas) ?> prendas
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Observaciones si existen -->
    <?php if (!empty($entrega['observaciones'])): ?>
        <div style="font-size:12px; color:#495057; background:#fffbeb; border:1px solid #fef08a; padding:8px 12px; border-radius:4px; margin-bottom:24px;">
            <strong>Observaciones:</strong> <?= escapar($entrega['observaciones']) ?>
        </div>
    <?php endif; ?>

    <!-- Declaración de Conformidad -->
    <p style="font-size:11px; color:#6c757d; line-height:1.4; text-align:justify; margin-bottom:35px;">
        Por medio de la presente, la persona abajo firmante manifiesta haber recibido de entera conformidad la cantidad de uniformes escolares descritos en este comprobante, en perfecto estado físico y completos conforme a las tallas especificadas para su distribución oficial conforme a los lineamientos del Programa.
    </p>

    <!-- Firmas Oficiales -->
    <div style="display:flex; justify-content:space-around; gap:40px; margin-top:20px;">
        <div style="flex:1; text-align:center;">
            <div style="border-bottom:1px solid #333; height:45px; margin-bottom:8px;"></div>
            <div style="font-size:13px; font-weight:700; text-transform:uppercase;"><?= escapar($entrega['entregado_por_nombre'] ?: 'Personal Responsable') ?></div>
            <div style="font-size:11px; color:#666;">ENTREGÓ (PERSONAL SEG)</div>
        </div>

        <div style="flex:1; text-align:center;">
            <div style="border-bottom:1px solid #333; height:45px; margin-bottom:8px;"></div>
            <div style="font-size:13px; font-weight:700; text-transform:uppercase;"><?= escapar($entrega['recibido_por_nombre']) ?></div>
            <div style="font-size:11px; color:#666;">
                <?= escapar($entrega['recibido_por_cargo'] ?: 'RECIBIÓ DE CONFORMIDAD') ?>
                <?= !empty($entrega['recibido_por_telefono']) ? ' • Tel: ' . escapar($entrega['recibido_por_telefono']) : '' ?>
            </div>
            <div style="font-size:10px; color:#888; margin-top:3px;">Firma y Sello Oficial</div>
        </div>
    </div>
</div>

<!-- Estilos específicos para impresión -->
<style>
@media print {
    /* Ocultar elementos de navegación y botones */
    .no-print,
    .navigation,
    .page-title,
    header,
    footer,
    nav,
    .menu-toggle {
        display: none !important;
    }

    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: Arial, sans-serif !important;
    }

    .content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .comprobante-hoja {
        border: none !important;
        box-shadow: none !important;
        padding: 10px 15px !important;
        max-width: 100% !important;
        width: 100% !important;
    }
}
</style>

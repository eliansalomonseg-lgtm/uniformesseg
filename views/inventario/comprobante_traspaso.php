<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:12px;">
    <a class="back-link" style="margin-bottom:0;" href="<?= escapar(url('almacen-detalle', ['id' => $traspaso['almacen_origen_id']])) ?>">← Volver al almacén emisor</a>
    
    <div style="display:flex;gap:10px;align-items:center;">
        <button type="button" class="button" onclick="window.print()" style="background:var(--coal);display:inline-flex;align-items:center;gap:6px;">
            🖨️ Imprimir Vale de Traspaso
        </button>
        <a class="button" href="<?= escapar(url('inventario-traspaso', ['origen_id' => $traspaso['almacen_origen_id']])) ?>" style="background:#235782;">
            ⇄ Nuevo traspaso
        </a>
    </div>
</div>

<?php if (isset($_GET['exito'])): ?>
    <div class="request-success" style="margin-bottom:18px;">
        ✓ <strong>Traspaso ejecutado correctamente.</strong> Las existencias fueron descontadas del almacén emisor y añadidas de inmediato al almacén receptor con sus respectivas partidas en el kardex.
    </div>
<?php endif; ?>

<section class="summary print-section" style="padding:28px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px;border-bottom:2px solid #ead8b9;padding-bottom:18px;">
        <div>
            <span style="font-size:11px;font-weight:800;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;">Secretaría de Educación Guerrero · Logística de Distribución</span>
            <h2 style="margin:4px 0;color:var(--wine);font-size:27px;">
                Vale Oficial de Traspaso entre Almacenes
            </h2>
            <p style="margin:4px 0;font-size:14px;color:var(--muted);">
                Transferencia interna de uniformes escolares sin intermediarios
            </p>
        </div>
        <div style="text-align:right;">
            <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;">Folio de Traspaso</span>
            <div style="font-size:24px;font-weight:800;color:var(--wine);letter-spacing:.04em;"><?= escapar($traspaso['folio']) ?></div>
            <small style="color:var(--muted);">Fecha de Salida: <?= escapar($traspaso['fecha_traspaso']) ?></small>
        </div>
    </div>

    <!-- Metadatos del Traspaso -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:20px 0;padding:16px;background:#fcfaf7;border:1px solid #eee5dc;border-radius:8px;">
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Almacén Emisor (Origen)</span>
            <strong style="font-size:14px;color:var(--wine);"><?= escapar($traspaso['origen_nombre']) ?></strong>
            <small style="display:block;color:var(--muted);">Resp: <?= escapar($traspaso['origen_responsable'] ?: 'No asignado') ?></small>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Almacén Receptor (Destino)</span>
            <strong style="font-size:14px;color:#235782;"><?= escapar($traspaso['destino_nombre']) ?></strong>
            <small style="display:block;color:var(--muted);">Resp: <?= escapar($traspaso['destino_responsable'] ?: 'No asignado') ?></small>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Transportista / Unidad</span>
            <strong style="font-size:14px;color:var(--text);"><?= escapar($traspaso['transportista'] ?: 'Personal de Almacén') ?></strong>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Estatus Operativo</span>
            <span class="status status-delivered" style="display:inline-block;margin-top:4px;">COMPLETADO</span>
        </div>
    </div>

    <?php if ($traspaso['observaciones']): ?>
        <div style="margin-bottom:20px;padding:12px 16px;background:#fff;border-left:4px solid var(--gold);border-radius:6px;font-size:13px;">
            <strong>Motivo / Justificación:</strong> <?= escapar($traspaso['observaciones']) ?>
        </div>
    <?php endif; ?>

    <!-- Desglose por Tallas -->
    <h3 style="margin:20px 0 10px;font-size:18px;">Desglose de Prendas Traspasadas</h3>
    <div class="table-wrap" style="margin-bottom:24px;">
        <table>
            <thead>
                <tr>
                    <th>Talla</th>
                    <th style="text-align:center;color:#ffd0e4;">♀ Niña</th>
                    <th style="text-align:center;color:#cce7ff;">♂ Niño</th>
                    <th style="text-align:right;">Subtotal Talla</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $tallasAgrupadas = [];
                foreach ($traspaso['detalle'] as $d) {
                    $tallasAgrupadas[$d['talla']]['talla'] = $d['talla'];
                    $tallasAgrupadas[$d['talla']][$d['sexo']] = (int)$d['cantidad'];
                }
                ?>
                <?php foreach ($tallasAgrupadas as $talla => $datos): ?>
                    <?php
                    $cantNina = $datos['NINA'] ?? 0;
                    $cantNino = $datos['NINO'] ?? 0;
                    $sub = $cantNina + $cantNino;
                    ?>
                    <tr>
                        <td><strong>Talla <?= escapar($talla) ?></strong></td>
                        <td style="text-align:center;font-weight:700;color:#bd3f78;"><?= numero($cantNina) ?></td>
                        <td style="text-align:center;font-weight:700;color:#2676bd;"><?= numero($cantNino) ?></td>
                        <td style="text-align:right;font-weight:800;"><?= numero($sub) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-size:16px;background:#f8f4ef;">
                    <td><strong>TOTAL TRASPASADO</strong></td>
                    <td style="text-align:center;color:#bd3f78;"><strong><?= numero($traspaso['total_nina']) ?></strong></td>
                    <td style="text-align:center;color:#2676bd;"><strong><?= numero($traspaso['total_nino']) ?></strong></td>
                    <td style="text-align:right;color:var(--wine);"><strong><?= numero($traspaso['total_piezas']) ?> uniformes</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Sección de Firmas de Validez Normativa -->
    <div style="margin-top:40px;padding-top:20px;border-top:1px solid #e2d9cf;">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:25px;text-align:center;">
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:12px;"><?= escapar($traspaso['origen_responsable'] ?: $traspaso['origen_nombre']) ?></strong>
                <small style="color:var(--muted);">Entregó (Almacén Emisor)</small>
            </div>
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:12px;"><?= escapar($traspaso['transportista'] ?: 'Logística / Traslado') ?></strong>
                <small style="color:var(--muted);">Transportó</small>
            </div>
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:12px;"><?= escapar($traspaso['destino_responsable'] ?: $traspaso['destino_nombre']) ?></strong>
                <small style="color:var(--muted);">Recibió de Conformidad (Almacén Destino)</small>
            </div>
        </div>
    </div>
</section>

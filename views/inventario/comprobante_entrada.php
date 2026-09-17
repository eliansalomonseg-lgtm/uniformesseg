<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:12px;">
    <a class="back-link" style="margin-bottom:0;" href="<?= escapar(url('almacen-detalle', ['id' => $entrada['almacen_id']])) ?>">← Volver al detalle del almacén</a>
    
    <div style="display:flex;gap:10px;align-items:center;">
        <button type="button" class="button" onclick="window.print()" style="background:var(--coal);display:inline-flex;align-items:center;gap:6px;">
            🖨️ Imprimir Comprobante
        </button>
        <a class="button" href="<?= escapar(url('inventario-entrada', ['almacen_id' => $entrada['almacen_id']])) ?>" style="background:#246e45;">
            ➕ Nueva entrada
        </a>
    </div>
</div>

<?php if (isset($_GET['exito'])): ?>
    <div class="request-success" style="margin-bottom:18px;">
        ✓ <strong>Entrada registrada exitosamente.</strong> El inventario del almacén ha sido incrementado y el movimiento quedó registrado en el kardex oficial.
    </div>
<?php endif; ?>

<section class="summary print-section" style="padding:28px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px;border-bottom:2px solid #ead8b9;padding-bottom:18px;">
        <div>
            <span style="font-size:11px;font-weight:800;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;">Secretaría de Educación Guerrero · Control de Almacenes</span>
            <h2 style="margin:4px 0;color:var(--wine);font-size:27px;">
                Comprobante Oficial de Entrada de Uniformes
            </h2>
            <p style="margin:4px 0;font-size:14px;color:var(--muted);">
                Recepción institucional en <strong><?= escapar($entrada['almacen_nombre']) ?></strong> [<?= escapar($entrada['almacen_clave']) ?>]
            </p>
        </div>
        <div style="text-align:right;">
            <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;">Folio Oficial</span>
            <div style="font-size:24px;font-weight:800;color:var(--wine);letter-spacing:.04em;"><?= escapar($entrada['folio']) ?></div>
            <small style="color:var(--muted);">Fecha: <?= escapar($entrada['fecha_entrada']) ?></small>
        </div>
    </div>

    <!-- Metadatos de la Entrada -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:20px 0;padding:16px;background:#fcfaf7;border:1px solid #eee5dc;border-radius:8px;">
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Proveedor / Fuente de Origen</span>
            <strong style="font-size:14px;color:var(--text);"><?= escapar($entrada['proveedor_origen']) ?></strong>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">No. Remisión / Factura</span>
            <strong style="font-size:14px;color:var(--text);"><?= escapar($entrada['num_remision_factura'] ?: 'No especificada') ?></strong>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Almacén Receptor</span>
            <strong style="font-size:14px;color:var(--wine);"><?= escapar($entrada['almacen_nombre']) ?></strong>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Responsable de Almacén</span>
            <strong style="font-size:14px;color:var(--text);"><?= escapar($entrada['almacen_responsable'] ?: 'No asignado') ?></strong>
        </div>
    </div>

    <?php if ($entrada['observaciones']): ?>
        <div style="margin-bottom:20px;padding:12px 16px;background:#fff;border-left:4px solid var(--gold);border-radius:6px;font-size:13px;">
            <strong>Observaciones:</strong> <?= escapar($entrada['observaciones']) ?>
        </div>
    <?php endif; ?>

    <!-- Desglose por Tallas -->
    <h3 style="margin:20px 0 10px;font-size:18px;">Desglose de Prendas Recibidas</h3>
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
                foreach ($entrada['detalle'] as $d) {
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
                    <td><strong>TOTAL RECIBIDO</strong></td>
                    <td style="text-align:center;color:#bd3f78;"><strong><?= numero($entrada['total_nina']) ?></strong></td>
                    <td style="text-align:center;color:#2676bd;"><strong><?= numero($entrada['total_nino']) ?></strong></td>
                    <td style="text-align:right;color:var(--wine);"><strong><?= numero($entrada['total_piezas']) ?> uniformes</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Sección de Firmas de Validez Normativa -->
    <div style="margin-top:40px;padding-top:20px;border-top:1px solid #e2d9cf;">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:40px;text-align:center;">
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:13px;"><?= escapar($entrada['proveedor_origen']) ?></strong>
                <small style="color:var(--muted);">Entregó / Conforme Proveedor</small>
            </div>
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:13px;"><?= escapar($entrada['almacen_responsable'] ?: $entrada['almacen_nombre']) ?></strong>
                <small style="color:var(--muted);">Recibió y Validó / Responsable de Almacén</small>
            </div>
        </div>
    </div>
</section>

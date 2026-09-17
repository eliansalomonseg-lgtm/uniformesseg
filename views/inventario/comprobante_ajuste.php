<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:12px;">
    <a class="back-link" style="margin-bottom:0;" href="<?= escapar(url('almacen-detalle', ['id' => $ajuste['almacen_id']])) ?>">← Volver al detalle del almacén</a>
    
    <div style="display:flex;gap:10px;align-items:center;">
        <button type="button" class="button" onclick="window.print()" style="background:var(--coal);display:inline-flex;align-items:center;gap:6px;">
            🖨️ Imprimir Acta de Ajuste
        </button>
        <a class="button" href="<?= escapar(url('inventario-ajuste', ['almacen_id' => $ajuste['almacen_id']])) ?>" style="background:#963b1a;">
            ⚖️ Nuevo ajuste
        </a>
    </div>
</div>

<?php if (isset($_GET['exito'])): ?>
    <div class="request-success" style="margin-bottom:18px;">
        ✓ <strong>Ajuste de inventario aplicado con éxito.</strong> El stock físico fue actualizado y el movimiento de auditoría quedó asentado en el sistema con su folio oficial.
    </div>
<?php endif; ?>

<section class="summary print-section" style="padding:28px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px;border-bottom:2px solid #ead8b9;padding-bottom:18px;">
        <div>
            <span style="font-size:11px;font-weight:800;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;">Secretaría de Educación Guerrero · Auditoría de Inventarios</span>
            <h2 style="margin:4px 0;color:var(--wine);font-size:27px;">
                Dictamen Oficial de Ajuste y Regularización de Stock
            </h2>
            <p style="margin:4px 0;font-size:14px;color:var(--muted);">
                Almacén: <strong><?= escapar($ajuste['almacen_nombre']) ?></strong> [<?= escapar($ajuste['almacen_clave']) ?>]
            </p>
        </div>
        <div style="text-align:right;">
            <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;">Folio Oficial</span>
            <div style="font-size:24px;font-weight:800;color:var(--wine);letter-spacing:.04em;"><?= escapar($ajuste['folio']) ?></div>
            <small style="color:var(--muted);">Fecha: <?= escapar($ajuste['fecha_ajuste']) ?></small>
        </div>
    </div>

    <!-- Metadatos del Ajuste -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:20px 0;padding:16px;background:#fcfaf7;border:1px solid #eee5dc;border-radius:8px;">
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Tipo de Operación</span>
            <?php if ($ajuste['tipo_ajuste'] === 'MERMA'): ?>
                <span class="status" style="background:#fff0f1;color:#b8323f;margin-top:4px;">BAJA POR MERMA / DEFECTO</span>
            <?php elseif ($ajuste['tipo_ajuste'] === 'AJUSTE_NEGATIVO'): ?>
                <span class="status" style="background:#fff3e0;color:#c05621;margin-top:4px;">BAJA POR CONTEO FÍSICO</span>
            <?php else: ?>
                <span class="status" style="background:#eaf5ef;color:#38825d;margin-top:4px;">ALTA POR CONTEO FÍSICO</span>
            <?php endif; ?>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">No. de Acta Circunstanciada</span>
            <strong style="font-size:14px;color:var(--text);"><?= escapar($ajuste['num_acta'] ?: 'Sin acta física referenciada') ?></strong>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Motivo Dictaminado</span>
            <strong style="font-size:14px;color:var(--wine);"><?= escapar($ajuste['motivo']) ?></strong>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Responsable de Almacén</span>
            <strong style="font-size:14px;color:var(--text);"><?= escapar($ajuste['almacen_responsable'] ?: 'No asignado') ?></strong>
        </div>
    </div>

    <?php if ($ajuste['observaciones']): ?>
        <div style="margin-bottom:20px;padding:12px 16px;background:#fff;border-left:4px solid var(--wine);border-radius:6px;font-size:13px;">
            <strong>Observaciones de Auditoría:</strong> <?= escapar($ajuste['observaciones']) ?>
        </div>
    <?php endif; ?>

    <!-- Desglose por Tallas -->
    <h3 style="margin:20px 0 10px;font-size:18px;">Desglose de Prendas Dictaminadas</h3>
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
                foreach ($ajuste['detalle'] as $d) {
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
                    <td><strong>TOTAL AJUSTADO</strong></td>
                    <td style="text-align:center;color:#bd3f78;"><strong><?= numero($ajuste['total_nina']) ?></strong></td>
                    <td style="text-align:center;color:#2676bd;"><strong><?= numero($ajuste['total_nino']) ?></strong></td>
                    <td style="text-align:right;color:var(--wine);"><strong><?= numero($ajuste['total_piezas']) ?> uniformes</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Sección de Firmas de Validez Normativa -->
    <div style="margin-top:40px;padding-top:20px;border-top:1px solid #e2d9cf;">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:40px;text-align:center;">
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:13px;"><?= escapar($ajuste['almacen_responsable'] ?: $ajuste['almacen_nombre']) ?></strong>
                <small style="color:var(--muted);">Responsable de Almacén / Solicitó Ajuste</small>
            </div>
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:13px;">Órgano Interno de Control / Auditoría</strong>
                <small style="color:var(--muted);">Verificó y Autorizó</small>
            </div>
        </div>
    </div>
</section>

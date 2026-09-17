<?php
$totalTallas = count($tallas);
?>
<a class="back-link" href="<?= escapar(url('almacenes')) ?>">← Volver a almacenes</a>

<section class="warehouse-detail-hero" style="background:linear-gradient(120deg,#1f5e3b,#2d8253);margin-bottom:20px;">
    <div>
        <span style="color:#d4f3e1;">OPERACIÓN DE INVENTARIO</span>
        <h3>➕ Registrar Entrada de Uniformes</h3>
        <p>Recepción oficial de prendas escolares por compra, maquila, proveedor o dotación institucional.</p>
    </div>
    <div class="warehouse-detail-total" style="border-left-color:rgba(255,255,255,0.25);">
        <span>Total a ingresar</span>
        <strong id="live-total-general">0</strong>
        <small>piezas acumuladas</small>
    </div>
</section>

<?php if ($error): ?>
    <div class="notice request-error" style="margin-bottom:18px;">
        ⚠️ <strong>Error al procesar la entrada:</strong> <?= escapar($error) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= escapar(url('inventario-entrada')) ?>" class="inventory-form" id="form-entrada">
    <div class="inventory-grid-layout">
        <!-- Panel de Datos Generales -->
        <article class="chart-panel" style="padding:22px;">
            <div class="panel-heading" style="margin-bottom:16px;">
                <div>
                    <span>Paso 1</span>
                    <h3>Datos del Comprobante y Recepción</h3>
                </div>
            </div>

            <div class="request-fields">
                <label>
                    Almacén de Recepción *
                    <select name="almacen_id" id="almacen_id" required>
                        <option value="">-- Seleccione el almacén receptor --</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= (int)$a['id'] ?>" <?= $almacenIdInicial === (int)$a['id'] ? 'selected' : '' ?>>
                                [<?= escapar($a['clave']) ?>] <?= escapar($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Fecha de Recepción *
                    <input type="date" name="fecha_entrada" value="<?= date('Y-m-d') ?>" required>
                </label>

                <label class="full-field">
                    Proveedor / Maquilador / Fuente de Origen *
                    <input type="text" name="proveedor_origen" placeholder="Ej. Confecciones Textiles del Sur S.A. de C.V. / Programa Estatal" required>
                </label>

                <label class="full-field">
                    No. de Remisión / Factura / Orden de Compra
                    <input type="text" name="num_remision_factura" placeholder="Ej. REM-98421 / FACT-2026-A">
                </label>

                <label class="full-field">
                    Observaciones o Notas de Entrega
                    <textarea name="observaciones" placeholder="Detalles de empaque, lote, condiciones de recepción o notas adicionales..."></textarea>
                </label>
            </div>
        </article>

        <!-- Resumen Rápido Lateral -->
        <aside class="chart-panel" style="padding:22px;display:flex;flex-direction:column;justify-content:space-between;">
            <div>
                <div class="panel-heading" style="margin-bottom:16px;">
                    <div>
                        <span>Desglose</span>
                        <h3>Resumen del Lote</h3>
                    </div>
                </div>
                <div class="warehouse-kpis" style="grid-template-columns:1fr 1fr;gap:12px;margin:0 0 15px;">
                    <article class="girl-kpi" style="padding:14px;">
                        <span>♀ Niña</span>
                        <strong id="live-total-nina">0</strong>
                    </article>
                    <article class="boy-kpi" style="padding:14px;">
                        <span>♂ Niño</span>
                        <strong id="live-total-nino">0</strong>
                    </article>
                </div>
                <div class="notice gold" style="padding:14px;font-size:12px;margin:0;">
                    ℹ️ <strong>Efecto en Inventario:</strong> Las prendas ingresadas se sumarán directamente al stock físico disponible del almacén seleccionado y quedarán auditadas en el kardex institucional con su folio único.
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="button" style="width:100%;text-align:center;padding:14px;font-size:15px;background:#246e45;">
                    ✓ Confirmar y Registrar Entrada
                </button>
            </div>
        </aside>
    </div>

    <!-- Panel de Captura por Tallas -->
    <article class="chart-panel" style="margin-top:20px;padding:22px;">
        <div class="panel-heading" style="margin-bottom:16px;">
            <div>
                <span>Paso 2</span>
                <h3>Cantidades por Talla y Género</h3>
            </div>
            <span class="panel-tag"><?= numero($totalTallas) ?> tallas disponibles</span>
        </div>

        <div class="table-wrap">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th style="width:140px;">Talla</th>
                        <th style="text-align:center;color:#ffd0e4;">♀ Uniformes Niña</th>
                        <th style="text-align:center;color:#cce7ff;">♂ Uniformes Niño</th>
                        <th style="text-align:right;width:150px;">Subtotal Talla</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tallas as $t): ?>
                        <tr>
                            <td>
                                <div class="size-label" style="display:inline-flex;align-items:center;gap:8px;padding:4px 10px;">
                                    <strong>Talla <?= escapar($t['talla']) ?></strong>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <input type="number" name="cantidad[NINA][<?= (int)$t['id'] ?>]" min="0" value="0"
                                       class="input-qty input-nina" data-talla-id="<?= (int)$t['id'] ?>"
                                       style="width:110px;text-align:center;font-weight:700;color:#bd3f78;">
                            </td>
                            <td style="text-align:center;">
                                <input type="number" name="cantidad[NINO][<?= (int)$t['id'] ?>]" min="0" value="0"
                                       class="input-qty input-nino" data-talla-id="<?= (int)$t['id'] ?>"
                                       style="width:110px;text-align:center;font-weight:700;color:#2676bd;">
                            </td>
                            <td style="text-align:right;">
                                <strong class="subtotal-talla" id="subtotal-talla-<?= (int)$t['id'] ?>">0</strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>TOTAL GENERAL</strong></td>
                        <td style="text-align:center;"><strong id="foot-total-nina" style="color:#bd3f78;font-size:17px;">0</strong></td>
                        <td style="text-align:center;"><strong id="foot-total-nino" style="color:#2676bd;font-size:17px;">0</strong></td>
                        <td style="text-align:right;"><strong id="foot-total-general" style="color:var(--wine);font-size:19px;">0</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </article>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputsNina = document.querySelectorAll('.input-nina');
    const inputsNino = document.querySelectorAll('.input-nino');
    const liveGeneral = document.getElementById('live-total-general');
    const liveNina = document.getElementById('live-total-nina');
    const liveNino = document.getElementById('live-total-nino');
    const footGeneral = document.getElementById('foot-total-general');
    const footNina = document.getElementById('foot-total-nina');
    const footNino = document.getElementById('foot-total-nino');

    function actualizarTotales() {
        let totalNina = 0;
        let totalNino = 0;

        inputsNina.forEach(input => {
            const val = parseInt(input.value) || 0;
            totalNina += val;
            const tallaId = input.getAttribute('data-talla-id');
            const inputComp = document.querySelector(`.input-nino[data-talla-id="${tallaId}"]`);
            const valComp = parseInt(inputComp?.value) || 0;
            const sub = document.getElementById(`subtotal-talla-${tallaId}`);
            if (sub) sub.textContent = (val + valComp).toLocaleString();
        });

        inputsNino.forEach(input => {
            const val = parseInt(input.value) || 0;
            totalNino += val;
        });

        const totalGen = totalNina + totalNino;
        liveGeneral.textContent = totalGen.toLocaleString();
        footGeneral.textContent = totalGen.toLocaleString();
        liveNina.textContent = totalNina.toLocaleString();
        footNina.textContent = totalNina.toLocaleString();
        liveNino.textContent = totalNino.toLocaleString();
        footNino.textContent = totalNino.toLocaleString();
    }

    document.querySelectorAll('.input-qty').forEach(input => {
        input.addEventListener('input', actualizarTotales);
        input.addEventListener('focus', function() { if (this.value === '0') this.value = ''; });
        input.addEventListener('blur', function() { if (this.value === '') this.value = '0'; });
    });

    actualizarTotales();
});
</script>

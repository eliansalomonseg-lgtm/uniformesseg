<?php
$totalTallas = count($tallas);
?>
<a class="back-link" href="<?= escapar(url('almacenes')) ?>">← Volver a almacenes</a>

<section class="warehouse-detail-hero" style="background:linear-gradient(120deg,#873d1f,#a64b26);margin-bottom:20px;">
    <div>
        <span style="color:#fae2d6;">AUDITORÍA Y CONTROL</span>
        <h3>⚖️ Ajuste de Inventario y Mermas</h3>
        <p>Registro de bajas por prendas defectuosas/dañadas o regularizaciones por conteo físico.</p>
    </div>
    <div class="warehouse-detail-total" style="border-left-color:rgba(255,255,255,0.25);">
        <span>Total a ajustar</span>
        <strong id="live-total-general">0</strong>
        <small>piezas indicadas</small>
    </div>
</section>

<?php if ($error): ?>
    <div class="notice request-error" style="margin-bottom:18px;">
        ⚠️ <strong>Error al procesar el ajuste:</strong> <?= escapar($error) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= escapar(url('inventario-ajuste')) ?>" class="inventory-form" id="form-ajuste">
    <div class="inventory-grid-layout">
        <!-- Panel de Datos del Ajuste -->
        <article class="chart-panel" style="padding:22px;">
            <div class="panel-heading" style="margin-bottom:16px;">
                <div>
                    <span>Paso 1</span>
                    <h3>Tipo de Movimiento y Justificación Oficial</h3>
                </div>
            </div>

            <div class="request-fields">
                <label>
                    Almacén *
                    <select name="almacen_id" id="almacen_id" required>
                        <option value="">-- Seleccione el almacén --</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= (int)$a['id'] ?>" <?= $almacenIdInicial === (int)$a['id'] ? 'selected' : '' ?>>
                                [<?= escapar($a['clave']) ?>] <?= escapar($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Tipo de Ajuste *
                    <select name="tipo_ajuste" id="tipo_ajuste" required onchange="cambiarTipoAjuste(this.value)">
                        <option value="MERMA">🚫 Merma / Daño / Defecto de Fábrica (Baja)</option>
                        <option value="AJUSTE_NEGATIVO">📉 Faltante por Conteo Físico (Baja)</option>
                        <option value="AJUSTE_POSITIVO">📈 Sobrante por Conteo Físico (Alta)</option>
                    </select>
                </label>

                <label>
                    Fecha del Ajuste / Acta *
                    <input type="date" name="fecha_ajuste" value="<?= date('Y-m-d') ?>" required>
                </label>

                <label>
                    No. de Acta Circunstanciada / Oficio
                    <input type="text" name="num_acta" placeholder="Ej. ACTA-OIC-2026-014 / OF-ALM-088">
                </label>

                <label class="full-field">
                    Motivo Detallado / Causa del Ajuste *
                    <input type="text" name="motivo" placeholder="Ej. Prendas con defecto de costura reportadas por control de calidad" required>
                </label>

                <label class="full-field">
                    Observaciones o Dictamen
                    <textarea name="observaciones" placeholder="Detalles circunstanciados, testigos de la verificación física o medidas tomadas..."></textarea>
                </label>
            </div>
        </article>

        <!-- Resumen Rápido Lateral -->
        <aside class="chart-panel" style="padding:22px;display:flex;flex-direction:column;justify-content:space-between;">
            <div>
                <div class="panel-heading" style="margin-bottom:16px;">
                    <div>
                        <span>Dictamen</span>
                        <h3>Resumen del Ajuste</h3>
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
                <div class="notice" id="tipo-notice" style="padding:14px;font-size:12px;margin:0;border-left-color:#a64b26;">
                    ⚠️ <strong>Acción de Auditoría:</strong> Las mermas y ajustes negativos descuentan stock físico del almacén. Asegúrese de contar con el acta circunstanciada física correspondiente firmada por el responsable del almacén.
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="button" id="btn-submit-ajuste" style="width:100%;text-align:center;padding:14px;font-size:15px;background:#963b1a;">
                    ✓ Confirmar y Registrar Ajuste
                </button>
            </div>
        </aside>
    </div>

    <!-- Panel de Captura por Tallas -->
    <article class="chart-panel" style="margin-top:20px;padding:22px;">
        <div class="panel-heading" style="margin-bottom:16px;">
            <div>
                <span>Paso 2</span>
                <h3>Cantidades por Talla</h3>
            </div>
            <span class="panel-tag" id="tag-operacion">Baja de stock</span>
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
function cambiarTipoAjuste(tipo) {
    const tag = document.getElementById('tag-operacion');
    const notice = document.getElementById('tipo-notice');
    const btn = document.getElementById('btn-submit-ajuste');

    if (tipo === 'AJUSTE_POSITIVO') {
        tag.textContent = 'Alta de stock por sobrante';
        notice.innerHTML = '📈 <strong>Regularización Positiva:</strong> Las prendas indicadas se sumarán al inventario físico disponible del almacén tras comprobar sobrantes en conteo físico.';
        btn.style.background = '#246e45';
    } else if (tipo === 'MERMA') {
        tag.textContent = 'Baja de stock por merma/daño';
        notice.innerHTML = '🚫 <strong>Baja por Merma:</strong> Las prendas dañadas o con defecto se descontarán definitivamente del inventario físico disponible.';
        btn.style.background = '#963b1a';
    } else {
        tag.textContent = 'Baja de stock por faltante';
        notice.innerHTML = '📉 <strong>Regularización Negativa:</strong> Las prendas indicadas se descontarán del inventario físico para cuadrar con el conteo de auditoría.';
        btn.style.background = '#963b1a';
    }
}

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

    document.getElementById('form-ajuste').addEventListener('submit', function(e) {
        const totalGen = parseInt(liveGeneral.textContent.replace(/,/g, '')) || 0;
        if (totalGen <= 0) {
            e.preventDefault();
            alert('Debe ingresar al menos una prenda para registrar el ajuste.');
            return false;
        }
    });

    actualizarTotales();
});
</script>

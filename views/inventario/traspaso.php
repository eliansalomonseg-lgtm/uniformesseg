<?php
$totalTallas = count($tallas);
?>
<a class="back-link" href="<?= escapar(url('almacenes')) ?>">← Volver a almacenes</a>

<section class="warehouse-detail-hero" style="background:linear-gradient(120deg,#1c4568,#286394);margin-bottom:20px;">
    <div>
        <span style="color:#d3e8fa;">OPERACIÓN DE INVENTARIO</span>
        <h3>⇄ Traspaso Directo entre Almacenes</h3>
        <p>Transferencia oficial y balanceo de stock entre almacenes regionales sin intermediarios.</p>
    </div>
    <div class="warehouse-detail-total" style="border-left-color:rgba(255,255,255,0.25);">
        <span>Total a transferir</span>
        <strong id="live-total-general">0</strong>
        <small>piezas seleccionadas</small>
    </div>
</section>

<?php if ($error): ?>
    <div class="notice request-error" style="margin-bottom:18px;">
        ⚠️ <strong>Error al procesar el traspaso:</strong> <?= escapar($error) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= escapar(url('inventario-traspaso')) ?>" class="inventory-form" id="form-traspaso">
    <div class="inventory-grid-layout">
        <!-- Panel de Sedes y Ruta -->
        <article class="chart-panel" style="padding:22px;">
            <div class="panel-heading" style="margin-bottom:16px;">
                <div>
                    <span>Paso 1</span>
                    <h3>Ruta de Transferencia y Datos Generales</h3>
                </div>
            </div>

            <div class="request-fields">
                <label>
                    Almacén Origen (Emisor) *
                    <select name="almacen_origen_id" id="almacen_origen_id" required onchange="cambiarAlmacenOrigen(this.value)">
                        <option value="">-- Seleccione almacén de salida --</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= (int)$a['id'] ?>" <?= $origenIdInicial === (int)$a['id'] ? 'selected' : '' ?>>
                                [<?= escapar($a['clave']) ?>] <?= escapar($a['nombre']) ?> (<?= numero($a['disponible']) ?> disp.)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Almacén Destino (Receptor) *
                    <select name="almacen_destino_id" id="almacen_destino_id" required>
                        <option value="">-- Seleccione almacén receptor --</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= (int)$a['id'] ?>">
                                [<?= escapar($a['clave']) ?>] <?= escapar($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Fecha de Envío / Traspaso *
                    <input type="date" name="fecha_traspaso" value="<?= date('Y-m-d') ?>" required>
                </label>

                <label>
                    Responsable de Transporte / Logística
                    <input type="text" name="transportista" placeholder="Ej. Chofer Juan Pérez / Unidad Móvil 04 SEG">
                </label>

                <label class="full-field">
                    Motivo u Observaciones del Traspaso
                    <textarea name="observaciones" placeholder="Justificación de la redistribución, oficio de solicitud regional o notas adicionales..."></textarea>
                </label>
            </div>
        </article>

        <!-- Resumen Rápido Lateral -->
        <aside class="chart-panel" style="padding:22px;display:flex;flex-direction:column;justify-content:space-between;">
            <div>
                <div class="panel-heading" style="margin-bottom:16px;">
                    <div>
                        <span>Balance</span>
                        <h3>Resumen del Movimiento</h3>
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
                    ✓ <strong>Garantía Transaccional:</strong> El sistema descontará automáticamente del stock disponible del almacén emisor y lo acreditará de forma inmediata en el almacén receptor, generando los movimientos cruzados en el kardex institucional.
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="button" style="width:100%;text-align:center;padding:14px;font-size:15px;background:#235782;">
                    ✓ Confirmar y Ejecutar Traspaso
                </button>
            </div>
        </aside>
    </div>

    <!-- Panel de Captura por Tallas con Disponibilidad en Origen -->
    <article class="chart-panel" style="margin-top:20px;padding:22px;">
        <div class="panel-heading" style="margin-bottom:16px;">
            <div>
                <span>Paso 2</span>
                <h3>Cantidades a Traspasar</h3>
            </div>
            <span class="panel-tag" id="label-origen-stock">Disponibilidad en origen</span>
        </div>

        <div class="table-wrap">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th style="width:130px;">Talla</th>
                        <th style="text-align:center;color:#ffd0e4;">♀ Traspasar Niña</th>
                        <th style="text-align:center;font-size:11px;color:#d8c7aa;">Disp. Niña en Origen</th>
                        <th style="text-align:center;color:#cce7ff;">♂ Traspasar Niño</th>
                        <th style="text-align:center;font-size:11px;color:#d8c7aa;">Disp. Niño en Origen</th>
                        <th style="text-align:right;width:140px;">Subtotal Talla</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tallas as $t): ?>
                        <?php
                        $dispNina = $existenciasOrigen[$t['id']]['disponible_nina'] ?? 0;
                        $dispNino = $existenciasOrigen[$t['id']]['disponible_nino'] ?? 0;
                        ?>
                        <tr>
                            <td>
                                <div class="size-label" style="display:inline-flex;align-items:center;gap:8px;padding:4px 10px;">
                                    <strong>Talla <?= escapar($t['talla']) ?></strong>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <input type="number" name="cantidad[NINA][<?= (int)$t['id'] ?>]" min="0" max="<?= (int)$dispNina ?>" value="0"
                                       class="input-qty input-nina" data-talla-id="<?= (int)$t['id'] ?>"
                                       style="width:105px;text-align:center;font-weight:700;color:#bd3f78;">
                            </td>
                            <td style="text-align:center;font-weight:600;color:var(--muted);" class="disp-nina" id="disp-nina-<?= (int)$t['id'] ?>">
                                <?= numero($dispNina) ?>
                            </td>
                            <td style="text-align:center;">
                                <input type="number" name="cantidad[NINO][<?= (int)$t['id'] ?>]" min="0" max="<?= (int)$dispNino ?>" value="0"
                                       class="input-qty input-nino" data-talla-id="<?= (int)$t['id'] ?>"
                                       style="width:105px;text-align:center;font-weight:700;color:#2676bd;">
                            </td>
                            <td style="text-align:center;font-weight:600;color:var(--muted);" class="disp-nino" id="disp-nino-<?= (int)$t['id'] ?>">
                                <?= numero($dispNino) ?>
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
                        <td></td>
                        <td style="text-align:center;"><strong id="foot-total-nino" style="color:#2676bd;font-size:17px;">0</strong></td>
                        <td></td>
                        <td style="text-align:right;"><strong id="foot-total-general" style="color:var(--wine);font-size:19px;">0</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </article>
</form>

<script>
function cambiarAlmacenOrigen(almacenId) {
    if (!almacenId) return;
    const destinoSelect = document.getElementById('almacen_destino_id');
    // Inhabilitar en destino la opción seleccionada como origen
    Array.from(destinoSelect.options).forEach(opt => {
        opt.disabled = (opt.value === almacenId);
        if (opt.value === almacenId && opt.selected) {
            opt.selected = false;
        }
    });

    // Consultar existencias dinámicas del almacén origen
    fetch('index.php?ruta=inventario-existencias-json&almacen_id=' + almacenId)
        .then(res => res.json())
        .then(data => {
            document.querySelectorAll('.input-nina').forEach(input => {
                const id = input.getAttribute('data-talla-id');
                const disp = data[id] ? data[id].disponible_nina : 0;
                input.max = disp;
                const tdDisp = document.getElementById('disp-nina-' + id);
                if (tdDisp) tdDisp.textContent = disp.toLocaleString();
            });
            document.querySelectorAll('.input-nino').forEach(input => {
                const id = input.getAttribute('data-talla-id');
                const disp = data[id] ? data[id].disponible_nino : 0;
                input.max = disp;
                const tdDisp = document.getElementById('disp-nino-' + id);
                if (tdDisp) tdDisp.textContent = disp.toLocaleString();
            });
            document.getElementById('label-origen-stock').textContent = 'Existencias cargadas de origen';
        })
        .catch(err => console.error('Error al cargar existencias:', err));
}

document.addEventListener('DOMContentLoaded', () => {
    const origenSelect = document.getElementById('almacen_origen_id');
    if (origenSelect.value) {
        cambiarAlmacenOrigen(origenSelect.value);
    }

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

    document.getElementById('form-traspaso').addEventListener('submit', function(e) {
        const origen = document.getElementById('almacen_origen_id').value;
        const destino = document.getElementById('almacen_destino_id').value;
        if (origen && destino && origen === destino) {
            e.preventDefault();
            alert('El almacén origen y el almacén destino no pueden ser el mismo.');
            return false;
        }
        const totalGen = parseInt(liveGeneral.textContent.replace(/,/g, '')) || 0;
        if (totalGen <= 0) {
            e.preventDefault();
            alert('Debe ingresar al menos una prenda para realizar el traspaso.');
            return false;
        }
    });

    actualizarTotales();
});
</script>

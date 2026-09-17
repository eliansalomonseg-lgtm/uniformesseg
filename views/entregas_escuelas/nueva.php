<?php
$escuela = $escuelaData;
?>
<a class="back-link" href="<?= escapar(url('solicitud-detalle', ['id' => $solicitud['id']])) ?>">← Volver al detalle de la solicitud</a>

<section class="warehouse-detail-hero" style="background:linear-gradient(120deg,#5f1722,#8a1c2e);margin-bottom:20px;">
    <div>
        <span style="color:#f2d39e;">FASE FINAL DE ENTREGA · PLANTEL EDUCATIVO</span>
        <h3>📦 Entrega de Uniformes a la Escuela</h3>
        <p>Emisión del Acta Oficial de Entrega-Recepción con Código QR y validación normativa.</p>
    </div>
    <div class="warehouse-detail-total" style="border-left-color:rgba(255,255,255,0.25);">
        <span>Total a entregar</span>
        <strong id="live-total-general"><?= numero($escuela['total']) ?></strong>
        <small>prendas asignadas</small>
    </div>
</section>

<?php if ($error): ?>
    <div class="notice request-error" style="margin-bottom:18px;">
        ⚠️ <strong>Error al registrar la entrega:</strong> <?= escapar($error) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= escapar(url('entrega-escuela-nueva', ['solicitud_id' => $solicitud['id'], 'escuela_id' => $escuela['escuela_id']])) ?>" class="inventory-form" id="form-entrega-escuela">
    <input type="hidden" name="solicitud_id" value="<?= (int)$solicitud['id'] ?>">
    <input type="hidden" name="escuela_id" value="<?= (int)$escuela['escuela_id'] ?>">
    <input type="hidden" name="servicio_regional_id" value="<?= (int)$escuela['servicio_regional_id'] ?>">

    <div class="inventory-grid-layout">
        <!-- Panel de Datos del Plantel y Quien Recibe -->
        <article class="chart-panel" style="padding:22px;">
            <div class="panel-heading" style="margin-bottom:16px;">
                <div>
                    <span>Paso 1</span>
                    <h3>Datos del Plantel y Representación Escolar</h3>
                </div>
            </div>

            <!-- Ficha del Plantel -->
            <div style="margin-bottom:18px;padding:14px;background:#fcfaf7;border:1px solid #eedecb;border-radius:8px;">
                <span class="pill-cct" style="background:var(--wine);"><?= escapar($escuela['cct']) ?></span>
                <strong style="font-size:15px;color:var(--text);"><?= escapar($escuela['nombre']) ?></strong>
                <p style="margin:4px 0 0;font-size:12px;color:var(--muted);">
                    <?= escapar($escuela['municipio']) ?> · <?= escapar($escuela['localidad']) ?> | <?= escapar($escuela['servicio_regional']) ?>
                </p>
            </div>

            <div class="request-fields">
                <label>
                    Fecha de Entrega Física *
                    <input type="date" name="fecha_entrega" value="<?= date('Y-m-d') ?>" required>
                </label>

                <label>
                    Enlace / Funcionario que Entrega *
                    <input type="text" name="entregado_por_nombre" placeholder="Nombre completo del personal que entrega" value="Representante de Servicios Regionales" required>
                </label>

                <label class="full-field">
                    Nombre del Director(a) o Representante que Recibe *
                    <input type="text" name="recibido_por_nombre" placeholder="Nombre completo del titular que recibe en la escuela" required>
                </label>

                <label>
                    Cargo Oficial *
                    <select name="recibido_por_cargo" required>
                        <option value="Director(a)">Director(a) de la Escuela</option>
                        <option value="Subdirector(a)">Subdirector(a)</option>
                        <option value="Encargado(a) de Dirección">Encargado(a) de Dirección</option>
                        <option value="Comité de Padres de Familia">Presidente(a) Comité de Padres</option>
                        <option value="Docente Comisionado(a)">Docente Comisionado(a)</option>
                    </select>
                </label>

                <label>
                    Identificación Oficial (INE / Clave Presupuestal)
                    <input type="text" name="recibido_por_identificacion" placeholder="Ej. INE / Cédula / Clave servidor público">
                </label>

                <label class="full-field">
                    Teléfono de Contacto
                    <input type="text" name="recibido_por_telefono" placeholder="Teléfono del plantel o director">
                </label>

                <label class="full-field">
                    Observaciones o Incidencias
                    <textarea name="observaciones" placeholder="Condiciones del paquete, sello del plantel o notas complementarias..."></textarea>
                </label>
            </div>
        </article>

        <!-- Resumen Lateral -->
        <aside class="chart-panel" style="padding:22px;display:flex;flex-direction:column;justify-content:space-between;">
            <div>
                <div class="panel-heading" style="margin-bottom:16px;">
                    <div>
                        <span>Validez</span>
                        <h3>Resumen del Acta</h3>
                    </div>
                </div>
                <div class="warehouse-kpis" style="grid-template-columns:1fr 1fr;gap:12px;margin:0 0 15px;">
                    <article class="girl-kpi" style="padding:14px;">
                        <span>♀ Niña</span>
                        <strong id="live-total-nina"><?= numero($escuela['nina']) ?></strong>
                    </article>
                    <article class="boy-kpi" style="padding:14px;">
                        <span>♂ Niño</span>
                        <strong id="live-total-nino"><?= numero($escuela['nino']) ?></strong>
                    </article>
                </div>
                <div class="notice gold" style="padding:14px;font-size:12px;margin:0;">
                    📜 <strong>Emisión Inmediata:</strong> Al confirmar, se generará el <b>Acta Oficial de Entrega-Recepción</b> con su Código QR único de autenticidad para firma del Director y sello físico de la escuela.
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="button" style="width:100%;text-align:center;padding:14px;font-size:15px;background:var(--wine);">
                    ✓ Generar Acta Oficial de Entrega
                </button>
            </div>
        </aside>
    </div>

    <!-- Matriz de Tallas para la Escuela -->
    <article class="chart-panel" style="margin-top:20px;padding:22px;">
        <div class="panel-heading" style="margin-bottom:16px;">
            <div>
                <span>Paso 2</span>
                <h3>Desglose de Prendas a Entregar al Plantel</h3>
            </div>
            <span class="panel-tag">Prendas según requerimiento</span>
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
                        <?php
                        $cantNina = $escuela['desglose']['NINA'][$t['id']] ?? 0;
                        $cantNino = $escuela['desglose']['NINO'][$t['id']] ?? 0;
                        $sub = $cantNina + $cantNino;
                        ?>
                        <tr>
                            <td>
                                <div class="size-label" style="display:inline-flex;align-items:center;gap:8px;padding:4px 10px;">
                                    <strong>Talla <?= escapar($t['talla']) ?></strong>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <input type="number" name="cantidad[NINA][<?= (int)$t['id'] ?>]" min="0" value="<?= (int)$cantNina ?>"
                                       class="input-qty input-nina" data-talla-id="<?= (int)$t['id'] ?>"
                                       style="width:110px;text-align:center;font-weight:700;color:#bd3f78;">
                            </td>
                            <td style="text-align:center;">
                                <input type="number" name="cantidad[NINO][<?= (int)$t['id'] ?>]" min="0" value="<?= (int)$cantNino ?>"
                                       class="input-qty input-nino" data-talla-id="<?= (int)$t['id'] ?>"
                                       style="width:110px;text-align:center;font-weight:700;color:#2676bd;">
                            </td>
                            <td style="text-align:right;">
                                <strong class="subtotal-talla" id="subtotal-talla-<?= (int)$t['id'] ?>"><?= numero($sub) ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>TOTAL A ENTREGAR</strong></td>
                        <td style="text-align:center;"><strong id="foot-total-nina" style="color:#bd3f78;font-size:17px;"><?= numero($escuela['nina']) ?></strong></td>
                        <td style="text-align:center;"><strong id="foot-total-nino" style="color:#2676bd;font-size:17px;"><?= numero($escuela['nino']) ?></strong></td>
                        <td style="text-align:right;"><strong id="foot-total-general" style="color:var(--wine);font-size:19px;"><?= numero($escuela['total']) ?></strong></td>
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

    document.getElementById('form-entrega-escuela').addEventListener('submit', function(e) {
        const totalGen = parseInt(liveGeneral.textContent.replace(/,/g, '')) || 0;
        if (totalGen <= 0) {
            e.preventDefault();
            alert('Debe entregar al menos una prenda para registrar el acta.');
            return false;
        }
    });

    actualizarTotales();
});
</script>

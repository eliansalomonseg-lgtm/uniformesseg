<?php if (!empty($error)): ?>
    <div class="notice request-error" style="background:#fde8e8; border-color:#f8b4b4; color:#9b1c1c; margin-bottom:20px; padding:14px 18px; border-radius:8px;">
        <strong>⚠ Error:</strong> <?= escapar($error) ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= escapar(url('entrega-guardar')) ?>" id="formEntrega" style="max-width:960px;">
    
    <!-- 1. Tipo de Destino -->
    <div class="card" style="margin-bottom:24px; padding:22px; background:#ffffff; border-radius:10px; border:1px solid var(--border); box-shadow:0 2px 4px rgba(0,0,0,0.03);">
        <h3 style="margin-top:0; margin-bottom:14px; font-size:16px; color:var(--wine); border-bottom:2px solid var(--border); padding-bottom:8px;">
            1. ¿A quién se le entrega?
        </h3>
        
        <div style="display:flex; gap:24px; margin-bottom:18px; flex-wrap:wrap;">
            <label style="display:flex; align-items:center; gap:8px; font-size:15px; font-weight:600; cursor:pointer;">
                <input type="radio" name="tipo_destino" value="ESCUELA" id="radioEscuela" <?= (($_POST['tipo_destino'] ?? 'ESCUELA') === 'ESCUELA') ? 'checked' : '' ?> onchange="toggleDestino()">
                🏫 Entrega a Escuela (Plantel / CCT)
            </label>
            <label style="display:flex; align-items:center; gap:8px; font-size:15px; font-weight:600; cursor:pointer;">
                <input type="radio" name="tipo_destino" value="SERVICIO_REGIONAL" id="radioRegional" <?= (($_POST['tipo_destino'] ?? '') === 'SERVICIO_REGIONAL') ? 'checked' : '' ?> onchange="toggleDestino()">
                🏢 Entrega a Servicio Regional
            </label>
        </div>

        <!-- Selector de Escuela -->
        <div id="campoEscuela" style="margin-top:12px;">
            <label for="escuela_id" style="display:block; font-weight:600; margin-bottom:6px;">Selecciona la Escuela receptora: <span style="color:#e03131;">*</span></label>
            <select name="escuela_id" id="escuela_id" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
                <option value="">-- Buscar / Seleccionar escuela por CCT o nombre --</option>
                <?php foreach ($escuelas as $esc): ?>
                    <option value="<?= $esc['id'] ?>" <?= (isset($_POST['escuela_id']) && (int)$_POST['escuela_id'] === (int)$esc['id']) ? 'selected' : '' ?>>
                        <?= escapar($esc['cct']) ?> - <?= escapar($esc['nombre']) ?> (<?= escapar($esc['municipio']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color:var(--muted); display:block; margin-top:4px;">Se muestran las escuelas registradas con su Centro de Trabajo (CCT) y Municipio.</small>
        </div>

        <!-- Selector de Servicio Regional -->
        <div id="campoRegional" style="margin-top:12px; display:none;">
            <label for="servicio_regional_id" style="display:block; font-weight:600; margin-bottom:6px;">Selecciona la Coordinación Regional: <span style="color:#e03131;">*</span></label>
            <select name="servicio_regional_id" id="servicio_regional_id" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
                <option value="">-- Seleccionar servicio regional --</option>
                <?php foreach ($serviciosRegionales as $sr): ?>
                    <option value="<?= $sr['id'] ?>" <?= (isset($_POST['servicio_regional_id']) && (int)$_POST['servicio_regional_id'] === (int)$sr['id']) ? 'selected' : '' ?>>
                        <?= escapar($sr['clave']) ?> - <?= escapar($sr['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- 2. Datos Generales de la Entrega -->
    <div class="card" style="margin-bottom:24px; padding:22px; background:#ffffff; border-radius:10px; border:1px solid var(--border); box-shadow:0 2px 4px rgba(0,0,0,0.03);">
        <h3 style="margin-top:0; margin-bottom:14px; font-size:16px; color:var(--wine); border-bottom:2px solid var(--border); padding-bottom:8px;">
            2. Datos Generales de Recepción
        </h3>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px;">
            <div>
                <label for="fecha_entrega" style="display:block; font-weight:600; margin-bottom:6px;">Fecha de entrega: <span style="color:#e03131;">*</span></label>
                <input type="date" name="fecha_entrega" id="fecha_entrega" value="<?= escapar($_POST['fecha_entrega'] ?? date('Y-m-d')) ?>" required style="width:100%; padding:9px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
            </div>

            <div>
                <label for="recibido_por_nombre" style="display:block; font-weight:600; margin-bottom:6px;">Nombre de quien recibe: <span style="color:#e03131;">*</span></label>
                <input type="text" name="recibido_por_nombre" id="recibido_por_nombre" value="<?= escapar($_POST['recibido_por_nombre'] ?? '') ?>" placeholder="Ej. Profr. Juan Pérez Gómez" required style="width:100%; padding:9px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
            </div>

            <div>
                <label for="recibido_por_cargo" style="display:block; font-weight:600; margin-bottom:6px;">Cargo de quien recibe:</label>
                <input type="text" name="recibido_por_cargo" id="recibido_por_cargo" value="<?= escapar($_POST['recibido_por_cargo'] ?? 'Director(a)') ?>" placeholder="Ej. Director(a), Responsable de Almacén" style="width:100%; padding:9px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
            </div>

            <div>
                <label for="recibido_por_telefono" style="display:block; font-weight:600; margin-bottom:6px;">Teléfono de contacto (opcional):</label>
                <input type="tel" name="recibido_por_telefono" id="recibido_por_telefono" value="<?= escapar($_POST['recibido_por_telefono'] ?? '') ?>" placeholder="Ej. 747 123 4567" style="width:100%; padding:9px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
            </div>

            <div>
                <label for="entregado_por_nombre" style="display:block; font-weight:600; margin-bottom:6px;">Entregado por (Personal SEG):</label>
                <input type="text" name="entregado_por_nombre" id="entregado_por_nombre" value="<?= escapar($_POST['entregado_por_nombre'] ?? 'Personal de Almacén SEG') ?>" placeholder="Nombre de quien entrega" style="width:100%; padding:9px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
            </div>

            <div>
                <label for="observaciones" style="display:block; font-weight:600; margin-bottom:6px;">Observaciones / Notas:</label>
                <input type="text" name="observaciones" id="observaciones" value="<?= escapar($_POST['observaciones'] ?? '') ?>" placeholder="Notas adicionales o detalles de entrega" style="width:100%; padding:9px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
            </div>
        </div>
    </div>

    <!-- 3. Desglose de Uniformes por Talla -->
    <div class="card" style="margin-bottom:24px; padding:22px; background:#ffffff; border-radius:10px; border:1px solid var(--border); box-shadow:0 2px 4px rgba(0,0,0,0.03);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:2px solid var(--border); padding-bottom:8px;">
            <h3 style="margin:0; font-size:16px; color:var(--wine);">
                3. Cantidad de Uniformes a Entregar por Talla
            </h3>
            <div style="font-size:15px; font-weight:700; color:var(--wine); background:#fff5f5; padding:4px 12px; border-radius:15px; border:1px solid #ffd8d8;">
                Total: <span id="spanGranTotal">0</span> prendas
            </div>
        </div>

        <p style="color:var(--muted); font-size:13px; margin-top:0; margin-bottom:16px;">
            Escribe el número de uniformes a entregar en cada casilla. Las casillas vacías o en 0 no se contabilizarán.
        </p>

        <div class="table-wrap">
            <table style="width:100%; text-align:center;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th style="text-align:left; padding:10px;">Talla</th>
                        <th style="padding:10px; width:180px; color:#1971c2;">👦 Uniformes Niño</th>
                        <th style="padding:10px; width:180px; color:#d6336c;">👧 Uniformes Niña</th>
                        <th style="padding:10px; width:140px; text-align:right;">Subtotal Talla</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tallas as $t): ?>
                        <?php 
                            $tId = $t['id'];
                            $valNino = (int)($_POST['cantidades'][$tId]['NINO'] ?? 0);
                            $valNina = (int)($_POST['cantidades'][$tId]['NINA'] ?? 0);
                        ?>
                        <tr>
                            <td style="text-align:left; font-weight:700; font-size:15px;">
                                Talla <?= escapar($t['talla']) ?>
                            </td>
                            <td>
                                <input type="number" 
                                       name="cantidades[<?= $tId ?>][NINO]" 
                                       class="input-cantidad cant-nino" 
                                       data-talla="<?= $tId ?>"
                                       value="<?= $valNino > 0 ? $valNino : '' ?>" 
                                       min="0" 
                                       placeholder="0" 
                                       oninput="calcularTotales()" 
                                       style="width:100px; text-align:center; padding:7px; font-size:15px; font-weight:600; border:1px solid #ced4da; border-radius:6px;">
                            </td>
                            <td>
                                <input type="number" 
                                       name="cantidades[<?= $tId ?>][NINA]" 
                                       class="input-cantidad cant-nina" 
                                       data-talla="<?= $tId ?>"
                                       value="<?= $valNina > 0 ? $valNina : '' ?>" 
                                       min="0" 
                                       placeholder="0" 
                                       oninput="calcularTotales()" 
                                       style="width:100px; text-align:center; padding:7px; font-size:15px; font-weight:600; border:1px solid #ced4da; border-radius:6px;">
                            </td>
                            <td style="text-align:right; font-weight:700; font-size:15px; color:#495057;">
                                <span id="subtotal_<?= $tId ?>"><?= $valNino + $valNina ?></span> pzs
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f1f3f5; font-size:15px; font-weight:700;">
                        <td style="text-align:left; padding:12px 10px;">TOTALES:</td>
                        <td style="color:#1971c2; padding:12px 10px;"><span id="totalNino">0</span> pzs</td>
                        <td style="color:#d6336c; padding:12px 10px;"><span id="totalNina">0</span> pzs</td>
                        <td style="text-align:right; color:var(--wine); padding:12px 10px;"><span id="totalFinal">0</span> pzs</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Acciones -->
    <div style="display:flex; gap:14px; align-items:center; margin-bottom:40px;">
        <button type="submit" class="button" style="font-size:16px; padding:12px 28px; background:var(--wine); font-weight:700;">
            ✓ Guardar Entrega y Generar Comprobante
        </button>
        <a href="<?= escapar(url('entregas')) ?>" class="button button-outline" style="font-size:15px; padding:12px 20px;">
            Cancelar
        </a>
    </div>
</form>

<script>
function toggleDestino() {
    const esEscuela = document.getElementById('radioEscuela').checked;
    const campoEscuela = document.getElementById('campoEscuela');
    const campoRegional = document.getElementById('campoRegional');
    const selectEscuela = document.getElementById('escuela_id');
    const selectRegional = document.getElementById('servicio_regional_id');

    if (esEscuela) {
        campoEscuela.style.display = 'block';
        campoRegional.style.display = 'none';
        selectEscuela.required = true;
        selectRegional.required = false;
        selectRegional.value = '';
    } else {
        campoEscuela.style.display = 'none';
        campoRegional.style.display = 'block';
        selectEscuela.required = false;
        selectRegional.required = true;
        selectEscuela.value = '';
    }
}

function calcularTotales() {
    let totNino = 0;
    let totNina = 0;
    let granTotal = 0;

    // Calcular por fila de talla
    document.querySelectorAll('.cant-nino').forEach(input => {
        const tId = input.getAttribute('data-talla');
        const inputNina = document.querySelector('.cant-nina[data-talla="' + tId + '"]');
        
        const valNino = parseInt(input.value) || 0;
        const valNina = parseInt(inputNina ? inputNina.value : 0) || 0;
        const sub = valNino + valNina;

        const elSub = document.getElementById('subtotal_' + tId);
        if (elSub) elSub.textContent = sub;

        totNino += valNino;
        totNina += valNina;
    });

    granTotal = totNino + totNina;

    document.getElementById('totalNino').textContent = totNino;
    document.getElementById('totalNina').textContent = totNina;
    document.getElementById('totalFinal').textContent = granTotal;
    document.getElementById('spanGranTotal').textContent = granTotal;
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', () => {
    toggleDestino();
    calcularTotales();
});
</script>

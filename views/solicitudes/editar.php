<?php
$disponiblePorTalla = [];
foreach ($disponibilidadGeneral as $existencia) {
    $disponiblePorTalla[$existencia['sexo']][(int)$existencia['talla_id']] = (int)$existencia['disponible'];
}
$numEscuelas = count($solicitud['escuelas']);
?>
<a class="back-link" href="<?= escapar(url('solicitud-detalle', ['id' => $solicitud['id']])) ?>">← Volver al detalle</a>

<?php if ($error): ?>
    <div class="notice request-error"><?= escapar($error) ?></div>
<?php endif; ?>

<section class="edit-request-hero">
    <div>
        <span>Modificación de solicitud</span>
        <h3><?= escapar($solicitud['folio']) ?></h3>
        <p>
            <b><?= numero($numEscuelas) ?> <?= $numEscuelas === 1 ? 'Escuela vinculada' : 'Escuelas vinculadas' ?></b>
            · Estado: <?= escapar(str_replace('_', ' ', $solicitud['estado'])) ?>
        </p>
    </div>
    <div>
        <span>Servicio(s) Regional(es)</span>
        <strong><?= escapar($solicitud['servicio_regional']) ?></strong>
        <small>Puedes modificar las cantidades, agregar más escuelas o retirar planteles.</small>
    </div>
</section>

<form method="post" class="request-form multi-school-form" id="form-solicitud-multi">
    <section class="request-data">
        <div class="panel-heading">
            <div>
                <span>Datos generales</span>
                <h3>Actualizar información</h3>
            </div>
        </div>
        <div class="request-fields">
            <label>
                Fecha de solicitud
                <input type="date" name="fecha_solicitud" value="<?= escapar($solicitud['fecha_solicitud']) ?>" required>
            </label>
            <label>
                Ciclo o periodo
                <input type="text" name="ciclo_periodo" maxlength="50" value="<?= escapar($solicitud['ciclo_periodo'] ?? '') ?>">
            </label>
            <label class="full-field">
                Observaciones
                <textarea name="observaciones" maxlength="1000"><?= escapar($solicitud['observaciones'] ?? '') ?></textarea>
            </label>
        </div>
    </section>

    <!-- Buscador para agregar más escuelas si se desea -->
    <section class="school-search-panel">
        <div class="panel-heading">
            <div>
                <span>Agregar planteles</span>
                <h3>Buscar y agregar más escuelas</h3>
            </div>
            <span class="panel-tag">Multi-regional habilitado</span>
        </div>

        <div class="school-search-form">
            <label for="busqueda-escuela">CCT o nombre de la escuela</label>
            <div class="autocomplete-wrap" data-busqueda-url="<?= escapar(url('solicitud-buscar-escuelas')) ?>">
                <span class="search-symbol">⌕</span>
                <input id="busqueda-escuela" placeholder="Escribe al menos 2 caracteres para buscar y agregar otra escuela..." autocomplete="off">
                <div class="autocomplete-results" hidden></div>
            </div>
        </div>
    </section>

    <!-- Contenedor de escuelas y uniformes -->
    <section class="request-quantities multi-school-container">
        <div class="panel-heading">
            <div>
                <span>Escuelas y requerimientos</span>
                <h3>Escuelas en esta solicitud (<span id="schools-count"><?= numero($numEscuelas) ?></span>)</h3>
            </div>
            <div class="inline-legend">
                <span class="girl-dot">Niña</span>
                <span class="boy-dot">Niño</span>
            </div>
        </div>

        <div class="empty multi-school-empty" id="multi-school-empty" <?= $numEscuelas > 0 ? 'hidden' : '' ?>>
            <p><strong>No hay escuelas en esta solicitud.</strong> Agrega al menos una escuela para continuar.</p>
        </div>

        <div class="schools-cards-list" id="schools-cards-list"></div>

        <!-- Disponibilidad -->
        <div class="request-availability" id="request-availability">
            <strong>Disponibilidad consolidada de almacenes</strong>
            <span id="request-availability-message">Verificando existencias para las cantidades capturadas...</span>
        </div>
    </section>

    <!-- Barra de envío -->
    <div class="request-submit-bar">
        <div class="request-summary-counters">
            <div>
                <strong id="summary-schools-count"><?= numero($numEscuelas) ?></strong>
                <span>Escuelas</span>
            </div>
            <div>
                <strong id="summary-regionals-count">0</strong>
                <span>Servicios Reg.</span>
            </div>
            <div>
                <strong id="summary-total-uniforms">0</strong>
                <span>Uniformes totales</span>
            </div>
        </div>
        <button class="button" type="submit" id="btn-submit-solicitud">
            Guardar cambios (<span id="submit-schools-label"><?= numero($numEscuelas) ?> escuelas</span>)
        </button>
    </div>
</form>

<?php
// Preparamos las escuelas existentes con sus cantidades para que JS las inicialice
$escuelasIniciales = [];
foreach ($escuelasDetalle as $item) {
    $esc = $item['escuela'];
    $cantidades = ['NINO' => [], 'NINA' => []];
    foreach ($item['tallas'] as $t) {
        $cantidades['NINO'][$t['id']] = $t['nino'];
        $cantidades['NINA'][$t['id']] = $t['nina'];
    }
    $escuelasIniciales[] = [
        'id' => (int)$esc['escuela_id'],
        'cct' => $esc['cct'],
        'nombre' => $esc['escuela'],
        'nivel' => $esc['nivel'],
        'municipio' => $esc['municipio'],
        'localidad' => $esc['localidad'],
        'servicio_regional' => $esc['servicio_regional'],
        'cantidades' => $cantidades,
    ];
}
?>

<script id="catalog-tallas" type="application/json"><?= json_encode($tallas, JSON_UNESCAPED_UNICODE) ?></script>
<script id="catalog-disponibilidad" type="application/json"><?= json_encode($disponiblePorTalla, JSON_UNESCAPED_UNICODE) ?></script>
<script id="existing-schools" type="application/json"><?= json_encode($escuelasIniciales, JSON_UNESCAPED_UNICODE) ?></script>

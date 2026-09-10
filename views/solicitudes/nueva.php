<?php
$disponiblePorTalla = [];
foreach ($disponibilidadGeneral as $existencia) {
    $disponiblePorTalla[$existencia['sexo']][(int)$existencia['talla_id']] = (int)$existencia['disponible'];
}
?>
<a class="back-link" href="<?= escapar(url('solicitudes')) ?>">← Volver a solicitudes</a>

<section class="request-intro">
    <div>
        <span>Registro de solicitud multi-escolar</span>
        <h3>Captura de requerimientos de uniformes</h3>
        <p>Puedes agregar una o varias escuelas simultáneamente, incluso si pertenecen a diferentes Servicios Regionales.</p>
    </div>
    <div class="request-step">
        <b class="step-ready">1</b><span>Datos</span>
        <i>→</i>
        <b id="step-schools" class="<?= $escuelaInicial ? 'step-ready' : '' ?>">2</b><span>Escuelas</span>
        <i>→</i>
        <b id="step-quantities">3</b><span>Cantidades</span>
    </div>
</section>

<?php if ($error): ?>
    <div class="notice request-error"><?= escapar($error) ?></div>
<?php endif; ?>

<form method="post" class="request-form multi-school-form" id="form-solicitud-multi">
    <input type="hidden" name="ruta" value="solicitud-nueva">

    <!-- Paso 1: Datos generales -->
    <section class="request-data">
        <div class="panel-heading">
            <div>
                <span>Paso 1</span>
                <h3>Datos de la solicitud</h3>
            </div>
        </div>
        <div class="request-fields">
            <label>
                Fecha de solicitud
                <input type="date" name="fecha_solicitud" value="<?= escapar(date('Y-m-d')) ?>" required>
            </label>
            <label>
                Ciclo o periodo
                <input type="text" name="ciclo_periodo" maxlength="50" placeholder="Ejemplo: 2026-2027">
            </label>
            <label class="full-field">
                Observaciones
                <textarea name="observaciones" maxlength="1000" placeholder="Información adicional de la solicitud o del conjunto de escuelas"></textarea>
            </label>
        </div>
    </section>

    <!-- Paso 2: Buscador interactivo de escuelas -->
    <section class="school-search-panel">
        <div class="panel-heading">
            <div>
                <span>Paso 2</span>
                <h3>Buscar y agregar escuelas a esta solicitud</h3>
            </div>
            <span class="panel-tag">Multi-regional habilitado</span>
        </div>

        <div class="school-search-form">
            <label for="busqueda-escuela">CCT o nombre de la escuela</label>
            <div class="autocomplete-wrap" data-busqueda-url="<?= escapar(url('solicitud-buscar-escuelas')) ?>">
                <span class="search-symbol">⌕</span>
                <input id="busqueda-escuela" placeholder="Escribe al menos 2 caracteres del CCT o nombre del plantel..." autocomplete="off">
                <div class="autocomplete-results" hidden></div>
            </div>
        </div>
        <p class="search-help">Busca las escuelas y presiona <b>"+ Agregar a la solicitud"</b>. Puedes meter tantas escuelas como necesites de cualquier región.</p>
    </section>

    <!-- Paso 3: Escuelas agregadas con captura por escuela -->
    <section class="request-quantities multi-school-container">
        <div class="panel-heading">
            <div>
                <span>Paso 3</span>
                <h3>Escuelas y uniformes solicitados (<span id="schools-count">0</span>)</h3>
            </div>
            <div class="inline-legend">
                <span class="girl-dot">Niña</span>
                <span class="boy-dot">Niño</span>
            </div>
        </div>

        <div class="empty multi-school-empty" id="multi-school-empty" <?= $escuelaInicial ? 'hidden' : '' ?>>
            <p><strong>Aún no has agregado ninguna escuela a esta solicitud.</strong></p>
            <p>Utiliza el buscador de arriba para localizar y agregar la primera escuela.</p>
        </div>

        <div class="schools-cards-list" id="schools-cards-list"></div>

        <!-- Barra de disponibilidad general consolidada -->
        <div class="request-availability" id="request-availability">
            <strong>Disponibilidad consolidada de almacenes</strong>
            <span id="request-availability-message">Agrega escuelas y captura cantidades para verificar existencias globales.</span>
        </div>
    </section>

    <!-- Barra de resumen y envío -->
    <div class="request-submit-bar">
        <div class="request-summary-counters">
            <div>
                <strong id="summary-schools-count">0</strong>
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
        <button class="button" type="submit" id="btn-submit-solicitud" disabled>
            Registrar solicitud (<span id="submit-schools-label">0 escuelas</span>)
        </button>
    </div>
</form>

<script id="catalog-tallas" type="application/json"><?= json_encode($tallas, JSON_UNESCAPED_UNICODE) ?></script>
<script id="catalog-disponibilidad" type="application/json"><?= json_encode($disponiblePorTalla, JSON_UNESCAPED_UNICODE) ?></script>
<script id="initial-school" type="application/json"><?= json_encode($escuelaInicial, JSON_UNESCAPED_UNICODE) ?></script>

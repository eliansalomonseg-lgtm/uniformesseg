<?php
$totalSolicitudes = count($solicitudes);
$totalUniformes = array_sum(array_column($solicitudes, 'total'));
$pendientes = count(array_filter($solicitudes, fn($solicitud) => in_array($solicitud['estado'], ['PENDIENTE', 'EN_REVISION', 'ASIGNADA', 'EN_PREPARACION'], true)));
?>
<section class="requests-header">
    <div>
        <span>Control institucional de uniformes</span>
        <h3>Solicitudes escolares</h3>
        <p>Registro y consolidación de requerimientos de uniformes por escuela y por Servicio Regional.</p>
    </div>
    <div class="requests-header-metrics">
        <div>
            <strong><?= numero($totalSolicitudes) ?></strong>
            <span>Solicitudes</span>
        </div>
        <div>
            <strong><?= numero($totalUniformes) ?></strong>
            <span>Uniformes</span>
        </div>
        <div>
            <strong><?= numero($pendientes) ?></strong>
            <span>Pendientes</span>
        </div>
    </div>
</section>

<?php if (isset($_GET['entrega_ok'])): ?>
    <div class="request-success">
        ✓ <strong>Entrega registrada exitosamente.</strong> Se generó el folio <b><?= escapar($_GET['folios'] ?? '') ?></b> por un total de <b><?= numero($_GET['piezas'] ?? 0) ?></b> uniformes. La solicitud ha sido actualizada a <i>INCLUIDA EN ENTREGA</i>.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="notice request-error">
        ⚠️ <strong>Error al procesar entrega:</strong> <?= escapar($_GET['error']) ?>
    </div>
<?php endif; ?>

<!-- Barra de filtros y acciones -->
<div class="request-filters-bar">
    <form class="filters" method="get">
        <input type="hidden" name="ruta" value="solicitudes">
        <label>
            Filtrar por Servicio Regional
            <select name="servicio_id" onchange="this.form.submit()">
                <option value="0">Todos los Servicios Regionales</option>
                <?php foreach ($servicios as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= $servicioId === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= escapar($s['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($servicioId > 0): ?>
            <a class="button secondary" href="<?= escapar(url('solicitudes')) ?>">Ver todas</a>
        <?php endif; ?>
    </form>

    <div class="request-actions">
        <a class="button secondary" href="<?= escapar(url('escuelas')) ?>">
            <span>⌕</span> Consultar escuelas
        </a>
        <a class="button" href="<?= escapar(url('solicitud-nueva')) ?>">
            <span>＋</span> Nueva solicitud
        </a>
    </div>
</div>

<section class="requests-list">
    <div class="requests-list-heading">
        <div>
            <span>Registro de requerimientos</span>
            <h3><?= numero($totalSolicitudes) ?> <?= $totalSolicitudes === 1 ? 'solicitud encontrada' : 'solicitudes encontradas' ?></h3>
        </div>
    </div>

    <?php if (!$solicitudes): ?>
        <div class="empty">Aún no hay solicitudes registradas con el criterio seleccionado. Cree una nueva solicitud agregando una o más escuelas.</div>
    <?php endif; ?>

    <?php foreach ($solicitudes as $solicitud): ?>
        <?php
        $numEsc = (int)($solicitud['total_escuelas'] ?? 1);
        $numServ = (int)($solicitud['total_servicios'] ?? 1);
        $esPendiente = in_array($solicitud['estado'], ['PENDIENTE', 'EN_REVISION', 'ASIGNADA', 'EN_PREPARACION'], true);
        ?>
        <article class="request-list-card <?= $numEsc > 1 ? 'card-multi-school' : '' ?>">
            <div class="request-list-folio">
                <span>Folio</span>
                <strong><?= escapar($solicitud['folio']) ?></strong>
                <small><?= escapar($solicitud['fecha_solicitud']) ?></small>
            </div>

            <div class="request-list-school">
                <?php if ($numEsc === 1): ?>
                    <span class="request-cct"><?= escapar($solicitud['cct']) ?></span>
                    <h4><?= escapar($solicitud['escuela']) ?></h4>
                    <p><?= escapar($solicitud['municipio'] ?? '') ?> · <?= escapar($solicitud['localidad'] ?? '') ?></p>
                <?php else: ?>
                    <div class="multi-school-badge-wrap">
                        <span class="badge-multi-school">★ <?= numero($numEsc) ?> Escuelas incluidas</span>
                    </div>
                    <h4>
                        <?php
                        $nombresEsc = array_column($solicitud['escuelas'], 'nombre');
                        echo escapar(implode(' · ', array_slice($nombresEsc, 0, 2)));
                        if (count($nombresEsc) > 2) {
                            echo ' y ' . (count($nombresEsc) - 2) . ' más...';
                        }
                        ?>
                    </h4>
                    <p class="multi-school-cct-list">
                        <?php foreach (array_slice($solicitud['escuelas'], 0, 4) as $e): ?>
                            <small class="mini-cct-tag"><?= escapar($e['cct']) ?></small>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="request-list-regional">
                <span>Servicio Regional</span>
                <?php if ($numServ > 1): ?>
                    <strong class="multi-reg-text"><?= numero($numServ) ?> Servicios Regionales</strong>
                    <small><?= escapar($solicitud['servicios_resumen'] ?? '') ?></small>
                <?php else: ?>
                    <strong><?= escapar($solicitud['servicio_regional']) ?></strong>
                <?php endif; ?>
            </div>

            <div class="request-list-total">
                <span>Solicitado</span>
                <strong><?= numero($solicitud['total']) ?></strong>
                <small>uniformes</small>
            </div>

            <div class="request-list-status">
                <span class="status"><?= escapar(str_replace('_', ' ', $solicitud['estado'])) ?></span>

                <?php if ($esPendiente): ?>
                    <button type="button" class="btn-direct-delivery"
                        data-solicitud-id="<?= (int)$solicitud['id'] ?>"
                        data-folio="<?= escapar($solicitud['folio']) ?>"
                        data-total="<?= numero($solicitud['total']) ?>"
                        data-escuelas="<?= numero($numEsc) ?>"
                        data-servicios="<?= escapar($solicitud['servicios_resumen'] ?: $solicitud['servicio_regional']) ?>"
                        data-plan='<?= escapar(json_encode($solicitud['plan_despacho'] ?? [])) ?>'>
                        📦 Entregar
                    </button>
                    <a class="request-detail-link" href="<?= escapar(url('solicitud-editar', ['id' => $solicitud['id']])) ?>">Editar</a>
                <?php endif; ?>

                <a class="request-detail-link" href="<?= escapar(url('solicitud-detalle', ['id' => $solicitud['id']])) ?>">Ver detalle →</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<!-- Modal de entrega directa por almacenes correspondientes -->
<div class="modal-overlay" id="modal-entrega-rapida" hidden>
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <span>Control oficial de entrega</span>
                <h3>📦 Despachar Entrega por Almacén Correspondiente</h3>
            </div>
            <button type="button" class="modal-close-btn" id="btn-close-modal">✕</button>
        </div>
        <form method="post" action="<?= escapar(url('solicitud-entregar')) ?>">
            <input type="hidden" name="solicitud_id" id="modal-solicitud-id" value="">
            <div class="modal-body">
                <div class="modal-summary-grid">
                    <div class="modal-summary-item">
                        <span>Folio de solicitud</span>
                        <strong id="modal-folio">-</strong>
                    </div>
                    <div class="modal-summary-item">
                        <span>Total a entregar</span>
                        <strong id="modal-total" class="highlight-total">-</strong>
                    </div>
                </div>

                <div class="modal-routing-section">
                    <span class="routing-section-title">🏢 Almacenes correspondientes por región:</span>
                    <div id="modal-plan-container" class="modal-plan-list">
                        <!-- Llenado dinámicamente por JS -->
                    </div>
                </div>

                <div class="modal-notice">
                    ✓ <strong>Garantía de no cruce:</strong> Cada escuela se entregará y descontará del inventario de su <b>almacén regional correspondiente</b>, generándose folios oficiales de entrega por cada región involucrada.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button secondary" id="btn-cancel-modal">Cancelar</button>
                <button type="submit" class="button" id="btn-submit-delivery">✓ Confirmar Entrega Oficial</button>
            </div>
        </form>
    </div>
</div>

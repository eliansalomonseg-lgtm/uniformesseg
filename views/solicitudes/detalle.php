<?php
$totalSolicitado = array_sum(array_column($detalle, 'total'));
$numEscuelas = count($solicitud['escuelas']);
$serviciosRegionales = $solicitud['servicios_regionales'];
?>
<a class="back-link" href="<?= escapar(url('solicitudes')) ?>">← Volver a solicitudes</a>

<?php if (isset($_GET['guardada']) && $_GET['guardada'] === '1'): ?>
    <div class="request-success">✓ Solicitud registrada correctamente con <?= numero($numEscuelas) ?> <?= $numEscuelas === 1 ? 'escuela vinculada' : 'escuelas vinculadas' ?>.</div>
<?php endif; ?>

<?php if (isset($_GET['actualizada']) && $_GET['actualizada'] === '1'): ?>
    <div class="request-success">✓ Solicitud actualizada correctamente.</div>
<?php endif; ?>

<section class="request-detail-hero <?= $numEscuelas > 1 ? 'multi-hero' : '' ?>">
    <div class="request-detail-folio">
        <span>Folio de solicitud</span>
        <strong><?= escapar($solicitud['folio']) ?></strong>
        <small><?= escapar($solicitud['fecha_solicitud']) ?></small>
    </div>

    <div class="request-detail-school">
        <?php if ($numEscuelas === 1): ?>
            <span>Escuela solicitante</span>
            <h3><?= escapar($solicitud['escuela']) ?></h3>
            <p><b><?= escapar($solicitud['cct']) ?></b> · <?= escapar($solicitud['nivel'] ?? '') ?></p>
            <p><?= escapar($solicitud['municipio'] ?? '') ?> · <?= escapar($solicitud['localidad'] ?? '') ?></p>
        <?php else: ?>
            <span>Solicitud multi-escolar</span>
            <h3><?= numero($numEscuelas) ?> Escuelas incluidas</h3>
            <p>Planteles de <?= count($serviciosRegionales) ?> <?= count($serviciosRegionales) === 1 ? 'Servicio Regional' : 'Servicios Regionales distintos' ?></p>
            <p class="multi-school-ccts">
                <?php foreach ($solicitud['escuelas'] as $idx => $esc): ?>
                    <span class="pill-cct"><?= escapar($esc['cct']) ?></span>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="request-detail-status">
        <span>Estado interno</span>
        <strong><?= escapar(str_replace('_', ' ', $solicitud['estado'])) ?></strong>
        <small><?= numero($totalSolicitado) ?> uniformes en total</small>
    </div>
</section>

<section class="request-regional-card">
    <div class="regional-icon">⌂</div>
    <div>
        <span>Destino administrativo</span>
        <?php if (count($serviciosRegionales) === 1): ?>
            <h3>Servicio Regional <?= escapar($serviciosRegionales[0]) ?></h3>
            <p>Todas las escuelas de esta solicitud corresponden a este Servicio Regional.</p>
        <?php else: ?>
            <h3><?= count($serviciosRegionales) ?> Servicios Regionales involucrados</h3>
            <p>Esta solicitud consolida escuelas de diferentes regiones: <b><?= escapar(implode(' · ', $serviciosRegionales)) ?></b>.</p>
        <?php endif; ?>
    </div>
    <div class="request-total">
        <span>Total solicitado</span>
        <strong><?= numero($totalSolicitado) ?></strong>
        <small>uniformes</small>
    </div>
</section>

<!-- Listado de escuelas vinculadas -->
<section class="request-detail-panel">
    <div class="panel-heading">
        <div>
            <span>Planteles incluidos</span>
            <h3><?= numero($numEscuelas) ?> <?= $numEscuelas === 1 ? 'Escuela en esta solicitud' : 'Escuelas en esta solicitud' ?></h3>
        </div>
        <span class="panel-tag"><?= numero($totalSolicitado) ?> piezas totales</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>CCT</th>
                    <th>Nombre de la Escuela</th>
                    <th>Nivel</th>
                    <th>Ubicación</th>
                    <th>Servicio Regional Oficial</th>
                    <th style="text-align:right">Uniformes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitud['escuelas'] as $esc): ?>
                    <tr>
                        <td><strong><?= escapar($esc['cct']) ?></strong></td>
                        <td><?= escapar($esc['escuela']) ?></td>
                        <td><?= escapar($esc['nivel'] ?? '—') ?></td>
                        <td><?= escapar($esc['municipio'] ?? '') ?> · <?= escapar($esc['localidad'] ?? '') ?></td>
                        <td><span class="status regional-badge"><?= escapar($esc['servicio_regional'] ?? 'Sin relación vigente') ?></span></td>
                        <td style="text-align:right"><b><?= numero($esc['total_escuela']) ?></b></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Desglose consolidado por talla -->
<section class="request-detail-panel">
    <div class="panel-heading">
        <div>
            <span>Consolidado general</span>
            <h3>Total de uniformes por talla y género</h3>
        </div>
        <div class="inline-legend">
            <span class="girl-dot">Niña</span>
            <span class="boy-dot">Niño</span>
        </div>
    </div>

    <div class="request-detail-grid">
        <?php foreach ($detalle as $registro): ?>
            <article class="request-size-card">
                <div class="request-size-heading">
                    <span>Talla</span>
                    <strong><?= escapar($registro['talla']) ?></strong>
                    <em><?= numero($registro['total']) ?> piezas</em>
                </div>
                <div class="request-size-values">
                    <div class="girl-value">
                        <span>♀ Niña</span>
                        <strong><?= numero($registro['nina']) ?></strong>
                    </div>
                    <div class="boy-value">
                        <span>♂ Niño</span>
                        <strong><?= numero($registro['nino']) ?></strong>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Desglose individual por escuela -->
<?php if (!empty($escuelasDetalle)): ?>
<section class="request-detail-panel">
    <div class="panel-heading">
        <div>
            <span>Detalle desglosado</span>
            <h3>Uniformes solicitados por cada escuela</h3>
        </div>
    </div>

    <div class="school-breakdown-list">
        <?php foreach ($escuelasDetalle as $item): ?>
            <?php $esc = $item['escuela']; ?>
            <article class="school-breakdown-card">
                <div class="school-breakdown-header">
                    <div>
                        <span class="request-cct"><?= escapar($esc['cct']) ?></span>
                        <h4><?= escapar($esc['escuela']) ?></h4>
                        <p><?= escapar($esc['municipio'] ?? '') ?> · <?= escapar($esc['localidad'] ?? '') ?> · <b><?= escapar($esc['servicio_regional'] ?? '') ?></b></p>
                    </div>
                    <div class="school-breakdown-subtotal">
                        <strong><?= numero($item['total']) ?></strong>
                        <small>uniformes</small>
                    </div>
                </div>

                <div class="school-breakdown-grid">
                    <?php foreach ($item['tallas'] as $t): ?>
                        <?php if ($t['total'] > 0): ?>
                            <div class="mini-size-card">
                                <b>Talla <?= escapar($t['talla']) ?></b>
                                <div class="mini-size-counts">
                                    <span class="girl-pill">♀ <?= numero($t['nina']) ?></span>
                                    <span class="boy-pill">♂ <?= numero($t['nino']) ?></span>
                                </div>
                                <small>Subtotal: <?= numero($t['total']) ?></small>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="request-scope-note">
    <strong>Control interno de almacén</strong>
    <span>El estado se actualiza por el personal de almacén para controlar la preparación y envío a los Servicios Regionales. No representa confirmación de recepción escolar.</span>
</section>

<?php if (in_array($solicitud['estado'], ['PENDIENTE', 'EN_REVISION', 'ASIGNADA', 'EN_PREPARACION'], true)): ?>
    <div class="detail-edit-action">
        <button type="button" class="button btn-direct-delivery"
            data-solicitud-id="<?= (int)$solicitud['id'] ?>"
            data-folio="<?= escapar($solicitud['folio']) ?>"
            data-total="<?= numero($totalSolicitado) ?>"
            data-escuelas="<?= numero($numEscuelas) ?>"
            data-servicios="<?= escapar($solicitud['servicio_regional']) ?>"
            data-plan='<?= escapar(json_encode($planDespacho ?? [])) ?>'>
            📦 Registrar entrega de esta solicitud
        </button>
        <a class="button secondary" href="<?= escapar(url('solicitud-editar', ['id' => $solicitud['id']])) ?>">Editar solicitud</a>
        <span>Disponible mientras la solicitud no haya sido entregada.</span>
    </div>

    <!-- Modal rápido de entrega en detalle -->
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
                <input type="hidden" name="solicitud_id" id="modal-solicitud-id" value="<?= (int)$solicitud['id'] ?>">
                <div class="modal-body">
                    <div class="modal-summary-grid">
                        <div class="modal-summary-item">
                            <span>Folio de solicitud</span>
                            <strong id="modal-folio"><?= escapar($solicitud['folio']) ?></strong>
                        </div>
                        <div class="modal-summary-item">
                            <span>Total a entregar</span>
                            <strong id="modal-total" class="highlight-total"><?= numero($totalSolicitado) ?> piezas</strong>
                        </div>
                    </div>

                    <div class="modal-routing-section">
                        <span class="routing-section-title">🏢 Almacenes correspondientes por región:</span>
                        <div id="modal-plan-container" class="modal-plan-list">
                            <?php if (!empty($planDespacho)): ?>
                                <?php foreach ($planDespacho as $p): ?>
                                    <div class="modal-plan-item">
                                        <div class="modal-plan-reg">
                                            <strong><?= escapar($p['servicio_nombre']) ?></strong>
                                            <span><?= numero(count($p['escuelas'])) ?> escuela(s) · <?= numero($p['total_piezas']) ?> uniformes</span>
                                        </div>
                                        <div class="modal-plan-alm">
                                            <span>Se entrega desde:</span>
                                            <b><?= escapar($p['almacen_nombre']) ?></b>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-notice">
                        ✓ <strong>Garantía de no cruce:</strong> Cada escuela se entregará y descontará del inventario de su <b>almacén regional correspondiente</b>, sin cruces entre almacenes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" id="btn-cancel-modal">Cancelar</button>
                    <button type="submit" class="button" id="btn-submit-delivery">✓ Confirmar Entrega Oficial</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

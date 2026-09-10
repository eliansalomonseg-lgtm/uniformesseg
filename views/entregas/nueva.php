<?php
$totalSolicitado = 0;
$totalDisponibleParaSolicitud = 0;
$totalFaltante = 0;
$requerimientos = [];
if ($almacenId && $servicioId) {
    foreach ($comparativo as $registro) {
        foreach ([['NINA', 'Niña', 'solicitado_nina', 'disponible_nina', 'girl'], ['NINO', 'Niño', 'solicitado_nino', 'disponible_nino', 'boy']] as [$sexo, $etiqueta, $campoSolicitado, $campoDisponible, $clase]) {
            $solicitado = (int)$registro[$campoSolicitado];
            $disponible = (int)$registro[$campoDisponible];
            if ($solicitado > 0) {
                $cubierto = min($solicitado, $disponible);
                $faltante = max(0, $solicitado - $disponible);
                $totalSolicitado += $solicitado;
                $totalDisponibleParaSolicitud += $cubierto;
                $totalFaltante += $faltante;
                $requerimientos[] = [
                    'talla' => $registro['talla'],
                    'sexo' => $etiqueta,
                    'clase' => $clase,
                    'solicitado' => $solicitado,
                    'disponible' => $disponible,
                    'faltante' => $faltante,
                ];
            }
        }
    }
}
?>
<div class="notice">
    🔒 <strong>Vinculación oficial sin cruces:</strong> Cada almacén realiza entregas exclusivamente a su Servicio Regional correspondiente. Al seleccionar uno, el sistema vincula automáticamente su par oficial.
</div>

<?php if ($error): ?>
    <div class="notice request-error"><?= escapar($error) ?></div>
<?php endif; ?>

<form class="filters delivery-selection" method="get">
    <input type="hidden" name="ruta" value="entrega-nueva">
    <label class="delivery-warehouse-label">
        <span>Almacén entregador</span>
        <select name="almacen_id" id="entrega-select-almacen" required>
            <option value="">Seleccione el almacén</option>
            <?php foreach ($almacenes as $a): ?>
                <option value="<?= (int)$a['id'] ?>"
                    data-servicio-id="<?= (int)($a['servicio_regional_id'] ?? 0) ?>"
                    <?= $almacenId === (int)$a['id'] ? 'selected' : '' ?>>
                    <?= escapar($a['nombre']) ?> <?= !empty($a['servicio_regional']) ? '→ (' . escapar($a['servicio_regional']) . ')' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small>Cada almacén administra exclusivamente el stock de su región.</small>
    </label>
    <label>
        <span>Servicio Regional receptor</span>
        <select name="servicio_id" id="entrega-select-servicio" required>
            <option value="">Seleccione el Servicio Regional</option>
            <?php foreach ($servicios as $s): ?>
                <option value="<?= (int)$s['id'] ?>"
                    data-almacen-id="<?= (int)($s['almacen_id'] ?? 0) ?>"
                    <?= $servicioId === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= escapar($s['nombre']) ?> <?= !empty($s['almacen']) ? '← [' . escapar($s['almacen']) . ']' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small>Se vincula con su almacén asignado automáticamente.</small>
    </label>
    <button class="button">Revisar existencias</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selAlm = document.getElementById('entrega-select-almacen');
    const selServ = document.getElementById('entrega-select-servicio');
    if (selAlm && selServ) {
        selAlm.addEventListener('change', () => {
            const opt = selAlm.selectedOptions[0];
            const sId = opt ? opt.getAttribute('data-servicio-id') : null;
            if (sId && sId !== '0') {
                selServ.value = sId;
            }
        });
        selServ.addEventListener('change', () => {
            const opt = selServ.selectedOptions[0];
            const aId = opt ? opt.getAttribute('data-almacen-id') : null;
            if (aId && aId !== '0') {
                selAlm.value = aId;
            }
        });
    }
});
</script>

<?php if ($almacenId && $servicioId): ?>
    <section class="delivery-result-header">
        <div>
            <span>Resultado de la revisión</span>
            <h3><?= $totalFaltante > 0 ? 'Hay uniformes faltantes' : 'El almacén puede cubrir la entrega' ?></h3>
            <p><?= $totalFaltante > 0 ? 'Las tarjetas en rojo indican exactamente qué talla y género no alcanzan.' : 'Todas las cantidades solicitadas están disponibles en el almacén seleccionado.' ?></p>
        </div>
        <div class="delivery-result-metrics">
            <div>
                <strong><?= numero($totalSolicitado) ?></strong>
                <span>Solicitados</span>
            </div>
            <div class="available-result">
                <strong><?= numero($totalDisponibleParaSolicitud) ?></strong>
                <span>Se pueden entregar</span>
            </div>
            <div class="<?= $totalFaltante > 0 ? 'shortage-result' : 'available-result' ?>">
                <strong><?= numero($totalFaltante) ?></strong>
                <span>Faltantes</span>
            </div>
        </div>
    </section>

    <section class="delivery-requests">
        <div class="panel-heading">
            <div>
                <span>Solicitudes incluidas</span>
                <h3><?= numero(count($solicitudes)) ?> escuela<?= count($solicitudes) === 1 ? '' : 's' ?> para <?= escapar($servicios[array_search($servicioId, array_column($servicios, 'id'))]['nombre'] ?? 'este Servicio Regional') ?></h3>
            </div>
        </div>
        <?php if (!$solicitudes): ?>
            <div class="empty">No hay solicitudes pendientes para este Servicio Regional.</div>
        <?php else: ?>
            <div class="delivery-request-list">
                <?php foreach ($solicitudes as $solicitud): ?>
                    <article>
                        <div>
                            <span><?= escapar($solicitud['folio']) ?></span>
                            <h4><?= escapar($solicitud['escuela']) ?></h4>
                            <p>CCT <?= escapar($solicitud['cct']) ?></p>
                        </div>
                        <strong><?= numero($solicitud['total']) ?><small>uniformes solicitados</small></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($requerimientos): ?>
        <section class="requirements-review">
            <div class="panel-heading">
                <div>
                    <span>Revisión por talla</span>
                    <h3>¿Qué puede entregar este almacén?</h3>
                </div>
                <div class="delivery-key">
                    <span class="key-available">Disponible</span>
                    <span class="key-shortage">Faltante</span>
                </div>
            </div>
            <p>Solo se muestran las tallas y géneros que fueron solicitados.</p>
            <div class="requirements-grid">
                <?php foreach ($requerimientos as $requerimiento): ?>
                    <article class="requirement-card <?= $requerimiento['faltante'] > 0 ? 'requirement-shortage' : '' ?> <?= escapar($requerimiento['clase']) ?>">
                        <div class="requirement-card-heading">
                            <span><?= escapar($requerimiento['sexo']) ?></span>
                            <strong>Talla <?= escapar($requerimiento['talla']) ?></strong>
                        </div>
                        <div class="requirement-values">
                            <div>
                                <span>Pidieron</span>
                                <strong><?= numero($requerimiento['solicitado']) ?></strong>
                            </div>
                            <div>
                                <span>Hay en almacén</span>
                                <strong><?= numero($requerimiento['disponible']) ?></strong>
                            </div>
                        </div>
                        <div class="requirement-decision">
                            <?php if ($requerimiento['faltante'] > 0): ?>
                                <strong>Faltan <?= numero($requerimiento['faltante']) ?></strong>
                                <span>No alcanza para cubrir esta cantidad.</span>
                            <?php else: ?>
                                <strong>Disponible</strong>
                                <span>Esta cantidad se puede entregar.</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($totalFaltante === 0 && $totalSolicitado > 0): ?>
        <form method="post" class="delivery-send-form">
            <input type="hidden" name="ruta" value="entrega-nueva">
            <input type="hidden" name="accion" value="enviar">
            <input type="hidden" name="almacen_id" value="<?= (int)$almacenId ?>">
            <input type="hidden" name="servicio_id" value="<?= (int)$servicioId ?>">
            <div>
                <strong>Todo está disponible para realizar la entrega</strong>
                <span>Al confirmar se registrará la entrega oficial, se descontarán <?= numero($totalSolicitado) ?> uniformes del inventario y las solicitudes quedarán marcadas como entregadas.</span>
            </div>
            <button class="button">✓ Registrar entrega de <?= numero($totalSolicitado) ?> uniformes →</button>
        </form>
    <?php elseif ($totalFaltante > 0): ?>
        <div class="notice gold">No es posible registrar la entrega completa porque faltan <?= numero($totalFaltante) ?> uniformes. Las solicitudes permanecen pendientes.</div>
    <?php endif; ?>
<?php endif; ?>

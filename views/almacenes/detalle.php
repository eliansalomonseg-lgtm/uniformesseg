<?php
$totalFisico = array_sum(array_column($existencias, 'total'));
$totalNina = array_sum(array_column($existencias, 'nina'));
$totalNino = array_sum(array_column($existencias, 'nino'));
$totalApartado = array_sum(array_column($existencias, 'apartado'));
$totalDisponible = array_sum(array_column($existencias, 'disponible'));
$maximoTalla = max(array_map(fn($existencia) => (int)$existencia['total'], $existencias) ?: [1]);

// Contar alertas de stock
$alertasCriticas = 0;
$alertasBajas = 0;
foreach ($existencias as $e) {
    if (($e['nivel_alerta'] ?? '') === 'CRITICO' || ($e['nivel_alerta'] ?? '') === 'AGOTADO') {
        $alertasCriticas++;
    } elseif (($e['nivel_alerta'] ?? '') === 'BAJO') {
        $alertasBajas++;
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <a class="back-link" style="margin-bottom:0;" href="<?= escapar(url('almacenes')) ?>">← Volver a almacenes</a>
    
    <div class="warehouse-action-buttons">
        <a class="button button-action-in" href="<?= escapar(url('inventario-entrada', ['almacen_id' => $almacen['id']])) ?>" title="Registrar recepción de uniformes por proveedor o compra">
            ➕ Entrada de Stock
        </a>
        <a class="button button-action-transfer" href="<?= escapar(url('inventario-traspaso', ['origen_id' => $almacen['id']])) ?>" title="Transferir stock a otro almacén">
            ⇄ Traspasar Stock
        </a>
        <a class="button button-action-adjust" href="<?= escapar(url('inventario-ajuste', ['almacen_id' => $almacen['id']])) ?>" title="Registrar merma, baja o ajuste por inventario físico">
            ⚖️ Ajuste / Merma
        </a>
        <a class="button secondary" href="<?= escapar(url('entrega-nueva', ['almacen_id' => $almacen['id']])) ?>" title="Preparar entrega hacia Servicio Regional">
            📦 Despachar Entrega
        </a>
    </div>
</div>

<?php if ($alertasCriticas > 0 || $alertasBajas > 0): ?>
    <div class="warehouse-stock-alert-banner">
        <div>
            <strong>⚠️ Semáforo de Reorden Activo:</strong>
            <span>
                Este almacén tiene <?= numero($alertasCriticas) ?> talla(s) en nivel crítico/agotado y <?= numero($alertasBajas) ?> talla(s) por debajo del stock mínimo sugerido (<?= numero($existencias[0]['stock_minimo'] ?? 50) ?> pzas).
            </span>
        </div>
        <a class="button button-action-in" style="padding:6px 12px;font-size:12px;" href="<?= escapar(url('inventario-entrada', ['almacen_id' => $almacen['id']])) ?>">
            + Surtir Almacén
        </a>
    </div>
<?php endif; ?>

<section class="warehouse-detail-hero">
    <div>
        <span><?= escapar($almacen['clave'] ?? 'ALMACÉN') ?></span>
        <h3><?= escapar($almacen['nombre']) ?></h3>
        <p><?= escapar($almacen['ubicacion'] ?: 'Ubicación pendiente de registrar') ?> · Responsable: <strong><?= escapar($almacen['responsable'] ?: 'No asignado') ?></strong></p>
    </div>
    <div class="warehouse-detail-total">
        <span>Existencia física</span>
        <strong><?= numero($totalFisico) ?></strong>
        <small>uniformes</small>
    </div>
</section>

<section class="warehouse-kpis">
    <article class="girl-kpi">
        <span>♀ Uniformes Niña</span>
        <strong><?= numero($totalNina) ?></strong>
    </article>
    <article class="boy-kpi">
        <span>♂ Uniformes Niño</span>
        <strong><?= numero($totalNino) ?></strong>
    </article>
    <article>
        <span>Apartado</span>
        <strong><?= numero($totalApartado) ?></strong>
    </article>
    <article class="available-kpi">
        <span>Disponible para entrega</span>
        <strong><?= numero($totalDisponible) ?></strong>
    </article>
</section>

<section class="warehouse-detail-grid">
    <article class="size-inventory-panel">
        <div class="panel-heading">
            <div>
                <span>Distribución operativa</span>
                <h3>Existencia por talla y semáforo de reorden</h3>
            </div>
            <div class="inline-legend">
                <span class="girl-dot">Niña</span>
                <span class="boy-dot">Niño</span>
            </div>
        </div>

        <div class="size-inventory-list">
            <?php foreach ($existencias as $e): ?>
                <?php
                $ancho = $maximoTalla > 0 ? (int)$e['total'] * 100 / $maximoTalla : 0;
                $nivel = $e['nivel_alerta'] ?? 'OPTIMO';
                $badgeColor = ($nivel === 'AGOTADO' || $nivel === 'CRITICO') ? 'status-stock-critical' : (($nivel === 'BAJO') ? 'status-stock-low' : 'status-stock-ok');
                $badgeTexto = ($nivel === 'AGOTADO') ? 'Agotado' : (($nivel === 'CRITICO') ? 'Crítico (≤15)' : (($nivel === 'BAJO') ? 'Reorden (≤50)' : 'Óptimo'));
                ?>
                <div class="size-inventory-row">
                    <div class="size-label">
                        <strong><?= escapar($e['talla']) ?></strong>
                        <span>Talla</span>
                    </div>
                    <div class="size-data">
                        <div class="size-data-header">
                            <span><?= numero($e['total']) ?> piezas (<?= numero($e['disponible']) ?> disp.)</span>
                            <span class="status-stock-pill <?= $badgeColor ?>"><?= escapar($badgeTexto) ?></span>
                        </div>
                        <div class="dual-bar">
                            <i class="dual-girl" style="width:<?= (int)$e['total'] > 0 ? escapar(round((int)$e['nina'] * 100 / $maximoTalla, 2)) : 0 ?>%"></i>
                            <i class="dual-boy" style="width:<?= (int)$e['total'] > 0 ? escapar(round((int)$e['nino'] * 100 / $maximoTalla, 2)) : 0 ?>%"></i>
                        </div>
                    </div>
                    <div class="size-gender-numbers">
                        <span class="girl-value">♀ <?= numero($e['nina']) ?></span>
                        <span class="boy-value">♂ <?= numero($e['nino']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="availability-panel">
        <div class="panel-heading">
            <div>
                <span>Estado de operación</span>
                <h3>Disponibilidad</h3>
            </div>
        </div>
        <div class="availability-ring" style="--available:<?= $totalFisico > 0 ? escapar(round($totalDisponible * 360 / $totalFisico, 2)) : 0 ?>deg">
            <div>
                <strong><?= $totalFisico > 0 ? escapar(round($totalDisponible * 100 / $totalFisico, 1)) : 0 ?>%</strong>
                <span>disponible</span>
            </div>
        </div>
        <dl>
            <div>
                <dt>Físico</dt>
                <dd><?= numero($totalFisico) ?></dd>
            </div>
            <div>
                <dt>Apartado</dt>
                <dd><?= numero($totalApartado) ?></dd>
            </div>
            <div>
                <dt>Disponible</dt>
                <dd><?= numero($totalDisponible) ?></dd>
            </div>
        </dl>
        <p>La existencia disponible considera únicamente piezas que no están comprometidas en una entrega preparada o solicitud activa.</p>
    </article>
</section>

<section class="warehouse-movements">
    <div class="panel-heading">
        <div>
            <span>Trazabilidad</span>
            <h3>Movimientos recientes en este almacén</h3>
        </div>
        <a href="<?= escapar(url('movimientos', ['almacen_id' => $almacen['id']])) ?>">Ver kardex filtrado →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Género</th>
                    <th>Talla</th>
                    <th>Cantidad</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$movimientos): ?>
                    <tr>
                        <td colspan="6" class="empty">Sin movimientos registrados.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($movimientos as $m): ?>
                    <?php
                    $tipoClass = match ($m['tipo']) {
                        'ENTRADA' => 'status-in',
                        'SALIDA' => 'status-out',
                        'MERMA' => 'status-loss',
                        'AJUSTE_POSITIVO' => 'status-in',
                        'AJUSTE_NEGATIVO' => 'status-loss',
                        default => ''
                    };
                    ?>
                    <tr>
                        <td><?= escapar($m['fecha_movimiento']) ?></td>
                        <td><span class="status <?= $tipoClass ?>"><?= escapar(str_replace('_', ' ', $m['tipo'])) ?></span></td>
                        <td class="<?= $m['sexo'] === 'NINA' ? 'girl-cell' : 'boy-cell' ?>"><?= escapar($m['sexo'] === 'NINO' ? 'Niño' : 'Niña') ?></td>
                        <td><?= escapar($m['talla']) ?></td>
                        <td><strong><?= numero($m['cantidad']) ?></strong></td>
                        <td><?= escapar($m['observaciones'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

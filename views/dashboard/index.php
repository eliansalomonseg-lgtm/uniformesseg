<?php
$totalFisico = (int)$resumen['inventario']['total'];
$totalNino = (int)$resumen['inventario']['nino'];
$totalNina = (int)$resumen['inventario']['nina'];
$porcentajeNino = $totalFisico > 0 ? round($totalNino * 100 / $totalFisico, 1) : 0;
$porcentajeNina = $totalFisico > 0 ? round($totalNina * 100 / $totalFisico, 1) : 0;
$maximoAlmacen = max(array_map(fn($a) => (int)$a['total'], $almacenes) ?: [1]);
$maximoTalla = max(array_map(fn($t) => max((int)$t['nino'], (int)$t['nina']), $existenciasPorTalla) ?: [1]);
$alertasStock = $alertasStock ?? [];
?>

<section class="dashboard-hero">
    <div>
        <span>Inventario institucional actualizado</span>
        <h3><?= numero($totalFisico) ?> uniformes bajo control</h3>
        <p>Existencia consolidada en <?= numero($resumen['activos']) ?> almacenes activos de Guerrero.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a class="button" style="background:#246e45;color:#fff;" href="<?= escapar(url('inventario-entrada')) ?>">➕ Entrada de Stock</a>
        <a class="button" style="background:#235782;color:#fff;" href="<?= escapar(url('inventario-traspaso')) ?>">⇄ Traspaso</a>
        <a class="button" href="<?= escapar(url('almacenes')) ?>">Consultar existencias</a>
    </div>
</section>

<section class="executive-metrics">
    <article class="executive-card total-card">
        <div class="metric-icon">∑</div>
        <div>
            <span>Total físico</span>
            <strong><?= numero($totalFisico) ?></strong>
            <small>100% del inventario</small>
        </div>
    </article>
    <article class="executive-card girl-card">
        <div class="metric-icon">♀</div>
        <div>
            <span>Uniformes Niña</span>
            <strong><?= numero($totalNina) ?></strong>
            <small><?= escapar($porcentajeNina) ?>% del inventario</small>
        </div>
    </article>
    <article class="executive-card boy-card">
        <div class="metric-icon">♂</div>
        <div>
            <span>Uniformes Niño</span>
            <strong><?= numero($totalNino) ?></strong>
            <small><?= escapar($porcentajeNino) ?>% del inventario</small>
        </div>
    </article>
    <article class="executive-card available-card">
        <div class="metric-icon">✓</div>
        <div>
            <span>Disponible</span>
            <strong><?= numero($resumen['inventario']['disponible']) ?></strong>
            <small><?= numero($resumen['inventario']['apartado']) ?> piezas apartadas</small>
        </div>
    </article>
</section>

<?php if ($alertasStock): ?>
    <section class="chart-panel" style="margin-top:20px;border-left:4px solid #c94b56;">
        <div class="panel-heading">
            <div>
                <span style="color:#c94b56;">Semáforo de Reorden Operativo</span>
                <h3>⚠️ Alertas de Stock Mínimo por Almacén</h3>
            </div>
            <a href="<?= escapar(url('inventario-entrada')) ?>" style="color:#246e45;font-weight:700;">+ Programar Ingreso</a>
        </div>
        <p style="margin:-10px 0 16px;color:var(--muted);font-size:13px;">
            Las siguientes tallas registran existencias disponibles iguales o menores al umbral de reorden (≤50 prendas) o están en nivel crítico/agotado:
        </p>
        <div class="table-wrap" style="margin:0;">
            <table>
                <thead>
                    <tr>
                        <th>Almacén</th>
                        <th>Talla</th>
                        <th>Género</th>
                        <th>Físico</th>
                        <th>Apartado</th>
                        <th>Disponible</th>
                        <th>Estado de Stock</th>
                        <th style="text-align:right;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($alertasStock, 0, 8) as $al): ?>
                        <?php
                        $badgeClase = match ($al['nivel_alerta']) {
                            'AGOTADO' => 'status-stock-critical',
                            'CRITICO' => 'status-stock-critical',
                            'BAJO' => 'status-stock-low',
                            default => 'status-stock-ok'
                        };
                        ?>
                        <tr>
                            <td><strong><?= escapar($al['almacen_nombre']) ?></strong></td>
                            <td>Talla <?= escapar($al['talla']) ?></td>
                            <td><?= $al['sexo'] === 'NINA' ? '♀ Niña' : '♂ Niño' ?></td>
                            <td><?= numero($al['cantidad_fisica']) ?></td>
                            <td><?= numero($al['cantidad_apartada']) ?></td>
                            <td><strong><?= numero($al['disponible']) ?></strong></td>
                            <td>
                                <span class="status-stock-pill <?= $badgeClase ?>">
                                    <?= escapar($al['nivel_alerta']) ?> (≤ <?= numero($al['stock_minimo']) ?>)
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <a class="button button-action-in" style="padding:4px 9px;font-size:11px;" href="<?= escapar(url('inventario-entrada', ['almacen_id' => $al['almacen_id']])) ?>">
                                    + Surtir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="dashboard-grid">
    <article class="chart-panel distribution-panel">
        <div class="panel-heading">
            <div>
                <span>Distribución general</span>
                <h3>Inventario por género</h3>
            </div>
            <span class="panel-tag"><?= numero($totalFisico) ?> piezas</span>
        </div>
        <div class="donut-layout">
            <div class="donut-chart" style="--girl:<?= escapar($porcentajeNina) ?>deg;--girl-angle:<?= escapar($porcentajeNina * 3.6) ?>deg">
                <div>
                    <strong><?= numero($totalFisico) ?></strong>
                    <span>Total</span>
                </div>
            </div>
            <div class="chart-legend">
                <div class="girl-legend">
                    <i></i>
                    <span>Niña<strong><?= numero($totalNina) ?></strong><small><?= escapar($porcentajeNina) ?>%</small></span>
                </div>
                <div class="boy-legend">
                    <i></i>
                    <span>Niño<strong><?= numero($totalNino) ?></strong><small><?= escapar($porcentajeNino) ?>%</small></span>
                </div>
            </div>
        </div>
    </article>

    <article class="chart-panel">
        <div class="panel-heading">
            <div>
                <span>Concentración operativa</span>
                <h3>Existencias por almacén</h3>
            </div>
            <a href="<?= escapar(url('almacenes')) ?>">Ver detalle</a>
        </div>
        <div class="warehouse-chart">
            <?php foreach ($almacenes as $a): ?>
                <?php $ancho = $maximoAlmacen > 0 ? (int)$a['total'] * 100 / $maximoAlmacen : 0; ?>
                <div class="warehouse-row">
                    <div class="warehouse-label">
                        <span><?= escapar(str_replace('ALMACEN REGIONAL ZONA ', '', $a['nombre'])) ?></span>
                        <strong><?= numero($a['total']) ?></strong>
                    </div>
                    <div class="bar-track">
                        <div class="bar-total" style="width:<?= escapar(round($ancho, 2)) ?>%">
                            <span class="bar-girl" style="width:<?= (int)$a['total'] > 0 ? escapar(round((int)$a['nina'] * 100 / (int)$a['total'], 2)) : 0 ?>%"></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<article class="chart-panel size-panel">
    <div class="panel-heading">
        <div>
            <span>Composición del inventario</span>
            <h3>Niña y Niño por talla</h3>
        </div>
        <div class="inline-legend">
            <span class="girl-dot">Niña</span>
            <span class="boy-dot">Niño</span>
        </div>
    </div>
    <div class="size-chart">
        <?php foreach ($existenciasPorTalla as $t): ?>
            <?php
            $altoNina = $maximoTalla > 0 ? (int)$t['nina'] * 100 / $maximoTalla : 0;
            $altoNino = $maximoTalla > 0 ? (int)$t['nino'] * 100 / $maximoTalla : 0;
            ?>
            <div class="size-group">
                <div class="size-columns">
                    <div class="vertical-bar girl-bar" style="height:<?= escapar(round($altoNina, 2)) ?>%">
                        <span><?= numero($t['nina']) ?></span>
                    </div>
                    <div class="vertical-bar boy-bar" style="height:<?= escapar(round($altoNino, 2)) ?>%">
                        <span><?= numero($t['nino']) ?></span>
                    </div>
                </div>
                <strong>Talla <?= escapar($t['talla']) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</article>

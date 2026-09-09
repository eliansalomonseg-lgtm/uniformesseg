<?php
$totalFisico=(int)$resumen['inventario']['total'];
$totalNino=(int)$resumen['inventario']['nino'];
$totalNina=(int)$resumen['inventario']['nina'];
$porcentajeNino=$totalFisico>0?round($totalNino*100/$totalFisico,1):0;
$porcentajeNina=$totalFisico>0?round($totalNina*100/$totalFisico,1):0;
$maximoAlmacen=max(array_map(fn($a)=>(int)$a['total'],$almacenes)?:[1]);
$maximoTalla=max(array_map(fn($t)=>max((int)$t['nino'],(int)$t['nina']),$existenciasPorTalla)?:[1]);
?>
<section class="dashboard-hero"><div><span>Inventario institucional actualizado</span><h3><?= numero($totalFisico) ?> uniformes bajo control</h3><p>Existencia consolidada en <?= numero($resumen['activos']) ?> almacenes activos.</p></div><a class="button" href="<?= escapar(url('almacenes')) ?>">Consultar existencias</a></section>

<section class="executive-metrics">
    <article class="executive-card total-card"><div class="metric-icon">∑</div><div><span>Total físico</span><strong><?= numero($totalFisico) ?></strong><small>100% del inventario</small></div></article>
    <article class="executive-card girl-card"><div class="metric-icon">♀</div><div><span>Uniformes Niña</span><strong><?= numero($totalNina) ?></strong><small><?= escapar($porcentajeNina) ?>% del inventario</small></div></article>
    <article class="executive-card boy-card"><div class="metric-icon">♂</div><div><span>Uniformes Niño</span><strong><?= numero($totalNino) ?></strong><small><?= escapar($porcentajeNino) ?>% del inventario</small></div></article>
    <article class="executive-card available-card"><div class="metric-icon">✓</div><div><span>Disponible</span><strong><?= numero($resumen['inventario']['disponible']) ?></strong><small><?= numero($resumen['inventario']['apartado']) ?> piezas apartadas</small></div></article>
</section>

<section class="dashboard-grid">
    <article class="chart-panel distribution-panel"><div class="panel-heading"><div><span>Distribución general</span><h3>Inventario por género</h3></div><span class="panel-tag"><?= numero($totalFisico) ?> piezas</span></div><div class="donut-layout"><div class="donut-chart" style="--girl:<?= escapar($porcentajeNina) ?>deg;--girl-angle:<?= escapar($porcentajeNina*3.6) ?>deg"><div><strong><?= numero($totalFisico) ?></strong><span>Total</span></div></div><div class="chart-legend"><div class="girl-legend"><i></i><span>Niña<strong><?= numero($totalNina) ?></strong><small><?= escapar($porcentajeNina) ?>%</small></span></div><div class="boy-legend"><i></i><span>Niño<strong><?= numero($totalNino) ?></strong><small><?= escapar($porcentajeNino) ?>%</small></span></div></div></div></article>
    <article class="chart-panel"><div class="panel-heading"><div><span>Concentración operativa</span><h3>Existencias por almacén</h3></div><a href="<?= escapar(url('almacenes')) ?>">Ver detalle</a></div><div class="warehouse-chart"><?php foreach($almacenes as $a): $ancho=$maximoAlmacen>0?(int)$a['total']*100/$maximoAlmacen:0; ?><div class="warehouse-row"><div class="warehouse-label"><span><?= escapar(str_replace('ALMACEN REGIONAL ZONA ','',$a['nombre'])) ?></span><strong><?= numero($a['total']) ?></strong></div><div class="bar-track"><div class="bar-total" style="width:<?= escapar(round($ancho,2)) ?>%"><span class="bar-girl" style="width:<?= (int)$a['total']>0?escapar(round((int)$a['nina']*100/(int)$a['total'],2)):0 ?>%"></span></div></div></div><?php endforeach; ?></div></article>
</section>

<article class="chart-panel size-panel"><div class="panel-heading"><div><span>Composición del inventario</span><h3>Niña y Niño por talla</h3></div><div class="inline-legend"><span class="girl-dot">Niña</span><span class="boy-dot">Niño</span></div></div><div class="size-chart"><?php foreach($existenciasPorTalla as $t): $altoNina=$maximoTalla>0?(int)$t['nina']*100/$maximoTalla:0; $altoNino=$maximoTalla>0?(int)$t['nino']*100/$maximoTalla:0; ?><div class="size-group"><div class="size-columns"><div class="vertical-bar girl-bar" style="height:<?= escapar(round($altoNina,2)) ?>%"><span><?= numero($t['nina']) ?></span></div><div class="vertical-bar boy-bar" style="height:<?= escapar(round($altoNino,2)) ?>%"><span><?= numero($t['nino']) ?></span></div></div><strong>Talla <?= escapar($t['talla']) ?></strong></div><?php endforeach; ?></div></article>

<?php
$totalFisico=array_sum(array_column($almacenes,'total'));
$totalNina=array_sum(array_column($almacenes,'nina'));
$totalNino=array_sum(array_column($almacenes,'nino'));
$totalDisponible=array_sum(array_column($almacenes,'disponible'));
$maximoAlmacen=max(array_map(fn($almacen)=>(int)$almacen['total'],$almacenes)?:[1]);
?>
<section class="warehouse-intro"><div><span>Inventario por sede</span><h3><?= numero($totalFisico) ?> uniformes en <?= numero(count($almacenes)) ?> almacenes</h3><p>Consulta operativa de existencias físicas, apartados y disponibilidad por cada almacén.</p></div><div class="warehouse-intro-stats"><div><strong><?= numero($totalNina) ?></strong><span>Niña</span></div><div><strong><?= numero($totalNino) ?></strong><span>Niño</span></div><div><strong><?= numero($totalDisponible) ?></strong><span>Disponible</span></div></div></section>

<section class="warehouse-directory">
<?php if(!$almacenes): ?><div class="empty">Sin almacenes registrados.</div><?php endif; ?>
<?php foreach($almacenes as $a):
    $porcentajeNina=(int)$a['total']>0?(int)$a['nina']*100/(int)$a['total']:0;
    $porcentajeTotal=$maximoAlmacen>0?(int)$a['total']*100/$maximoAlmacen:0;
?>
<article class="warehouse-card">
    <div class="warehouse-card-top"><div class="warehouse-pin">⌂</div><div><span class="warehouse-code"><?= escapar($a['clave']??'ALM') ?></span><h3><?= escapar(str_replace('ALMACEN ','',$a['nombre'])) ?></h3></div><span class="stock-state">Disponible</span></div>
    <div class="warehouse-total"><span>Existencia física</span><strong><?= numero($a['total']) ?></strong><small>uniformes registrados</small></div>
    <div class="warehouse-gender"><div class="gender-value girl-value"><span>♀ Niña</span><strong><?= numero($a['nina']) ?></strong></div><div class="gender-value boy-value"><span>♂ Niño</span><strong><?= numero($a['nino']) ?></strong></div></div>
    <div class="warehouse-composition"><div><span>Composición por género</span><strong><?= escapar(round($porcentajeNina,1)) ?>% Niña</strong></div><div class="composition-track"><span class="composition-girl" style="width:<?= escapar(round($porcentajeNina,2)) ?>%"></span></div></div>
    <div class="warehouse-capacity"><span>Volumen frente al almacén con mayor existencia</span><div><i style="width:<?= escapar(round($porcentajeTotal,2)) ?>%"></i></div></div>
    <div class="warehouse-card-footer"><span><?= numero($a['apartado']) ?> apartados</span><a class="button" href="<?= escapar(url('almacen-detalle',['id'=>$a['id']])) ?>">Ver detalle <b>→</b></a></div>
</article>
<?php endforeach; ?>
</section>

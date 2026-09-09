<?php
$totalSolicitado=0;
$totalDisponibleParaSolicitud=0;
$totalFaltante=0;
$requerimientos=[];
if($almacenId&&$servicioId){
    foreach($comparativo as $registro){
        foreach([['NINA','Niña','solicitado_nina','disponible_nina','girl'],['NINO','Niño','solicitado_nino','disponible_nino','boy']] as [$sexo,$etiqueta,$campoSolicitado,$campoDisponible,$clase]){
            $solicitado=(int)$registro[$campoSolicitado];
            $disponible=(int)$registro[$campoDisponible];
            if($solicitado>0){
                $cubierto=min($solicitado,$disponible);
                $faltante=max(0,$solicitado-$disponible);
                $totalSolicitado+=$solicitado;
                $totalDisponibleParaSolicitud+=$cubierto;
                $totalFaltante+=$faltante;
                $requerimientos[]=['talla'=>$registro['talla'],'sexo'=>$etiqueta,'clase'=>$clase,'solicitado'=>$solicitado,'disponible'=>$disponible,'faltante'=>$faltante];
            }
        }
    }
}
?>
<div class="notice">Esta pantalla revisa si el almacén seleccionado puede cubrir la solicitud. Consultarla no aparta ni descuenta inventario.</div>
<?php if($error): ?><div class="notice request-error"><?= escapar($error) ?></div><?php endif; ?>
<form class="filters delivery-selection" method="get"><input type="hidden" name="ruta" value="entrega-nueva"><label class="delivery-warehouse-label"><span>¿Qué almacén realizará el envío?</span><select name="almacen_id" required><option value="">Seleccione el almacén que enviará</option><?php foreach($almacenes as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $almacenId===(int)$a['id']?'selected':'' ?>><?= escapar($a['nombre']) ?></option><?php endforeach; ?></select><small>Sus existencias se usarán para revisar la disponibilidad.</small></label><label><span>Servicio Regional de destino</span><select name="servicio_id" required><option value="">Seleccione</option><?php foreach($servicios as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $servicioId===(int)$s['id']?'selected':'' ?>><?= escapar($s['nombre']) ?></option><?php endforeach; ?></select><small>Se llena automáticamente desde la solicitud cuando corresponde.</small></label><button class="button">Revisar existencias</button></form>

<?php if($almacenId&&$servicioId): ?>
<section class="delivery-result-header"><div><span>Resultado de la revisión</span><h3><?= $totalFaltante>0?'Hay uniformes faltantes':'El almacén puede cubrir la solicitud' ?></h3><p><?= $totalFaltante>0?'Las tarjetas en rojo indican exactamente qué talla y género no alcanzan.':'Todas las cantidades solicitadas están disponibles en el almacén seleccionado.' ?></p></div><div class="delivery-result-metrics"><div><strong><?= numero($totalSolicitado) ?></strong><span>Solicitados</span></div><div class="available-result"><strong><?= numero($totalDisponibleParaSolicitud) ?></strong><span>Se pueden enviar</span></div><div class="<?= $totalFaltante>0?'shortage-result':'available-result' ?>"><strong><?= numero($totalFaltante) ?></strong><span>Faltantes</span></div></div></section>

<section class="delivery-requests"><div class="panel-heading"><div><span>Solicitudes incluidas</span><h3><?= numero(count($solicitudes)) ?> escuela<?= count($solicitudes)===1?'':'s' ?> para <?= escapar($servicios[array_search($servicioId,array_column($servicios,'id'))]['nombre']??'este Servicio Regional') ?></h3></div></div><?php if(!$solicitudes): ?><div class="empty">No hay solicitudes pendientes para este Servicio Regional.</div><?php else: ?><div class="delivery-request-list"><?php foreach($solicitudes as $solicitud): ?><article><div><span><?= escapar($solicitud['folio']) ?></span><h4><?= escapar($solicitud['escuela']) ?></h4><p>CCT <?= escapar($solicitud['cct']) ?></p></div><strong><?= numero($solicitud['total']) ?><small>uniformes solicitados</small></strong></article><?php endforeach; ?></div><?php endif; ?></section>

<?php if($requerimientos): ?><section class="requirements-review"><div class="panel-heading"><div><span>Revisión por talla</span><h3>¿Qué puede salir de este almacén?</h3></div><div class="delivery-key"><span class="key-available">Disponible</span><span class="key-shortage">Faltante</span></div></div><p>Solo se muestran las tallas y géneros que fueron solicitados.</p><div class="requirements-grid"><?php foreach($requerimientos as $requerimiento): ?><article class="requirement-card <?= $requerimiento['faltante']>0?'requirement-shortage':'' ?> <?= escapar($requerimiento['clase']) ?>"><div class="requirement-card-heading"><span><?= escapar($requerimiento['sexo']) ?></span><strong>Talla <?= escapar($requerimiento['talla']) ?></strong></div><div class="requirement-values"><div><span>Pidieron</span><strong><?= numero($requerimiento['solicitado']) ?></strong></div><div><span>Hay en almacén</span><strong><?= numero($requerimiento['disponible']) ?></strong></div></div><div class="requirement-decision"><?php if($requerimiento['faltante']>0): ?><strong>Faltan <?= numero($requerimiento['faltante']) ?></strong><span>No alcanza para cubrir esta cantidad.</span><?php else: ?><strong>Disponible</strong><span>Esta cantidad se puede enviar.</span><?php endif; ?></div></article><?php endforeach; ?></div></section><?php endif; ?>
<?php if($totalFaltante===0 && $totalSolicitado>0): ?><form method="post" class="delivery-send-form"><input type="hidden" name="ruta" value="entrega-nueva"><input type="hidden" name="accion" value="enviar"><input type="hidden" name="almacen_id" value="<?= (int)$almacenId ?>"><input type="hidden" name="servicio_id" value="<?= (int)$servicioId ?>"><div><strong>Todo está disponible para este envío</strong><span>Al confirmar se registrará la salida interna, se descontarán <?= numero($totalSolicitado) ?> uniformes y las solicitudes quedarán incluidas en el envío.</span></div><button class="button">Enviar <?= numero($totalSolicitado) ?> uniformes al Servicio Regional →</button></form><?php elseif($totalFaltante>0): ?><div class="notice gold">No es posible registrar el envío completo porque faltan <?= numero($totalFaltante) ?> uniformes. La solicitud permanece registrada para conservar la necesidad real.</div><?php endif; ?>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:12px;">
    <a class="back-link" style="margin-bottom:0;" href="<?= escapar(url('entregas')) ?>">← Volver al listado de entregas</a>
    
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <button type="button" class="button secondary" onclick="window.print()" title="Imprimir comprobante de entrega">
            🖨️ Imprimir
        </button>
        <?php if (!empty($entrega['archivo_acuse'])): ?>
            <a class="button" style="background:#1d663b;" href="<?= escapar($entrega['archivo_acuse']) ?>" target="_blank" title="Ver comprobante escaneado firmado">
                📎 Ver Acuse Firmado
            </a>
            <a class="button secondary" href="<?= escapar(url('subir-acuse-regional', ['id' => $entrega['id']])) ?>" title="Reemplazar archivo de acuse">
                🔄 Reemplazar
            </a>
        <?php elseif ($entrega['estado'] !== 'CANCELADO'): ?>
            <a class="button" style="background:#b45309;" href="<?= escapar(url('subir-acuse-regional', ['id' => $entrega['id']])) ?>" title="Subir acuse físico firmado y sellado">
                📤 Subir Acuse Firmado
            </a>
        <?php endif; ?>
        <?php if ($entrega['estado'] !== 'CANCELADO'): ?>
            <button type="button" class="button button-cancel" onclick="document.getElementById('modal-cancelar-detalle').hidden=false" title="Cancela la entrega y reingresa el stock al almacén">
                🚫 Cancelar entrega
            </button>
        <?php endif; ?>
        <button type="button" class="button button-danger" onclick="document.getElementById('modal-borrar-detalle').hidden=false" title="Elimina permanentemente esta entrega">
            🗑️ Borrar entrega
        </button>
    </div>
</div>

<?php if (isset($_GET['enviada']) && $_GET['enviada'] === '1'): ?>
    <div class="request-success" style="margin-bottom:18px;">
        ✓ <strong>Entrega registrada correctamente.</strong> Las existencias y los movimientos del almacén fueron actualizados.
    </div>
<?php endif; ?>

<?php if (isset($_GET['acuse_subido']) && $_GET['acuse_subido'] === '1'): ?>
    <div class="request-success" style="margin-bottom:18px;background:#ecfdf5;border-color:#a7f3d0;color:#065f46;">
        ✓ <strong>Acuse físico resguardado digitalmente.</strong> El comprobante firmado y sellado por el Servicio Regional ha quedado registrado para auditoría oficial.
    </div>
<?php endif; ?>

<?php if (isset($_GET['cancelada']) && $_GET['cancelada'] === '1'): ?>
    <div class="request-success" style="background:#fff8e6;border-color:#f6d38e;color:#8a5a00;margin-bottom:18px;">
        ✓ <strong>Entrega cancelada exitosamente.</strong> El inventario fue reintegrado al almacén correspondiente y las solicitudes asociadas volvieron a estar disponibles.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="notice request-error" style="margin-bottom:18px;">
        ⚠ <?= escapar($_GET['error']) ?>
    </div>
<?php endif; ?>

<?php
$esCancelado = ($entrega['estado'] === 'CANCELADO');
$esEntregado = in_array($entrega['estado'], ['ENTREGADO', 'RECIBIDO'], true);
$estadoTexto = $esCancelado ? 'CANCELADO' : ($esEntregado ? 'ENTREGADO' : str_replace('_', ' ', $entrega['estado']));
$badgeClase = $esCancelado ? 'status-canceled' : ($esEntregado ? 'status-delivered' : ($entrega['estado'] === 'PENDIENTE' ? 'status-pending' : ''));
$totalGeneralEntregado = 0;
foreach ($detalle as $d) {
    $totalGeneralEntregado += (int)($d['entregado_nino'] ?? 0) + (int)($d['entregado_nina'] ?? 0);
}
?>

<?php if ($esCancelado): ?>
    <div class="notice-warning" style="margin-bottom:18px;">
        <strong>⚠ Comprobante Cancelado:</strong> Esta entrega fue cancelada. Las <b><?= numero($totalGeneralEntregado) ?></b> piezas fueron devueltas al inventario del <b><?= escapar($entrega['almacen']) ?></b> y las solicitudes asociadas volvieron a estar en estado <b>PENDIENTE</b>. Si ya no requiere este registro, puede usar el botón <b>Borrar entrega</b>.
    </div>
<?php endif; ?>

<section class="summary">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px;">
        <div>
            <span style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;">Comprobante oficial de entrega</span>
            <h3 style="margin:4px 0;color:var(--wine);font-size:26px;<?= $esCancelado ? 'text-decoration:line-through;color:var(--muted);' : '' ?>">
                <?= escapar($entrega['folio']) ?>
            </h3>
            <p style="margin:6px 0;font-size:15px;">
                Entrega realizada por <strong><?= escapar($entrega['almacen']) ?></strong> al <strong><?= escapar($entrega['servicio_regional']) ?></strong>
            </p>
            <?php if (!empty($entrega['fecha_salida'])): ?>
                <small style="color:var(--muted);font-weight:600;">Fecha de entrega: <?= escapar($entrega['fecha_salida']) ?></small>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <div style="margin-bottom:8px;">
                <span class="status <?= $badgeClase ?>" style="font-size:14px;padding:6px 14px;"><?= escapar($estadoTexto) ?></span>
            </div>
            <strong style="font-size:24px;color:var(--wine);"><?= numero($totalGeneralEntregado) ?></strong>
            <span style="display:block;font-size:12px;color:var(--muted);font-weight:600;">uniformes <?= $esCancelado ? 'reintegrados' : 'entregados' ?></span>
        </div>
    </div>
</section>

<!-- Panel de Verificación QR y Resguardo de Acuse -->
<section class="chart-panel" style="margin-top:18px;padding:20px;background:#fff;border:1px solid #ebdccb;border-radius:12px;">
    <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
        <div style="background:#fdfcfb;border:1px solid #dfcfbc;padding:10px;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <?= $qrSvg ?>
        </div>
        <div style="flex:1;min-width:280px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
                <span style="font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--gold);background:#fef9ee;padding:3px 8px;border-radius:4px;border:1px solid #f6d38e;">
                    Sello Digital de Autenticidad SEG
                </span>
                <?php if (!empty($entrega['archivo_acuse'])): ?>
                    <span style="font-size:11px;font-weight:700;color:#065f46;background:#ecfdf5;padding:3px 8px;border-radius:4px;border:1px solid #a7f3d0;">
                        ✓ Acuse Firmado Digitalizado
                    </span>
                <?php else: ?>
                    <span style="font-size:11px;font-weight:700;color:#92400e;background:#fffbeb;padding:3px 8px;border-radius:4px;border:1px solid #fde68a;">
                        ⏳ Acuse Físico Pendiente de Subir
                    </span>
                <?php endif; ?>
            </div>
            <h4 style="margin:4px 0 6px;color:var(--wine);font-size:17px;">
                Verificación Electrónica y Resguardo Documental
            </h4>
            <p style="margin:0 0 10px;font-size:13px;color:var(--muted);line-height:1.5;">
                Escanee el código QR desde cualquier teléfono móvil para validar la autenticidad oficial de este comprobante en los servidores de la SEG, o consulte los documentos de resguardo.
            </p>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <code style="background:#f4ece1;color:var(--wine);padding:5px 10px;border-radius:6px;font-size:12px;font-weight:700;letter-spacing:.03em;">
                    <?= escapar($entrega['codigo_verificacion']) ?>
                </code>
                <a class="button secondary" style="font-size:12px;padding:6px 12px;" href="<?= escapar($urlVerif) ?>" target="_blank">
                    🔍 Abrir Verificación Pública
                </a>
                <?php if (!empty($entrega['archivo_acuse'])): ?>
                    <a class="button" style="font-size:12px;padding:6px 12px;background:#1d663b;" href="<?= escapar($entrega['archivo_acuse']) ?>" target="_blank">
                        📎 Consultar Acuse Adjunto
                    </a>
                <?php else: ?>
                    <a class="button" style="font-size:12px;padding:6px 12px;background:#b45309;" href="<?= escapar(url('subir-acuse-regional', ['id' => $entrega['id']])) ?>">
                        📤 Cargar Acuse Físico
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($solicitudes)): ?>
    <section class="chart-panel" style="margin-top:18px;padding:16px 20px;">
        <div class="panel-heading" style="margin-bottom:12px;">
            <div>
                <span>Trazabilidad</span>
                <h3 style="font-size:15px;margin:2px 0;">Solicitudes escolares vinculadas a esta entrega</h3>
            </div>
            <span class="panel-tag"><?= count($solicitudes) ?> solicitud(es)</span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <?php foreach ($solicitudes as $s): ?>
                <div style="display:inline-flex;align-items:center;gap:10px;padding:8px 14px;background:#fbf9f6;border:1px solid #ebdccb;border-radius:8px;">
                    <a href="<?= escapar(url('solicitud-detalle', ['id' => $s['id']])) ?>" style="font-weight:800;color:var(--wine);text-decoration:none;font-size:13px;">
                        📄 <?= escapar($s['folio']) ?>
                    </a>
                    <span style="font-size:11px;color:var(--muted);"><?= escapar($s['total_escuelas']) ?> escuelas</span>
                    <span class="status" style="font-size:10px;padding:3px 8px;"><?= escapar(str_replace('_', ' ', $s['estado'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Talla</th>
                <th>Solicitado Niño</th>
                <th>Entregado Niño</th>
                <th>Solicitado Niña</th>
                <th>Entregado Niña</th>
                <th style="text-align:right;">Total Talla</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$detalle): ?>
                <tr>
                    <td colspan="6" class="empty">Sin detalle registrado.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($detalle as $d): ?>
                <?php $subtotal = (int)$d['entregado_nino'] + (int)$d['entregado_nina']; ?>
                <tr>
                    <td><strong>Talla <?= escapar($d['talla']) ?></strong></td>
                    <td><?= numero($d['solicitado_nino']) ?></td>
                    <td><strong><?= numero($d['entregado_nino']) ?></strong></td>
                    <td><?= numero($d['solicitado_nina']) ?></td>
                    <td><strong><?= numero($d['entregado_nina']) ?></strong></td>
                    <td style="text-align:right;"><b><?= numero($subtotal) ?></b></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Cancelar Entrega (Detalle) -->
<div class="modal-overlay" id="modal-cancelar-detalle" hidden>
    <div class="modal-box modal-cancel-theme">
        <div class="modal-header">
            <div>
                <span style="color:#b45309;">Acción Reversible</span>
                <h3 style="color:#92400e;">🚫 Cancelar Entrega <?= escapar($entrega['folio']) ?></h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="document.getElementById('modal-cancelar-detalle').hidden=true">✕</button>
        </div>
        <form method="post" action="<?= escapar(url('entrega-cancelar')) ?>">
            <input type="hidden" name="id" value="<?= (int)$entrega['id'] ?>">
            <input type="hidden" name="origen" value="detalle">
            <div class="modal-body">
                <div class="modal-summary-grid">
                    <div class="modal-summary-item">
                        <span>Folio</span>
                        <strong><?= escapar($entrega['folio']) ?></strong>
                    </div>
                    <div class="modal-summary-item">
                        <span>Total a devolver</span>
                        <strong style="color:#b45309;"><?= numero($totalGeneralEntregado) ?> uniformes</strong>
                    </div>
                    <div class="modal-summary-item full">
                        <span>Almacén / Receptor</span>
                        <p><?= escapar($entrega['almacen']) ?> → <?= escapar($entrega['servicio_regional']) ?></p>
                    </div>
                </div>

                <div class="modal-notice" style="background:#fffbeb;border-left-color:#d97706;color:#92400e;">
                    <strong>Consecuencias de cancelar:</strong>
                    <ul style="margin:6px 0 0;padding-left:18px;">
                        <li>Se devolverán <b><?= numero($totalGeneralEntregado) ?></b> uniformes al inventario físico del <b><?= escapar($entrega['almacen']) ?></b>.</li>
                        <li>Las solicitudes escolares vinculadas volverán a estado <b>PENDIENTE</b> para poder editarse o atenderse de nuevo.</li>
                        <li>El comprobante quedará marcado con estado <b>CANCELADO</b> para auditoría.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="document.getElementById('modal-cancelar-detalle').hidden=true">No cancelar</button>
                <button type="submit" class="button button-cancel">Confirmar Cancelación</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Borrar Entrega (Detalle) -->
<div class="modal-overlay" id="modal-borrar-detalle" hidden>
    <div class="modal-box modal-delete-theme">
        <div class="modal-header">
            <div>
                <span style="color:#b91c1c;">Acción Permanente</span>
                <h3 style="color:#991b1b;">🗑 Borrar Entrega <?= escapar($entrega['folio']) ?></h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="document.getElementById('modal-borrar-detalle').hidden=true">✕</button>
        </div>
        <form method="post" action="<?= escapar(url('entrega-eliminar')) ?>">
            <input type="hidden" name="id" value="<?= (int)$entrega['id'] ?>">
            <input type="hidden" name="origen" value="entregas">
            <div class="modal-body">
                <div class="modal-summary-grid">
                    <div class="modal-summary-item">
                        <span>Folio</span>
                        <strong><?= escapar($entrega['folio']) ?></strong>
                    </div>
                    <div class="modal-summary-item">
                        <span>Uniformes</span>
                        <strong style="color:#b91c1c;"><?= numero($totalGeneralEntregado) ?> piezas</strong>
                    </div>
                    <div class="modal-summary-item full">
                        <span>Almacén / Receptor</span>
                        <p><?= escapar($entrega['almacen']) ?> → <?= escapar($entrega['servicio_regional']) ?></p>
                    </div>
                </div>

                <div class="modal-notice" style="background:#fef2f2;border-left-color:#b91c1c;color:#991b1b;">
                    <strong>¿Está seguro de borrar definitivamente este comprobante?</strong>
                    <p style="margin:6px 0 0;">
                        <?php if ($esCancelado): ?>
                            Esta entrega ya está cancelada. Se eliminará permanentemente el registro de la base de datos.
                        <?php else: ?>
                            Como la entrega no estaba cancelada, los <b><?= numero($totalGeneralEntregado) ?> uniformes serán reintegrados automáticamente al almacén</b> y las solicitudes asociadas regresarán a estado <b>PENDIENTE</b>. El comprobante desaparecerá por completo.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="document.getElementById('modal-borrar-detalle').hidden=true">Cancelar</button>
                <button type="submit" class="button button-danger">Eliminar Definitivamente</button>
            </div>
        </form>
    </div>
</div>

<script>
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var mC = document.getElementById('modal-cancelar-detalle');
        var mB = document.getElementById('modal-borrar-detalle');
        if (mC) mC.hidden = true;
        if (mB) mB.hidden = true;
    }
});

document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.hidden = true;
        }
    });
});
</script>

<div class="actions">
    <a class="button" href="<?= escapar(url('entrega-nueva')) ?>">+ Registrar nueva entrega</a>
</div>

<?php if (isset($_GET['cancelada'])): ?>
    <div class="request-success" style="background:#fff8e6;border-color:#f6d38e;color:#8a5a00;margin-bottom:18px;">
        ✓ <strong>Entrega cancelada con éxito:</strong> El comprobante <strong><?= escapar($_GET['folio'] ?? '') ?></strong> fue cancelado y las prendas fueron reintegradas al inventario del almacén correspondiente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['eliminada'])): ?>
    <div class="request-success" style="background:#eaf6ef;border-color:#b9e2cb;color:#1e6a3d;margin-bottom:18px;">
        ✓ <strong>Entrega eliminada:</strong> La entrega <strong><?= escapar($_GET['folio'] ?? '') ?></strong> fue eliminada permanentemente del sistema.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="notice request-error" style="margin-bottom:18px;">
        ⚠ <?= escapar($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="notice">
    <strong>Registro oficial de entregas por almacén:</strong> Cada entrega es realizada y despachada directamente por el almacén correspondiente a su respectivo Servicio Regional, garantizando el control de existencias sin cruces.
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Folio</th>
                <th>Almacén entregador</th>
                <th>Servicio Regional receptor</th>
                <th>Fecha de entrega</th>
                <th>Estado</th>
                <th>Uniformes entregados</th>
                <th style="text-align:right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$entregas): ?>
                <tr>
                    <td colspan="7" class="empty">Sin entregas registradas.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($entregas as $e): ?>
                <?php
                $esCancelado = ($e['estado'] === 'CANCELADO');
                $esEntregado = in_array($e['estado'], ['ENTREGADO', 'RECIBIDO'], true);
                $estadoTexto = $esCancelado ? 'CANCELADO' : ($esEntregado ? 'ENTREGADO' : str_replace('_', ' ', $e['estado']));
                $badgeClase = $esCancelado ? 'status-canceled' : ($esEntregado ? 'status-delivered' : ($e['estado'] === 'PENDIENTE' ? 'status-pending' : ''));
                ?>
                <tr style="<?= $esCancelado ? 'opacity:0.8;background:#fffaf9;' : '' ?>">
                    <td>
                        <strong style="<?= $esCancelado ? 'text-decoration:line-through;color:var(--muted);' : '' ?>"><?= escapar($e['folio']) ?></strong>
                    </td>
                    <td>
                        <strong style="color:var(--wine);"><?= escapar($e['almacen']) ?></strong>
                    </td>
                    <td>
                        <span><?= escapar($e['servicio_regional']) ?></span>
                    </td>
                    <td><?= escapar($e['fecha_salida'] ?? $e['created_at'] ?? '—') ?></td>
                    <td>
                        <span class="status <?= $badgeClase ?>"><?= escapar($estadoTexto) ?></span>
                    </td>
                    <td><strong><?= numero($e['total']) ?></strong></td>
                    <td style="text-align:right;">
                        <div class="delivery-table-actions">
                            <a class="btn-delivery-action btn-view" href="<?= escapar(url('entrega-detalle', ['id' => $e['id']])) ?>" title="Ver comprobante y desglose">👁 Ver</a>
                            
                            <?php if (!$esCancelado): ?>
                                <button type="button" class="btn-delivery-action btn-cancel"
                                    onclick="abrirModalCancelar(<?= (int)$e['id'] ?>, '<?= escapar($e['folio']) ?>', '<?= escapar($e['almacen']) ?>', '<?= escapar($e['servicio_regional']) ?>', <?= (int)$e['total'] ?>)"
                                    title="Cancelar entrega y devolver uniformes al almacén">
                                    🚫 Cancelar
                                </button>
                            <?php endif; ?>

                            <button type="button" class="btn-delivery-action btn-delete"
                                onclick="abrirModalBorrar(<?= (int)$e['id'] ?>, '<?= escapar($e['folio']) ?>', '<?= escapar($e['almacen']) ?>', '<?= escapar($e['servicio_regional']) ?>', <?= (int)$e['total'] ?>, <?= $esCancelado ? 'true' : 'false' ?>)"
                                title="Borrar entrega permanentemente">
                                🗑 Borrar
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Cancelar Entrega -->
<div class="modal-overlay" id="modal-cancelar" hidden>
    <div class="modal-box modal-cancel-theme">
        <div class="modal-header">
            <div>
                <span style="color:#b45309;">Acción Reversible</span>
                <h3 style="color:#92400e;">🚫 Cancelar Entrega Oficial</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="cerrarModal('modal-cancelar')">✕</button>
        </div>
        <form method="post" action="<?= escapar(url('entrega-cancelar')) ?>">
            <input type="hidden" name="id" id="cancelar-entrega-id" value="">
            <input type="hidden" name="origen" value="entregas">
            <div class="modal-body">
                <div class="modal-summary-grid">
                    <div class="modal-summary-item">
                        <span>Folio de entrega</span>
                        <strong id="cancelar-folio">-</strong>
                    </div>
                    <div class="modal-summary-item">
                        <span>Prendas a reintegrar</span>
                        <strong id="cancelar-total" style="color:#b45309;">-</strong>
                    </div>
                    <div class="modal-summary-item full">
                        <span>Almacén / Receptor</span>
                        <p id="cancelar-ruta">-</p>
                    </div>
                </div>

                <div class="modal-notice" style="background:#fffbeb;border-left-color:#d97706;color:#92400e;">
                    <strong>Efectos de la cancelación:</strong>
                    <ul style="margin:6px 0 0;padding-left:18px;">
                        <li>Las <strong id="cancelar-piezas-texto">0</strong> piezas entregadas se <b>reintegrarán inmediatamente al inventario físico</b> del almacén.</li>
                        <li>Se generará un movimiento de entrada por cancelación en la bitácora del almacén.</li>
                        <li>Las solicitudes escolares vinculadas volverán a estado <b>PENDIENTE</b> para poder editarse o despacharse nuevamente.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="cerrarModal('modal-cancelar')">No cancelar</button>
                <button type="submit" class="button button-cancel">Confirmar Cancelación</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Borrar Entrega -->
<div class="modal-overlay" id="modal-borrar" hidden>
    <div class="modal-box modal-delete-theme">
        <div class="modal-header">
            <div>
                <span style="color:#b91c1c;">Acción Permanente</span>
                <h3 style="color:#991b1b;">🗑 Borrar Entrega</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="cerrarModal('modal-borrar')">✕</button>
        </div>
        <form method="post" action="<?= escapar(url('entrega-eliminar')) ?>">
            <input type="hidden" name="id" id="borrar-entrega-id" value="">
            <input type="hidden" name="origen" value="entregas">
            <div class="modal-body">
                <div class="modal-summary-grid">
                    <div class="modal-summary-item">
                        <span>Folio de entrega</span>
                        <strong id="borrar-folio">-</strong>
                    </div>
                    <div class="modal-summary-item">
                        <span>Uniformes</span>
                        <strong id="borrar-total" style="color:#b91c1c;">-</strong>
                    </div>
                    <div class="modal-summary-item full">
                        <span>Almacén / Receptor</span>
                        <p id="borrar-ruta">-</p>
                    </div>
                </div>

                <div class="modal-notice" style="background:#fef2f2;border-left-color:#b91c1c;color:#991b1b;">
                    <strong>¿Está seguro de eliminar este registro?</strong>
                    <p id="borrar-aviso-stock" style="margin:6px 0 0;">
                        Si la entrega no fue cancelada antes, las piezas serán <b>devueltas automáticamente al almacén</b> para proteger la integridad del inventario. El registro del comprobante será eliminado de forma definitiva.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="cerrarModal('modal-borrar')">Cancelar</button>
                <button type="submit" class="button button-danger">Eliminar Definitivamente</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCancelar(id, folio, almacen, servicio, total) {
    document.getElementById('cancelar-entrega-id').value = id;
    document.getElementById('cancelar-folio').textContent = folio;
    document.getElementById('cancelar-total').textContent = total.toLocaleString() + ' piezas';
    document.getElementById('cancelar-piezas-texto').textContent = total.toLocaleString();
    document.getElementById('cancelar-ruta').textContent = almacen + ' → ' + servicio;
    document.getElementById('modal-cancelar').hidden = false;
}

function abrirModalBorrar(id, folio, almacen, servicio, total, esCancelado) {
    document.getElementById('borrar-entrega-id').value = id;
    document.getElementById('borrar-folio').textContent = folio;
    document.getElementById('borrar-total').textContent = total.toLocaleString() + ' piezas';
    document.getElementById('borrar-ruta').textContent = almacen + ' → ' + servicio;
    
    var avisoStock = document.getElementById('borrar-aviso-stock');
    if (esCancelado) {
        avisoStock.innerHTML = 'Esta entrega ya estaba cancelada y su stock ya fue reintegrado. Al borrarla se eliminará permanentemente el registro del historial.';
    } else {
        avisoStock.innerHTML = 'Las ' + total.toLocaleString() + ' piezas serán <b>devueltas automáticamente al inventario físico</b> del almacén y las solicitudes asociadas volverán a estado <b>PENDIENTE</b>. El comprobante será eliminado definitivamente.';
    }
    
    document.getElementById('modal-borrar').hidden = false;
}

function cerrarModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.hidden = true;
}

// Cerrar modales con tecla Escape o clic fuera
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal('modal-cancelar');
        cerrarModal('modal-borrar');
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

<div class="actions" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <a class="button" href="<?= escapar(url('entrega-nueva')) ?>" style="font-size:15px; padding:10px 18px;">+ Registrar Nueva Entrega</a>
    </div>

    <!-- Buscador -->
    <form method="GET" action="<?= escapar(url('entregas')) ?>" style="display:flex; gap:8px; align-items:center; margin:0;">
        <input type="hidden" name="ruta" value="entregas">
        <?php if (!empty($_GET['tipo'])): ?>
            <input type="hidden" name="tipo" value="<?= escapar($_GET['tipo']) ?>">
        <?php endif; ?>
        <input type="text" name="q" value="<?= escapar($_GET['q'] ?? '') ?>" placeholder="Buscar folio, escuela, CCT o receptor..." style="padding:8px 14px; border:1px solid var(--border); border-radius:6px; min-width:260px; font-size:14px;">
        <button type="submit" class="button button-outline" style="padding:8px 14px;">Buscar</button>
        <?php if (!empty($_GET['q'])): ?>
            <a href="<?= escapar(url('entregas') . (!empty($_GET['tipo']) ? '&tipo=' . urlencode($_GET['tipo']) : '')) ?>" class="button button-text" style="font-size:13px; color:var(--muted);">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Notificaciones -->
<?php if (isset($_GET['eliminada'])): ?>
    <div class="request-success" style="background:#eaf6ef; border-color:#b9e2cb; color:#1e6a3d; margin-bottom:18px; padding:12px 16px; border-radius:8px;">
        ✓ <strong>Entrega eliminada:</strong> El registro <strong><?= escapar($_GET['folio'] ?? '') ?></strong> fue eliminado exitosamente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="notice request-error" style="background:#fde8e8; border-color:#f8b4b4; color:#9b1c1c; margin-bottom:18px; padding:12px 16px; border-radius:8px;">
        ⚠ <?= escapar($_GET['error']) ?>
    </div>
<?php endif; ?>

<!-- Pestañas de Filtro Rápido -->
<?php
$filtroActual = $_GET['tipo'] ?? '';
$urlBase = url('entregas') . (!empty($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '');
?>
<div style="display:flex; gap:8px; margin-bottom:16px; border-bottom:2px solid var(--border); padding-bottom:10px;">
    <a href="<?= escapar($urlBase) ?>" 
       style="padding:8px 16px; border-radius:6px; font-weight:600; text-decoration:none; font-size:14px; <?= empty($filtroActual) ? 'background:var(--wine); color:#fff;' : 'background:#f4f5f7; color:var(--text);' ?>">
       Todas las Entregas
    </a>
    <a href="<?= escapar($urlBase . '&tipo=ESCUELA') ?>" 
       style="padding:8px 16px; border-radius:6px; font-weight:600; text-decoration:none; font-size:14px; <?= $filtroActual === 'ESCUELA' ? 'background:var(--wine); color:#fff;' : 'background:#f4f5f7; color:var(--text);' ?>">
       🏫 A Escuelas (Planteles)
    </a>
    <a href="<?= escapar($urlBase . '&tipo=SERVICIO_REGIONAL') ?>" 
       style="padding:8px 16px; border-radius:6px; font-weight:600; text-decoration:none; font-size:14px; <?= $filtroActual === 'SERVICIO_REGIONAL' ? 'background:var(--wine); color:#fff;' : 'background:#f4f5f7; color:var(--text);' ?>">
       🏢 A Servicios Regionales
    </a>
</div>

<!-- Tabla de Entregas -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:140px;">Folio</th>
                <th style="width:130px;">Destino</th>
                <th>Nombre del Destinatario / Ubicación</th>
                <th style="width:110px;">Fecha</th>
                <th>Recibió</th>
                <th style="text-align:center; width:100px;">Uniformes</th>
                <th style="text-align:right; width:160px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entregas)): ?>
                <tr>
                    <td colspan="7" class="empty" style="text-align:center; padding:35px 20px;">
                        <p style="font-size:16px; color:var(--muted); margin-bottom:10px;">No se encontraron entregas registradas.</p>
                        <a href="<?= escapar(url('entrega-nueva')) ?>" class="button button-outline" style="font-size:14px;">+ Registrar Primera Entrega</a>
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($entregas as $e): ?>
                <?php $esEscuela = ($e['tipo_destino'] === 'ESCUELA'); ?>
                <tr>
                    <td>
                        <strong style="color:var(--wine); font-size:14px;"><?= escapar($e['folio']) ?></strong>
                    </td>
                    <td>
                        <?php if ($esEscuela): ?>
                            <span style="display:inline-block; padding:3px 8px; border-radius:12px; font-size:12px; font-weight:600; background:#e8f4fd; color:#1971c2;">
                                🏫 Escuela
                            </span>
                        <?php else: ?>
                            <span style="display:inline-block; padding:3px 8px; border-radius:12px; font-size:12px; font-weight:600; background:#f3f0ff; color:#6741d9;">
                                🏢 Regional
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($esEscuela): ?>
                            <strong><?= escapar($e['escuela_nombre'] ?: 'Escuela sin especificar') ?></strong>
                            <div style="font-size:12px; color:var(--muted);">
                                CCT: <strong><?= escapar($e['escuela_cct'] ?: 'N/D') ?></strong> 
                                <?= !empty($e['escuela_municipio']) ? '• ' . escapar($e['escuela_municipio']) : '' ?>
                            </div>
                        <?php else: ?>
                            <strong><?= escapar($e['servicio_nombre'] ?: 'Coordinación Regional') ?></strong>
                            <div style="font-size:12px; color:var(--muted);">
                                Clave: <?= escapar($e['servicio_clave'] ?: 'N/D') ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap; font-size:13px;">
                        <?= date('d/m/Y', strtotime($e['fecha_entrega'])) ?>
                    </td>
                    <td>
                        <div style="font-weight:600; font-size:13px;"><?= escapar($e['recibido_por_nombre']) ?></div>
                        <?php if (!empty($e['recibido_por_cargo'])): ?>
                            <div style="font-size:11px; color:var(--muted);"><?= escapar($e['recibido_por_cargo']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <span style="display:inline-block; padding:4px 10px; border-radius:20px; font-weight:700; font-size:13px; background:#ebfbee; color:#2b8a3e;">
                            <?= number_format((int)$e['total_piezas']) ?> pzs
                        </span>
                    </td>
                    <td style="text-align:right; white-space:nowrap;">
                        <a class="button button-outline" href="<?= escapar(url('entrega-detalle') . '&id=' . $e['id']) ?>" style="padding:5px 10px; font-size:12px;" title="Ver e imprimir comprobante">
                            📄 Comprobante
                        </a>
                        <form method="POST" action="<?= escapar(url('entrega-eliminar')) ?>" style="display:inline;" onsubmit="return confirm('¿Confirma que desea eliminar la entrega <?= escapar($e['folio']) ?>? Esta acción no se puede deshacer.');">
                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                            <button type="submit" class="button" style="padding:5px 9px; font-size:12px; background:#fff0f0; color:#c92a2a; border:1px solid #ffc9c9;" title="Eliminar registro">
                                🗑️
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

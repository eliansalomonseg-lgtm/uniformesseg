<?php
$totalMovs = count($movimientos);
$totalPiezas = array_sum(array_column($movimientos, 'cantidad'));
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
    <div>
        <span style="font-size:11px;font-weight:800;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;">Auditoría y Trazabilidad</span>
        <h2 style="margin:2px 0;font-size:26px;">Kardex de Movimientos de Almacén</h2>
    </div>
    <div class="warehouse-action-buttons">
        <a class="button button-action-in" href="<?= escapar(url('inventario-entrada')) ?>">➕ Registrar Entrada</a>
        <a class="button button-action-transfer" href="<?= escapar(url('inventario-traspaso')) ?>">⇄ Traspasar Stock</a>
        <a class="button button-action-adjust" href="<?= escapar(url('inventario-ajuste')) ?>">⚖️ Ajuste / Merma</a>
    </div>
</div>

<!-- Barra de Filtros del Kardex -->
<div class="summary" style="padding:16px 20px;margin-bottom:20px;">
    <form class="filters" method="get" style="border:0;box-shadow:none;padding:0;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;">
        <input type="hidden" name="ruta" value="movimientos">

        <label style="min-width:220px;">
            Almacén
            <select name="almacen_id" onchange="this.form.submit()">
                <option value="0">Todos los almacenes</option>
                <?php foreach ($almacenes as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $almacenId === (int)$a['id'] ? 'selected' : '' ?>>
                        [<?= escapar($a['clave']) ?>] <?= escapar($a['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label style="min-width:180px;">
            Tipo de Movimiento
            <select name="tipo" onchange="this.form.submit()">
                <option value="0">Todos los tipos</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= escapar($t) ?>" <?= $tipo === $t ? 'selected' : '' ?>>
                        <?= escapar(str_replace('_', ' ', $t)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label style="min-width:140px;">
            Desde
            <input type="date" name="fecha_desde" value="<?= escapar($fechaDesde) ?>">
        </label>

        <label style="min-width:140px;">
            Hasta
            <input type="date" name="fecha_hasta" value="<?= escapar($fechaHasta) ?>">
        </label>

        <div style="display:flex;gap:8px;">
            <button class="button" type="submit">Filtrar</button>
            <?php if ($almacenId > 0 || $tipo !== '' || $fechaDesde !== '' || $fechaHasta !== ''): ?>
                <a class="button secondary" href="<?= escapar(url('movimientos')) ?>">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabla de Movimientos -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Almacén</th>
                <th>Tipo</th>
                <th>Género</th>
                <th>Talla</th>
                <th>Cantidad</th>
                <th>Referencia / Folio</th>
                <th>Observaciones / Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$movimientos): ?>
                <tr>
                    <td colspan="8" class="empty">No se encontraron movimientos registrados con los filtros seleccionados.</td>
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

                // Enlace inteligente al comprobante según tipo de referencia
                $enlaceComprobante = null;
                $textoRef = trim(($m['referencia_tipo'] ?? '') . ' #' . ($m['referencia_id'] ?? ''));
                if (!empty($m['referencia_id'])) {
                    $refId = (int)$m['referencia_id'];
                    $refTipo = (string)($m['referencia_tipo'] ?? '');
                    if (str_contains($refTipo, 'ENTREGA')) {
                        $enlaceComprobante = url('entrega-detalle', ['id' => $refId]);
                    } elseif (str_contains($refTipo, 'ENTRADA_PROVEEDOR')) {
                        $enlaceComprobante = url('comprobante-entrada', ['id' => $refId]);
                    } elseif (str_contains($refTipo, 'TRASPASO')) {
                        $enlaceComprobante = url('comprobante-traspaso', ['id' => $refId]);
                    } elseif (str_contains($refTipo, 'AJUSTE')) {
                        $enlaceComprobante = url('comprobante-ajuste', ['id' => $refId]);
                    }
                }
                ?>
                <tr>
                    <td><small style="color:var(--muted);font-weight:600;"><?= escapar($m['fecha_movimiento']) ?></small></td>
                    <td><strong><?= escapar($m['almacen']) ?></strong></td>
                    <td><span class="status <?= $tipoClass ?>"><?= escapar(str_replace('_', ' ', $m['tipo'])) ?></span></td>
                    <td class="<?= $m['sexo'] === 'NINA' ? 'girl-cell' : 'boy-cell' ?>"><?= escapar($m['sexo'] === 'NINO' ? 'Niño' : 'Niña') ?></td>
                    <td><strong>Talla <?= escapar($m['talla']) ?></strong></td>
                    <td><strong style="font-size:15px;"><?= numero($m['cantidad']) ?></strong></td>
                    <td>
                        <?php if ($enlaceComprobante): ?>
                            <a href="<?= escapar($enlaceComprobante) ?>" class="request-detail-link" title="Ver comprobante oficial">
                                <?= escapar($textoRef) ?> ↗
                            </a>
                        <?php else: ?>
                            <span style="font-size:11px;color:var(--muted);"><?= escapar($textoRef ?: '—') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><small style="color:var(--text);"><?= escapar($m['observaciones'] ?? '—') ?></small></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if ($movimientos): ?>
            <tfoot>
                <tr style="background:#f8f4ef;font-size:14px;">
                    <td colspan="5"><strong>TOTAL EN PANTALLA (<?= numero($totalMovs) ?> movimientos)</strong></td>
                    <td colspan="3"><strong style="color:var(--wine);font-size:16px;"><?= numero($totalPiezas) ?> prendas registradas</strong></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php
$totalUniformes = (int)($metricas['total_uniformes'] ?? 0);
$totalEntregas = (int)($metricas['total_entregas'] ?? 0);
$uniformesEscuelas = (int)($metricas['uniformes_escuelas'] ?? 0);
$uniformesRegionales = (int)($metricas['uniformes_regionales'] ?? 0);
$entregasEscuelas = (int)($metricas['entregas_escuelas'] ?? 0);
$entregasRegionales = (int)($metricas['entregas_regionales'] ?? 0);
$ultimas = $metricas['ultimas_entregas'] ?? [];
?>

<!-- Hero Banner Principal -->
<section class="dashboard-hero" style="background:linear-gradient(135deg, #4a1525 0%, #6e1a33 100%); color:#fff; border-radius:12px; padding:28px 32px; margin-bottom:28px; box-shadow:0 4px 12px rgba(74,21,37,0.15); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
    <div>
        <span style="font-size:13px; text-transform:uppercase; letter-spacing:1px; opacity:0.85; display:block; margin-bottom:6px;">
            Programa de Dotación de Uniformes Escolares SEG
        </span>
        <h3 style="font-size:28px; font-weight:800; margin:0 0 8px 0; color:#fff;">
            <?= number_format($totalUniformes) ?> uniformes entregados
        </h3>
        <p style="margin:0; font-size:15px; opacity:0.9;">
            Registrados en <?= number_format($totalEntregas) ?> entregas oficiales directas a planteles y coordinaciones.
        </p>
    </div>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <a class="button" href="<?= escapar(url('entrega-nueva')) ?>" style="background:#fff; color:#4a1525; font-weight:700; font-size:15px; padding:12px 22px; border:none; box-shadow:0 2px 5px rgba(0,0,0,0.2);">
            + Registrar Nueva Entrega
        </a>
        <a class="button button-outline" href="<?= escapar(url('entregas')) ?>" style="border-color:rgba(255,255,255,0.7); color:#fff; font-size:15px; padding:12px 20px;">
            Ver Todas las Entregas
        </a>
    </div>
</section>

<!-- Métricas Clave -->
<section class="executive-metrics" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:30px;">
    
    <!-- Tarjeta: Total Entregas -->
    <article class="executive-card" style="background:#fff; border:1px solid var(--border); border-radius:10px; padding:20px; display:flex; align-items:center; gap:16px; box-shadow:0 2px 4px rgba(0,0,0,0.03);">
        <div style="width:50px; height:50px; border-radius:12px; background:#fdf2f4; color:#a61e4d; font-size:22px; display:flex; align-items:center; justify-content:center; font-weight:bold;">
            📦
        </div>
        <div>
            <span style="font-size:13px; color:var(--muted); font-weight:600; text-transform:uppercase;">Total Entregas</span>
            <strong style="display:block; font-size:24px; font-weight:800; color:#212529;"><?= number_format($totalEntregas) ?></strong>
            <small style="color:var(--muted); font-size:12px;">Comprobantes emitidos</small>
        </div>
    </article>

    <!-- Tarjeta: A Escuelas -->
    <article class="executive-card" style="background:#fff; border:1px solid var(--border); border-radius:10px; padding:20px; display:flex; align-items:center; gap:16px; box-shadow:0 2px 4px rgba(0,0,0,0.03);">
        <div style="width:50px; height:50px; border-radius:12px; background:#e8f4fd; color:#1971c2; font-size:22px; display:flex; align-items:center; justify-content:center; font-weight:bold;">
            🏫
        </div>
        <div>
            <span style="font-size:13px; color:var(--muted); font-weight:600; text-transform:uppercase;">A Escuelas</span>
            <strong style="display:block; font-size:24px; font-weight:800; color:#1971c2;"><?= number_format($uniformesEscuelas) ?> pzs</strong>
            <small style="color:var(--muted); font-size:12px;"><?= number_format($entregasEscuelas) ?> planteles atendidos</small>
        </div>
    </article>

    <!-- Tarjeta: A Servicios Regionales -->
    <article class="executive-card" style="background:#fff; border:1px solid var(--border); border-radius:10px; padding:20px; display:flex; align-items:center; gap:16px; box-shadow:0 2px 4px rgba(0,0,0,0.03);">
        <div style="width:50px; height:50px; border-radius:12px; background:#f3f0ff; color:#6741d9; font-size:22px; display:flex; align-items:center; justify-content:center; font-weight:bold;">
            🏢
        </div>
        <div>
            <span style="font-size:13px; color:var(--muted); font-weight:600; text-transform:uppercase;">A Coordinaciones</span>
            <strong style="display:block; font-size:24px; font-weight:800; color:#6741d9;"><?= number_format($uniformesRegionales) ?> pzs</strong>
            <small style="color:var(--muted); font-size:12px;"><?= number_format($entregasRegionales) ?> entregas regionales</small>
        </div>
    </article>

    <!-- Tarjeta: Promedio -->
    <article class="executive-card" style="background:#fff; border:1px solid var(--border); border-radius:10px; padding:20px; display:flex; align-items:center; gap:16px; box-shadow:0 2px 4px rgba(0,0,0,0.03);">
        <div style="width:50px; height:50px; border-radius:12px; background:#ebfbee; color:#2b8a3e; font-size:22px; display:flex; align-items:center; justify-content:center; font-weight:bold;">
            📊
        </div>
        <div>
            <span style="font-size:13px; color:var(--muted); font-weight:600; text-transform:uppercase;">Promedio x Entrega</span>
            <strong style="display:block; font-size:24px; font-weight:800; color:#2b8a3e;">
                <?= $totalEntregas > 0 ? number_format(round($totalUniformes / $totalEntregas)) : 0 ?> pzs
            </strong>
            <small style="color:var(--muted); font-size:12px;">Por cada registro</small>
        </div>
    </article>
</section>

<!-- Últimas Entregas Registradas -->
<div class="card" style="background:#fff; border:1px solid var(--border); border-radius:10px; padding:24px; box-shadow:0 2px 4px rgba(0,0,0,0.03);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:2px solid var(--border); padding-bottom:10px;">
        <h3 style="margin:0; font-size:18px; color:var(--wine); font-weight:700;">
            Últimas Entregas Realizadas
        </h3>
        <a href="<?= escapar(url('entregas')) ?>" style="font-size:14px; font-weight:600; color:var(--wine); text-decoration:none;">
            Ver historial completo →
        </a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Destino</th>
                    <th>Nombre del Destinatario</th>
                    <th>Fecha</th>
                    <th>Recibido por</th>
                    <th style="text-align:center;">Prendas</th>
                    <th style="text-align:right;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimas)): ?>
                    <tr>
                        <td colspan="7" class="empty" style="text-align:center; padding:30px;">
                            Sin entregas registradas aún.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($ultimas as $u): ?>
                    <?php $esEscuela = ($u['tipo_destino'] === 'ESCUELA'); ?>
                    <tr>
                        <td>
                            <strong style="color:var(--wine);"><?= escapar($u['folio']) ?></strong>
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
                            <strong><?= escapar($esEscuela ? ($u['escuela_nombre'] ?: 'Escuela') : ($u['servicio_nombre'] ?: 'Coordinación Regional')) ?></strong>
                            <?php if ($esEscuela && !empty($u['escuela_cct'])): ?>
                                <div style="font-size:11px; color:var(--muted);">CCT: <?= escapar($u['escuela_cct']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px;">
                            <?= date('d/m/Y', strtotime($u['fecha_entrega'])) ?>
                        </td>
                        <td>
                            <span style="font-size:13px; font-weight:600;"><?= escapar($u['recibido_por_nombre']) ?></span>
                        </td>
                        <td style="text-align:center;">
                            <span style="display:inline-block; padding:3px 8px; border-radius:12px; font-weight:700; font-size:12px; background:#ebfbee; color:#2b8a3e;">
                                <?= number_format((int)$u['total_piezas']) ?> pzs
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <a class="button button-outline" href="<?= escapar(url('entrega-detalle') . '&id=' . $u['id']) ?>" style="padding:4px 9px; font-size:12px;">
                                📄 Comprobante
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

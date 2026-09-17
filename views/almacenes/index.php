<?php
$totalFisico = array_sum(array_column($almacenes, 'total'));
$totalNina = array_sum(array_column($almacenes, 'nina'));
$totalNino = array_sum(array_column($almacenes, 'nino'));
$totalDisponible = array_sum(array_column($almacenes, 'disponible'));
$maximoAlmacen = max(array_map(fn($almacen) => (int)$almacen['total'], $almacenes) ?: [1]);
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
    <div>
        <span style="font-size:11px;font-weight:800;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;">Administración de Inventarios</span>
        <h2 style="margin:2px 0;font-size:26px;">Gestión de Almacenes Regionales</h2>
    </div>
    <div class="warehouse-action-buttons">
        <a class="button button-action-in" href="<?= escapar(url('inventario-entrada')) ?>" title="Registrar recepción de uniformes de proveedor">
            ➕ Entrada de Stock
        </a>
        <a class="button button-action-transfer" href="<?= escapar(url('inventario-traspaso')) ?>" title="Traspasar uniformes de una sede a otra">
            ⇄ Traspasar entre Almacenes
        </a>
        <a class="button button-action-adjust" href="<?= escapar(url('inventario-ajuste')) ?>" title="Registrar merma o regularización por conteo">
            ⚖️ Ajuste / Merma
        </a>
    </div>
</div>

<section class="warehouse-intro">
    <div>
        <span>Inventario consolidado</span>
        <h3><?= numero($totalFisico) ?> uniformes en <?= numero(count($almacenes)) ?> almacenes activos</h3>
        <p>Control de existencias físicas, balanceo regional, apartados por solicitudes y stock disponible.</p>
    </div>
    <div class="warehouse-intro-stats">
        <div>
            <strong><?= numero($totalNina) ?></strong>
            <span>Niña</span>
        </div>
        <div>
            <strong><?= numero($totalNino) ?></strong>
            <span>Niño</span>
        </div>
        <div>
            <strong><?= numero($totalDisponible) ?></strong>
            <span>Disponible</span>
        </div>
    </div>
</section>

<section class="warehouse-directory">
    <?php if (!$almacenes): ?>
        <div class="empty">Sin almacenes registrados en el sistema.</div>
    <?php endif; ?>

    <?php foreach ($almacenes as $a): ?>
        <?php
        $porcentajeNina = (int)$a['total'] > 0 ? (int)$a['nina'] * 100 / (int)$a['total'] : 0;
        $porcentajeTotal = $maximoAlmacen > 0 ? (int)$a['total'] * 100 / $maximoAlmacen : 0;
        ?>
        <article class="warehouse-card">
            <div class="warehouse-card-top">
                <div class="warehouse-pin">⌂</div>
                <div>
                    <span class="warehouse-code"><?= escapar($a['clave'] ?? 'ALM') ?></span>
                    <h3><?= escapar(str_replace('ALMACEN ', '', $a['nombre'])) ?></h3>
                </div>
                <span class="stock-state">Operando</span>
            </div>

            <div class="warehouse-total">
                <span>Existencia física total</span>
                <strong><?= numero($a['total']) ?></strong>
                <small><?= numero($a['disponible']) ?> disponibles para entrega</small>
            </div>

            <div class="warehouse-gender">
                <div class="gender-value girl-value">
                    <span>♀ Niña</span>
                    <strong><?= numero($a['nina']) ?></strong>
                </div>
                <div class="gender-value boy-value">
                    <span>♂ Niño</span>
                    <strong><?= numero($a['nino']) ?></strong>
                </div>
            </div>

            <div class="warehouse-composition">
                <div>
                    <span>Composición por género</span>
                    <strong><?= escapar(round($porcentajeNina, 1)) ?>% Niña</strong>
                </div>
                <div class="composition-track">
                    <span class="composition-girl" style="width:<?= escapar(round($porcentajeNina, 2)) ?>%"></span>
                </div>
            </div>

            <div class="warehouse-capacity">
                <span>Volumen relativo frente a la red de almacenes</span>
                <div>
                    <i style="width:<?= escapar(round($porcentajeTotal, 2)) ?>%"></i>
                </div>
            </div>

            <div class="warehouse-card-footer">
                <span><?= numero($a['apartado']) ?> apartados</span>
                <div style="display:flex;gap:6px;">
                    <a class="button" style="padding:7px 11px;font-size:11px;" href="<?= escapar(url('almacen-detalle', ['id' => $a['id']])) ?>">
                        Ver detalle <b>→</b>
                    </a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</section>

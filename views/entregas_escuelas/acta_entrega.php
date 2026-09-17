<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:12px;">
    <a class="back-link" style="margin-bottom:0;" href="<?= escapar(url('solicitud-detalle', ['id' => $entrega['solicitud_id']])) ?>">← Volver a la solicitud</a>
    
    <div style="display:flex;gap:10px;align-items:center;">
        <button type="button" class="button" onclick="window.print()" style="background:var(--coal);display:inline-flex;align-items:center;gap:6px;">
            🖨️ Imprimir Acta Oficial
        </button>

        <?php if (!empty($entrega['archivo_acuse'])): ?>
            <a class="button" style="background:#1d663b;display:inline-flex;align-items:center;gap:6px;" href="<?= escapar($entrega['archivo_acuse']) ?>" target="_blank">
                📎 Ver Acuse Firmado y Sellado
            </a>
        <?php else: ?>
            <a class="button" style="background:#b45309;display:inline-flex;align-items:center;gap:6px;" href="<?= escapar(url('subir-acuse-escuela', ['id' => $entrega['id']])) ?>">
                📤 Subir Acuse Firmado
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['exito'])): ?>
    <div class="request-success" style="margin-bottom:18px;">
        ✓ <strong>Acta de entrega emitida exitosamente.</strong> Se ha generado el folio oficial <b><?= escapar($entrega['folio']) ?></b> con Código QR de verificación institucional.
    </div>
<?php endif; ?>

<?php if (isset($_GET['acuse_ok'])): ?>
    <div class="request-success" style="background:#eaf6ef;border-color:#b9e2cb;color:#1e6a3d;margin-bottom:18px;">
        ✓ <strong>Acuse digital guardado correctamente.</strong> El documento escaneado/fotografía ha quedado resguardado en el expediente oficial.
    </div>
<?php endif; ?>

<section class="summary print-section" style="padding:28px;">
    <!-- Encabezado Institucional -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px;border-bottom:2px solid #ead8b9;padding-bottom:18px;">
        <div>
            <span style="font-size:11px;font-weight:800;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;">Gobierno del Estado de Guerrero · Secretaría de Educación</span>
            <h2 style="margin:4px 0;color:var(--wine);font-size:26px;">
                Acta Oficial de Entrega-Recepción de Uniformes Escolares
            </h2>
            <p style="margin:4px 0;font-size:14px;color:var(--muted);">
                Constancia de dotación de prendas escolares gratuitas a plantel educativo
            </p>
        </div>
        <div style="text-align:right;">
            <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;">Folio de Acta</span>
            <div style="font-size:23px;font-weight:800;color:var(--wine);letter-spacing:.04em;"><?= escapar($entrega['folio']) ?></div>
            <small style="color:var(--muted);">Fecha: <b><?= escapar($entrega['fecha_entrega']) ?></b></small>
        </div>
    </div>

    <!-- Bloque de Plantel y QR -->
    <div style="display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;margin:20px 0;padding:18px;background:#fcfaf7;border:1px solid #eee5dc;border-radius:9px;">
        <div>
            <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
                <span class="pill-cct" style="background:var(--wine);font-size:12px;padding:4px 10px;"><?= escapar($entrega['cct']) ?></span>
                <span style="font-size:12px;color:var(--muted);font-weight:700;"><?= escapar($entrega['escuela_nivel'] ?? '') ?></span>
            </div>
            <h3 style="margin:0 0 6px;font-size:20px;color:var(--text);"><?= escapar($entrega['escuela_nombre']) ?></h3>
            <p style="margin:0;font-size:13px;color:var(--muted);">
                Municipio: <strong><?= escapar($entrega['escuela_municipio']) ?></strong> · Localidad: <strong><?= escapar($entrega['escuela_localidad']) ?></strong>
            </p>
            <p style="margin:4px 0 0;font-size:12px;color:var(--muted);">
                Región Educativa: <strong><?= escapar($entrega['servicio_regional_nombre']) ?></strong> [<?= escapar($entrega['servicio_regional_clave']) ?>]
            </p>
            <div style="margin-top:10px;display:flex;gap:16px;font-size:12px;">
                <span>Solicitud: <b><?= escapar($entrega['solicitud_folio']) ?></b></span>
                <?php if ($entrega['entrega_regional_folio']): ?>
                    <span>Lote Regional: <b><?= escapar($entrega['entrega_regional_folio']) ?></b></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Código QR Verificador -->
        <div style="display:flex;flex-direction:column;align-items:center;text-align:center;padding:10px 14px;background:#fff;border:1px solid #e7ddd2;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <div style="display:grid;place-items:center;">
                <?= $qrSvg ?>
            </div>
            <span style="font-size:9px;color:var(--muted);font-weight:700;margin-top:4px;text-transform:uppercase;letter-spacing:.04em;">Verificación Digital SEG</span>
            <small style="font-size:8px;color:#855e25;max-width:140px;line-height:1.2;margin-top:2px;">Escanee para certificar validez oficial en tiempo real</small>
        </div>
    </div>

    <!-- Datos de Entrega y Recepción -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:20px;padding:14px;background:#fff;border-left:4px solid var(--gold);border-radius:6px;">
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Entrega (Servicios Regionales)</span>
            <strong style="font-size:13px;"><?= escapar($entrega['entregado_por_nombre']) ?></strong>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Recibe en el Plantel</span>
            <strong style="font-size:13px;color:var(--wine);"><?= escapar($entrega['recibido_por_nombre']) ?></strong>
            <small style="display:block;color:var(--muted);"><?= escapar($entrega['recibido_por_cargo']) ?></small>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Identificación Oficial</span>
            <strong style="font-size:13px;"><?= escapar($entrega['recibido_por_identificacion'] ?: 'No registrada') ?></strong>
        </div>
        <div>
            <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Teléfono de Contacto</span>
            <strong style="font-size:13px;"><?= escapar($entrega['recibido_por_telefono'] ?: 'No registrado') ?></strong>
        </div>
    </div>

    <?php if ($entrega['observaciones']): ?>
        <div style="margin-bottom:20px;padding:12px 16px;background:#f9f6f2;border-radius:6px;font-size:13px;">
            <strong>Observaciones de la Entrega:</strong> <?= escapar($entrega['observaciones']) ?>
        </div>
    <?php endif; ?>

    <!-- Tabla de Prendas Entregadas -->
    <h3 style="margin:20px 0 10px;font-size:17px;">Desglose de Uniformes Entregados</h3>
    <div class="table-wrap" style="margin-bottom:24px;">
        <table>
            <thead>
                <tr>
                    <th>Talla</th>
                    <th style="text-align:center;color:#ffd0e4;">♀ Uniformes Niña</th>
                    <th style="text-align:center;color:#cce7ff;">♂ Uniformes Niño</th>
                    <th style="text-align:right;">Subtotal Talla</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $tallasAgrupadas = [];
                foreach ($entrega['detalle'] as $d) {
                    $tallasAgrupadas[$d['talla']]['talla'] = $d['talla'];
                    $tallasAgrupadas[$d['talla']][$d['sexo']] = (int)$d['cantidad_entregada'];
                }
                ?>
                <?php foreach ($tallasAgrupadas as $talla => $datos): ?>
                    <?php
                    $cantNina = $datos['NINA'] ?? 0;
                    $cantNino = $datos['NINO'] ?? 0;
                    $sub = $cantNina + $cantNino;
                    ?>
                    <tr>
                        <td><strong>Talla <?= escapar($talla) ?></strong></td>
                        <td style="text-align:center;font-weight:700;color:#bd3f78;"><?= numero($cantNina) ?></td>
                        <td style="text-align:center;font-weight:700;color:#2676bd;"><?= numero($cantNino) ?></td>
                        <td style="text-align:right;font-weight:800;"><?= numero($sub) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-size:16px;background:#f8f4ef;">
                    <td><strong>TOTAL ENTREGADO A LA ESCUELA</strong></td>
                    <td style="text-align:center;color:#bd3f78;"><strong><?= numero($entrega['total_nina']) ?></strong></td>
                    <td style="text-align:center;color:#2676bd;"><strong><?= numero($entrega['total_nino']) ?></strong></td>
                    <td style="text-align:right;color:var(--wine);"><strong><?= numero($entrega['total_piezas']) ?> uniformes</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Sección de Firmas y Sello Oficial -->
    <div style="margin-top:40px;padding-top:20px;border-top:1px solid #e2d9cf;">
        <div style="display:grid;grid-template-columns:1fr 1fr 180px;gap:25px;align-items:end;text-align:center;">
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:12px;"><?= escapar($entrega['entregado_por_nombre']) ?></strong>
                <small style="color:var(--muted);">Entregó (Servicios Regionales SEG)</small>
            </div>
            <div>
                <div style="border-bottom:1px solid #777;height:65px;margin-bottom:8px;"></div>
                <strong style="display:block;font-size:12px;"><?= escapar($entrega['recibido_por_nombre']) ?></strong>
                <small style="color:var(--muted);">Recibió de Conformidad (<?= escapar($entrega['recibido_por_cargo']) ?>)</small>
            </div>
            <div style="border:2px dashed #bba998;border-radius:8px;height:105px;display:grid;place-items:center;color:var(--muted);font-size:10px;font-weight:700;text-transform:uppercase;padding:8px;">
                Espacio para Sello Oficial del CCT
            </div>
        </div>
    </div>
</section>

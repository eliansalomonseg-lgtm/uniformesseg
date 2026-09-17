<section class="verification-wrapper" style="max-width:800px;margin:30px auto;">
    <?php if ($esValido && $documento): ?>
        <!-- Estado Válido -->
        <div class="verification-badge-valid" style="display:flex;align-items:center;gap:16px;padding:22px 26px;border-radius:12px;background:#eaf6ef;border:2px solid #86efac;color:#166534;margin-bottom:24px;box-shadow:0 6px 20px rgba(22,101,52,0.08);">
            <div style="font-size:36px;line-height:1;">✓</div>
            <div>
                <span style="font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#15803d;">Certificación Institucional SEG</span>
                <h2 style="margin:2px 0 0;font-size:22px;color:#14532d;">DOCUMENTO OFICIAL AUTÉNTICO Y VÁLIDO</h2>
                <small style="font-size:12px;color:#166534;">Registrado en el Sistema Integral de Control de Uniformes Escolares de Guerrero.</small>
            </div>
        </div>

        <article class="chart-panel" style="padding:28px;">
            <div class="panel-heading" style="border-bottom:1px solid #eee5dc;padding-bottom:16px;margin-bottom:20px;">
                <div>
                    <span style="color:var(--gold);">Folio Certificado</span>
                    <h3 style="font-size:24px;color:var(--wine);"><?= escapar($documento['folio']) ?></h3>
                </div>
                <span class="status status-delivered" style="font-size:12px;padding:6px 12px;">
                    <?= $documento['tipo_documento'] === 'ENTREGA_ESCUELA' ? 'ENTREGA ESCOLAR' : 'ENTREGA REGIONAL' ?>
                </span>
            </div>

            <!-- Datos Oficiales -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px;padding:18px;background:#fcfaf7;border:1px solid #eee5dc;border-radius:9px;">
                <?php if ($documento['tipo_documento'] === 'ENTREGA_ESCUELA'): ?>
                    <div>
                        <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Plantel Educativo</span>
                        <strong style="font-size:15px;color:var(--text);"><?= escapar($documento['escuela_nombre']) ?></strong>
                        <small style="display:block;color:#855e25;font-weight:700;">CCT: <?= escapar($documento['cct']) ?> (<?= escapar($documento['escuela_nivel']) ?>)</small>
                    </div>
                    <div>
                        <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Ubicación Geográfica</span>
                        <strong style="font-size:14px;color:var(--text);"><?= escapar($documento['municipio']) ?></strong>
                        <small style="display:block;color:var(--muted);"><?= escapar($documento['localidad']) ?></small>
                    </div>
                    <div>
                        <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Recibió en la Escuela</span>
                        <strong style="font-size:14px;color:var(--wine);"><?= escapar($documento['recibido_por_nombre']) ?></strong>
                        <small style="display:block;color:var(--muted);"><?= escapar($documento['recibido_por_cargo']) ?></small>
                    </div>
                    <div>
                        <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Fecha Oficial de Recepción</span>
                        <strong style="font-size:14px;color:var(--text);"><?= escapar($documento['fecha_oficial']) ?></strong>
                    </div>
                <?php else: ?>
                    <div>
                        <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Almacén Emisor</span>
                        <strong style="font-size:15px;color:var(--text);"><?= escapar($documento['almacen_origen']) ?></strong>
                    </div>
                    <div>
                        <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Servicio Regional Receptor</span>
                        <strong style="font-size:15px;color:var(--wine);"><?= escapar($documento['servicio_regional']) ?></strong>
                    </div>
                    <div>
                        <span style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:800;">Fecha Oficial de Despacho</span>
                        <strong style="font-size:14px;color:var(--text);"><?= escapar($documento['fecha_oficial']) ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Desglose Certificado de Uniformes -->
            <h4 style="margin:0 0 12px;font-size:16px;">Dotación Certificada de Prendas</h4>
            <div class="table-wrap" style="margin-bottom:20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Talla</th>
                            <th style="text-align:right;">Cantidad Verificada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documento['detalle'] as $det): ?>
                            <tr>
                                <td><strong>Talla <?= escapar($det['talla']) ?></strong> (<?= ($det['sexo'] ?? '') === 'NINA' ? '♀ Niña' : '♂ Niño' ?>)</td>
                                <td style="text-align:right;font-weight:700;"><?= numero($det['cantidad_entregada']) ?> pzas</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8f4ef;font-size:16px;">
                            <td><strong>TOTAL GENERAL VALIDADO</strong></td>
                            <td style="text-align:right;color:var(--wine);"><strong><?= numero($documento['total_piezas']) ?> uniformes</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <?php if (!empty($documento['archivo_acuse'])): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:#f3f7f4;border:1px solid #c9e2d1;border-radius:8px;margin-top:16px;">
                    <div>
                        <strong style="color:#1d663b;font-size:14px;display:block;">Acuse Digital Firmado y Sellado Disponible</strong>
                        <small style="color:var(--muted);">El documento físico con sello del CCT se encuentra resguardado en el expediente digital.</small>
                    </div>
                    <a class="button" style="background:#1d663b;" href="<?= escapar($documento['archivo_acuse']) ?>" target="_blank">
                        📎 Consultar Acuse ↗
                    </a>
                </div>
            <?php endif; ?>

            <div style="margin-top:24px;text-align:center;color:var(--muted);font-size:11px;border-top:1px solid #eee5dc;padding-top:14px;">
                Código criptográfico único: <code><?= escapar($codigo) ?></code> · Consultado el <?= date('d/m/Y H:i:s') ?>
            </div>
        </article>
    <?php else: ?>
        <!-- Estado No Válido -->
        <div style="padding:32px;border-radius:12px;background:#fef2f2;border:2px solid #f87171;color:#991b1b;text-align:center;box-shadow:0 6px 20px rgba(185,28,28,0.08);">
            <div style="font-size:48px;margin-bottom:12px;">⚠️</div>
            <span style="font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#b91c1c;">Alerta de Validación</span>
            <h2 style="margin:6px 0 10px;font-size:24px;color:#7f1d1d;">DOCUMENTO NO VÁLIDO O INEXISTENTE</h2>
            <p style="margin:0 auto 18px;max-width:520px;font-size:14px;color:#991b1b;line-height:1.5;">
                El código escaneado <b><?= escapar($codigo ?: 'VACÍO') ?></b> no coincide con ningún registro oficial emitido por la Secretaría de Educación Guerrero o el documento ha sido revocado.
            </p>
            <a class="button secondary" href="<?= escapar(url('inicio')) ?>">Ir al inicio del sistema</a>
        </div>
    <?php endif; ?>
</section>

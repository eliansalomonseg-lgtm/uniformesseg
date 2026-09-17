<a class="back-link" href="<?= escapar(url('acta-escuela', ['id' => $entrega['id']])) ?>">← Volver al acta oficial</a>

<section class="warehouse-detail-hero" style="background:linear-gradient(120deg,#1e4263,#2d5f8c);margin-bottom:20px;">
    <div>
        <span style="color:#d5e7f7;">EXPEDIENTE DIGITAL INSTITUCIONAL</span>
        <h3>📤 Adjuntar Acuse Escaneado / Fotografía de Entrega</h3>
        <p>Resguardo digital del acta física firmada por el director y con el sello oficial del plantel.</p>
    </div>
    <div class="warehouse-detail-total" style="border-left-color:rgba(255,255,255,0.25);">
        <span>Folio de Acta</span>
        <strong><?= escapar($entrega['folio']) ?></strong>
        <small><?= escapar($entrega['cct']) ?></small>
    </div>
</section>

<?php if ($error): ?>
    <div class="notice request-error" style="margin-bottom:18px;">
        ⚠️ <strong>Error al procesar archivo:</strong> <?= escapar($error) ?>
    </div>
<?php endif; ?>

<article class="chart-panel" style="max-width:720px;margin:0 auto;padding:26px;">
    <div class="panel-heading" style="margin-bottom:18px;">
        <div>
            <span>Plantel: <?= escapar($entrega['escuela_nombre']) ?></span>
            <h3>Subida de Documento Probatorio</h3>
        </div>
    </div>

    <?php if (!empty($entrega['archivo_acuse'])): ?>
        <div class="notice" style="background:#eaf5ef;border-left-color:#38825d;margin-bottom:20px;">
            ✓ <strong>Ya existe un acuse resguardado:</strong> 
            <a href="<?= escapar($entrega['archivo_acuse']) ?>" target="_blank" style="color:#1d663b;font-weight:700;margin-left:6px;">
                Ver acuse actual ↗
            </a>. Si sube un nuevo archivo, se actualizará el documento.
        </div>
    <?php endif; ?>

    <form method="post" action="<?= escapar(url('subir-acuse-escuela', ['id' => $entrega['id']])) ?>" enctype="multipart/form-data">
        <div class="request-fields">
            <div class="full-field" style="display:grid;gap:8px;">
                <label style="font-weight:700;font-size:13px;">
                    Seleccione el archivo escaneado o foto legible (PDF, JPG o PNG máx. 5 MB) *
                </label>
                <input type="file" name="archivo_acuse" accept=".pdf,.jpg,.jpeg,.png" required style="padding:14px;border:2px dashed #cfc7c2;border-radius:8px;background:#fcfaf7;">
                <small style="color:var(--muted);">Asegúrese de que sean legibles las firmas de entrega/recepción y el sello oficial de la escuela.</small>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px;">
            <a class="button secondary" href="<?= escapar(url('acta-escuela', ['id' => $entrega['id']])) ?>">Cancelar</a>
            <button type="submit" class="button" style="background:#246e45;">
                ✓ Subir y Resguardar Acuse
            </button>
        </div>
    </form>
</article>

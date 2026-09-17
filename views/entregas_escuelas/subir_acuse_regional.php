<a class="back-link" href="<?= escapar(url('entrega-detalle', ['id' => $entrega['id']])) ?>">← Volver a la entrega regional</a>

<section class="warehouse-detail-hero" style="background:linear-gradient(120deg,#1e4263,#2d5f8c);margin-bottom:20px;">
    <div>
        <span style="color:#d5e7f7;">EXPEDIENTE DIGITAL REGIONAL</span>
        <h3>📤 Adjuntar Acuse de Entrega a Servicio Regional</h3>
        <p>Resguardo del comprobante físico firmado por el titular o enlace del Servicio Regional.</p>
    </div>
    <div class="warehouse-detail-total" style="border-left-color:rgba(255,255,255,0.25);">
        <span>Folio de Entrega</span>
        <strong><?= escapar($entrega['folio']) ?></strong>
        <small><?= escapar($entrega['servicio_regional']) ?></small>
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
            <span>Destino: <?= escapar($entrega['servicio_regional']) ?></span>
            <h3>Subida de Comprobante Firmado</h3>
        </div>
    </div>

    <?php if (!empty($entrega['archivo_acuse'])): ?>
        <div class="notice" style="background:#eaf5ef;border-left-color:#38825d;margin-bottom:20px;">
            ✓ <strong>Ya existe un comprobante digital resguardado:</strong> 
            <a href="<?= escapar($entrega['archivo_acuse']) ?>" target="_blank" style="color:#1d663b;font-weight:700;margin-left:6px;">
                Ver comprobante actual ↗
            </a>. Si sube un nuevo archivo, se actualizará el documento.
        </div>
    <?php endif; ?>

    <form method="post" action="<?= escapar(url('subir-acuse-regional', ['id' => $entrega['id']])) ?>" enctype="multipart/form-data">
        <div class="request-fields">
            <div class="full-field" style="display:grid;gap:8px;">
                <label style="font-weight:700;font-size:13px;">
                    Seleccione el archivo escaneado o fotografía (PDF, JPG o PNG máx. 5 MB) *
                </label>
                <input type="file" name="archivo_acuse" accept=".pdf,.jpg,.jpeg,.png" required style="padding:14px;border:2px dashed #cfc7c2;border-radius:8px;background:#fcfaf7;">
                <small style="color:var(--muted);">Debe ser visible la firma de recepción del Servicio Regional receptor.</small>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px;">
            <a class="button secondary" href="<?= escapar(url('entrega-detalle', ['id' => $entrega['id']])) ?>">Cancelar</a>
            <button type="submit" class="button" style="background:#246e45;">
                ✓ Subir y Resguardar Acuse Regional
            </button>
        </div>
    </form>
</article>

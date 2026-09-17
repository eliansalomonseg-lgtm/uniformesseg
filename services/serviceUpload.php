<?php

declare(strict_types=1);

class serviceUpload
{
    private const MAX_BYTES = 5242880; // 5 MB
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/pjpeg',
        'image/png'
    ];

    /**
     * Procesa y valida la subida de un acuse firmado.
     * Retorna la ruta relativa del archivo guardado o lanza una excepción.
     */
    public static function guardarAcuse(array $file, string $prefijo = 'acuse'): string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new DomainException('Parámetros de archivo no válidos.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new DomainException('No se seleccionó ningún archivo para subir.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new DomainException('El archivo excede el tamaño máximo permitido (5 MB).');
            default:
                throw new DomainException('Error desconocido al subir el archivo.');
        }

        if ($file['size'] > self::MAX_BYTES) {
            throw new DomainException('El archivo supera el límite de 5 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new DomainException('Formato de archivo no admitido. Sólo se permiten documentos PDF e imágenes JPG o PNG.');
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new DomainException('Extensión de archivo no permitida.');
        }

        $uploadDir = __DIR__ . '/../uploads/acuses';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $nuevoNombre = sprintf(
            '%s_%s_%s.%s',
            preg_replace('/[^a-zA-Z0-9_-]/', '', $prefijo),
            date('Ymd_His'),
            bin2hex(random_bytes(6)),
            $ext
        );

        $destinoAbsoluto = $uploadDir . '/' . $nuevoNombre;
        if (!move_uploaded_file($file['tmp_name'], $destinoAbsoluto)) {
            throw new RuntimeException('No se pudo guardar el archivo en el servidor. Revise permisos de escritura.');
        }

        return 'uploads/acuses/' . $nuevoNombre;
    }
}

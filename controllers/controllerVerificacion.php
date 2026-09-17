<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/modelEntregaEscuela.php';

class controllerVerificacion
{
    public function __construct(private PDO $pdo) {}

    /**
     * Pantalla pública de validación y autenticación por Código QR.
     */
    public function validar(): void
    {
        $codigo = trim((string)($_GET['codigo'] ?? ''));
        $documento = null;
        $esValido = false;

        if ($codigo !== '') {
            $modelo = new modelEntregaEscuela($this->pdo);
            $documento = $modelo->obtenerPorCodigoVerificacion($codigo);
            $esValido = ($documento !== null);
        }

        renderizarVista(
            'verificacion/validar',
            compact('codigo', 'documento', 'esValido'),
            'Verificación Oficial de Documento',
            'inicio'
        );
    }
}

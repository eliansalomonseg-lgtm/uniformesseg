<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/modelEntregaSimple.php';

class controllerEntregaSimple
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: serviceDatabase::obtenerConexion();
    }

    /**
     * Listado principal de entregas con buscador y filtros
     */
    public function listar(): void
    {
        $tipo = isset($_GET['tipo']) ? trim((string)$_GET['tipo']) : '';
        $busqueda = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        $filtros = [];
        if ($tipo !== '') {
            $filtros['tipo'] = $tipo;
        }
        if ($busqueda !== '') {
            $filtros['busqueda'] = $busqueda;
        }

        $entregas = modelEntregaSimple::obtenerEntregas($filtros);

        $activo = 'entregas';
        $titulo = 'Control de Entregas de Uniformes';

        require __DIR__ . '/../views/fragments/header.php';
        require __DIR__ . '/../views/fragments/sidebar.php';
        require __DIR__ . '/../views/entregas/lista_simple.php';
        require __DIR__ . '/../views/fragments/footer.php';
    }

    /**
     * Formulario para registrar una nueva entrega
     */
    public function nueva(): void
    {
        $tallas = modelEntregaSimple::obtenerTallas();
        $escuelas = modelEntregaSimple::obtenerEscuelas();
        $serviciosRegionales = modelEntregaSimple::obtenerServiciosRegionales();

        $activo = 'entregas';
        $titulo = 'Registrar Entrega de Uniformes';

        require __DIR__ . '/../views/fragments/header.php';
        require __DIR__ . '/../views/fragments/sidebar.php';
        require __DIR__ . '/../views/entregas/nueva_simple.php';
        require __DIR__ . '/../views/fragments/footer.php';
    }

    /**
     * Procesa el guardado de la nueva entrega
     */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('entregas'));
            exit;
        }

        try {
            $tipoDestino = ($_POST['tipo_destino'] ?? '') === 'SERVICIO_REGIONAL' ? 'SERVICIO_REGIONAL' : 'ESCUELA';

            $datos = [
                'tipo_destino' => $tipoDestino,
                'escuela_id' => !empty($_POST['escuela_id']) ? (int)$_POST['escuela_id'] : null,
                'servicio_regional_id' => !empty($_POST['servicio_regional_id']) ? (int)$_POST['servicio_regional_id'] : null,
                'fecha_entrega' => !empty($_POST['fecha_entrega']) ? trim($_POST['fecha_entrega']) : date('Y-m-d'),
                'recibido_por_nombre' => trim($_POST['recibido_por_nombre'] ?? ''),
                'recibido_por_cargo' => trim($_POST['recibido_por_cargo'] ?? ''),
                'recibido_por_telefono' => trim($_POST['recibido_por_telefono'] ?? ''),
                'entregado_por_nombre' => trim($_POST['entregado_por_nombre'] ?? ''),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
            ];

            if ($tipoDestino === 'ESCUELA' && empty($datos['escuela_id'])) {
                throw new InvalidArgumentException('Debe seleccionar la escuela receptora.');
            }

            if ($tipoDestino === 'SERVICIO_REGIONAL' && empty($datos['servicio_regional_id'])) {
                throw new InvalidArgumentException('Debe seleccionar el servicio regional receptor.');
            }

            if (empty($datos['recibido_por_nombre'])) {
                throw new InvalidArgumentException('Debe especificar el nombre de la persona que recibe los uniformes.');
            }

            $cantidades = $_POST['cantidades'] ?? [];
            if (!is_array($cantidades)) {
                throw new InvalidArgumentException('Formato de cantidades inválido.');
            }

            $entregaId = modelEntregaSimple::registrarEntrega($datos, $cantidades);

            header('Location: ' . url('entrega-detalle') . '&id=' . $entregaId . '&creada=1');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $tallas = modelEntregaSimple::obtenerTallas();
            $escuelas = modelEntregaSimple::obtenerEscuelas();
            $serviciosRegionales = modelEntregaSimple::obtenerServiciosRegionales();

            $activo = 'entregas';
            $titulo = 'Registrar Entrega de Uniformes';

            require __DIR__ . '/../views/fragments/header.php';
            require __DIR__ . '/../views/fragments/sidebar.php';
            require __DIR__ . '/../views/entregas/nueva_simple.php';
            require __DIR__ . '/../views/fragments/footer.php';
        }
    }

    /**
     * Detalle y comprobante de entrega
     */
    public function detalle(?int $id = null): void
    {
        $id = $id ?: (isset($_GET['id']) ? (int)$_GET['id'] : 0);
        $entrega = modelEntregaSimple::obtenerPorId($id);

        if (!$entrega) {
            header('Location: ' . url('entregas') . '&error=' . urlencode('La entrega no existe.'));
            exit;
        }

        $detalles = modelEntregaSimple::obtenerDetalleTallas($id);

        // Agrupar por talla para tabla limpia
        $tablaTallas = [];
        foreach ($detalles as $row) {
            $tallaNom = $row['talla_nombre'];
            if (!isset($tablaTallas[$tallaNom])) {
                $tablaTallas[$tallaNom] = ['talla' => $tallaNom, 'nino' => 0, 'nina' => 0, 'total' => 0];
            }
            if ($row['sexo'] === 'NINO') {
                $tablaTallas[$tallaNom]['nino'] += (int)$row['cantidad'];
            } else {
                $tablaTallas[$tallaNom]['nina'] += (int)$row['cantidad'];
            }
            $tablaTallas[$tallaNom]['total'] += (int)$row['cantidad'];
        }

        $activo = 'entregas';
        $titulo = 'Comprobante de Entrega - ' . $entrega['folio'];

        require __DIR__ . '/../views/fragments/header.php';
        require __DIR__ . '/../views/fragments/sidebar.php';
        require __DIR__ . '/../views/entregas/comprobante_simple.php';
        require __DIR__ . '/../views/fragments/footer.php';
    }

    /**
     * Eliminar entrega
     */
    public function eliminar(?int $id = null): void
    {
        $id = $id ?: (isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0));
        $entrega = $id ? modelEntregaSimple::obtenerPorId($id) : null;

        if ($entrega) {
            modelEntregaSimple::eliminarEntrega($id);
            header('Location: ' . url('entregas') . '&eliminada=1&folio=' . urlencode($entrega['folio']));
            exit;
        }

        header('Location: ' . url('entregas'));
        exit;
    }
}

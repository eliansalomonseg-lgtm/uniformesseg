<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/modelInventario.php';
require_once __DIR__ . '/../models/modelAlmacen.php';

class controllerInventario
{
    public function __construct(private PDO $pdo) {}

    /**
     * Registro de entrada oficial de uniformes (proveedor / maquila / donación).
     */
    public function entrada(): void
    {
        $modelo = new modelInventario($this->pdo);
        $modelAlmacen = new modelAlmacen($this->pdo);
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $almacenId = filter_input(INPUT_POST, 'almacen_id', FILTER_VALIDATE_INT) ?: 0;
                $proveedor = (string)($_POST['proveedor_origen'] ?? '');
                $remision = (string)($_POST['num_remision_factura'] ?? '');
                $fecha = (string)($_POST['fecha_entrada'] ?? date('Y-m-d'));
                $observaciones = (string)($_POST['observaciones'] ?? '');
                $cantidades = (array)($_POST['cantidad'] ?? []);

                $entradaId = $modelo->registrarEntrada(
                    $almacenId,
                    $proveedor,
                    $remision,
                    $fecha,
                    $observaciones,
                    $cantidades
                );

                header('Location: ' . url('comprobante-entrada', ['id' => $entradaId, 'exito' => 1]));
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $almacenIdInicial = filter_input(INPUT_GET, 'almacen_id', FILTER_VALIDATE_INT) ?: 0;
        $almacenes = $modelAlmacen->obtenerAlmacenes();
        $tallas = $modelo->obtenerTallas();

        renderizarVista(
            'inventario/entrada',
            compact('almacenes', 'tallas', 'almacenIdInicial', 'error'),
            'Registrar Entrada de Stock',
            'almacenes'
        );
    }

    /**
     * Registro de traspaso directo entre dos almacenes.
     */
    public function traspaso(): void
    {
        $modelo = new modelInventario($this->pdo);
        $modelAlmacen = new modelAlmacen($this->pdo);
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $origenId = filter_input(INPUT_POST, 'almacen_origen_id', FILTER_VALIDATE_INT) ?: 0;
                $destinoId = filter_input(INPUT_POST, 'almacen_destino_id', FILTER_VALIDATE_INT) ?: 0;
                $fecha = (string)($_POST['fecha_traspaso'] ?? date('Y-m-d'));
                $transportista = (string)($_POST['transportista'] ?? '');
                $observaciones = (string)($_POST['observaciones'] ?? '');
                $cantidades = (array)($_POST['cantidad'] ?? []);

                $traspasoId = $modelo->registrarTraspaso(
                    $origenId,
                    $destinoId,
                    $fecha,
                    $transportista,
                    $observaciones,
                    $cantidades
                );

                header('Location: ' . url('comprobante-traspaso', ['id' => $traspasoId, 'exito' => 1]));
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $origenIdInicial = filter_input(INPUT_GET, 'origen_id', FILTER_VALIDATE_INT) ?: 0;
        $almacenes = $modelAlmacen->obtenerAlmacenes();
        $tallas = $modelo->obtenerTallas();

        // Obtener existencias actuales del almacén origen para mostrar disponibilidad en tiempo real
        $existenciasOrigen = [];
        if ($origenIdInicial > 0) {
            $existencias = $modelAlmacen->obtenerExistenciasPorTalla($origenIdInicial);
            foreach ($existencias as $ex) {
                $existenciasOrigen[$ex['id']] = [
                    'talla' => $ex['talla'],
                    'disponible_nino' => (int)($ex['disponible_nino'] ?? 0),
                    'disponible_nina' => (int)($ex['disponible_nina'] ?? 0),
                    'disponible' => (int)($ex['disponible'] ?? 0),
                ];
            }
        }

        renderizarVista(
            'inventario/traspaso',
            compact('almacenes', 'tallas', 'origenIdInicial', 'existenciasOrigen', 'error'),
            'Traspaso entre Almacenes',
            'almacenes'
        );
    }

    /**
     * Registro de ajuste de inventario o merma (baja justificada o regularización por conteo).
     */
    public function ajuste(): void
    {
        $modelo = new modelInventario($this->pdo);
        $modelAlmacen = new modelAlmacen($this->pdo);
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $almacenId = filter_input(INPUT_POST, 'almacen_id', FILTER_VALIDATE_INT) ?: 0;
                $tipoAjuste = (string)($_POST['tipo_ajuste'] ?? 'MERMA');
                $motivo = (string)($_POST['motivo'] ?? '');
                $acta = (string)($_POST['num_acta'] ?? '');
                $fecha = (string)($_POST['fecha_ajuste'] ?? date('Y-m-d'));
                $observaciones = (string)($_POST['observaciones'] ?? '');
                $cantidades = (array)($_POST['cantidad'] ?? []);

                $ajusteId = $modelo->registrarAjuste(
                    $almacenId,
                    $tipoAjuste,
                    $motivo,
                    $acta,
                    $fecha,
                    $observaciones,
                    $cantidades
                );

                header('Location: ' . url('comprobante-ajuste', ['id' => $ajusteId, 'exito' => 1]));
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $almacenIdInicial = filter_input(INPUT_GET, 'almacen_id', FILTER_VALIDATE_INT) ?: 0;
        $almacenes = $modelAlmacen->obtenerAlmacenes();
        $tallas = $modelo->obtenerTallas();

        renderizarVista(
            'inventario/ajuste',
            compact('almacenes', 'tallas', 'almacenIdInicial', 'error'),
            'Ajuste de Inventario y Mermas',
            'almacenes'
        );
    }

    /**
     * Comprobante oficial de entrada.
     */
    public function comprobanteEntrada(int $id): void
    {
        $modelo = new modelInventario($this->pdo);
        $entrada = $modelo->obtenerEntradaPorId($id);
        if (!$entrada) {
            mostrarNoEncontrado();
            return;
        }

        renderizarVista(
            'inventario/comprobante_entrada',
            compact('entrada'),
            'Comprobante de Entrada de Almacén',
            'almacenes'
        );
    }

    /**
     * Comprobante oficial de traspaso.
     */
    public function comprobanteTraspaso(int $id): void
    {
        $modelo = new modelInventario($this->pdo);
        $traspaso = $modelo->obtenerTraspasoPorId($id);
        if (!$traspaso) {
            mostrarNoEncontrado();
            return;
        }

        renderizarVista(
            'inventario/comprobante_traspaso',
            compact('traspaso'),
            'Comprobante de Traspaso de Almacén',
            'almacenes'
        );
    }

    /**
     * Comprobante oficial de ajuste o merma.
     */
    public function comprobanteAjuste(int $id): void
    {
        $modelo = new modelInventario($this->pdo);
        $ajuste = $modelo->obtenerAjustePorId($id);
        if (!$ajuste) {
            mostrarNoEncontrado();
            return;
        }

        renderizarVista(
            'inventario/comprobante_ajuste',
            compact('ajuste'),
            'Comprobante de Ajuste / Merma',
            'almacenes'
        );
    }

    /**
     * Endpoint JSON para obtener existencias en tiempo real de un almacén (usado por JS en traspasos).
     */
    public function existenciasJson(): void
    {
        $almacenId = filter_input(INPUT_GET, 'almacen_id', FILTER_VALIDATE_INT) ?: 0;
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        if (!$almacenId) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $modelAlmacen = new modelAlmacen($this->pdo);
        $existencias = $modelAlmacen->obtenerExistenciasPorTalla($almacenId);
        $mapa = [];
        foreach ($existencias as $e) {
            $mapa[$e['id']] = [
                'talla_id' => (int)$e['id'],
                'talla' => $e['talla'],
                'nino' => (int)$e['nino'],
                'nina' => (int)$e['nina'],
                'disponible_nino' => (int)($e['disponible_nino'] ?? 0),
                'disponible_nina' => (int)($e['disponible_nina'] ?? 0),
                'disponible' => (int)$e['disponible'],
            ];
        }
        echo json_encode($mapa, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

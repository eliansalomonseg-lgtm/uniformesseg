<?php

declare(strict_types=1);

/**
 * Generador autónomo y ligero de Códigos QR (Modelo 2, Modo Byte, Corrección M)
 * 100% PHP nativo sin extensiones pesadas ni conexión a internet requerida.
 */
class serviceQr
{
    /**
     * Retorna la URL pública completa de verificación para un código dado.
     */
    public static function generarUrlVerificacion(string $codigo): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = str_replace('\\', '/', dirname($script));
        $base = rtrim($protocol . '://' . $host . ($dir === '/' ? '' : $dir), '/');
        
        return $base . '/index.php?' . http_build_query([
            'ruta' => 'verificar-documento',
            'codigo' => $codigo
        ]);
    }

    /**
     * Genera un código QR en formato SVG vectorial nativo listo para incrustar en HTML.
     */
    public static function generarQrSvg(string $data, int $size = 180): string
    {
        $matrix = self::encodeData($data);
        $count = count($matrix);
        $quietZone = 4;
        $totalSize = $count + ($quietZone * 2);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $totalSize . ' ' . $totalSize . '" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges">';
        $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';
        $svg .= '<path fill="#000000" d="';

        $d = '';
        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($matrix[$r][$c]) {
                    $x = $c + $quietZone;
                    $y = $r + $quietZone;
                    $d .= "M{$x},{$y}h1v1h-1z ";
                }
            }
        }
        $svg .= trim($d) . '"/>';
        $svg .= '</svg>';

        return $svg;
    }

    // ==========================================
    // ALGORITMO COMPACTO DE CODIFICACIÓN QR MODEL 2
    // ==========================================

    private static array $exp = [];
    private static array $log = [];
    private static bool $gfInit = false;

    private static function initGF(): void
    {
        if (self::$gfInit) return;
        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);
        $val = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $val;
            self::$log[$val] = $i;
            $val <<= 1;
            if ($val & 256) {
                $val ^= 0x11d;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
        self::$gfInit = true;
    }

    private static function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) return 0;
        return self::$exp[self::$log[$x] + self::$log[$y]];
    }

    private static function getGeneratorPoly(int $degree): array
    {
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $factor = [1, self::$exp[$i]];
            $newPoly = array_fill(0, count($poly) + 1, 0);
            for ($j = 0; $j < count($poly); $j++) {
                for ($k = 0; $k < count($factor); $k++) {
                    $newPoly[$j + $k] ^= self::gfMul($poly[$j], $factor[$k]);
                }
            }
            $poly = $newPoly;
        }
        return $poly;
    }

    private static function calculateEC(array $data, int $ecCount): array
    {
        self::initGF();
        $poly = self::getGeneratorPoly($ecCount);
        $result = array_merge($data, array_fill(0, $ecCount, 0));
        $dataLen = count($data);

        for ($i = 0; $i < $dataLen; $i++) {
            $coef = $result[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($poly); $j++) {
                    $result[$i + $j] ^= self::gfMul($poly[$j], $coef);
                }
            }
        }
        return array_slice($result, $dataLen);
    }

    private static function encodeData(string $text): array
    {
        self::initGF();
        $bytes = array_values(unpack('C*', $text));
        $len = count($bytes);

        // Determinación de versión para nivel de corrección M
        // V1: 14 bytes datos, 10 EC (total 26 cap 208 bits)
        // V2: 26 bytes datos, 16 EC (total 44)
        // V3: 42 bytes datos, 26 EC (total 70)
        // V4: 62 bytes datos, 36 EC
        // V5: 84 bytes datos, 48 EC
        // V6: 106 bytes datos, 64 EC
        // V7: 122 bytes datos, 72 EC
        // V8: 152 bytes datos, 88 EC
        $versions = [
            1 => ['data' => 14, 'ec' => 10, 'size' => 21, 'blocks' => 1],
            2 => ['data' => 26, 'ec' => 16, 'size' => 25, 'blocks' => 1],
            3 => ['data' => 42, 'ec' => 26, 'size' => 29, 'blocks' => 1],
            4 => ['data' => 62, 'ec' => 36, 'size' => 33, 'blocks' => 2],
            5 => ['data' => 84, 'ec' => 48, 'size' => 37, 'blocks' => 2],
            6 => ['data' => 106, 'ec' => 64, 'size' => 41, 'blocks' => 4],
            7 => ['data' => 122, 'ec' => 72, 'size' => 45, 'blocks' => 4],
            8 => ['data' => 152, 'ec' => 88, 'size' => 49, 'blocks' => 4],
        ];

        $version = 1;
        foreach ($versions as $v => $spec) {
            if ($len + 3 <= $spec['data']) { // +3 para indicador de modo byte y longitud
                $version = $v;
                break;
            }
            $version = $v;
        }
        $spec = $versions[$version];

        // Codificación Byte (Modo 0100)
        $bitBuffer = '';
        $bitBuffer .= '0100'; // 4 bits modo Byte
        $bitBuffer .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT); // 8 bits longitud
        foreach ($bytes as $b) {
            $bitBuffer .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }

        // Terminador (hasta 4 ceros)
        $maxBits = $spec['data'] * 8;
        $bitBuffer .= substr('0000', 0, max(0, min(4, $maxBits - strlen($bitBuffer))));
        
        // Rellenar a múltiplo de 8
        if (strlen($bitBuffer) % 8 !== 0) {
            $bitBuffer .= str_repeat('0', 8 - (strlen($bitBuffer) % 8));
        }

        // Relleno de bytes alternados 0xEC y 0x11
        $padBytes = ['11101100', '00010001'];
        $padIdx = 0;
        while (strlen($bitBuffer) < $maxBits) {
            $bitBuffer .= $padBytes[$padIdx % 2];
            $padIdx++;
        }

        // Convertir bits en bytes de datos
        $dataBytes = [];
        for ($i = 0; $i < strlen($bitBuffer); $i += 8) {
            $dataBytes[] = bindec(substr($bitBuffer, $i, 8));
        }

        // Cálculo de bloques de corrección de errores
        $numBlocks = $spec['blocks'];
        $totalData = count($dataBytes);
        $dataPerBlock = intdiv($totalData, $numBlocks);
        $ecPerBlock = intdiv($spec['ec'], $numBlocks);

        $dataBlocks = [];
        $ecBlocks = [];
        for ($b = 0; $b < $numBlocks; $b++) {
            $start = $b * $dataPerBlock;
            $blockData = array_slice($dataBytes, $start, $dataPerBlock);
            $dataBlocks[] = $blockData;
            $ecBlocks[] = self::calculateEC($blockData, $ecPerBlock);
        }

        // Intercalado de datos y EC
        $finalCodewords = [];
        for ($i = 0; $i < $dataPerBlock; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $finalCodewords[] = $dataBlocks[$b][$i];
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $finalCodewords[] = $ecBlocks[$b][$i];
            }
        }

        // Convertir a secuencia de bits final
        $finalBits = '';
        foreach ($finalCodewords as $cw) {
            $finalBits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        // Construir Matriz QR
        $size = $spec['size'];
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $isFunction = array_fill(0, $size, array_fill(0, $size, false));

        // 1. Patrones de búsqueda (Finder patterns)
        self::placeFinder($matrix, $isFunction, 0, 0);
        self::placeFinder($matrix, $isFunction, $size - 7, 0);
        self::placeFinder($matrix, $isFunction, 0, $size - 7);

        // 2. Patrones de sincronización (Timing)
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = ($i % 2 === 0);
            $isFunction[6][$i] = true;
            $matrix[$i][6] = ($i % 2 === 0);
            $isFunction[$i][6] = true;
        }

        // 3. Módulo oscuro fijo
        $matrix[$size - 8][8] = true;
        $isFunction[$size - 8][8] = true;

        // 4. Patrones de alineación para Version >= 2
        if ($version >= 2) {
            $alignPositions = [
                2 => [6, 18],
                3 => [6, 22],
                4 => [6, 26],
                5 => [6, 30],
                6 => [6, 34],
                7 => [6, 38],
                8 => [6, 42],
            ];
            $coords = $alignPositions[$version] ?? [];
            foreach ($coords as $r) {
                foreach ($coords as $c) {
                    if ($matrix[$r][$c] === null) {
                        self::placeAlignment($matrix, $isFunction, $r - 2, $c - 2);
                    }
                }
            }
        }

        // 5. Reservar áreas de información de formato
        for ($i = 0; $i < 9; $i++) {
            $isFunction[8][$i] = true;
            $isFunction[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $isFunction[8][$size - 1 - $i] = true;
            $isFunction[$size - 1 - $i][8] = true;
        }

        // 6. Colocación de bits de datos en zig-zag
        $bitIdx = 0;
        $totalBits = strlen($finalBits);
        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) $col--; // Saltar columna de sincronización
            $rows = range(0, $size - 1);
            if ((($size - 1 - $col) / 2) % 2 === 0) {
                $rows = array_reverse($rows); // Hacia arriba
            }
            foreach ($rows as $row) {
                for ($c = $col; $c >= $col - 1; $c--) {
                    if (!$isFunction[$row][$c]) {
                        $matrix[$row][$c] = ($bitIdx < $totalBits) ? ($finalBits[$bitIdx] === '1') : false;
                        $bitIdx++;
                    }
                }
            }
        }

        // 7. Aplicar máscara óptima (usamos máscara 0: (row + col) % 2 == 0)
        // Corrección M (00) con máscara 0 (000) -> 00000 XOR 101010000010010 = 101010000010010
        $formatBits = '101010000010010';
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if (!$isFunction[$row][$col]) {
                    if (($row + $col) % 2 === 0) {
                        $matrix[$row][$col] = !$matrix[$row][$col];
                    }
                }
            }
        }

        // Escribir formato en la matriz
        self::placeFormatBits($matrix, $formatBits, $size);

        return $matrix;
    }

    private static function placeFinder(array &$matrix, array &$isFunc, int $r, int $c): void
    {
        for ($y = -1; $y <= 7; $y++) {
            for ($x = -1; $x <= 7; $x++) {
                $row = $r + $y;
                $col = $c + $x;
                if ($row >= 0 && $row < count($matrix) && $col >= 0 && $col < count($matrix)) {
                    $isBlack = ($y >= 0 && $y <= 6 && ($x === 0 || $x === 6)) ||
                               ($x >= 0 && $x <= 6 && ($y === 0 || $y === 6)) ||
                               ($y >= 2 && $y <= 4 && $x >= 2 && $x <= 4);
                    $matrix[$row][$col] = $isBlack;
                    $isFunc[$row][$col] = true;
                }
            }
        }
    }

    private static function placeAlignment(array &$matrix, array &$isFunc, int $r, int $c): void
    {
        for ($y = 0; $y < 5; $y++) {
            for ($x = 0; $x < 5; $x++) {
                $isBlack = ($y === 0 || $y === 4 || $x === 0 || $x === 4 || ($y === 2 && $x === 2));
                $matrix[$r + $y][$c + $x] = $isBlack;
                $isFunc[$r + $y][$c + $x] = true;
            }
        }
    }

    private static function placeFormatBits(array &$matrix, string $bits, int $size): void
    {
        // Alrededor del patrón superior izquierdo
        $coordsLeft = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8]
        ];
        // En los otros dos extremos
        $coordsSplit = [
            [$size - 1, 8], [$size - 2, 8], [$size - 3, 8], [$size - 4, 8],
            [$size - 5, 8], [$size - 6, 8], [$size - 7, 8],
            [8, $size - 8], [8, $size - 7], [8, $size - 6], [8, $size - 5],
            [8, $size - 4], [8, $size - 3], [8, $size - 2], [8, $size - 1]
        ];

        for ($i = 0; $i < 15; $i++) {
            $val = ($bits[$i] === '1');
            $matrix[$coordsLeft[$i][0]][$coordsLeft[$i][1]] = $val;
            $matrix[$coordsSplit[$i][0]][$coordsSplit[$i][1]] = $val;
        }
    }
}

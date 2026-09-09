<?php

declare(strict_types=1);

require_once __DIR__.'/../config/database.php';

class serviceDatabase
{
    private static ?PDO $conexion = null;

    public static function obtenerConexion(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $configuracion = obtenerConfiguracionDatabase();
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $configuracion['host'],
            $configuracion['database'],
            $configuracion['charset']
        );

        self::$conexion = new PDO(
            $dsn,
            $configuracion['username'],
            $configuracion['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$conexion;
    }
}

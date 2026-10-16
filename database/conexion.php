<?php

class Database {
    public function connect() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $ruta = __DIR__ . '/../.env';
        if (!is_readable($ruta)) {
            throw new RuntimeException('No se encuentra el archivo .env');
        }
        $env = parse_ini_file($ruta, false, INI_SCANNER_RAW);

        $conexion = new mysqli(
            $env['URL_DB'] ?? '',
            $env['USER_DB'] ?? '',
            $env['PASS_DB'] ?? '',
            $env['NAME_DB'] ?? ''
        );
        $conexion->set_charset('utf8mb4');
        return $conexion;
    }
}
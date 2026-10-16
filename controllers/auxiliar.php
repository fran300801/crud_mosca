<?php

class Auxiliar {
    private static ?array $env = null;

    public static function sendJson($code, $data = null) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(int $code, string $mensaje) {
        self::sendJson($code, [
            'ok' => false,
            'error' => $mensaje
        ]);
    }

    // No conseguía hacer que leyera el .env, así que he hecho un método estático para leerlo y cachearlo en memoria

    public static function env(string $clave): string {
        if (self::$env === null) {
            $ruta = __DIR__ . '/../.env';
            self::$env = is_readable($ruta)
                ? parse_ini_file($ruta, false, INI_SCANNER_RAW) ?: []
                : [];
        }

        return trim(self::$env[$clave] ?? '');
    }

    public static function exigirAdmin(UsuarioDTO $usuario){
        if ($usuario->getRol() !== 1) {
            self::error(403, 'Solo el administrador puede gestionar usuarios');
        }
    }

    public static function exigirJugador(UsuarioDTO $usuario){
        if ($usuario->getRol() !== 0) {
            self::error(403, 'El administrador no puede jugar');
        }
    }

    // Toda esta función de abajo no tenía ni idea de como hacerla

    public static function enviarEmail(string $destino, string $asunto, string $texto): bool {
        $remitente = self::env('EMAIL');
        $clave = str_replace(' ', '', self::env('SMTP_PASSWORD'));

        if ($remitente === '' || $clave === '') {
            error_log('Falta EMAIL o SMTP_PASSWORD en el .env');
            return false;
        }

        $socket = @stream_socket_client('ssl://smtp.gmail.com:465', $errno, $errstr, 15);

        if (!$socket) {
            return false;
        }

        stream_set_timeout($socket, 15);

        $ok = self::smtpLeer($socket, '220')
            && self::smtpComando($socket, 'EHLO localhost', '250')
            && self::smtpComando($socket, 'AUTH LOGIN', '334')
            && self::smtpComando($socket, base64_encode($remitente), '334')
            && self::smtpComando($socket, base64_encode($clave), '235')
            && self::smtpComando($socket, "MAIL FROM:<$remitente>", '250')
            && self::smtpComando($socket, "RCPT TO:<$destino>", '250')
            && self::smtpComando($socket, 'DATA', '354');

        if ($ok) {
            $mensaje = "From: <$remitente>\r\n"
                . "To: <$destino>\r\n"
                . 'Subject: =?UTF-8?B?' . base64_encode($asunto) . "?=\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "\r\n"
                . $texto . "\r\n.";

            $ok = self::smtpComando($socket, $mensaje, '250');
        }

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return $ok;
    }

    private static function smtpComando($socket, string $comando, string $esperado): bool {
        fwrite($socket, $comando . "\r\n");
        return self::smtpLeer($socket, $esperado);
    }

    private static function smtpLeer($socket, string $esperado): bool {
        $respuesta = '';

        while (($linea = fgets($socket, 515)) !== false) {
            $respuesta .= $linea;

            if (strlen($linea) < 4 || $linea[3] === ' ') {
                break;
            }
        }

        return strpos($respuesta, $esperado) === 0;
    }
}
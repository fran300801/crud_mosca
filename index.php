
<?php

header("Content-Type: application/json");
require_once("Controllers/auxiliar.php");
require_once("Models/tablero.php");
require_once("Models/usuario_dto.php");
require_once("Database/conexion.php");
require_once("Database/usuario_dao.php");
require_once("Database/mosca_dao.php");
require_once("Controllers/controladorUsuario.php");
require_once("Controllers/controladorMosca.php");

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?? '';
$ruta = trim($path, "/");
$metodo = $_SERVER['REQUEST_METHOD'];
$datos = json_decode(file_get_contents("php://input"), true) ?? [];

try {
    $conexion = (new Database())->connect();
    $usuarioDao = new UsuarioDAO($conexion);
    $cUsuario = new ControllerUsuario($usuarioDao);
    $cMosca = new ControllerMosca(new MoscaDAO($conexion), $usuarioDao);

    $partes = explode('/', $ruta);

    switch ($metodo) {
        case 'POST':
            if ($ruta === 'register') {
                $cUsuario->register($datos);
            } elseif ($ruta === 'login') {
                $cUsuario->login($datos);
            } elseif ($ruta === 'olvido') {
                $cUsuario->olvido($datos);
            } elseif (
                count($partes) === 2 &&
                $partes[0] === 'crear' &&
                ctype_digit($partes[1])
            ) {
                $datos['id_usuario'] = (int)$partes[1];
                $cMosca->iniciarTablero($datos);
            } elseif (
                count($partes) === 2 &&
                $partes[0] === 'jugar' &&
                ctype_digit($partes[1])
            ) {
                $datos['id_usuario'] = (int)$partes[1];
                $cMosca->jugar($datos);
            } elseif (
                count($partes) === 2 &&
                $partes[0] === 'usuarios' &&
                ctype_digit($partes[1])
            ) {
                $idAdmin = (int)$partes[1];
                $cUsuario->crearUsuario($idAdmin, $datos);
            } else {
                Auxiliar::error(404, "Ruta no encontrada");
            }
            break;

        case 'GET':
            if (
                count($partes) === 2 &&
                $partes[0] === 'intentos' &&
                ctype_digit($partes[1])
            ) {
                $datos['id_usuario'] = (int)$partes[1];
                $cMosca->verIntentos((int)$partes[1]);
            } elseif (
                count($partes) === 2 &&
                $partes[0] === 'usuarios' &&
                ctype_digit($partes[1])
            ) {
                $idAdmin = (int)$partes[1];
                $cUsuario->listar($idAdmin);
            } elseif (
                count($partes) === 3 &&
                $partes[0] === 'usuarios' &&
                ctype_digit($partes[1]) &&
                ctype_digit($partes[2])
            ) {
                $idAdmin = (int)$partes[1];
                $idUsuario = (int)$partes[2];
                $cUsuario->getUsuario($idAdmin, $idUsuario);
            } else {
                Auxiliar::error(404, "Ruta no encontrada");
            }
            break;

        case 'PUT':
            if (
                count($partes) === 2 &&
                $partes[0] === 'rendirse' &&
                ctype_digit($partes[1])
            ) {
                $datos['id_usuario'] = (int)$partes[1];
                $cMosca->rendirse($datos);
            } elseif (
                count($partes) === 3 &&
                $partes[0] === 'usuarios' &&
                ctype_digit($partes[1]) &&
                ctype_digit($partes[2])
            ) {
                $idAdmin = (int)$partes[1];
                $idUsuario = (int)$partes[2];
                $cUsuario->editUsuario($idAdmin, $idUsuario, $datos);
            } elseif (
                count($partes) === 4 &&
                $partes[0] === 'usuarios' &&
                ctype_digit($partes[1]) &&
                ctype_digit($partes[2]) &&
                $partes[3] === 'rol'
            ) {
                $idAdmin = (int)$partes[1];
                $idUsuario = (int)$partes[2];
                $cUsuario->cambiarRol($idAdmin, $idUsuario, $datos);
            } else {
                Auxiliar::error(404, "Ruta no encontrada");
            }
            break;

        case 'DELETE':
            if (
                count($partes) === 3 &&
                $partes[0] === 'usuarios' &&
                ctype_digit($partes[1]) &&
                ctype_digit($partes[2])
            ) {
                $idAdmin = (int)$partes[1];
                $idUsuario = (int)$partes[2];
                $cUsuario->deleteUsuario($idAdmin, $idUsuario);
            } else {
                Auxiliar::error(404, "Ruta no encontrada");
            }
            break;

        default:
            Auxiliar::error(405, "Método no permitido");
            break;
    }

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    Auxiliar::error(500, "Error de base de datos");
} catch (Throwable $e) {
    error_log($e->getMessage());
    Auxiliar::error(500, "Error del servidor");
}

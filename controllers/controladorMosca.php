<?php

class ControllerMosca {

    private MoscaDAO $dao;
    private UsuarioDAO $usuarioDao;

    public function __construct(MoscaDAO $dao, UsuarioDAO $usuarioDao) {
        $this->dao = $dao;
        $this->usuarioDao = $usuarioDao;
    }

    private function obtenerJugadorValido(array $datos): UsuarioDTO {
        if (!isset($datos['id_usuario'])) {
            Auxiliar::error(400, 'Debes indicar el id_usuario en el body JSON');
        }

        $usuarioId = (int) $datos['id_usuario'];
        $usuario = $this->usuarioDao->getById($usuarioId);

        if ($usuario === null) {
            Auxiliar::error(404, 'Usuario no encontrado');
        }

        Auxiliar::exigirJugador($usuario);

        return $usuario;
    }

    public function iniciarTablero(array $datos) {
        $usuario = $this->obtenerJugadorValido($datos);

        $tamano = (int) ($datos['tamano'] ?? 10);
        $intentos = (int) ($datos['intentos'] ?? 5);

        if ($tamano < 3 || $tamano > 100) {
            Auxiliar::error(422, 'El tamaño debe estar entre 3 y 100');
        }

        if ($intentos < 1 || $intentos > 100) {
            Auxiliar::error(422, 'Los intentos deben estar entre 1 y 100');
        }

        if ($this->dao->obtenerTableroActivo($usuario->getId()) !== null) {
            Auxiliar::error(409, 'Ya tienes un tablero activo. Termínalo o ríndete antes de crear otro');
        }

        $tablero = new Tablero($tamano, $intentos);
        $tablero->colocarMosca();

        $id = $this->dao->crearPartida($usuario->getId(), $tablero);

        Auxiliar::sendJson(201, [
            'ok' => true,
            'mensaje' => 'Tablero creado correctamente',
            'tablero' => [
                'id' => $id,
                'tamano' => $tamano,
                'intentos_restantes' => $intentos,
                'activo' => true
            ]
        ]);
    }

    public function jugar(array $datos) {
        $usuario = $this->obtenerJugadorValido($datos);

        $this->dao->begin();

        $tablero = $this->dao->obtenerTableroActivo($usuario->getId(), true);

        if ($tablero === null) {
            $this->fallar(404, 'No tienes ningún tablero activo');
        }

        if (!isset($datos['casilla'])) {
            $this->fallar(400, 'Debes indicar la casilla');
        }

        $casilla = (int) $datos['casilla'];

        if ($casilla < 1 || $casilla > $tablero->tamano) {
            $this->fallar(422, 'La casilla debe estar entre 1 y ' . $tablero->tamano);
        }

        $resultado = $tablero->darManotazo($casilla);

        $this->dao->actualizarPartida($tablero);
        $this->dao->commit();

        $respuesta = [
            'ok' => true,
            'resultado' => $resultado,
            'intentos_restantes' => $tablero->intentos,
            'activo' => $tablero->activo
        ];

        if (!$tablero->estaActivo()) {
            $respuesta['mosca'] = $tablero->posMosca;
        }

        Auxiliar::sendJson(200, $respuesta);
    }

    public function verIntentos(int $usuarioId) {
        $usuario = $this->usuarioDao->getById($usuarioId);

        if ($usuario === null) {
            Auxiliar::error(404, 'Usuario no encontrado');
        }

        Auxiliar::exigirJugador($usuario);

        $tablero = $this->dao->obtenerTableroActivo($usuario->getId());

        if ($tablero === null) {
            Auxiliar::error(404, 'No tienes ningún tablero activo');
        }

        Auxiliar::sendJson(200, [
            'ok' => true,
            'intentos_restantes' => $tablero->intentos
        ]);
    }
    public function rendirse(array $datos) {
        $usuario = $this->obtenerJugadorValido($datos);

        $this->dao->begin();

        $tablero = $this->dao->obtenerTableroActivo($usuario->getId(), true);

        if ($tablero === null) {
            $this->fallar(404, 'No tienes ningún tablero activo');
        }

        $tablero->rendirse();

        $this->dao->actualizarPartida($tablero);
        $this->dao->commit();

        Auxiliar::sendJson(200, [
            'ok' => true,
            'activo' => $tablero->activo,
            'mosca' => $tablero->posMosca
        ]);
    }

    private function fallar(int $codigo, string $mensaje) {
        $this->dao->rollback();
        Auxiliar::error($codigo, $mensaje);
    }
}
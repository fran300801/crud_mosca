<?php

class ControllerUsuario {

    private UsuarioDAO $dao;
    private const PASS_MIN = 6;

    public function __construct(UsuarioDAO $dao) {
        $this->dao = $dao;
    }

    public function register(array $datos) {
        $user = trim($datos['user'] ?? '');
        $email = trim($datos['email'] ?? '');
        $pass = $datos['pass'] ?? '';

        if ($user === '' || $email === '' || $pass === '' || !isset($datos['edad'])) {
            Auxiliar::error(400, 'Debes indicar user, edad, email y pass');
        }

        $edad = (int) $datos['edad'];

        if ($edad < 1 || $edad > 120) {
            Auxiliar::error(422, 'La edad debe ser un entero entre 1 y 120');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Auxiliar::error(422, 'El formato del email no es válido');
        }

        if (strlen($pass) < self::PASS_MIN) {
            Auxiliar::error(422, 'La contraseña debe tener al menos 6 caracteres');
        }

        if ($this->dao->getByUser($user) !== null) {
            Auxiliar::error(409, 'Ese usuario ya existe');
        }

        if ($this->dao->getByEmail($email) !== null) {
            Auxiliar::error(409, 'Ese email ya está registrado');
        }

        $nuevo = new UsuarioDTO(null, $user, $edad, $email, md5($pass), 0);
        $id = $this->dao->insert($nuevo);

        Auxiliar::sendJson(201, [
            'ok' => true,
            'mensaje' => 'Usuario registrado correctamente como JUGADOR',
            'usuario' => (new UsuarioDTO($id, $user, $edad, $email, '', 0))->toArray()
        ]);
    }

    public function login(array $datos) {
        $user = trim($datos['user'] ?? '');
        $pass = $datos['pass'] ?? '';

        if ($user === '' || $pass === '') {
            Auxiliar::error(400, 'Debes indicar user y pass');
        }

        $usuario = $this->dao->getByUser($user);

        if ($usuario === null || !hash_equals($usuario->getPass(), md5($pass))) {
            Auxiliar::error(401, 'Usuario o contraseña incorrectos');
        }

        Auxiliar::sendJson(200, [
            'ok' => true,
            'mensaje' => 'Login correcto',
            'usuario' => $usuario->toArray()
        ]);
    }

    public function olvido(array $datos) {
        $email = trim($datos['email'] ?? '');

        if ($email === '') {
            Auxiliar::error(400, 'Debes indicar el email');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Auxiliar::error(422, 'El formato del email no es válido');
        }

        $usuario = $this->dao->getByEmail($email);

        if ($usuario !== null) {
            $nueva = substr(bin2hex(random_bytes(8)), 0, 10);

            $texto = "Hola " . $usuario->getUser();
            $texto .= "Tu nueva contraseña provisional es: " . $nueva;
            $texto .= "Cámbiala cuando inicies sesión.";

            if (!Auxiliar::enviarEmail($email, 'Recuperación de contraseña - La Mosca', $texto)) {
                Auxiliar::error(500, 'No se pudo enviar el correo');
            }

            $this->dao->update($usuario->getId(), $usuario->getEdad(), md5($nueva));
        }

        Auxiliar::sendJson(200, [
            'ok' => true,
            'mensaje' => 'Si el email está registrado, recibirás una nueva contraseña'
        ]);
    }

    public function listar(int $idAdmin) {
        $actual = $this->dao->getById($idAdmin);

        if ($actual === null) {
            Auxiliar::error(404, 'Administrador no encontrado');
        }

        Auxiliar::exigirAdmin($actual);

        $usuarios = [];

        foreach ($this->dao->getAll() as $usuario) {
            $usuarios[] = $usuario->toArray();
        }

        Auxiliar::sendJson(200, [
            'ok' => true,
            'usuarios' => $usuarios
        ]);
    }

    public function crearUsuario(int $idAdmin, array $datos) {
        $actual = $this->dao->getById($idAdmin);

        if ($actual === null) {
            Auxiliar::error(404, 'Administrador no encontrado');
        }

        Auxiliar::exigirAdmin($actual);

        $user = trim($datos['user'] ?? '');
        $email = trim($datos['email'] ?? '');
        $pass = $datos['pass'] ?? '';
        $rol = (int) ($datos['rol'] ?? 0);

        if ($user === '' || $email === '' || $pass === '' || !isset($datos['edad'])) {
            Auxiliar::error(400, 'Debes indicar user, edad, email y pass');
        }

        if ($rol !== 0 && $rol !== 1) {
            Auxiliar::error(422, 'El rol debe ser 0 (JUGADOR) o 1 (ADMIN)');
        }

        $edad = (int) $datos['edad'];

        if ($edad < 1 || $edad > 120) {
            Auxiliar::error(422, 'La edad debe ser un entero entre 1 y 120');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Auxiliar::error(422, 'El formato del email no es válido');
        }

        if (strlen($pass) < self::PASS_MIN) {
            Auxiliar::error(422, 'La contraseña debe tener al menos 6 caracteres');
        }

        if ($this->dao->getByUser($user) !== null || $this->dao->getByEmail($email) !== null) {
            Auxiliar::error(409, 'El usuario o email ya existe');
        }

        $nuevo = new UsuarioDTO(null, $user, $edad, $email, md5($pass), $rol);
        $id = $this->dao->insert($nuevo);

        Auxiliar::sendJson(201, [
            'ok' => true,
            'mensaje' => 'Usuario creado correctamente por el administrador',
            'usuario' => (new UsuarioDTO($id, $user, $edad, $email, '', $rol))->toArray()
        ]);
    }

    public function getUsuario(int $idAdmin, int $id) {
        $actual = $this->dao->getById($idAdmin);

        if ($actual === null) {
            Auxiliar::error(404, 'Administrador no encontrado');
        }

        Auxiliar::exigirAdmin($actual);

        $usuario = $this->dao->getById($id);

        if ($usuario === null) {
            Auxiliar::error(404, 'Usuario no encontrado');
        }

        Auxiliar::sendJson(200, [
            'ok' => true,
            'usuario' => $usuario->toArray()
        ]);
    }

    public function editUsuario(int $idAdmin, int $id, array $datos) {
        $actual = $this->dao->getById($idAdmin);

        if ($actual === null) {
            Auxiliar::error(404, 'Administrador no encontrado');
        }

        Auxiliar::exigirAdmin($actual);

        $usuario = $this->dao->getById($id);

        if ($usuario === null) {
            Auxiliar::error(404, 'Usuario no encontrado');
        }

        $edad = $usuario->getEdad();
        $pass = $usuario->getPass();

        if (isset($datos['edad'])) {
            $edad = (int) $datos['edad'];

            if ($edad < 1 || $edad > 120) {
                Auxiliar::error(422, 'La edad debe ser un entero entre 1 y 120');
            }
        }

        if (isset($datos['pass'])) {
            if (strlen($datos['pass']) < self::PASS_MIN) {
                Auxiliar::error(422, 'La contraseña debe tener al menos 6 caracteres');
            }

            $pass = md5($datos['pass']);
        }

        $this->dao->update($id, $edad, $pass);

        Auxiliar::sendJson(200, [
            'ok' => true,
            'mensaje' => 'Usuario modificado correctamente'
        ]);
    }

    public function cambiarRol(int $idAdmin, int $id, array $datos) {
        $actual = $this->dao->getById($idAdmin);

        if ($actual === null) {
            Auxiliar::error(404, 'Administrador no encontrado');
        }

        Auxiliar::exigirAdmin($actual);

        if (!isset($datos['rol'])) {
            Auxiliar::error(400, 'Debes indicar el rol');
        }

        $rol = (int) $datos['rol'];

        if ($rol !== 0 && $rol !== 1) {
            Auxiliar::error(422, 'El rol debe ser 0 (JUGADOR) o 1 (ADMIN)');
        }

        if ($actual->getId() === $id && $rol !== 1) {
            Auxiliar::error(403, 'No puedes quitarte el rol de administrador a ti mismo');
        }

        $usuario = $this->dao->getById($id);

        if ($usuario === null) {
            Auxiliar::error(404, 'Usuario no encontrado');
        }

        $this->dao->updateRol($id, $rol);

        Auxiliar::sendJson(200, [
            'ok' => true,
            'mensaje' => 'Rol modificado correctamente',
            'rol_nuevo' => $rol
        ]);
    }

    public function deleteUsuario(int $idAdmin, int $id) {
        $actual = $this->dao->getById($idAdmin);

        if ($actual === null) {
            Auxiliar::error(404, 'Administrador no encontrado');
        }

        Auxiliar::exigirAdmin($actual);

        if ($actual->getId() === $id) {
            Auxiliar::error(403, 'No puedes eliminar tu propia cuenta de administrador');
        }

        if ($this->dao->getById($id) === null) {
            Auxiliar::error(404, 'Usuario no encontrado');
        }

        $this->dao->delete($id);

        Auxiliar::sendJson(200, [
            'ok' => true,
            'mensaje' => 'Usuario eliminado correctamente'
        ]);
    }
}
<?php

class UsuarioDAO {
    private mysqli $conexion;

    public function __construct(mysqli $conexion) {
        $this->conexion = $conexion;
    }

    public function getByUser(string $user): ?UsuarioDTO {
        $sql = "SELECT u.id, u.`user`, u.edad, u.email, u.pass, r.rol AS rol
                FROM usuarios u
                LEFT JOIN roles_asignados ra ON u.id = ra.id_usuario
                LEFT JOIN roles r ON ra.id_rol = r.id
                WHERE u.`user` = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('s', $user);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $fila ? $this->filaADto($fila) : null;
    }

    public function getByEmail(string $email): ?UsuarioDTO {
        $sql = "SELECT u.id, u.`user`, u.edad, u.email, u.pass, r.rol AS rol
                FROM usuarios u
                LEFT JOIN roles_asignados ra ON u.id = ra.id_usuario
                LEFT JOIN roles r ON ra.id_rol = r.id
                WHERE u.email = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $fila ? $this->filaADto($fila) : null;
    }

    public function getById(int $id): ?UsuarioDTO {
        $sql = "SELECT u.id, u.`user`, u.edad, u.email, u.pass, r.rol AS rol
                FROM usuarios u
                LEFT JOIN roles_asignados ra ON u.id = ra.id_usuario
                LEFT JOIN roles r ON ra.id_rol = r.id
                WHERE u.id = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $fila ? $this->filaADto($fila) : null;
    }

    public function getAll(): array {
        $sql = "SELECT u.id, u.`user`, u.edad, u.email, u.pass, r.rol AS rol
                FROM usuarios u
                LEFT JOIN roles_asignados ra ON u.id = ra.id_usuario
                LEFT JOIN roles r ON ra.id_rol = r.id
                ORDER BY u.id";

        $resultado = $this->conexion->query($sql);
        $usuarios = [];

        while ($fila = $resultado->fetch_assoc()) {
            $usuarios[] = $this->filaADto($fila);
        }

        return $usuarios;
    }

    public function insert(UsuarioDTO $usuario): int {
        $stmt = $this->conexion->prepare(
            'INSERT INTO usuarios (`user`, edad, email, pass) VALUES (?, ?, ?, ?)'
        );

        $user = $usuario->getUser();
        $edad = $usuario->getEdad();
        $email = $usuario->getEmail();
        $pass = $usuario->getPass();

        $stmt->bind_param('siss', $user, $edad, $email, $pass);
        $stmt->execute();

        $usuarioId = $stmt->insert_id;
        $stmt->close();

        $rol = $usuario->getRol();

        $stmt = $this->conexion->prepare(
            'SELECT id FROM roles WHERE rol = ? LIMIT 1'
        );
        $stmt->bind_param('i', $rol);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila) {
            $rolId = (int) $fila['id'];
        } else {
            $stmt = $this->conexion->prepare(
                'INSERT INTO roles (rol) VALUES (?)'
            );
            $stmt->bind_param('i', $rol);
            $stmt->execute();

            $rolId = $stmt->insert_id;
            $stmt->close();
        }

        $stmt = $this->conexion->prepare(
            'INSERT INTO roles_asignados (id_rol, id_usuario) VALUES (?, ?)'
        );
        $stmt->bind_param('ii', $rolId, $usuarioId);
        $stmt->execute();
        $stmt->close();

        return $usuarioId;
    }

    public function update(int $id, int $edad, string $pass){
        $stmt = $this->conexion->prepare(
            'UPDATE usuarios SET edad = ?, pass = ? WHERE id = ?'
        );

        $stmt->bind_param('isi', $edad, $pass, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function updateRol(int $id, int $rol){
        $stmt = $this->conexion->prepare(
            'SELECT id FROM roles WHERE rol = ? LIMIT 1'
        );
        $stmt->bind_param('i', $rol);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila) {
            $rolId = (int) $fila['id'];
        } else {
            $stmt = $this->conexion->prepare(
                'INSERT INTO roles (rol) VALUES (?)'
            );
            $stmt->bind_param('i', $rol);
            $stmt->execute();

            $rolId = $stmt->insert_id;
            $stmt->close();
        }

        $stmt = $this->conexion->prepare(
            'SELECT id FROM roles_asignados WHERE id_usuario = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila) {
            $stmt = $this->conexion->prepare(
                'UPDATE roles_asignados SET id_rol = ? WHERE id_usuario = ?'
            );
        } else {
            $stmt = $this->conexion->prepare(
                'INSERT INTO roles_asignados (id_rol, id_usuario) VALUES (?, ?)'
            );
        }

        $stmt->bind_param('ii', $rolId, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function delete(int $id){
        $stmt = $this->conexion->prepare(
            'DELETE FROM roles_asignados WHERE id_usuario = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conexion->prepare(
            'DELETE FROM partidas WHERE id_usuario = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conexion->prepare(
            'DELETE FROM usuarios WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    private function filaADto(array $fila): UsuarioDTO {
        return new UsuarioDTO(
            (int) $fila['id'],
            $fila['user'],
            (int) $fila['edad'],
            $fila['email'],
            $fila['pass'],
            (int) $fila['rol']
        );
    }
}
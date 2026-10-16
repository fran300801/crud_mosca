<?php

class MoscaDAO {
    private mysqli $conexion;

    public function __construct(mysqli $conexion) {
        $this->conexion = $conexion;
    }

    public function begin() {
        $this->conexion->begin_transaction();
    }

    public function commit() {
        $this->conexion->commit();
    }

    public function rollback() {
        $this->conexion->rollback();
    }

    public function obtenerTableroActivo(int $usuarioId, bool $bloquear = false): ?Tablero {
        $sql = "SELECT id, tablero, intentos, activo, id_usuario
                FROM partidas
                WHERE id_usuario = ? AND activo = 1
                LIMIT 1";

        if ($bloquear) {
            $sql .= " FOR UPDATE";
        }

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($fila === null) {
            return null;
        }

        return Tablero::desdeFila($fila);
    }

    public function crearPartida(int $usuarioId, Tablero $tablero): int {
        $sql = "INSERT INTO partidas (tablero, intentos, activo, id_usuario)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        $json = $tablero->toJsonData();
        $intentos = $tablero->intentos;
        $activo = $tablero->activo ? 1 : 0;

        $stmt->bind_param('siii', $json, $intentos, $activo, $usuarioId);
        $stmt->execute();

        $id = $stmt->insert_id;

        $stmt->close();

        return $id;
    }

    public function actualizarPartida(Tablero $tablero) {
        $sql = "UPDATE partidas
                SET tablero = ?, intentos = ?, activo = ?
                WHERE id = ?";

        $stmt = $this->conexion->prepare($sql);

        $json = $tablero->toJsonData();
        $intentos = $tablero->intentos;
        $activo = $tablero->activo ? 1 : 0;
        $id = $tablero->id;

        $stmt->bind_param('siii', $json, $intentos, $activo, $id);
        $stmt->execute();

        $stmt->close();
    }
}
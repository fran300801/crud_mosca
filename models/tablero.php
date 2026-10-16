<?php

class Tablero {
    public ?int $id = null;
    public int $usuarioId;
    public int $tamano;
    public int $intentosIniciales;
    public int $intentos;
    public int $posMosca;
    public bool $activo = true;

    public function __construct(int $tamano = 10, int $intentos = 5) {
        $this->tamano = $tamano;
        $this->intentosIniciales = $intentos;
        $this->intentos = $intentos;
    }

    public function colocarMosca() {
        $this->posMosca = random_int(1, $this->tamano);
    }

    private function revolotear() {
        do {
            $nueva = random_int(1, $this->tamano);
        } while ($nueva === $this->posMosca);

        $this->posMosca = $nueva;
    }

    public function darManotazo(int $casilla): string {
        $this->intentos--;

        if ($casilla === $this->posMosca) {
            $this->activo = false;
            return 'cazada';
        }

        $cerca = abs($casilla - $this->posMosca) === 1;

        if ($this->intentos <= 0) {
            $this->activo = false;
        } elseif ($cerca) {
            $this->revolotear();
        }

        return $cerca ? 'casi le das' : 'ni se entera';
    }

    public function rendirse() {
        $this->activo = false;
    }

    public function estaActivo(): bool {
        return $this->activo;
    }

    public function toJsonData(): string {
        return json_encode([
            'tamano' => $this->tamano,
            'intentos_iniciales' => $this->intentosIniciales,
            'pos_mosca' => $this->posMosca,
            'activo' => $this->activo
        ]);
    }

    public static function desdeFila(array $fila): Tablero {
        $datos = json_decode($fila['tablero'], true) ?? [
            'tamano' => 10,
            'intentos_iniciales' => 5,
            'pos_mosca' => 1,
            'activo' => ($fila['activo'] == 1)
        ];

        $tablero = new Tablero(
            (int) $datos['tamano'],
            (int) $datos['intentos_iniciales']
        );

        $tablero->id = (int) $fila['id'];
        $tablero->usuarioId = (int) $fila['id_usuario'];
        $tablero->intentos = (int) $fila['intentos'];
        $tablero->posMosca = (int) $datos['pos_mosca'];
        $tablero->activo = (bool) ($datos['activo'] ?? ($fila['activo'] == 1));

        return $tablero;
    }
}
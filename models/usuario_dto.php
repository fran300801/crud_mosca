<?php

class UsuarioDTO {
    private ?int $id;
    private string $user;
    private int $edad;
    private string $email;
    private string $pass;
    private int $rol; // 0 = JUGADOR, 1 = ADMIN

    public function __construct(?int $id, string $user, int $edad, string $email, string $pass, int $rol = 0) {
        $this->id = $id;
        $this->user = $user;
        $this->edad = $edad;
        $this->email = $email;
        $this->pass = $pass;
        $this->rol = $rol;
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): string { return $this->user; }
    public function getEdad(): int { return $this->edad; }
    public function getEmail(): string { return $this->email; }
    public function getPass(): string { return $this->pass; }
    public function getRol(): int { return $this->rol; }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'user' => $this->user,
            'edad' => $this->edad,
            'email' => $this->email,
            'rol' => $this->rol
        ];
    }
}
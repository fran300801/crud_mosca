# La Mosca - API REST en PHP

API REST hecha con PHP "vanilla" (sin frameworks) para el juego de la mosca: hay un tablero de casillas, una mosca escondida en una de ellas y un número limitado de intentos para darle un manotazo. Incluye gestión de usuarios con roles (jugador y administrador).

## Índice

1. [Instalación](#1-instalación)
2. [Configuración de la base de datos](#2-configuración-de-la-base-de-datos)
3. [Arrancar la API](#3-arrancar-la-api)
4. [Usuarios de prueba](#4-usuarios-de-prueba)
5. [Cómo autenticarse](#5-cómo-autenticarse)
6. [Cómo probar los endpoints](#6-cómo-probar-los-endpoints)
7. [Referencia de endpoints](#7-referencia-de-endpoints)

---

## 1. Instalación

### Requisitos

- PHP 7.4 o superior, con la extensión `mysqli` activada.
- MySQL o MariaDB.
- Postman (opcional, para lanzar la colección de pruebas).

### Pasos

1. Clona o descarga el proyecto:

   ```bash
   git clone <url-del-repositorio>
   cd <carpeta-del-proyecto>
   ```

2. Estructura esperada del proyecto:

   ```
   /
   ├── index.php            # Enrutador: recibe todas las peticiones
   ├── .env                 # Configuración privada (no se sube a git)
   ├── Controllers/
   │   ├── auxiliar.php
   │   ├── controladorUsuario.php
   │   └── controladorMosca.php
   ├── Models/
   │   ├── tablero.php
   │   └── usuario_dto.php
   └── Database/
       ├── conexion.php
       ├── usuario_dao.php
       └── mosca_dao.php
   ```

3. Crea el archivo `.env` en la raíz del proyecto (al lado de `index.php`):

   ```ini
   URL_DB=localhost
   USER_DB=root
   PASS_DB=
   NAME_DB=mosca

   # Solo necesarias para la ruta /olvido (recuperar contraseña por correo)
   EMAIL=tu_cuenta@gmail.com
   SMTP_PASSWORD=contraseña_de_aplicación_de_gmail
   ```

   - `URL_DB`, `USER_DB`, `PASS_DB` y `NAME_DB` son los datos de conexión a MySQL.
   - `EMAIL` y `SMTP_PASSWORD` se usan para enviar correos con Gmail (`smtp.gmail.com`). `SMTP_PASSWORD` debe ser una **contraseña de aplicación** de Google, no la contraseña normal de la cuenta.
   - Añade `.env` a tu `.gitignore` para no subir credenciales al repositorio.

---

## 2. Configuración de la base de datos

1. Crea la base de datos y las tablas. Este es el esquema que espera el código:

   ```sql
   CREATE DATABASE IF NOT EXISTS mosca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE mosca;

   CREATE TABLE usuarios (
       id    INT AUTO_INCREMENT PRIMARY KEY,
       `user` VARCHAR(50)  NOT NULL UNIQUE,
       edad  INT           NOT NULL,
       email VARCHAR(100)  NOT NULL UNIQUE,
       pass  VARCHAR(32)   NOT NULL            -- hash MD5
   );

   CREATE TABLE roles (
       id  INT AUTO_INCREMENT PRIMARY KEY,
       rol TINYINT NOT NULL UNIQUE             -- 0 = JUGADOR, 1 = ADMIN
   );

   CREATE TABLE roles_asignados (
       id         INT AUTO_INCREMENT PRIMARY KEY,
       id_rol     INT NOT NULL,
       id_usuario INT NOT NULL,
       FOREIGN KEY (id_rol)     REFERENCES roles(id),
       FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
   );

   CREATE TABLE partidas (
       id         INT AUTO_INCREMENT PRIMARY KEY,
       tablero    TEXT NOT NULL,               -- JSON con tamaño, posición de la mosca, etc.
       intentos   INT NOT NULL,
       activo     TINYINT(1) NOT NULL DEFAULT 1,
       id_usuario INT NOT NULL,
       FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
   );
   ```

   > La columna `tablero` debe ser `TEXT` o `JSON`. Si es un `VARCHAR` demasiado corto, el JSON se corta y la partida no se lee bien.

2. Inserta los roles y los usuarios de prueba (ver [Usuarios de prueba](#4-usuarios-de-prueba)):

   ```sql
   INSERT INTO roles (rol) VALUES (0), (1);

   INSERT INTO usuarios (`user`, edad, email, pass)
   VALUES ('admin', 30, 'admin@test.com', MD5('admin123'));
   INSERT INTO roles_asignados (id_rol, id_usuario)
   VALUES ((SELECT id FROM roles WHERE rol = 1), LAST_INSERT_ID());

   INSERT INTO usuarios (`user`, edad, email, pass)
   VALUES ('jugador', 25, 'jugador@test.com', MD5('jugador123'));
   INSERT INTO roles_asignados (id_rol, id_usuario)
   VALUES ((SELECT id FROM roles WHERE rol = 0), LAST_INSERT_ID());
   ```

> El registro por la API (`/register`) solo crea jugadores. **El primer administrador siempre hay que crearlo a mano en la base de datos**, como en el script de arriba. Después, ese administrador puede crear o ascender a otros desde la API.

---

## 3. Arrancar la API

### Con el servidor integrado de PHP

Desde la raíz del proyecto:

```bash
php -S localhost:8000 index.php
```

La API queda disponible en `http://localhost:8000`. Pasar `index.php` como último argumento hace que todas las rutas (`/login`, `/jugar/1`, etc.) se redirijan a él.

### Con Apache (XAMPP, WAMP...)

Apunta el `DocumentRoot` (o un virtualhost) a la carpeta del proyecto y añade un `.htaccess` que redirija todas las peticiones a `index.php`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```

La API debe servirse desde la raíz del host, porque el enrutador compara la ruta completa (`register`, `login`, `usuarios/1`...).

---

## 4. Usuarios de prueba

Con el script SQL del apartado 2 se crean:

| Usuario   | Contraseña   | Rol            | Qué puede hacer                         |
|-----------|--------------|----------------|-----------------------------------------|
| `admin`   | `admin123`   | ADMIN (1)      | Gestionar usuarios y roles. No puede jugar. |
| `jugador` | `jugador123` | JUGADOR (0)    | Crear tableros y jugar. No puede gestionar usuarios. |

Además, cualquiera puede registrarse como jugador con `POST /register`.

---

## 5. Cómo autenticarse

La API **no usa tokens ni sesiones**. El flujo es:

1. Hacer `POST /login` con `user` y `pass`.
2. La respuesta incluye el `id` del usuario:

   ```json
   {
     "ok": true,
     "mensaje": "Login correcto",
     "usuario": { "id": 2, "user": "jugador", "edad": 25, "email": "jugador@test.com", "rol": 0 }
   }
   ```

3. Ese `id` se pone en la URL de las demás peticiones para identificar quién actúa:
   - Jugador: `/crear/{id}`, `/jugar/{id}`, `/intentos/{id}`, `/rendirse/{id}`.
   - Administrador: `/usuarios/{idAdmin}/...`.

Según el `rol` de ese usuario, la API permite o rechaza la acción (`403` si no tiene permisos, `404` si el id no existe).

> Limitación conocida: como no hay token, quien conozca el id de un administrador puede actuar como él. Es una API de práctica, no está pensada para producción.

---

## 6. Cómo probar los endpoints

### Con Postman (recomendado)

1. Importa `La_Mosca_reducida.postman_collection.json` en Postman.
2. Revisa las variables de la colección (pestaña *Variables*):
   - `base_url`: por defecto `http://localhost:8000`.
   - `admin_user` y `admin_pass`: credenciales del administrador creado en la base de datos (`admin` / `admin123`).
3. Abre el **Collection Runner**, selecciona la colección y ejecútala **en orden**. Las peticiones guardan variables (`admin_id`, `jugador_id`...) que usan las siguientes.

Notas:

- La petición "Golpear hasta cazar la mosca" se repite sola en el Runner hasta que sale `cazada`. Si la lanzas a mano, reenvíala hasta que aparezca.
- "Golpe válido" juega la casilla 50 de un tablero de 100, así que hay un 1% de probabilidad de cazar la mosca a la primera y romper las pruebas siguientes. Si ocurre, vuelve a ejecutar la colección.

### Con curl

```bash
# Registro
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/json" \
  -d '{"user":"ana","edad":22,"email":"ana@test.com","pass":"secreta123"}'

# Login
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"user":"jugador","pass":"jugador123"}'

# Crear un tablero (usuario con id 2)
curl -X POST http://localhost:8000/crear/2 \
  -H "Content-Type: application/json" \
  -d '{"tamano":10,"intentos":5}'

# Dar un manotazo a la casilla 4
curl -X POST http://localhost:8000/jugar/2 \
  -H "Content-Type: application/json" \
  -d '{"casilla":4}'

# Consultar intentos restantes
curl http://localhost:8000/intentos/2

# Rendirse
curl -X PUT http://localhost:8000/rendirse/2

# Listar usuarios como administrador (admin con id 1)
curl http://localhost:8000/usuarios/1
```

---

## 7. Referencia de endpoints

Todas las peticiones con body usan JSON (`Content-Type: application/json`) y todas las respuestas son JSON con el campo `ok` (`true` o `false`). En caso de error, la respuesta incluye `error` con el mensaje.

### Cuentas

| Método | Ruta       | Body                                  | Descripción |
|--------|------------|---------------------------------------|-------------|
| POST   | `/register`| `user`, `edad`, `email`, `pass`       | Registra un usuario con rol JUGADOR. |
| POST   | `/login`   | `user`, `pass`                        | Comprueba las credenciales y devuelve el usuario. |
| POST   | `/olvido`  | `email`                               | Envía una contraseña provisional por correo. |

### Gestión de usuarios (solo administrador)

`{idAdmin}` es el id de un usuario con rol ADMIN.

| Método | Ruta                               | Body                                         | Descripción |
|--------|------------------------------------|----------------------------------------------|-------------|
| GET    | `/usuarios/{idAdmin}`              | -                                            | Lista todos los usuarios. |
| POST   | `/usuarios/{idAdmin}`              | `user`, `edad`, `email`, `pass`, `rol` (0/1) | Crea un usuario con el rol indicado. |
| GET    | `/usuarios/{idAdmin}/{id}`         | -                                            | Obtiene un usuario. |
| PUT    | `/usuarios/{idAdmin}/{id}`         | `edad` y/o `pass`                            | Modifica un usuario. |
| PUT    | `/usuarios/{idAdmin}/{id}/rol`     | `rol` (0/1)                                  | Cambia el rol de un usuario. |
| DELETE | `/usuarios/{idAdmin}/{id}`         | -                                            | Elimina un usuario. |

Un administrador no puede eliminarse a sí mismo ni quitarse su propio rol.

### Juego (solo jugadores)

`{id}` es el id de un usuario con rol JUGADOR. Un administrador no puede jugar.

| Método | Ruta              | Body                                   | Descripción |
|--------|-------------------|----------------------------------------|-------------|
| POST   | `/crear/{id}`     | `tamano` (3-100, def. 10), `intentos` (1-100, def. 5) | Crea un tablero. Solo se permite uno activo a la vez. |
| POST   | `/jugar/{id}`     | `casilla` (1 a `tamano`)               | Da un manotazo. Resta un intento. |
| GET    | `/intentos/{id}`  | -                                      | Devuelve los intentos restantes. |
| PUT    | `/rendirse/{id}`  | -                                      | Abandona la partida y revela dónde estaba la mosca. |

Resultados posibles de `/jugar`:

- `cazada`: has acertado, la partida termina y se revela la posición de la mosca.
- `casi le das`: la mosca estaba en una casilla contigua; si quedan intentos, la mosca cambia de casilla.
- `ni se entera`: has fallado por más de una casilla.

La partida termina al cazar la mosca, al rendirse o al quedarse sin intentos. Después hay que crear un tablero nuevo para seguir jugando.

### Códigos de respuesta

| Código | Significado |
|--------|-------------|
| 200    | Correcto. |
| 201    | Recurso creado (usuario o tablero). |
| 400    | Faltan datos obligatorios. |
| 401    | Usuario o contraseña incorrectos. |
| 403    | No tienes permisos (p. ej. un jugador gestionando usuarios, o un admin jugando). |
| 404    | Ruta, usuario o tablero activo no encontrado. |
| 405    | Método HTTP no permitido. |
| 409    | Conflicto: usuario o email duplicado, o ya existe un tablero activo. |
| 422    | Datos con formato inválido o fuera de rango. |
| 500    | Error del servidor o de la base de datos. |

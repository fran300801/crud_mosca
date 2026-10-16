# 🪰 Documentación de la API - Juego "La Mosca"

Esta es la documentación técnica y guía de referencia para los endpoints de la API del juego **La Mosca**, desarrollada en PHP con arquitectura MVC / DAO y soporte para roles de usuario (Jugadores y Administradores).

---

## 📋 Índice
1. [Información General](#información-general)
2. [Estructura de Roles](#estructura-de-roles)
3. [Formatos de Respuesta](#formatos-de-respuesta)
4. [Endpoints Públicos / Autenticación](#1-autenticación-y-usuarios-públicos)
5. [Endpoints de Juego (Jugadores)](#2-gestión-del-juego-jugadores)
6. [Endpoints de Administración (Administradores)](#3-gestión-de-usuarios-administrador)

---

## ℹ️ Información General

* **Formato de Comunicación:** `JSON` (`Content-Type: application/json`).
* **Manejo de Errores:** Todos los errores retornan un objeto JSON con `ok: false` y un mensaje explicativo en la propiedad `error`.
* **Codificación de Contraseñas:** `MD5` (en base de datos).

---

## 👥 Estructura de Roles

| ID Rol | Nombre | Permisos |
| :---: | :---: | :--- |
| `0` | **Jugador** | Crear tableros, realizar intentos/manotazos, consultar intentos y rendirse. No puede acceder al panel de administración. |
| `1` | **Admin** | Crear, consultar, modificar, cambiar rol y eliminar usuarios. No puede participar en partidas. |

---

## 📩 Formatos de Respuesta

### Respuesta Exitosa Generales
```json
{
  "ok": true,
  "mensaje": "Descripción de la operación exitosa"
}
```

### Respuesta de Error
```json
{
  "ok": false,
  "error": "Descripción del error cometido"
}
```

---

## 1. Autenticación y Usuarios Públicos

### 🔹 Registro de Usuario
Registra un nuevo usuario en la plataforma. Por defecto, todo registro público asigna el rol de **JUGADOR** (`rol = 0`).

* **Método:** `POST`
* **Ruta:** `/register`
* **Cuerpo de la Petición (JSON):**
  ```json
  {
    "user": "jugador1",
    "edad": 25,
    "email": "jugador1@email.com",
    "pass": "123456"
  }
  ```
* **Respuestas:**
  * **`201 Created`**
    ```json
    {
      "ok": true,
      "mensaje": "Usuario registrado correctamente como JUGADOR",
      "usuario": {
        "id": 5,
        "user": "jugador1",
        "edad": 25,
        "email": "jugador1@email.com",
        "rol": 0
      }
    }
    ```
  * **`400 Bad Request`**: Faltan datos requeridos.
  * **`409 Conflict`**: El nombre de usuario o correo electrónico ya existen.
  * **`422 Unprocessable Entity`**: Validación fallida (Edad fuera de rango 1-120, formato de email inválido o contraseña menor a 6 caracteres).

---

### 🔹 Iniciar Sesión (Login)
Valida las credenciales de un usuario.

* **Método:** `POST`
* **Ruta:** `/login`
* **Cuerpo de la Petición (JSON):**
  ```json
  {
    "user": "jugador1",
    "pass": "123456"
  }
  ```
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "mensaje": "Login correcto",
      "usuario": {
        "id": 2,
        "user": "jugador1",
        "edad": 40,
        "email": "jugador1@email.com",
        "rol": 0
      }
    }
    ```
  * **`400 Bad Request`**: Faltan parámetros `user` o `pass`.
  * **`401 Unauthorized`**: Usuario o contraseña incorrectos.

---

### 🔹 Recuperar Contraseña
Genera una contraseña provisional y la envía por correo electrónico mediante SMTP.

* **Método:** `POST`
* **Ruta:** `/olvido`
* **Cuerpo de la Petición (JSON):**
  ```json
  {
    "email": "jugador1@email.com"
  }
  ```
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "mensaje": "Si el email está registrado, recibirás una nueva contraseña"
    }
    ```
  * **`400 Bad Request`**: No se especificó el correo electrónico.
  * **`422 Unprocessable Entity`**: El formato del correo no es válido.
  * **`500 Internal Server Error`**: Falso envío SMTP / Error de servidor.

---

## 2. Gestión del Juego (Jugadores)

*Todas las rutas del juego requieren que el ID del usuario pertenezca a un perfil de tipo **Jugador** (`rol = 0`).*

### 🔹 Crear Tablero
Crea un nuevo tablero de juego activo para un jugador.

* **Método:** `POST`
* **Ruta:** `/crear/{id_usuario}`
* **Cuerpo de la Petición (JSON - Opcional):**
  ```json
  {
    "tamano": 10,
    "intentos": 5
  }
  ```
  *(Valores por defecto si se omiten: `tamano: 10`, `intentos: 5`)*

* **Respuestas:**
  * **`201 Created`**
    ```json
    {
      "ok": true,
      "mensaje": "Tablero creado correctamente",
      "tablero": {
        "id": 11,
        "tamano": 10,
        "intentos_restantes": 5,
        "activo": true
      }
    }
    ```
  * **`403 Forbidden`**: El usuario indicado es Administrador.
  * **`409 Conflict`**: El usuario ya posee un tablero activo sin terminar.
  * **`422 Unprocessable Entity`**: El tamaño debe estar entre 3 y 100, y los intentos entre 1 y 100.

---

### 🔹 Jugar (Dar Manotazo)
Realiza un intento indicando la posición o casilla a la que se desea golpear.

* **Método:** `POST`
* **Ruta:** `/jugar/{id_usuario}`
* **Cuerpo de la Petición (JSON):**
  ```json
  {
    "casilla": 4
  }
  ```
* **Respuestas:**
  * **`200 OK` (Partida continua):**
    ```json
    {
      "ok": true,
      "resultado": "casi le das",
      "intentos_restantes": 4,
      "activo": true
    }
    ```
    *Posibles resultados:* `"cazada"`, `"casi le das"` (la mosca revolotea a otra casilla), `"ni se entera"`.

  * **`200 OK` (Fin de partida - Ganada o Perdida):**
    ```json
    {
      "ok": true,
      "resultado": "cazada",
      "intentos_restantes": 3,
      "activo": false,
      "mosca": 4
    }
    ```
  * **`400 Bad Request`**: Falta indicar el parámetro `casilla`.
  * **`404 Not Found`**: El jugador no tiene tableros activos.
  * **`422 Unprocessable Entity`**: La casilla está fuera del rango permitido del tablero.

---

### 🔹 Ver Intentos Restantes
Muestra los intentos que le quedan al jugador en su partida activa.

* **Método:** `GET`
* **Ruta:** `/intentos/{id_usuario}`
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "intentos_restantes": 3
    }
    ```
  * **`404 Not Found`**: El usuario no existe o no posee ningún tablero activo.

---

### 🔹 Rendirse
Permite finalizar voluntariamente la partida activa actual.

* **Método:** `PUT`
* **Ruta:** `/rendirse/{id_usuario}`
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "activo": false,
      "mosca": 7
    }
    ```
  * **`404 Not Found`**: No hay ningún tablero activo para el usuario especificado.

---

## 3. Gestión de Usuarios (Administrador)

*Todas las rutas administrativas requieren que el `{id_admin}` pertenezca a un usuario con rol de **Administrador** (`rol = 1`).*

### 🔹 Listar Usuarios
Obtiene la lista completa de usuarios registrados.

* **Método:** `GET`
* **Ruta:** `/usuarios/{id_admin}`
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "usuarios": [
        {
          "id": 2,
          "user": "jugador1",
          "edad": 40,
          "email": "jugador1@email.com",
          "rol": 0
        },
        {
          "id": 4,
          "user": "admin",
          "edad": 25,
          "email": "admin@gmail.com",
          "rol": 1
        }
      ]
    }
    ```
  * **`403 Forbidden`**: El usuario `{id_admin}` no tiene privilegios de administrador.
  * **`404 Not Found`**: Administrador no encontrado.

---

### 🔹 Crear Usuario desde Administración
Permite a un administrador crear usuarios y definir directamente su rol.

* **Método:** `POST`
* **Ruta:** `/usuarios/{id_admin}`
* **Cuerpo de la Petición (JSON):**
  ```json
  {
    "user": "nuevoAdmin",
    "edad": 30,
    "email": "nuevoadmin@email.com",
    "pass": "admin123",
    "rol": 1
  }
  ```
  *(Propiedad `rol`: `0` para Jugador, `1` para Administrador)*

* **Respuestas:**
  * **`201 Created`**
    ```json
    {
      "ok": true,
      "mensaje": "Usuario creado correctamente por el administrador",
      "usuario": {
        "id": 6,
        "user": "nuevoAdmin",
        "edad": 30,
        "email": "nuevoadmin@email.com",
        "rol": 1
      }
    }
    ```
  * **`409 Conflict`**: El nombre de usuario o correo ya está registrado.
  * **`422 Unprocessable Entity`**: El rol asignado no es válido (`0` o `1`).

---

### 🔹 Obtener un Usuario por ID
Obtiene la información de un usuario específico.

* **Método:** `GET`
* **Ruta:** `/usuarios/{id_admin}/{id_usuario}`
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "usuario": {
        "id": 3,
        "user": "jugador2",
        "edad": 25,
        "email": "jugador2@gmail.com",
        "rol": 0
      }
    }
    ```
  * **`404 Not Found`**: El `{id_usuario}` solicitado no existe.

---

### 🔹 Modificar Usuario
Permite modificar la edad y/o contraseña de un usuario determinado.

* **Método:** `PUT`
* **Ruta:** `/usuarios/{id_admin}/{id_usuario}`
* **Cuerpo de la Petición (JSON - Opcional según lo a modificar):**
  ```json
  {
    "edad": 28,
    "pass": "nuevaClave123"
  }
  ```
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "mensaje": "Usuario modificado correctamente"
    }
    ```
  * **`422 Unprocessable Entity`**: Formato de edad o longitud de contraseña inválida.

---

### 🔹 Cambiar Rol de Usuario
Asigna un nuevo rol a un usuario determinado.

* **Método:** `PUT`
* **Ruta:** `/usuarios/{id_admin}/{id_usuario}/rol`
* **Cuerpo de la Petición (JSON):**
  ```json
  {
    "rol": 1
  }
  ```
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "mensaje": "Rol modificado correctamente",
      "rol_nuevo": 1
    }
    ```
  * **`403 Forbidden`**: Un administrador intenta quitarse a sí mismo su propio rol.
  * **`422 Unprocessable Entity`**: Rol no válido (`0` o `1`).

---

### 🔹 Eliminar Usuario
Elimina a un usuario, así como todos sus roles asignados y partidas asociadas.

* **Método:** `DELETE`
* **Ruta:** `/usuarios/{id_admin}/{id_usuario}`
* **Respuestas:**
  * **`200 OK`**
    ```json
    {
      "ok": true,
      "mensaje": "Usuario eliminado correctamente"
    }
    ```
  * **`403 Forbidden`**: Un administrador intenta eliminar su propia cuenta.
  * **`404 Not Found`**: Usuario a eliminar no encontrado.
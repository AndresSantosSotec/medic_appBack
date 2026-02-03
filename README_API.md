# Clinic Flow API - Backend Laravel

API REST para el sistema de gestión de clínicas MedicApp.

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL 8.0 o superior
- Laravel 12

## Instalación

1. Clonar el repositorio
2. Instalar dependencias:
```bash
composer install
```

3. Configurar el archivo `.env`:
```env
APP_NAME="Clinic Flow API"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medicapp
DB_USERNAME=root
DB_PASSWORD=
```

4. Generar la key de la aplicación:
```bash
php artisan key:generate
```

5. Ejecutar las migraciones:
```bash
php artisan migrate
```

6. (Opcional) Ejecutar los seeders para datos de prueba:
```bash
php artisan db:seed
```

## Ejecutar el servidor

```bash
php artisan serve
```

El API estará disponible en: `http://localhost:8000`

## Endpoints Principales

### Autenticación

- **POST** `/api/register` - Registrar nuevo usuario
- **POST** `/api/login` - Iniciar sesión
- **POST** `/api/logout` - Cerrar sesión (requiere autenticación)

### Branches (Sucursales)

- **GET** `/api/branches` - Listar todas las sucursales
- **POST** `/api/branches` - Crear una nueva sucursal
- **GET** `/api/branches/{id}` - Obtener detalle de una sucursal
- **PUT/PATCH** `/api/branches/{id}` - Actualizar una sucursal
- **DELETE** `/api/branches/{id}` - Eliminar una sucursal

### Doctors (Doctores)

- **GET** `/api/doctors` - Listar todos los doctores
- **POST** `/api/doctors` - Crear un nuevo doctor
- **GET** `/api/doctors/{id}` - Obtener detalle de un doctor
- **PUT/PATCH** `/api/doctors/{id}` - Actualizar un doctor
- **DELETE** `/api/doctors/{id}` - Eliminar un doctor

### Patients (Pacientes)

- **GET** `/api/patients` - Listar todos los pacientes
- **POST** `/api/patients` - Crear un nuevo paciente
- **GET** `/api/patients/{id}` - Obtener detalle de un paciente
- **PUT/PATCH** `/api/patients/{id}` - Actualizar un paciente
- **DELETE** `/api/patients/{id}` - Eliminar un paciente

### Appointments (Citas)

- **GET** `/api/appointments` - Listar todas las citas
- **POST** `/api/appointments` - Crear una nueva cita
- **GET** `/api/appointments/{id}` - Obtener detalle de una cita
- **PUT/PATCH** `/api/appointments/{id}` - Actualizar una cita
- **DELETE** `/api/appointments/{id}` - Eliminar una cita

### Payments (Pagos)

- **GET** `/api/payments` - Listar todos los pagos
- **POST** `/api/payments` - Registrar un nuevo pago
- **GET** `/api/payments/{id}` - Obtener detalle de un pago
- **PUT/PATCH** `/api/payments/{id}` - Actualizar un pago
- **DELETE** `/api/payments/{id}` - Eliminar un pago

### Reminders (Recordatorios)

- **GET** `/api/reminders` - Listar todos los recordatorios
- **POST** `/api/reminders` - Crear un nuevo recordatorio
- **GET** `/api/reminders/{id}` - Obtener detalle de un recordatorio
- **PUT/PATCH** `/api/reminders/{id}` - Actualizar un recordatorio
- **DELETE** `/api/reminders/{id}` - Eliminar un recordatorio

## Autenticación

Este API usa Laravel Sanctum para la autenticación. Para acceder a los endpoints protegidos:

1. Hacer login con `/api/login`
2. Usar el token recibido en el header de las peticiones:
```
Authorization: Bearer {token}
```

## Ejemplo de uso con cURL

### Registrar un usuario
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin",
    "email": "admin@medicapp.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@medicapp.com",
    "password": "password123"
  }'
```

### Crear un paciente (con token)
```bash
curl -X POST http://localhost:8000/api/patients \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {tu_token}" \
  -d '{
    "first_name": "Juan",
    "last_name": "Pérez",
    "date_of_birth": "1990-05-15",
    "gender": "male",
    "phone": "555-1234",
    "email": "juan.perez@example.com"
  }'
```

## Base de Datos

La aplicación crea las siguientes tablas:

- **branches** - Sucursales de la clínica
- **doctors** - Doctores
- **patients** - Pacientes
- **appointments** - Citas médicas
- **payments** - Pagos
- **reminders** - Recordatorios de citas

## Configuración CORS

Para permitir que tu frontend React se conecte al API, asegúrate de configurar CORS correctamente en el archivo `config/cors.php` o usando el middleware correspondiente.

## Tecnologías Utilizadas

- Laravel 12
- Laravel Sanctum (Autenticación API)
- MySQL
- PHP 8.2+

## Desarrollo

Para ejecutar en modo desarrollo:

```bash
php artisan serve
```

Para limpiar la base de datos y reiniciar:

```bash
php artisan migrate:fresh --seed
```

## Pruebas

Para ejecutar las pruebas:

```bash
php artisan test
```

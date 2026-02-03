# Guía de Inicio Rápido - Clinic Flow API

## ✅ Backend Laravel Instalado Exitosamente

Tu API backend para MedicApp está completamente configurada y lista para usar.

### 🚀 Servidor en Ejecución

El servidor está corriendo en: **http://localhost:8000**

### 📦 Lo que se ha configurado

✅ Laravel 12 instalado
✅ Laravel Sanctum para autenticación
✅ Base de datos MySQL configurada
✅ Migraciones ejecutadas
✅ 6 Modelos creados (Branch, Doctor, Patient, Appointment, Payment, Reminder)
✅ 6 Controladores API con CRUD completo
✅ Rutas API configuradas
✅ Seeder con datos de prueba

### 🗄️ Estructura de la Base de Datos

Las siguientes tablas fueron creadas:

1. **branches** - Sucursales de la clínica
2. **doctors** - Médicos
3. **patients** - Pacientes
4. **appointments** - Citas médicas
5. **payments** - Pagos
6. **reminders** - Recordatorios

### 🔑 Datos de Prueba

Para cargar datos de prueba en la base de datos, ejecuta:

```bash
php artisan db:seed
```

Esto creará:
- 1 usuario administrador
- 2 sucursales
- 3 doctores
- 3 pacientes
- 3 citas
- 2 pagos

**Credenciales de prueba:**
- Email: `admin@medicapp.com`
- Password: `password123`

### 📡 Endpoints Disponibles

#### Autenticación (Públicos)
```
POST /api/register - Registrar usuario
POST /api/login - Iniciar sesión
```

#### Endpoints Protegidos (Requieren token)
```
GET    /api/branches          - Listar sucursales
POST   /api/branches          - Crear sucursal
GET    /api/branches/{id}     - Ver sucursal
PUT    /api/branches/{id}     - Actualizar sucursal
DELETE /api/branches/{id}     - Eliminar sucursal

GET    /api/doctors           - Listar doctores
POST   /api/doctors           - Crear doctor
GET    /api/doctors/{id}      - Ver doctor
PUT    /api/doctors/{id}      - Actualizar doctor
DELETE /api/doctors/{id}      - Eliminar doctor

GET    /api/patients          - Listar pacientes
POST   /api/patients          - Crear paciente
GET    /api/patients/{id}     - Ver paciente
PUT    /api/patients/{id}     - Actualizar paciente
DELETE /api/patients/{id}     - Eliminar paciente

GET    /api/appointments      - Listar citas
POST   /api/appointments      - Crear cita
GET    /api/appointments/{id} - Ver cita
PUT    /api/appointments/{id} - Actualizar cita
DELETE /api/appointments/{id} - Eliminar cita

GET    /api/payments          - Listar pagos
POST   /api/payments          - Crear pago
GET    /api/payments/{id}     - Ver pago
PUT    /api/payments/{id}     - Actualizar pago
DELETE /api/payments/{id}     - Eliminar pago

GET    /api/reminders         - Listar recordatorios
POST   /api/reminders         - Crear recordatorio
GET    /api/reminders/{id}    - Ver recordatorio
PUT    /api/reminders/{id}    - Actualizar recordatorio
DELETE /api/reminders/{id}    - Eliminar recordatorio
```

### 🧪 Probar el API

#### 1. Registrar un usuario (con PowerShell/curl)

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/register" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"name":"Test User","email":"test@test.com","password":"password123","password_confirmation":"password123"}'
```

#### 2. Iniciar sesión

```powershell
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/login" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"email":"admin@medicapp.com","password":"password123"}'
  
$token = $response.access_token
Write-Host "Token: $token"
```

#### 3. Listar pacientes (con el token)

```powershell
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

Invoke-RestMethod -Uri "http://localhost:8000/api/patients" `
  -Method Get `
  -Headers $headers
```

### 🔗 Conectar con el Frontend

En tu aplicación React (clinic-flow), configura la URL base del API:

```typescript
// En tu archivo de configuración o servicio API
const API_BASE_URL = 'http://localhost:8000/api';

// Ejemplo de configuración con axios
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Agregar el token a las peticiones
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### 🛠️ Comandos Útiles

```bash
# Iniciar servidor
php artisan serve

# Ver rutas disponibles
php artisan route:list

# Limpiar cache
php artisan cache:clear
php artisan config:clear

# Resetear base de datos con datos de prueba
php artisan migrate:fresh --seed

# Ver migraciones pendientes
php artisan migrate:status
```

### 📝 Próximos Pasos

1. ✅ Cargar datos de prueba: `php artisan db:seed`
2. ✅ Probar endpoints con Postman o Thunder Client
3. ✅ Configurar CORS si es necesario
4. ✅ Conectar el frontend React
5. ✅ Implementar funcionalidades adicionales

### ⚙️ Configuración Adicional

#### CORS para el Frontend

Si necesitas configurar CORS para tu frontend en otro puerto, el middleware ya está incluido en Laravel. Solo asegúrate de que tu frontend envíe las credenciales correctamente.

#### Variables de Entorno

Revisa el archivo `.env` para:
- Configuración de base de datos
- URL de la aplicación
- Configuración de correo (para recordatorios futuros)

### 🎉 ¡Todo Listo!

Tu API está completamente funcional y lista para integrarse con tu frontend React.

**Servidor corriendo en:** http://localhost:8000
**Documentación completa:** README_API.md

---

Si tienes alguna pregunta o necesitas ayuda adicional, consulta la documentación de Laravel: https://laravel.com/docs

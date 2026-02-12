<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ===== CREAR PERMISOS =====
        $permissions = [
            // Gestión de usuarios
            ['name' => 'Ver Usuarios', 'slug' => 'view-users', 'description' => 'Ver lista de usuarios'],
            ['name' => 'Crear Usuarios', 'slug' => 'create-users', 'description' => 'Crear nuevos usuarios'],
            ['name' => 'Editar Usuarios', 'slug' => 'edit-users', 'description' => 'Editar usuarios existentes'],
            ['name' => 'Eliminar Usuarios', 'slug' => 'delete-users', 'description' => 'Eliminar usuarios'],

            // Gestión de roles
            ['name' => 'Ver Roles', 'slug' => 'view-roles', 'description' => 'Ver lista de roles'],
            ['name' => 'Crear Roles', 'slug' => 'create-roles', 'description' => 'Crear nuevos roles'],
            ['name' => 'Editar Roles', 'slug' => 'edit-roles', 'description' => 'Editar roles existentes'],
            ['name' => 'Eliminar Roles', 'slug' => 'delete-roles', 'description' => 'Eliminar roles'],

            // Gestión de sucursales
            ['name' => 'Ver Sucursales', 'slug' => 'view-branches', 'description' => 'Ver lista de sucursales'],
            ['name' => 'Crear Sucursales', 'slug' => 'create-branches', 'description' => 'Crear nuevas sucursales'],
            ['name' => 'Editar Sucursales', 'slug' => 'edit-branches', 'description' => 'Editar sucursales existentes'],
            ['name' => 'Eliminar Sucursales', 'slug' => 'delete-branches', 'description' => 'Eliminar sucursales'],

            // Gestión de doctores
            ['name' => 'Ver Doctores', 'slug' => 'view-doctors', 'description' => 'Ver lista de doctores'],
            ['name' => 'Crear Doctores', 'slug' => 'create-doctors', 'description' => 'Crear nuevos doctores'],
            ['name' => 'Editar Doctores', 'slug' => 'edit-doctors', 'description' => 'Editar doctores existentes'],
            ['name' => 'Eliminar Doctores', 'slug' => 'delete-doctors', 'description' => 'Eliminar doctores'],

            // Gestión de pacientes
            ['name' => 'Ver Pacientes', 'slug' => 'view-patients', 'description' => 'Ver lista de pacientes'],
            ['name' => 'Crear Pacientes', 'slug' => 'create-patients', 'description' => 'Crear nuevos pacientes'],
            ['name' => 'Editar Pacientes', 'slug' => 'edit-patients', 'description' => 'Editar pacientes existentes'],
            ['name' => 'Eliminar Pacientes', 'slug' => 'delete-patients', 'description' => 'Eliminar pacientes'],

            // Gestión de citas
            ['name' => 'Ver Citas', 'slug' => 'view-appointments', 'description' => 'Ver lista de citas'],
            ['name' => 'Crear Citas', 'slug' => 'create-appointments', 'description' => 'Crear nuevas citas'],
            ['name' => 'Editar Citas', 'slug' => 'edit-appointments', 'description' => 'Editar citas existentes'],
            ['name' => 'Eliminar Citas', 'slug' => 'delete-appointments', 'description' => 'Eliminar citas'],

            // Gestión de pagos
            ['name' => 'Ver Pagos', 'slug' => 'view-payments', 'description' => 'Ver lista de pagos'],
            ['name' => 'Crear Pagos', 'slug' => 'create-payments', 'description' => 'Registrar nuevos pagos'],
            ['name' => 'Editar Pagos', 'slug' => 'edit-payments', 'description' => 'Editar pagos existentes'],
            ['name' => 'Eliminar Pagos', 'slug' => 'delete-payments', 'description' => 'Eliminar pagos'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // ===== CREAR ROLES =====
        $adminRole = Role::create([
            'name' => 'Administrador',
            'slug' => 'admin',
            'description' => 'Acceso completo al sistema',
        ]);

        $doctorRole = Role::create([
            'name' => 'Doctor',
            'slug' => 'doctor',
            'description' => 'Médico del sistema - acceso limitado a sus propios datos',
        ]);

        $receptionistRole = Role::create([
            'name' => 'Recepcionista',
            'slug' => 'receptionist',
            'description' => 'Personal de recepción',
        ]);

        // ===== ASIGNAR PERMISOS A ROLES =====

        // Admin tiene TODOS los permisos
        $adminRole->permissions()->attach(Permission::all());

        // Doctor puede ver y gestionar sus pacientes, citas y ver pagos
        $doctorRole->permissions()->attach(Permission::whereIn('slug', [
            'view-patients', 'create-patients', 'edit-patients',
            'view-appointments', 'create-appointments', 'edit-appointments',
            'view-payments', 'create-payments', 'edit-payments',
            'view-doctors', 'edit-doctors',
            'view-branches',
        ])->get());

        // Recepcionista puede gestionar pacientes, citas y pagos (pero no usuarios ni configuración)
        $receptionistRole->permissions()->attach(Permission::whereIn('slug', [
            'view-patients', 'create-patients', 'edit-patients',
            'view-appointments', 'create-appointments', 'edit-appointments', 'delete-appointments',
            'view-payments', 'create-payments', 'edit-payments',
            'view-doctors', 'view-branches',
        ])->get());

        // ===== CREAR USUARIOS =====

        // Usuario Administrador
        $adminUser = User::create([
            'name' => 'Admin MedicApp',
            'email' => 'admin@medicapp.com',
            'password' => Hash::make('password123'),
        ]);
        $adminUser->roles()->attach($adminRole);

        // Usuario Recepcionista
        $receptionistUser = User::create([
            'name' => 'Ana López',
            'email' => 'recepcion@medicapp.com',
            'password' => Hash::make('password123'),
        ]);
        $receptionistUser->roles()->attach($receptionistRole);

        // ===== CREAR SUCURSALES =====
        $branch1 = Branch::create([
            'code' => 'CTR',
            'name' => 'Sucursal Centro',
            'address' => 'Av. Principal 123, Centro',
            'phone' => '555-0001',
            'email' => 'centro@medicapp.com',
            'opens_at' => '07:00',
            'closes_at' => '19:00',
            'is_active' => true,
        ]);

        $branch2 = Branch::create([
            'code' => 'NTE',
            'name' => 'Sucursal Norte',
            'address' => 'Calle Norte 456, Zona 1',
            'phone' => '555-0002',
            'email' => 'norte@medicapp.com',
            'opens_at' => '08:00',
            'closes_at' => '18:00',
            'is_active' => true,
        ]);

        // ===== CREAR DOCTORES CON USUARIOS =====

        // Doctor 1 - Carlos Ramírez (Medicina General)
        $doctorUser1 = User::create([
            'name' => 'Dr. Carlos Ramírez',
            'email' => 'doctor@medicapp.com',
            'password' => Hash::make('password123'),
        ]);
        $doctorUser1->roles()->attach($doctorRole);

        $doctor1 = Doctor::create([
            'user_id' => $doctorUser1->id,
            'first_name' => 'Carlos',
            'last_name' => 'Ramírez',
            'specialty' => 'Medicina General',
            'license_number' => 'MED-001',
            'phone' => '555-1001',
            'email' => 'carlos.ramirez@medicapp.com',
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        // Doctor 2 - María González (Pediatría)
        $doctorUser2 = User::create([
            'name' => 'Dra. María González',
            'email' => 'maria.gonzalez@medicapp.com',
            'password' => Hash::make('password123'),
        ]);
        $doctorUser2->roles()->attach($doctorRole);

        $doctor2 = Doctor::create([
            'user_id' => $doctorUser2->id,
            'first_name' => 'María',
            'last_name' => 'González',
            'specialty' => 'Pediatría',
            'license_number' => 'MED-002',
            'phone' => '555-1002',
            'email' => 'maria.gonzalez@medicapp.com',
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        // Doctor 3 - José Martínez (Cardiología)
        $doctorUser3 = User::create([
            'name' => 'Dr. José Martínez',
            'email' => 'jose.martinez@medicapp.com',
            'password' => Hash::make('password123'),
        ]);
        $doctorUser3->roles()->attach($doctorRole);

        $doctor3 = Doctor::create([
            'user_id' => $doctorUser3->id,
            'first_name' => 'José',
            'last_name' => 'Martínez',
            'specialty' => 'Cardiología',
            'license_number' => 'MED-003',
            'phone' => '555-1003',
            'email' => 'jose.martinez@medicapp.com',
            'branch_id' => $branch2->id,
            'is_active' => true,
        ]);

        // ===== CREAR PACIENTES =====

        // Pacientes del Dr. Carlos (Medicina General)
        $patient1 = Patient::create([
            'first_name' => 'Juan', 'last_name' => 'Pérez',
            'date_of_birth' => '1985-03-15', 'gender' => 'Masculino',
            'phone' => '555-2001', 'email' => 'juan.perez@example.com',
            'address' => 'Calle 1, Casa 10', 'blood_type' => 'O+',
        ]);

        $patient2 = Patient::create([
            'first_name' => 'Roberto', 'last_name' => 'García',
            'date_of_birth' => '1978-11-20', 'gender' => 'Masculino',
            'phone' => '555-2004', 'email' => 'roberto.garcia@example.com',
            'address' => 'Blvd. Sur 789', 'blood_type' => 'A-',
            'allergies' => 'Penicilina',
        ]);

        $patient3 = Patient::create([
            'first_name' => 'Lucía', 'last_name' => 'Fernández',
            'date_of_birth' => '1992-06-08', 'gender' => 'Femenino',
            'phone' => '555-2005', 'email' => 'lucia.fernandez@example.com',
            'address' => 'Av. Las Flores 321', 'blood_type' => 'B+',
        ]);

        // Pacientes de la Dra. María (Pediatría)
        $patient4 = Patient::create([
            'first_name' => 'Pedro', 'last_name' => 'Sánchez',
            'date_of_birth' => '2015-12-10', 'gender' => 'Masculino',
            'phone' => '555-2003', 'email' => null,
            'address' => 'Colonia Nueva, Casa 25', 'blood_type' => 'B+',
        ]);

        $patient5 = Patient::create([
            'first_name' => 'Sofía', 'last_name' => 'Ramírez',
            'date_of_birth' => '2018-04-22', 'gender' => 'Femenino',
            'phone' => '555-2006', 'email' => null,
            'address' => 'Residencial Los Pinos 45', 'blood_type' => 'O+',
        ]);

        $patient6 = Patient::create([
            'first_name' => 'Mateo', 'last_name' => 'López',
            'date_of_birth' => '2020-08-15', 'gender' => 'Masculino',
            'phone' => '555-2007', 'email' => null,
            'address' => 'Calle 5ta Avenida 12', 'blood_type' => 'A+',
            'allergies' => 'Lactosa',
        ]);

        // Pacientes del Dr. José (Cardiología)
        $patient7 = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Martínez',
            'date_of_birth' => '1990-07-22', 'gender' => 'Femenino',
            'phone' => '555-2002', 'email' => 'ana.martinez@example.com',
            'address' => 'Avenida 2, Edificio 5', 'blood_type' => 'A+',
        ]);

        $patient8 = Patient::create([
            'first_name' => 'Fernando', 'last_name' => 'Castillo',
            'date_of_birth' => '1965-01-30', 'gender' => 'Masculino',
            'phone' => '555-2008', 'email' => 'fernando.castillo@example.com',
            'address' => 'Col. Médica 67', 'blood_type' => 'AB+',
            'allergies' => 'Aspirina, Ibuprofeno',
            'medical_history' => 'Hipertensión arterial desde 2010. Infarto leve en 2019.',
        ]);

        $patient9 = Patient::create([
            'first_name' => 'Gloria', 'last_name' => 'Vásquez',
            'date_of_birth' => '1958-09-14', 'gender' => 'Femenino',
            'phone' => '555-2009', 'email' => 'gloria.v@example.com',
            'address' => 'Zona 10, Edificio Médico 3-B', 'blood_type' => 'O-',
            'medical_history' => 'Arritmia cardíaca diagnosticada en 2015.',
        ]);

        // ===== ASIGNAR PACIENTES A DOCTORES (tabla pivote doctor_patient) =====

        // Dr. Carlos tiene 3 pacientes
        $doctor1->patients()->attach([$patient1->id, $patient2->id, $patient3->id]);

        // Dra. María tiene 3 pacientes (pediátricos)
        $doctor2->patients()->attach([$patient4->id, $patient5->id, $patient6->id]);

        // Dr. José tiene 3 pacientes (cardiología)
        $doctor3->patients()->attach([$patient7->id, $patient8->id, $patient9->id]);

        // ===== CREAR CITAS =====

        // Citas del Dr. Carlos
        $apt1 = Appointment::create([
            'patient_id' => $patient1->id, 'doctor_id' => $doctor1->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->addDays(1)->setTime(9, 0),
            'duration' => 30, 'status' => 'scheduled',
            'reason' => 'Consulta general', 'notes' => 'Primera consulta del paciente',
        ]);

        $apt2 = Appointment::create([
            'patient_id' => $patient2->id, 'doctor_id' => $doctor1->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->addDays(1)->setTime(10, 0),
            'duration' => 30, 'status' => 'confirmed',
            'reason' => 'Seguimiento tratamiento antibiótico',
        ]);

        $apt3 = Appointment::create([
            'patient_id' => $patient3->id, 'doctor_id' => $doctor1->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->addDays(2)->setTime(11, 0),
            'duration' => 45, 'status' => 'scheduled',
            'reason' => 'Dolor de espalda crónico',
        ]);

        // Cita pasada completada del Dr. Carlos
        $aptPast1 = Appointment::create([
            'patient_id' => $patient1->id, 'doctor_id' => $doctor1->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->subDays(3)->setTime(10, 0),
            'duration' => 30, 'status' => 'completed',
            'reason' => 'Revisión previa', 'notes' => 'Paciente estable',
        ]);

        // Citas de la Dra. María
        $apt4 = Appointment::create([
            'patient_id' => $patient4->id, 'doctor_id' => $doctor2->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->addDays(1)->setTime(9, 30),
            'duration' => 30, 'status' => 'confirmed',
            'reason' => 'Control pediátrico anual',
        ]);

        $apt5 = Appointment::create([
            'patient_id' => $patient5->id, 'doctor_id' => $doctor2->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
            'duration' => 30, 'status' => 'scheduled',
            'reason' => 'Vacunación programada',
        ]);

        $apt6 = Appointment::create([
            'patient_id' => $patient6->id, 'doctor_id' => $doctor2->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->addDays(3)->setTime(11, 30),
            'duration' => 45, 'status' => 'scheduled',
            'reason' => 'Evaluación de alergias alimentarias',
        ]);

        // Cita pasada de la Dra. María
        $aptPast2 = Appointment::create([
            'patient_id' => $patient4->id, 'doctor_id' => $doctor2->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->subDays(5)->setTime(14, 0),
            'duration' => 30, 'status' => 'completed',
            'reason' => 'Control de crecimiento',
        ]);

        // Citas del Dr. José
        $apt7 = Appointment::create([
            'patient_id' => $patient7->id, 'doctor_id' => $doctor3->id,
            'branch_id' => $branch2->id,
            'appointment_date' => now()->addDays(2)->setTime(14, 30),
            'duration' => 45, 'status' => 'confirmed',
            'reason' => 'Evaluación cardiológica',
        ]);

        $apt8 = Appointment::create([
            'patient_id' => $patient8->id, 'doctor_id' => $doctor3->id,
            'branch_id' => $branch2->id,
            'appointment_date' => now()->addDays(3)->setTime(9, 0),
            'duration' => 60, 'status' => 'scheduled',
            'reason' => 'Control post-infarto semestral',
            'notes' => 'Traer estudios de laboratorio recientes',
        ]);

        $apt9 = Appointment::create([
            'patient_id' => $patient9->id, 'doctor_id' => $doctor3->id,
            'branch_id' => $branch2->id,
            'appointment_date' => now()->addDays(4)->setTime(15, 0),
            'duration' => 45, 'status' => 'scheduled',
            'reason' => 'Revisión de arritmia y ajuste de medicación',
        ]);

        // Citas pasadas del Dr. José
        $aptPast3 = Appointment::create([
            'patient_id' => $patient8->id, 'doctor_id' => $doctor3->id,
            'branch_id' => $branch2->id,
            'appointment_date' => now()->subDays(7)->setTime(10, 0),
            'duration' => 60, 'status' => 'completed',
            'reason' => 'Electrocardiograma de control',
        ]);

        $aptPast4 = Appointment::create([
            'patient_id' => $patient9->id, 'doctor_id' => $doctor3->id,
            'branch_id' => $branch2->id,
            'appointment_date' => now()->subDays(2)->setTime(16, 0),
            'duration' => 45, 'status' => 'completed',
            'reason' => 'Seguimiento de arritmia',
        ]);

        // ===== CREAR PAGOS =====

        // Pago del Dr. Carlos
        Payment::create([
            'appointment_id' => $aptPast1->id, 'patient_id' => $patient1->id,
            'amount' => 250.00, 'payment_method' => 'cash',
            'status' => 'completed', 'payment_date' => now()->subDays(3),
            'notes' => 'Pago en efectivo - consulta general',
        ]);

        // Pago de la Dra. María
        Payment::create([
            'appointment_id' => $aptPast2->id, 'patient_id' => $patient4->id,
            'amount' => 350.00, 'payment_method' => 'credit_card',
            'status' => 'completed', 'payment_date' => now()->subDays(5),
            'transaction_id' => 'TXN-PED-' . now()->timestamp,
            'notes' => 'Control pediátrico',
        ]);

        // Pagos del Dr. José
        Payment::create([
            'appointment_id' => $aptPast3->id, 'patient_id' => $patient8->id,
            'amount' => 800.00, 'payment_method' => 'transfer',
            'status' => 'completed', 'payment_date' => now()->subDays(7),
            'transaction_id' => 'TXN-CARD-' . now()->timestamp,
            'notes' => 'Electrocardiograma + consulta cardiológica',
        ]);

        Payment::create([
            'appointment_id' => $aptPast4->id, 'patient_id' => $patient9->id,
            'amount' => 500.00, 'payment_method' => 'insurance',
            'status' => 'completed', 'payment_date' => now()->subDays(2),
            'notes' => 'Cubierto por seguro médico',
        ]);

        // Pago pendiente
        Payment::create([
            'appointment_id' => $apt1->id, 'patient_id' => $patient1->id,
            'amount' => 250.00, 'payment_method' => 'cash',
            'status' => 'pending', 'payment_date' => now()->addDays(1),
        ]);

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════╗');
        $this->command->info('║       ¡Datos de prueba creados exitosamente!        ║');
        $this->command->info('╠══════════════════════════════════════════════════════╣');
        $this->command->info('║  USUARIOS DE PRUEBA                                 ║');
        $this->command->info('║──────────────────────────────────────────────────────║');
        $this->command->info('║  Admin:         admin@medicapp.com / password123     ║');
        $this->command->info('║  Dr. Carlos:    doctor@medicapp.com / password123    ║');
        $this->command->info('║  Dra. María:    maria.gonzalez@medicapp.com          ║');
        $this->command->info('║  Dr. José:      jose.martinez@medicapp.com           ║');
        $this->command->info('║  Recepción:     recepcion@medicapp.com               ║');
        $this->command->info('║                 (Todos: password123)                 ║');
        $this->command->info('╠══════════════════════════════════════════════════════╣');
        $this->command->info('║  DATOS CREADOS                                      ║');
        $this->command->info('║──────────────────────────────────────────────────────║');
        $this->command->info('║  3 Doctores (3 pacientes cada uno)                  ║');
        $this->command->info('║  9 Pacientes con relaciones correctas               ║');
        $this->command->info('║  13 Citas (4 completadas, 9 futuras)                ║');
        $this->command->info('║  5 Pagos (4 completados, 1 pendiente)               ║');
        $this->command->info('╚══════════════════════════════════════════════════════╝');
    }
}

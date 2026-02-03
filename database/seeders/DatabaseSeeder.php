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
            'description' => 'Médico del sistema',
        ]);

        $receptionistRole = Role::create([
            'name' => 'Recepcionista',
            'slug' => 'receptionist',
            'description' => 'Personal de recepción',
        ]);

        // ===== ASIGNAR PERMISOS A ROLES =====

        // Admin tiene TODOS los permisos
        $adminRole->permissions()->attach(Permission::all());

        // Doctor puede ver y gestionar pacientes, citas y ver pagos
        $doctorRole->permissions()->attach(Permission::whereIn('slug', [
            'view-patients', 'create-patients', 'edit-patients',
            'view-appointments', 'create-appointments', 'edit-appointments',
            'view-payments',
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

        // Usuario Doctor
        $doctorUser = User::create([
            'name' => 'Dr. Carlos Ramírez',
            'email' => 'doctor@medicapp.com',
            'password' => Hash::make('password123'),
        ]);
        $doctorUser->roles()->attach($doctorRole);

        // Usuario Recepcionista
        $receptionistUser = User::create([
            'name' => 'Ana López',
            'email' => 'recepcion@medicapp.com',
            'password' => Hash::make('password123'),
        ]);
        $receptionistUser->roles()->attach($receptionistRole);

        // Crear sucursales
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

        // Crear doctores
        $doctor1 = Doctor::create([
            'user_id' => $doctorUser->id,
            'first_name' => 'Carlos',
            'last_name' => 'Ramírez',
            'specialty' => 'Medicina General',
            'license_number' => 'MED-001',
            'phone' => '555-1001',
            'email' => 'carlos.ramirez@medicapp.com',
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        $doctor2 = Doctor::create([
            'first_name' => 'María',
            'last_name' => 'González',
            'specialty' => 'Pediatría',
            'license_number' => 'MED-002',
            'phone' => '555-1002',
            'email' => 'maria.gonzalez@medicapp.com',
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        $doctor3 = Doctor::create([
            'first_name' => 'José',
            'last_name' => 'Martínez',
            'specialty' => 'Cardiología',
            'license_number' => 'MED-003',
            'phone' => '555-1003',
            'email' => 'jose.martinez@medicapp.com',
            'branch_id' => $branch2->id,
            'is_active' => true,
        ]);

        // Crear pacientes
        $patient1 = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'date_of_birth' => '1985-03-15',
            'gender' => 'Masculino',
            'phone' => '555-2001',
            'email' => 'juan.perez@example.com',
            'address' => 'Calle 1, Casa 10',
            'blood_type' => 'O+',
        ]);

        $patient2 = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'López',
            'date_of_birth' => '1990-07-22',
            'gender' => 'Femenino',
            'phone' => '555-2002',
            'email' => 'ana.lopez@example.com',
            'address' => 'Avenida 2, Edificio 5',
            'blood_type' => 'A+',
        ]);

        $patient3 = Patient::create([
            'first_name' => 'Pedro',
            'last_name' => 'Sánchez',
            'date_of_birth' => '2015-12-10',
            'gender' => 'Masculino',
            'phone' => '555-2003',
            'email' => null,
            'address' => 'Colonia Nueva, Casa 25',
            'blood_type' => 'B+',
        ]);

        // Crear citas
        $appointment1 = Appointment::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $doctor1->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
            'duration' => 30,
            'status' => 'scheduled',
            'reason' => 'Consulta general',
            'notes' => 'Primera consulta del paciente',
        ]);

        $appointment2 = Appointment::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $doctor3->id,
            'branch_id' => $branch2->id,
            'appointment_date' => now()->addDays(3)->setTime(14, 30),
            'duration' => 45,
            'status' => 'confirmed',
            'reason' => 'Control cardiológico',
        ]);

        $appointment3 = Appointment::create([
            'patient_id' => $patient3->id,
            'doctor_id' => $doctor2->id,
            'branch_id' => $branch1->id,
            'appointment_date' => now()->addDays(1)->setTime(9, 0),
            'duration' => 30,
            'status' => 'confirmed',
            'reason' => 'Control pediátrico',
        ]);

        // Crear pagos
        Payment::create([
            'appointment_id' => $appointment1->id,
            'patient_id' => $patient1->id,
            'amount' => 250.00,
            'payment_method' => 'cash',
            'status' => 'completed',
            'payment_date' => now(),
            'notes' => 'Pago en efectivo',
        ]);

        Payment::create([
            'appointment_id' => $appointment2->id,
            'patient_id' => $patient2->id,
            'amount' => 500.00,
            'payment_method' => 'credit_card',
            'status' => 'completed',
            'payment_date' => now(),
            'transaction_id' => 'TXN-' . now()->timestamp,
        ]);

        $this->command->info('¡Datos de prueba creados exitosamente!');
        $this->command->info('');
        $this->command->info('=== USUARIOS DE PRUEBA ===');
        $this->command->info('Administrador: admin@medicapp.com / password123');
        $this->command->info('Doctor: doctor@medicapp.com / password123');
        $this->command->info('Recepcionista: recepcion@medicapp.com / password123');
    }
}


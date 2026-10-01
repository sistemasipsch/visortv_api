<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Sede;
use App\Models\MediaItem;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Superadmin Ashly Nicole (Cuenta Principal)
        $ashly = User::updateOrCreate(
            ['username' => 'ashly'],
            [
                'name' => 'Ashly Nicole',
                'email' => 'ashly@visortv.com',
                'password' => Hash::make('admin123'),
                'role' => 'superadmin',
                'is_active' => true,
                'avatar' => null,
            ]
        );

        // 2. Administrador General (Secundario)
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador General',
                'email' => 'admin@visortv.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'avatar' => null,
            ]
        );

        // 3. Operador de Pantallas
        $operator = User::updateOrCreate(
            ['username' => 'operador'],
            [
                'name' => 'Operador de Turno',
                'email' => 'operador@visortv.com',
                'password' => Hash::make('admin123'),
                'role' => 'operator',
                'is_active' => true,
                'avatar' => null,
            ]
        );

        // 4. Default Sedes (Vinculadas a Ashly Nicole)
        $sedes = [
            [
                'id' => 1,
                'name' => 'Sede Principal (Centro)',
                'slug' => 'sede-principal',
                'description' => 'Recepción y salas de espera centrales con Smart TV de alta resolución',
                'address' => 'Av. Principal # 100 - Torre A',
                'color' => '#2563eb',
                'icon' => 'Building2',
                'order_num' => 1,
                'is_active' => true,
                'created_by_user_id' => $ashly->id,
            ],
            [
                'id' => 2,
                'name' => 'Sede Norte',
                'slug' => 'sede-norte',
                'description' => 'Área de atención al público y pasillos de consulta',
                'address' => 'Calle 140 # 15 - 30',
                'color' => '#059669',
                'icon' => 'Compass',
                'order_num' => 2,
                'is_active' => true,
                'created_by_user_id' => $ashly->id,
            ],
            [
                'id' => 3,
                'name' => 'Sede Sur',
                'slug' => 'sede-sur',
                'description' => 'Pantallas de información general y cartelera médica',
                'address' => 'Carrera 10 # 35 Sur',
                'color' => '#d97706',
                'icon' => 'Landmark',
                'order_num' => 3,
                'is_active' => true,
                'created_by_user_id' => $ashly->id,
            ],
            [
                'id' => 4,
                'name' => 'Sede Occidente',
                'slug' => 'sede-occidente',
                'description' => 'Módulo de atención al usuario y auditorio empresarial',
                'address' => 'Avenida El Dorado # 68 - 90',
                'color' => '#7c3aed',
                'icon' => 'Store',
                'order_num' => 4,
                'is_active' => true,
                'created_by_user_id' => $ashly->id,
            ],
            [
                'id' => 5,
                'name' => 'Sede VIP / Corporativa',
                'slug' => 'sede-vip',
                'description' => 'Lounge ejecutivo y salas directivas en piso 12',
                'address' => 'Carrera 7 # 116 - 50 Piso 12',
                'color' => '#0891b2',
                'icon' => 'Crown',
                'order_num' => 5,
                'is_active' => true,
                'created_by_user_id' => $ashly->id,
            ],
        ];

        foreach ($sedes as $s) {
            Sede::updateOrCreate(['id' => $s['id']], $s);
        }

        // 5. Media Items Iniciales (Subidos por Ashly Nicole)
        $demoMedia = [
            [
                'id' => 1,
                'sede_id' => 1,
                'uploaded_by_user_id' => $ashly->id,
                'title' => 'Bienvenida Corporativa — Sede Principal',
                'type' => 'image',
                'filename' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1920&q=80',
                'original_name' => 'bienvenida_corporativa.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 1048576,
                'duration' => 10,
                'fit_mode' => 'contain',
                'resolution' => '1920x1080',
                'order_num' => 1,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'sede_id' => 1,
                'uploaded_by_user_id' => $ashly->id,
                'title' => 'Información de Servicios y Especialidades',
                'type' => 'image',
                'filename' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1920&q=80',
                'original_name' => 'catalogo_servicios.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 1048576,
                'duration' => 8,
                'fit_mode' => 'contain',
                'resolution' => '1920x1080',
                'order_num' => 2,
                'is_active' => true,
            ],
            [
                'id' => 3,
                'sede_id' => 2,
                'uploaded_by_user_id' => $ashly->id,
                'title' => 'Horarios de Atención — Sede Norte',
                'type' => 'image',
                'filename' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1920&q=80',
                'original_name' => 'horarios_sede_norte.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 1048576,
                'duration' => 12,
                'fit_mode' => 'contain',
                'resolution' => '1920x1080',
                'order_num' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($demoMedia as $m) {
            MediaItem::updateOrCreate(['id' => $m['id']], $m);
        }

        // 6. Settings Iniciales
        $settings = [
            'app_name' => 'Visor TV Sistemas',
            'app_author' => 'Ashly Nicole',
            'default_image_duration' => '10',
            'tv_show_clock' => '1',
            'tv_show_date' => '1',
            'tv_show_sede_title' => '1',
            'tv_show_progress_bar' => '1',
            'tv_auto_refresh_seconds' => '30',
            'tv_transition_effect' => 'fade',
            'tv_ticker_enabled' => '0',
            'tv_ticker_message' => 'Bienvenidos a Visor TV • Señalización Digital Inteligente',
        ];

        foreach ($settings as $k => $v) {
            Setting::set($k, $v);
        }

        // 7. Auditoría Inicial (Historial de Creación Real)
        AuditLog::create([
            'user_id' => $ashly->id,
            'user_name' => $ashly->name,
            'sede_id' => 1,
            'sede_name' => 'Sede Principal (Centro)',
            'action' => 'system.init',
            'entity_type' => 'System',
            'entity_id' => 1,
            'description' => 'Ashly Nicole inicializó la plataforma Visor TV con arquitectura sólida y auditoría activa',
            'details' => ['version' => '2.0.0', 'author' => 'Ashly Nicole'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'VisorTV Setup Script',
            'created_at' => now()->subMinutes(15),
        ]);

        AuditLog::create([
            'user_id' => $ashly->id,
            'user_name' => $ashly->name,
            'sede_id' => 1,
            'sede_name' => 'Sede Principal (Centro)',
            'action' => 'media.upload',
            'entity_type' => 'MediaItem',
            'entity_id' => 1,
            'description' => 'Ashly Nicole configuró el contenido "Bienvenida Corporativa" en Sede Principal',
            'details' => ['type' => 'image', 'duration' => 10],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'VisorTV Web Client',
            'created_at' => now()->subMinutes(10),
        ]);

        AuditLog::create([
            'user_id' => $ashly->id,
            'user_name' => $ashly->name,
            'sede_id' => 1,
            'sede_name' => 'Sede Principal (Centro)',
            'action' => 'media.upload',
            'entity_type' => 'MediaItem',
            'entity_id' => 2,
            'description' => 'Ashly Nicole configuró el contenido "Información de Servicios" en Sede Principal',
            'details' => ['type' => 'image', 'duration' => 8],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'VisorTV Web Client',
            'created_at' => now()->subMinutes(5),
        ]);
    }
}

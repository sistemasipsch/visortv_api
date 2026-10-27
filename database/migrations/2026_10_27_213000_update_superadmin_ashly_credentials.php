<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $user = User::where('username', 'ashly')->first();
        if ($user) {
            $user->password = Hash::make('Ashly2026*');
            $user->is_active = true;
            $user->role = 'superadmin';
            $user->save();
        } else {
            User::create([
                'name' => 'Ashly Nicole',
                'username' => 'ashly',
                'email' => 'ashly@visortv.com',
                'password' => Hash::make('Ashly2026*'),
                'role' => 'superadmin',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

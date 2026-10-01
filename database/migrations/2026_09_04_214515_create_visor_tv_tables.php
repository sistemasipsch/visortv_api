<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sedes (Locations / TV Screens)
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('color', 30)->default('#2563eb');
            $table->string('icon', 50)->default('Building2');
            $table->integer('order_num')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_num', 'is_active']);
        });

        // 2. Media Items (Videos & Images)
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('type', 20)->default('image'); // 'video' or 'image'
            $table->text('filename');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->integer('duration')->default(10);
            $table->string('fit_mode', 20)->default('contain'); // 'contain', 'cover', 'fill'
            $table->string('resolution', 30)->nullable(); // e.g. 1920x1080, 4K
            $table->integer('order_num')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sede_id', 'is_active', 'order_num']);
            $table->index(['uploaded_by_user_id']);
        });

        // 3. Settings Key-Value Store
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group', 30)->default('general');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Audit & Activity Logs (Quién hizo qué, en qué sede y cuándo)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150)->nullable();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->string('sede_name', 150)->nullable();
            $table->string('action', 60); // media.upload, media.delete, media.reorder, sede.create, auth.login, etc.
            $table->string('entity_type', 60)->nullable(); // MediaItem, Sede, User, Setting
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description');
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['action', 'created_at']);
            $table->index(['sede_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('media_items');
        Schema::dropIfExists('sedes');
    }
};

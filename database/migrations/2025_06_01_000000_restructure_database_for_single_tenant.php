<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        // 1. Ενημέρωση του tenants table - αφαίρεση του database πεδίου
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('database');
        });

        // 2. Προσθήκη tenant_id στον πίνακα trips
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('destination');
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('duration')->comment('Duration in days');
            $table->date('departure_date')->nullable();
            $table->date('return_date')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        // 3. Προσθήκη tenant_id στον πίνακα invitations
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('token', 64)->unique();
            $table->string('role')->default('staff'); // 'guide' ή 'staff'
            $table->foreignId('invited_by')->constrained('users');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('trips');
        Schema::dropIfExists('invitations');

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('database')->unique()->after('description');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Όνομα τουριστικού γραφείου
            $table->string('email'); // Email επικοινωνίας
            $table->string('phone')->nullable(); // Τηλέφωνο επικοινωνίας
            $table->string('logo')->nullable(); // Λογότυπο
            $table->text('description')->nullable(); // Περιγραφή
            $table->string('database')->unique(); // Όνομα βάσης δεδομένων για το tenant
            $table->boolean('is_active')->default(false); // Κατάσταση ενεργοποίησης
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete(); // ID ιδιοκτήτη
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('tenants');
    }
};

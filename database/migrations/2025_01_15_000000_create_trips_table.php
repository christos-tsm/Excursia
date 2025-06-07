<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('destination');
            $table->decimal('price', 10, 2);
            $table->integer('duration'); // σε ημέρες
            $table->date('departure_date');
            $table->date('return_date');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            // Indexes για καλύτερη απόδοση
            $table->index(['tenant_id', 'is_published']);
            $table->index('departure_date');
            $table->index('destination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('trips');
    }
};

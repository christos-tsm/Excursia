<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('trip_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('title'); // π.χ. "Εγχειρίδιο Ταξιδιού", "Πρόγραμμα"
            $table->text('description')->nullable();
            $table->string('file_name'); // Original filename
            $table->string('file_path'); // Path στο storage
            $table->string('file_type'); // pdf, docx, doc
            $table->integer('file_size'); // σε bytes
            $table->enum('document_type', ['manual', 'program', 'notes', 'other']); // Κατηγορία εγγράφου
            $table->boolean('is_public')->default(false); // Αν είναι διαθέσιμο σε όλους τους χρήστες του tenant
            $table->timestamps();

            // Indexes για καλύτερη απόδοση
            $table->index(['trip_id', 'document_type']);
            $table->index(['trip_id', 'is_public']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('trip_documents');
    }
};

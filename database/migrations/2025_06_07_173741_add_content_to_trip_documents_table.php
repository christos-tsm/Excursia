<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('trip_documents', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('description'); // HTML περιεχόμενο
            $table->enum('creation_type', ['upload', 'editor'])->default('upload')->after('document_type'); // Πώς δημιουργήθηκε
            $table->json('editor_metadata')->nullable()->after('creation_type'); // Metadata από editor (fonts, styles κτλ)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('trip_documents', function (Blueprint $table) {
            $table->dropColumn(['content', 'creation_type', 'editor_metadata']);
        });
    }
};

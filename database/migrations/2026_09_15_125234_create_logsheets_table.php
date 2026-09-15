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
      Schema::create('logsheets', function (Blueprint $table) {
    $table->id();                                   // auto-increment primary key
    $table->date('date_from');                      // date range you select before upload
    $table->date('date_to');
    $table->string('original_filename')->nullable(); // so you can show/download the file later
    $table->string('file_path')->nullable();          // where the uploaded file is stored
    $table->decimal('final_amount', 14, 2)->default(0); // calculated once at import time
    $table->unsignedInteger('total_rows')->default(0);  // handy to show "3,120 rows" without counting
    $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();                            // created_at / updated_at, Laravel adds these free
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logsheets');
    }
};

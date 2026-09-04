<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['debit', 'credit']);
            $table->decimal('amount', 14, 2);
            $table->enum('payment_mode', ['cash', 'bank_transfer', 'upi', 'cheque', 'other'])->nullable();
            $table->enum('payment_plan', ['full', 'emi', 'partial'])->nullable();
            $table->unsignedInteger('installment_no')->nullable();
            $table->unsignedInteger('installment_total')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->date('transaction_date');
            $table->decimal('running_balance', 14, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id');
            $table->index('direction');
            $table->index('transaction_date');
            $table->index(['reference_type', 'reference_id']);
            $table->index(['account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};

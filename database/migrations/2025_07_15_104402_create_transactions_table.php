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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // income, expense
            $table->string('status'); // paid, pending
            $table->string('recurrence_type')->nullable(); // none, daily, weekly, monthly, yearly
            $table->string('recurrence_period')->nullable(); // daily, weekly, fortnightly, monthly, yearly
            $table->integer('installment_number')->nullable();
            $table->integer('total_installments')->nullable();
            $table->datetime('next_date')->nullable();
            $table->datetime('date');
            $table->string('description');
            $table->integer('amount'); // stored in cents
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('suggested_category', 100)->nullable();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->string('cliente')->nullable(); // for income transactions
            $table->string('fornecedor')->nullable(); // for expense transactions
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

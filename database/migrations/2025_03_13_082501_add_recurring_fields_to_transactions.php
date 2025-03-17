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
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transaction_type', ['regular', 'recurring', 'fixed', 'installment'])->default('regular')->after('type');
            $table->integer('installments')->nullable()->after('amount');
            $table->integer('current_installment')->nullable()->after('installments');
            $table->string('recurrence_frequency')->nullable()->after('current_installment'); // daily, weekly, monthly, yearly
            $table->date('recurrence_end_date')->nullable()->after('recurrence_frequency');
            $table->string('parent_transaction_id')->nullable()->after('recurrence_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_type',
                'installments',
                'current_installment',
                'recurrence_frequency',
                'recurrence_end_date',
                'parent_transaction_id'
            ]);
        });
    }
};

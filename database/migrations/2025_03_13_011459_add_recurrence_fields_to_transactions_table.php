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
            $table->enum('recurrence_type', ['none', 'fixed', 'installment'])->default('none')->after('status');
            $table->integer('installment_number')->nullable()->after('recurrence_type');
            $table->integer('total_installments')->nullable()->after('installment_number');
            $table->date('next_date')->nullable()->after('total_installments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('recurrence_type');
            $table->dropColumn('installment_number');
            $table->dropColumn('total_installments');
            $table->dropColumn('next_date');
        });
    }
};

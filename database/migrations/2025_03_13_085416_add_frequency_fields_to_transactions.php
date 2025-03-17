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
            $table->string('installment_frequency')->nullable()->after('current_installment');
            $table->string('fixed_frequency')->nullable()->after('installment_frequency');
            $table->date('fixed_end_date')->nullable()->after('fixed_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('installment_frequency');
            $table->dropColumn('fixed_frequency');
            $table->dropColumn('fixed_end_date');
        });
    }
};

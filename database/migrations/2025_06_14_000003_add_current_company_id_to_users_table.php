<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_company_id')) {
                $table->unsignedBigInteger('current_company_id')->nullable()->after('is_admin');
                $table->foreign('current_company_id')
                      ->references('id')
                      ->on('companies')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_company_id')) {
                $table->dropForeign(['current_company_id']);
                $table->dropColumn('current_company_id');
            }
        });
    }
}; 
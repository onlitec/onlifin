<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Verifica se a coluna já existe
        $columns = Schema::getColumnListing('categories');
        if (!in_array('description', $columns)) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('description')->nullable()->after('name');
            });
        }
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            if (in_array('description', Schema::getColumnListing('categories'))) {
                $table->dropColumn('description');
            }
        });
    }
}; 
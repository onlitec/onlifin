<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientAndSupplierToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('cliente')->nullable()->after('description'); // Campo para receitas
            $table->string('fornecedor')->nullable()->after('description'); // Campo para despesas
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('cliente');
            $table->dropColumn('fornecedor');
        });
    }
}

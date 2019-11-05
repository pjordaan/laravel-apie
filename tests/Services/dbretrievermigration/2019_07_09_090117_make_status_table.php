<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeStatusTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('status', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('value');
            $table->enum('identifier', ['a', 'b']);
        });
        srand(0);
        $table = DB::table('status');
        for ($i = 0; $i < 100; $i++) {
            $table->insert(['id' => \Ramsey\Uuid\Uuid::uuid4(), 'value' => rand(), 'identifier' => 'a']);
        }
        for ($i = 0; $i < 100; $i++) {
            $table->insert(['id' => \Ramsey\Uuid\Uuid::uuid4(), 'value' => rand(), 'identifier' => 'b']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('status');
    }
}

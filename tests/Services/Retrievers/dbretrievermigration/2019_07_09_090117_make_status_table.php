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
        Schema::create(
            'status', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->unsignedInteger('value');
                $table->enum('enum_column', ['a', 'b']);
                $table->timestamps();
            }
        );
        srand(0);
        $table = DB::table('status');
        for ($i = 0; $i < 100; $i++) {
            $table->insert(['id' => (string) $i, 'value' => rand(), 'enum_column' => 'a']);
        }
        for ($i = 0; $i < 100; $i++) {
            $table->insert(['id' => (string) (100 + $i), 'value' => rand(), 'enum_column' => 'b']);
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

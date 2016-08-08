<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResZoneTurnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_zone_turn', function (Blueprint $table) {
            $table->bigInteger('res_zone_id')->unsigned();
            $table->bigInteger('ms_microsite_id')->unsigned();
            $table->bigInteger('res_turn_id')->unsigned();
            $table->bigInteger('res_type_turn_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_zone_turn');
    }
}

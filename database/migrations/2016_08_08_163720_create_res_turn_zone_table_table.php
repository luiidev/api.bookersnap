<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResTurnZoneTableTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('res_turn_zone_table', function (Blueprint $table) {
            $table->time('start_time');
            $table->time('end_time');
            $table->bigInteger('res_turn_id')->unsigned();
            $table->bigInteger('res_zone_id')->unsigned();
            $table->bigInteger('res_table_id')->unsigned();
            $table->bigInteger('res_turn_rule_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('res_turn_zone_table');
    }

}

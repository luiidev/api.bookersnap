<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResTurnZoneTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('res_turn_zone', function (Blueprint $table) {
            $table->bigInteger('res_zone_id')->unsigned();
            $table->bigInteger('res_turn_id')->unsigned();
            $table->bigInteger('res_turn_rule_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('res_turn_zone');
    }

}

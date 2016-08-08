<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResReservationTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('res_reservation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('ev_event_id')->unsigned()->nullable();
            $table->bigInteger('ms_microsite_id')->unsigned();
            $table->bigInteger('bs_user_id')->unsigned()->nullable();
            $table->bigInteger('res_guest_id')->unsigned();
            $table->date('date_reservation')->comment();
            $table->time('hours_reservation')->comment();
            $table->time('hours_duration')->nullable()->comment();
            $table->integer('num_people')->comment();
            $table->integer('status_reservation')->comment();
            $table->integer('status_released')->comment();
            $table->integer('num_people_1')->nullable()->comment();
            $table->integer('num_people_2')->nullable()->comment();
            $table->integer('total')->nullable()->comment();
            $table->decimal('consume')->nullable()->comment();
            $table->string('num_table', 80)->nullable()->comment();
            $table->string('colaborator', 45)->nullable()->comment();
            $table->string('note', 200)->nullable();
            $table->string('type_reservation', 45)->nullable();            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('res_reservation');
    }

}

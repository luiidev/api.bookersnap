<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResZoneTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_zones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('name',64);
            $table->string('sketch',128); // es croquis
            $table->integer('status')->unsigned();
            $table->integer('type_zone')->unsigned();
            $table->tinyInteger('join_table')->unsigned();
            $table->boolean('status_smoker');
            $table->integer('people_standing')->unsigned(); // personas de pie.
            $table->bigInteger('user_add')->unsigned();
            $table->bigInteger('user_upd')->unsigned();
            $table->bigInteger('ev_event_id')->unsigned()->nullable()->default(NULL);
            $table->bigInteger('ms_microsite_id')->unsigned();

            //$table->foreign('user_add')->references('id')->on('user');
            //$table->foreign('user_upd')->references('id')->on('user');
            //$table->foreign('ev_event_id')->references('id')->on('ev_event');
            //$table->foreign('ms_microsite_id')->references('id')->on('microsite');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_zones');
    }
}

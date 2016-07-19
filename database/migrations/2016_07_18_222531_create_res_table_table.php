<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_tables', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('res_zone_id')->unsigned();
            $table->string('name');
            $table->integer('min_cover')->unsigned()->default(1);
            $table->integer('max_cover')->unsigned();
            $table->double('price', 10, 2)->default(0);
            $table->integer('status')->unsigned()->default(1);
            $table->string('config_color',24);
            $table->string('config_position',64);
            $table->integer('config_forme');
            $table->integer('config_size')->unsigned();
            $table->integer('config_rotation')->unsigned();
            $table->dateTime('date_add');
            $table->timestamp('date_upd');
            $table->bigInteger('user_add')->unsigned();
            $table->bigInteger('user_upd')->unsigned();

            //$table->foreign('user_add')->references('id')->on('user');
            //$table->foreign('user_upd')->references('id')->on('user');

            //$table->foreign('res_zone_id')->references('id')->on('zone');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_tables');
    }
}

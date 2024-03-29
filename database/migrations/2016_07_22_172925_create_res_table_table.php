<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_table', function (Blueprint $table) {
            $table->bigIncrements('id');
//            $table->timestamps();
            $table->integer('res_zone_id')->unsigned();
            $table->string('name');
            $table->integer('min_cover')->unsigned()->nullable()->default(1);
            $table->integer('max_cover')->unsigned();
            $table->double('price', 10, 2)->default(0);
            $table->integer('status')->unsigned()->nullable()->default(1);
            $table->string('config_color',24)->nullable();
            $table->string('config_position',64)->nullable();
            $table->integer('config_forme')->nullable();
            $table->integer('config_size')->unsigned()->nullable();
            $table->integer('config_rotation')->unsigned()->nullable();
            $table->integer('config_rotation_text')->unsigned()->nullable();
            $table->dateTime('date_add');
            $table->dateTime('date_upd')->nullable()->default(NULL);
            $table->bigInteger('user_add')->unsigned();
            $table->bigInteger('user_upd')->unsigned()->nullable()->default(NULL);

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
        Schema::drop('res_table');
    }
}

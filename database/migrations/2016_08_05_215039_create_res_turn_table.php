<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResTurnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_turn', function (Blueprint $table) {
            $table->bigIncrements('id');
//            $table->timestamps();
            $table->bigInteger('res_type_turn_id')->unsigned();
            $table->bigInteger('ms_microsite_id')->unsigned();            
            $table->time('hours_ini');
            $table->time('hours_end');
            $table->integer('status')->default(1);
            $table->integer('on_table')->nullable()->unsigned()->default(1);
            $table->integer('early')->nullable()->default(0);
            $table->dateTime('date_add');
            $table->dateTime('date_upd')->nullable();
            $table->bigInteger('user_add')->unsigned();
            $table->bigInteger('user_upd')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_turn');
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResGuestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_guest', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name',45);
            $table->string('last_name',45);
            $table->date('birthdate');
            $table->string('gender',1);
            $table->bigInteger('user_add')->unsigned()->nullable()->default(NULL);
            $table->bigInteger('user_upd')->unsigned()->nullable()->default(NULL);
            $table->dateTime('date_add')->nullable()->default(NULL);
            $table->dateTime('date_upd')->nullable()->default(NULL);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_guest');
    }
}

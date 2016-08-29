<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResBlockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_block', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('start_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->bigInteger('user_add')->unsigned();
            $table->bigInteger('user_upd')->unsigned()->nullable()->default(NULL);
            $table->dateTime('date_add');
            $table->dateTime('date_upd')->nullable()->default(NULL);
            $table->bigInteger('ms_microsite_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_block');
    }
}

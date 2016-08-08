<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBsCityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bs_city', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 45);
            $table->string('postal_code', 45)->nullable();
            $table->string('bs_country_id', 3);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bs_city');
    }
}

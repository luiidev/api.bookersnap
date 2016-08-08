<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBsCountryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bs_country', function (Blueprint $table) {
            $table->char('id', 3)->primary();
            $table->string('name', 100)->nullable();
            $table->string('short', 2)->nullable();
            $table->string('image', 512)->nullable();
            $table->string('money', 100)->nullable();
            $table->string('money_name', 45)->nullable();
            $table->string('money_symbol', 4)->nullable();
            $table->string('money_code', 3)->nullable();
            $table->string('money_paypal', 4)->nullable();
            $table->integer('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bs_country');
    }
}

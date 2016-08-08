<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBsUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('bs_user')) {
            Schema::create('bs_user', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('email', 45);
                $table->string('firstname', 45);
                $table->string('lastname', 45)->nullable();
                $table->string('genere', 45)->nullable();
                $table->string('birthdate', 45)->nullable();
                $table->string('phone', 45)->nullable();
                $table->string('phone_extension', 45)->nullable();
                $table->string('photo', 45)->nullable();
                $table->integer('receive_notifications')->nullable();
                $table->string('remember_token', 100)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::drop('bs_user');
    }
}

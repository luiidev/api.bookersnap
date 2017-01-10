<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMsMicrositeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create('ms_microsite', function (Blueprint $table) {
//            $table->bigIncrements('id');
//            $table->string('name', 100)->comment('nombre del lugar');
//            $table->string('domain', 45)->nullable();
//            $table->string('site_name', 45)->nullable()
//                ->comment('nombre en la url de bookersnap.com para micrositios premium\n\nbookersnap.com/{sitename}');
//            $table->string('address', 100)->nullable();
//            $table->string('map_latitude', 45)->nullable()->comment('coordenada geografia de laltitud');
//            $table->string('map_longitude', 45)->nullable()->comment('coordenada geografia de longitud');
//            $table->string('phone', 20)->nullable();
//            $table->string('description', 2000)->nullable();
//            $table->string('image_logo', 200)->nullable();
//            $table->string('image_favicon', 200)->nullable();
//            $table->string('image_public', 200)->nullable();
//            $table->string('url_facebook', 100)->nullable();
//            $table->string('url_twitter', 100)->nullable();
//            $table->string('url_google', 100)->nullable();
//            $table->string('url_instagram', 100)->nullable();
//            $table->string('url_pinterest', 100)->nullable();
//            $table->string('url_facturation', 100)->nullable()
//                ->comment('url para redireccionar  a su sistema de facturacion.');
//            $table->string('code', 45)->nullable()->comment('codigo del micrositio : uso (dashboard)');
//            $table->string('sitename_free', 45)->nullable()
//                ->comment('nombre en la url de bookersnap.com para micrositios gratuito bookersnap.com/{sitename}-');
//            $table->integer('status_free')->nullable()->comment('0 -> activo 1 -> inactivo');
//            $table->integer('pid_free')->nullable()->comment('redireccionar a micrositio con este ID');
//            $table->string('meta_title', 70)->nullable();
//            $table->string('meta_description', 160)->nullable();
//            $table->string('meta_keywords', 255)->nullable();
//            $table->integer('bs_money_id')->nullable();
//            $table->datetime('date_add');
//            $table->datetime('date_upd')->nullable();
//            $table->bigInteger('user_add')->unsigned();
//            $table->bigInteger('user_upd')->nullable()->unsigned();
//            $table->bigInteger('bs_user_id')->nullable()->unsigned()->comment('id del propietario del micrositio (no obligatorio)');
//            $table->integer('status_claimed')->comment('Estado de la pgina reclamada 1 = reclamada 0 = no reclamada');
//            $table->integer('origin_request');
//            $table->string('photo_place', 500)->nullable();
//            $table->integer('bs_city_id')->unsigned();
//            $table->char('bs_country_id', 3);
//            //-------------------------------------------------------------
//            // FOREIGN KEYS
//            //-------------------------------------------------------------
//            $table->foreign('bs_city_id')->references('id')->on('bs_city');
//            $table->foreign('bs_country_id')->references('id')->on('bs_country');
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::drop('ms_microsite');
    }
}

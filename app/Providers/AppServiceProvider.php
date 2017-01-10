<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('multiple', function ($attribute, $value, $parameters) {
            $resto = fmod($value, $parameters[0]);
            if ($resto == 0) {
                return true;
            } else {
                return false;
            }
        }, "Los minutos no es compatible con multiplo determinado en la validación");

        Validator::extend('multiple_hour', function ($attribute, $value, $parameters) {
            list($y, $m, $d) = explode(":", $value);
            $resto           = fmod((int) $m, $parameters[0]);
            if ($resto == 0) {
                return true;
            } else {
                return false;
            }
        }, "Los minutos de la hora no son compatibles con el multiplo determinado en la validación");

        Validator::extend('alpha_spaces', function ($attribute, $value) {
            return preg_match('/^[\pL\s]+$/u', $value); 
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

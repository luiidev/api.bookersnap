<?php

namespace App\Services;

class Service
{

    protected $properties;
    
    public function __construct($request)
    {
        if ($request){
            $this->properties = ($request->route())?$request->route()->parameters():[];
            // $this->properties = [];
            // $this->properties["microsite_id"] = $request->route("microsite_id");
            // $this->properties["reservation"] = $request->route("reservation");
            $this->properties["req"] = $request;
            $this->properties["req"]["ms_microsite_id"] = $this->microsite_id;
        }
    }

    public static function make($request = null) {
        return new static($request);
    }

    public function __get($name)
    {
        return $this->getProperty($name);
    }

    public function __set($var, $val)
    {
        $this->setProperty($var, $val);
    }
    
    public function getProperty($name)
    {
        if (isset ($this->properties[$name]))
        {
            return $this->properties[$name];
        }
        return null;
    }

    public function setProperty($name, $valor)
    {
        $this->properties[$name] = $valor;
    }

}
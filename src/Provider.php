<?php

namespace Shridhar\Users;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class Provider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        Validator::extend("mobile", function($attribute, $value) {
            return preg_match("/^(9|8|7){1}\d{9}$/", $value);
        });

        Validator::extend("pin_code", function($attribute, $value) {
            return preg_match("/^\d{6}$/", $value);
        });

        Validator::extend("name", function($attribute, $value) {
            return preg_match("/^\D+$/", $value);
        });

        Validator::extend("aadhar_no", function($attribute, $value) {
            return preg_match("/^\d{12}$/", $value);
        });

        Validator::extend("ifsc", function($attribute, $value) {
            return preg_match("/^[A-Za-z0-9]+$/", $value);
        });

        $this->loadMigrationsFrom(__DIR__ . "/migrations");
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        //
    }

}

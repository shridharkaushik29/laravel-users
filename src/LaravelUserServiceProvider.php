<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ShridharKaushik;

use Illuminate\Support\ServiceProvider;

/**
 * Description of LaravelUserServiceProvider
 *
 * @author Shridhar
 */
class LaravelUserServiceProvider extends ServiceProvider {

    public function boot() {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    public function register() {
        
    }

}

<?php

namespace Shridhar\Users\Facades;

use Illuminate\Support\Facades\Hash;

/**
 * Description of Password
 *
 * @author Shridhar
 */
class Password {

    public static function match($password, $original) {
        return Hash::check($password, $original);
    }

    public static function hash($password) {
        return Hash::make($password);
    }

}

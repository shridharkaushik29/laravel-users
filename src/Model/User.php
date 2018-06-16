<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Shridhar\Users\Model;

use Shridhar\Users\Facades\Password;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cookie;
use Exception;

/**
 * Description of User
 *
 * @author Shridhar
 */
abstract class User extends Model {

    protected $date = ["deleted_at"];
    protected static $token_class, $role_class;
    protected static $login_cookie_name = "login_token", $login_time = 86000;
    protected $hidden = ["password"];

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->append(["is_logged_in"]);
    }

    public function login($config = []) {
        $options = collect([
                    "expiry" => static::$login_time,
                ])->merge($config)->toArray();

        $cookie_name = array_get($options, "cookie_name");

        if ($this->loggedInUser($cookie_name)) {
            $token = static::getLoginToken($cookie_name);
            $token->unlock();
        } else {
            static::logout();
            $token = $this->tokens()->make();
        }
        $expiry = array_get($options, "expiry");

        $token->ip_address = array_get($options, "ip_address");
        $token->user_agent = array_get($options, "user_agent");
        $token->expiry = date("Y-m-d H:i:s", time() + $expiry);
        $token->type = "login";
        $token->touch_last_seen();
        $token->save();

        static::setLoginToken($token, $cookie_name);
        return $this;
    }

    public static function loginUser($username, $password, $params = []) {

        if (empty($username) || empty($password)) {
            throw new Exception("Please enter username and password");
        }

        $user = static::query()->username($username)->first();

        if (!$user) {
            throw new Exception("User account not found");
        }

        if (!Password::match($password, $user->password)) {
            throw new Exception("Invalid Password");
        }

        return $user->login($params);
    }

    static function findByUsername($username) {
        return static::username($username)->first();
    }

    function scopeUsername($query, $username) {
        $query->where(function($query) use($username) {
            $query->orWhere("username", $username);
            $query->orWhere("email", $username);
            $query->orWhere("mobile", $username);
        });
    }

    function match_password($password) {
        return Password::match($password, $this->password);
    }

    public function password() {
        return app()->makeWith(Password::class, [
                    "user" => $this
        ]);
    }

    public function setPasswordAttribute($value) {
        return $this->attributes['password'] = Password::hash($value);
    }

    public function role() {
        return $this->belongsTo(static::$role_class);
    }

    function tokens() {
        return $this->hasMany(static::$token_class);
    }

    public function hasRole($role) {
        if ($this->role && $this->role->slug === $role) {
            return true;
        }
    }

    public function getIsLoggedInAttribute() {
        $user = static::loggedInUser();
        if ($user && $user->id === $this->id) {
            return true;
        } else {
            return false;
        }
    }

    public function scopeRole($query, $slug) {
        $query->whereHas("role", function($query) use($slug) {
            $query->where("slug", $slug);
        });
    }

    public function setRoleAttribute($slug) {
        $role = call_user_func([static::$role_class, "findWithSlug"], $slug);
        if ($role) {
            $this->role_id = $role->id;
        }
    }

    public static function logout($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token) {
            static::removeLoginToken($cookie_name);
            $token->forceDelete();
        }
        return true;
    }

    public static function lock($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token) {
            $token->lock();
        }
        return true;
    }

    public static function loggedInUser($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token) {
            return $token->user;
        }
    }

    public static function isLoggedIn($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token && !$token->is_locked()) {
            return true;
        }
    }

    public static function getLoginToken($cookie_name = null) {
        $id = Cookie::get($cookie_name ?: static::$login_cookie_name);
        $token = call_user_func([static::$token_class, "where"], "id", $id)->type("login")->first();
        if ($token && !$token->is_expired) {
            return $token;
        }
    }

    public static function setLoginToken($token, $cookie_name = null) {
        $cookie = Cookie::forever($cookie_name ?: static::$login_cookie_name, $token->id);
        Cookie::queue($cookie);
        return true;
    }

    public static function removeLoginToken($cookie_name = null) {
        Cookie::queue(Cookie::forget($cookie_name ?: static::$login_cookie_name));
        return true;
    }

}

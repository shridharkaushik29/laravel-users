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

    /**
     * @var array
     */
    protected $date = ["deleted_at"];
    /**
     * @var
     */
    /**
     * @var
     */
    protected static $token_class, $role_class;
    /**
     * @var string
     */
    /**
     * @var string
     */
    protected static $login_cookie_name = "login_token", $login_time = 86000;
    /**
     * @var array
     */
    protected $hidden = ["password"];

    /**
     * User constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->append(["is_logged_in"]);
    }

    /**
     * @param array $config
     * @return $this
     */
    public function login($config = []) {
        $options = collect([
            "expiry" => static::$login_time,
        ])->merge($config)->toArray();

        $cookie_name = array_get($options, "cookie_name");

        if ($this->loggedInUser($cookie_name)) {
            $token = static::getLoginToken($cookie_name);
            $token->user()->associate($this);
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

    /**
     * @param string $username
     * @param string $password
     * @param array $params
     * @return mixed
     * @throws Exception
     */
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

    /**
     * @param $username
     * @return mixed
     */
    static function findByUsername($username) {
        return static::username($username)->first();
    }

    /**
     * @param $query
     * @param $username
     */
    function scopeUsername($query, $username) {
        $query->where(function ($query) use ($username) {
            $query->orWhere("username", $username);
            $query->orWhere("email", $username);
            $query->orWhere("mobile", $username);
        });
    }

    /**
     * @param $password
     * @return bool
     */
    function match_password($password) {
        return Password::match($password, $this->password);
    }

    /**
     * @return mixed
     */
    public function password() {
        return app()->makeWith(Password::class, [
            "user" => $this
        ]);
    }

    /**
     * @param $value
     * @return string
     */
    public function setPasswordAttribute($value) {
        return $this->attributes['password'] = Password::hash($value);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role() {
        return $this->belongsTo(static::$role_class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function tokens() {
        return $this->hasMany(static::$token_class);
    }

    /**
     * @param $role
     * @return bool
     */
    public function hasRole($role) {
        if ($this->role && $this->role->slug === $role) {
            return true;
        }
    }

    /**
     * @return bool
     */
    public function getIsLoggedInAttribute() {
        $user = static::loggedInUser();
        if ($user && $user->id === $this->id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $query
     * @param string $slug
     */
    public function scopeRole($query, $slug) {
        $query->whereHas("role", function ($query) use ($slug) {
            $query->where("slug", $slug);
        });
    }

    /**
     * @param string $slug
     */
    public function setRoleAttribute($slug) {
        $role = call_user_func([static::$role_class, "findWithSlug"], $slug);
        if ($role) {
            $this->role_id = $role->id;
        }
    }

    /**
     * @param string|null $cookie_name
     * @return bool
     */
    public static function logout($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token) {
            static::removeLoginToken($cookie_name);
            $token->forceDelete();
        }
        return true;
    }

    /**
     * @param string|null $cookie_name
     * @return bool
     */
    public static function lock($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token) {
            $token->lock();
        }
        return true;
    }

    /**
     * @param null $cookie_name
     * @return mixed
     */
    public static function loggedInUser($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token) {
            return $token->user;
        }
    }

    /**
     * @param null $cookie_name
     * @return bool
     */
    public static function isLoggedIn($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token && !$token->is_locked()) {
            return true;
        }
    }

    /**
     * @param null $cookie_name
     * @return mixed
     */
    public static function getLoginToken($cookie_name = null) {
        $id = Cookie::get($cookie_name ?: static::$login_cookie_name);
        $token = call_user_func([static::$token_class, "where"], "id", $id)->type("login")->first();
        if ($token && !$token->is_expired) {
            return $token;
        }
    }

    /**
     * @param $token
     * @param string|null $cookie_name
     * @return bool
     */
    public static function setLoginToken($token, $cookie_name = null) {
        $cookie = Cookie::forever($cookie_name ?: static::$login_cookie_name, $token->id);
        Cookie::queue($cookie);
        return true;
    }

    /**
     * @param string|null $cookie_name
     * @return bool
     */
    public static function removeLoginToken($cookie_name = null) {
        Cookie::queue(Cookie::forget($cookie_name ?: static::$login_cookie_name));
        return true;
    }

}

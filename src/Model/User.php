<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Shridhar\Users\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Exception;

/**
 * Description of User
 *
 * @property int id
 * @property string password
 * @property Collection tokens
 * @property int role_id
 * @property Role role
 * @property Carbon created_at
 * @property Carbon updated_at
 * @author Shridhar
 */
abstract class User extends Model {

    protected $appends = ["is_logged_in", "is_locked"];
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
     * @param array $config
     * @return $this
     */
    public function login($config = []) {
        $options = collect([
            "login_time" => static::$login_time,
        ])->merge($config)->toArray();

        $cookie_name = array_get($options, "cookie_name");
        $login_time = array_get($options, "login_time");

        /** @var UserToken $token */
        $token = static::getLoginToken($cookie_name) ?: $token = $this->tokens()->make();

        $token->ip_address = array_get($options, "ip_address");
        $token->user_agent = array_get($options, "user_agent");
        $token->expiry = Carbon::now()->addSeconds($login_time);
        $token->type = UserToken::LoginType;
        $token->user()->associate($this);
        $token->unlock();
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

        /** @var User $user */
        $user = static::query()->where("username", $username)->first();

        if (!$user) {
            throw new Exception("User account not found");
        }

        if (!$user->match_password($password)) {
            throw new Exception("Invalid Password");
        }

        return $user->login($params);
    }

    /**
     * @param $username
     * @return mixed
     */
    static function findByUsername($username) {
        return static::query()->username($username)->first();
    }

    /**
     * @param Builder $query
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
     * @param string $password
     * @return bool
     */
    function match_password($password) {
        return Hash::check($password, $this->password);
    }

    /**
     * @param string $password
     * @return string
     */
    function hash_password($password) {
        return Hash::make($password);
    }

    /**
     * @param string $value
     * @return string
     */
    public function setPasswordAttribute($value) {
        return $this->attributes['password'] = $this->hash_password($value);
    }

    /**
     * @return BelongsTo
     */
    public function role() {
        return $this->belongsTo(static::$role_class);
    }

    /**
     * @return HasMany
     */
    function tokens() {
        return $this->hasMany(static::$token_class);
    }

    /**
     * @return HasOne
     */
    function login_token() {
        return $this->hasOne(static::$token_class)->where("type", UserToken::LoginType);
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
        return $user && $user->id === $this->id ? true : false;
    }

    /**
     * @return bool
     */
    public function getIsLockedAttribute() {
        $token = static::getLoginToken();
        return $token && $token->user_id === $this->id && $token->is_locked ? true : false;
    }

    /**
     * @param Builder $query
     * @param string $slug
     */
    public function scopeRole($query, string $slug) {
        $query->whereHas("role", function (Builder $query) use ($slug) {
            $query->where("slug", $slug);
        });
    }

    /**
     * @param string $slug
     */
    public function setRoleAttribute(string $slug) {
        /** @var Role $role */
        $role = call_user_func([static::$role_class, "findWithSlug"], $slug);
        if ($role) {
            $this->role_id = $role->id;
        }
    }

    /**
     * @param string|null $cookie_name
     * @return bool
     * @throws Exception
     */
    public static function logout($cookie_name = null) {
        self::removeLoginToken($cookie_name);
        return true;
    }

    /**
     * @param string|null $cookie_name
     * @return bool
     */
    public static function lock($cookie_name = null) {
        $token = static::getLoginToken($cookie_name ?: static::$login_cookie_name);
        if ($token) {
            $token->lock();
        }
        return true;
    }

    /**
     * @param null $cookie_name
     * @return User
     */
    public static function loggedInUser($cookie_name = null) {
        $token = static::getLoginToken($cookie_name);
        if ($token && !$token->is_locked) {
            return $token->user;
        }
    }

    /**
     * @param null $cookie_name
     * @return User
     */
    public static function currentUser($cookie_name = null) {
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
        return $token && !$token->is_locked ? true : false;
    }

    /**
     * @param null $cookie_name
     * @return UserToken
     */
    public static function getLoginToken($cookie_name = null) {
        /** @var UserToken $token */
        $token = call_user_func([static::$token_class, "getFromCookie"], $cookie_name ?: static::$login_cookie_name);
        if ($token && !$token->is_expired && $token->type === UserToken::LoginType) {
            return $token;
        }
    }

    /**
     * @param UserToken $token
     * @param string|null $cookie_name
     */
    public static function setLoginToken($token, $cookie_name = null) {
        if ($token) {
            $token->setCookie($cookie_name ?: static::$login_cookie_name);
        }
    }

    /**
     * @param string|null $cookie_name
     * @return bool
     * @throws Exception
     */
    public static function removeLoginToken($cookie_name = null) {
        $token = call_user_func([static::$token_class, "getFromCookie"], $cookie_name ?: static::$login_cookie_name);
        if ($token) {
            $token->delete();
        }
        Cookie::queue(Cookie::forget($cookie_name ?: static::$login_cookie_name));
        return true;
    }

}

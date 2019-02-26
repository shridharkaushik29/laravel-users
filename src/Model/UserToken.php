<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Shridhar\Users\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;

/**
 * Description of UserToken
 *
 * @author Shridhar
 * @property boolean is_expired
 * @property boolean user_agent
 * @property string ip_address
 * @property string type
 * @property boolean is_locked
 * @property Carbon locked_at
 * @property Carbon last_seen
 * @property Carbon expiry
 * @property array meta
 * @property int id
 * @property User user
 * @property int user_id
 * @method static UserToken find($id)
 */
abstract class UserToken extends Model {

    const LoginType = "login";

    /**
     * @var array
     */
    /**
     * @var array
     */
    /**
     * @var array
     */
    protected $fillable = [
        "user_id",
        "expiry",
        "last_seen",
        "ip_address",
        "locked_at",
        "user_agent",
        "meta"
    ],
        $casts = [
        "meta" => "array",
        "user_id" => "int"
    ],
        $dates = [
        "expiry",
        "last_seen",
        "locked_at"
    ];
    /**
     * @var
     */
    protected static $user_class;

    /**
     *
     */
    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {

            if (empty($model->ip_address)) {
                $model->ip_address = request()->ip();
            }

            if (empty($model->user_agent)) {
                $model->user_agent = request()->userAgent();
            }

        });
    }

    /**
     * @return BelongsTo
     */
    public function user() {
        return $this->belongsTo(static::$user_class);
    }

    /**
     * @return $this
     */
    public function lock() {
        $this->locked_at = Carbon::now();
        return $this;
    }

    /**
     * @return $this
     */
    public function unlock() {
        $this->locked_at = null;
        return $this;
    }

    /**
     * @return $this
     */
    public function touch_last_seen() {
        $this->last_seen = Carbon::now();
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    function getMeta($key) {
        return array_get($this->meta, $key);
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    function setMeta($key, $value) {
        $meta = $this->meta;
        array_set($meta, $key, $value);
        $this->meta = $meta;
        return $this;
    }

    /**
     * @param string $cookie_name
     * @param null $expiry
     * @return $this
     */
    function setCookie($cookie_name, $expiry = null) {
        $cookie = $expiry ? Cookie::make($cookie_name, $this->id, $expiry) : Cookie::forever($cookie_name, $this->id);
        Cookie::queue($cookie);
        return $this;
    }

    /**
     * @param string $cookie_name
     * @return UserToken|null
     */
    static function getFromCookie($cookie_name) {
        $id = Cookie::get($cookie_name);
        if ($id) {
            $token = static::find($id);
            return $token;
        }
    }


    /**
     * @param $cookie_name
     */
    static function removeCookie($cookie_name) {
        Cookie::queue(Cookie::forget($cookie_name));
    }

    /**
     * @return bool
     */
    public function getIsExpiredAttribute() {
        return strtotime($this->expiry) < time() ? true : false;
    }

    /**
     * @return bool
     */
    public function getIsLockedAttribute() {
        return $this->locked_at ? true : false;
    }

}

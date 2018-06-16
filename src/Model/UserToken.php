<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Shridhar\Users\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cookie;

/**
 * Description of UserToken
 *
 * @author Shridhar
 */
abstract class UserToken extends Model {

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
                "meta" => "array"
                    ],
            $dates = [
                "expiry"
    ];
    protected static $user_class;

    protected static function boot() {
        parent::boot();
        static::creating(function($model) {
            if (empty($model->ip_address)) {
                $model->ip_address = request()->ip();
            }
            if (empty($model->user_agent)) {
                $model->user_agent = request()->userAgent();
            }
        });
    }

    protected static function makeOtp() {
        $token = static::make();
        $token->type = "otp";
    }

    public function user() {
        return $this->belongsTo(static::$user_class);
    }

    public function lock() {
        $this->locked_at = date("Y-m-d H:i:s");
        return $this;
    }

    public function unlock() {
        $this->locked_at = null;
        return $this;
    }

    public function touch_last_seen() {
        $this->last_seen = date("Y-m-d H:i:s");
        return $this;
    }

    public function scopeType($query, $type) {
        $query->where("type", $type);
    }

    public function getIsLockedAttribute() {
        if ($this->locked_at) {
            return true;
        } else {
            return false;
        }
    }

    function meta($key = null) {
        if (empty($key)) {
            return $this->meta;
        } else {
            return array_get($this->meta, $key);
        }
    }

    function save_cookie($name, $expiry = null) {
        $token_expiry = 600;
        $cookie = Cookie::make($name, $this->id, $expiry ?: $token_expiry);
        Cookie::queue($cookie);
    }

    function scopeCookie($query, $name) {
        $id = Cookie::get($name);
        $query->where("id", $id);
    }

    static function findFromCookie($name) {
        $id = Cookie::get($name);
        if ($id) {
            $token = static::find($id);
            return $token;
        }
    }

    public function getIsExpiredAttribute() {
        if (strtotime($this->expiry) < time()) {
            return true;
        } else {
            return false;
        }
    }

}

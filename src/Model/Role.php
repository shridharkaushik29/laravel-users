<?php

namespace Shridhar\Users\Model;

use Illuminate\Database\Eloquent\Model;

abstract class Role extends Model {

    protected static $user_class;

    protected static function boot() {
        parent::boot();
        static::creating(function($model) {
            if (!$model->slug) {
                $model->slug = str_slug($model->name);
            }
        });
    }

    static function findWithSlug($slug) {
        return static::slug($slug)->first();
    }

    public function users() {
        return $this->hasMany(static::$user_class);
    }

    function scopeSlug($query, $slug) {
        $query->where("slug", $slug);
    }

}

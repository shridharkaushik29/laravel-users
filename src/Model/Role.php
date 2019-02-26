<?php

namespace Shridhar\Users\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Role
 * @property int id
 * @property string slug
 * @property string name
 * @property Carbon created_at
 * @property Carbon updated_at
 * @package Shridhar\Users\Model
 */
abstract class Role extends Model {

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
            if (!$model->slug) {
                $model->slug = str_slug($model->name);
            }
        });
    }

    /**
     * @param string $slug
     * @return Role mixed
     */
    static function findWithSlug($slug) {
        /** @var Role $role */
        $role = static::query()->where("slug", $slug)->first();
        return $role;
    }

    /**
     * @return HasMany
     */
    public function users() {
        return $this->hasMany(static::$user_class);
    }

}

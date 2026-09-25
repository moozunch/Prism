<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    public const SINGLETON_KEY = 'default';

    protected $fillable = [
        'singleton_key',
        'name',
        'email',
        'phone',
        'address',
        'description',
    ];

    public static function profile(): self
    {
        return static::query()->firstOrCreate(
            ['singleton_key' => self::SINGLETON_KEY],
            ['name' => config('app.name', 'PRISM')],
        );
    }
}

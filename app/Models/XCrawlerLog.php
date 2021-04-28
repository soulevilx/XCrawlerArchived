<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * @property boolean $succeed
 * @method static Builder forSource(string $source)
 * @package App\Models
 */
class XCrawlerLog extends Model
{
    use HasFactory, Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'url',
        'payload',
        'source',
        'succeed'
    ];

    protected $casts = [
        'url' => 'string',
        'payload' => 'array',
        'source' => 'string',
        'succeed' => 'boolean'
    ];

    public function scopeFilterSource(Builder $query, string $source)
    {
        return $query->where(['source' => $source]);
    }
}

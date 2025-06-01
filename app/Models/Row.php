<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $excel_id
 * @property string $name
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row whereExcelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Row whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Row extends Model {
    public $timestamps = false;

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'file_id',
        'name',
        'date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'date' => 'date:d.m.Y',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IosVersion extends Model
{
    protected $table = 'ios_version';

    protected $fillable = [
        'version',
        'platform',
    ];
}

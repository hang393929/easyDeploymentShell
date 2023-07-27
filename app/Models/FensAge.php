<?php
namespace App\Models;

class FensAge extends BaseModel
{
    protected $table = 'fens_age';

    protected $fillable = [
        'id', 'draw_id', 'range', 'number', 'ratio', 'created_at', 'updated_at'
    ];
}

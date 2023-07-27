<?php
namespace App\Models;

class FensCity extends BaseModel
{
    protected $table = 'fens_city';

    protected $fillable = [
        'id', 'draw_id', 'area_id', 'city_name', 'number', 'ratio', 'created_at', 'updated_at'
    ];
}

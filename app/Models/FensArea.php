<?php
namespace App\Models;

class FensArea extends BaseModel
{
    protected $table = 'fens_area';

    protected $fillable = [
        'id', 'draw_id', 'area_id', 'area_name', 'number', 'ratio', 'created_at', 'updated_at'
    ];
}

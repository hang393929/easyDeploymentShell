<?php
namespace App\Models;

class FensEducation extends BaseModel
{
    protected $table = 'fens_education';

    protected $fillable = [
        'id', 'draw_id', 'name', 'number', 'ratio', 'created_at', 'updated_at'
    ];
}

<?php
namespace App\Models;

class FensInterest extends BaseModel
{
    protected $table = 'fens_interest';

    protected $fillable = [
        'id', 'draw_id', 'name', 'number', 'ratio', 'created_at', 'updated_at'
    ];
}

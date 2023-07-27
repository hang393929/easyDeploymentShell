<?php
namespace App\Models;

class FensGender extends BaseModel
{
    protected $table = 'fens_gender';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'draw_id', 'type', 'number', 'ratio', 'created_at', 'updated_at'
    ];

    const TYPE_MALE   = 1; // 男
    const TYPE_FEMALE = 2; // 女
    const TYPE_OTHER  = 3; // 未知
}

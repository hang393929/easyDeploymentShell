<?php
namespace App\Models;

class FensDraw extends BaseModel
{
    protected $table = 'fens_draw';

    protected $fillable = [
        'id', 'user_unique', 'platform_id', 'project_id', 'fans_cnt', 'created_at', 'updated_at'
    ];
}

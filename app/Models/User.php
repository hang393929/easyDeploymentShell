<?php
namespace App\Models;

class User extends BaseModel
{
    protected $table = 'user';

    protected $fillable = [
        'id', 'account', 'platform', 'user_name', 'project', 'classify', 'channel', 'user_id', 'unique', 'sync_id', 'field',
        'original', 'level', 'status', 'quality', 'credit', 'credit_str', 'fens', 'mcn_id', 'video', 'message', 'level_name',
        'gcard_count', 'total_play', 'update_time', 'bili_score', 'cookie', 'cookie_status', 'kb_status', 'create_time',
        'update_cookie_time', 'page', 'like', 'heads', 'head', 'cookie_text', 'follow', 'finder_username', 'unique_2',
        'nick_name', 'unique_3', 'phone', 'link', 'channel_child'
    ];

    public $timestamps = false;
}

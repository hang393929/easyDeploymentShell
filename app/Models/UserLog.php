<?php
namespace App\Models;

class UserLog extends BaseModel
{
    protected $table = 'user_log';

    protected $fillable = [
        'id', 'user_id', 'date', 'consume_pv', 'like_pv', 'fav_pv', 'cmt_pv', 'share_pv', 'income', 'look_pv', 'recommend',
        'time_s', 'platform', 'fens', 'follow_cancel', 'follow_add', 'create_time', 'sync_id', 'mcn_id', 'video', 'sync_status'
    ];

    public $timestamps = false;
}

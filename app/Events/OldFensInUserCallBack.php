<?php
namespace App\Events;

use App\Events\Event;

class OldFensInUserCallBack extends Event
{
    /**
     * @var bool $status 事件处理结果状态
     */
    public $status = true;

    /**
     * @var array $data 待处理数据
     */
    public $data = [];

    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, array $data)
    {
        $this->user = $user;
        $this->data = $data;
    }
}

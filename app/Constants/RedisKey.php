<?php
namespace App\Constants;
class RedisKey
{
    const REDIS_DATABASES_ZREO  = 0;
    const REDIS_DATABASES_ONE   = 1;
    const REDIS_DATABASES_TWO   = 2;
    const REDIS_DATABASES_THREE = 3;
    const REDIS_DATABASES_FOUR  = 4;
    const REDIS_DATABASES_FIVE  = 5;
    const REDIS_DATABASES_SEVEN = 7;

    public static $conn = [
        self::REDIS_DATABASES_ONE   => 'redis_queue_one',
        self::REDIS_DATABASES_TWO   => 'redis_queue_two',
        self::REDIS_DATABASES_THREE => 'redis_queue_three',
        self::REDIS_DATABASES_FOUR  => 'redis_queue_four',
        self::REDIS_DATABASES_FIVE  => 'redis_queue_five',
        self::REDIS_DATABASES_SEVEN => 'redis_queue_seven',
    ];

}

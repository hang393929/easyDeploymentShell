<?php


return [
    // 效验接口权限-接口白名单
    'checkAuthorityWhite'       => [
        'checklogin',
        'user',
        'userj',
        'category',
        'delallpush',
        'delbind',
        'screen/accountstotal',
        'screen/fenstotal',
        'screen/fensranklist',
        'screen/fensplatformtotal',
        'screen/playranklist',
        'screen/playhistorytotal',
        'screen/pushnewlist',
        'screen/pushhistorytotal',
        'callback_water',
        'version',
        'platformedit',
        'screen/fensandplaytotal',
        'platformalllist',
        'unbinduser'
    ],

    // 不刷新登录信息的接口
    'checkAuthorityNotSetToken' => [
        'cookie',
        'conip',
        'updateinfo',
        'localcache'
    ],

];

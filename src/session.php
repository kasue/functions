<?php

namespace kasue\functions;

class session
{
    protected static $is_session_start = false;

    function __construct()
    {
        // session.auto_start が有効な場合はセッションスタート状態として処理
        if(ini_get('session.auto_start'))
            self::$is_session_start = true;

        // セッションが開始されていなければセッション開始
        if(!self::$is_session_start)
        {
            session_start();
            self::$is_session_start = true;
        }
            
    }

}
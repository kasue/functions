<?php

namespace kasue\functions;

// static で使える関数群
class functions
{
    // ランダム文字列を生成
    static function rnd_str($len, $src = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_')
    {
        // $src で指定した文字種からまず使う文字をランダムで取得し、それをシャッフルする
        for ($i=0; $i < $len; $i++) { 
            $tmp[] = substr($src, random_int(0, strlen($src)-1), 1);
        }

        shuffle($tmp);
        return implode('', $tmp);
    }

    // Ramsey\Uuid を使ったUUID取得のラッパー
    static function uuidv4()
        { return \Ramsey\Uuid\Uuid::uuid4()->toString(); }


}
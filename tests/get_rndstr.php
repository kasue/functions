<?php

require_once __DIR__ . '/../vendor/autoload.php';

// 32文字のランダム文字列
echo \kasue\functions\functions::rnd_str(32) . "\n";

// 32文字のランダム文字列（選択肢を1文字にして連続する32文字）
echo \kasue\functions\functions::rnd_str(32, '1') . "\n";


// UUIDv4取得
echo \kasue\functions\functions::uuidv4() . "\n";
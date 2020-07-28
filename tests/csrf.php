<?php

// session , cookie や REQUEST_METHOD が動作に影響するため、CLIでテストするとNoticeやWarningが表示されます。

require_once __DIR__ . '/../vendor/autoload.php';

$nonce = new \kasue\functions\csrf('my_nonce', 'my_win_id', 'my_nonce', 0);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h2>NONCE情報をHTMLで出力するした場合</h2>
    <pre>
    <?php var_dump($nonce->generate_nonce(true)); ?>
    </pre>

    <h2>NONCE情報を配列で取得し別途利用方法を設定する場合</h2>
    <pre>
    <?php var_dump($nonce->generate_nonce(false)); ?>
    </pre>

</body>
</html>


var_dump($nonce->generate_nonce(false));
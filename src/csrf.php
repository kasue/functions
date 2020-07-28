<?php

// CSRF対策として nonce を使った画面遷移の管理を行うクラス
// インスタンスとして生成した時点で検証自体は完了している : ->verify に結果を保存
// 検証に使われたキー（win_id）のデータは削除しワンタイム利用とする
// 

namespace kasue\functions;

class csrf
{
    private $config = [
        // セッション名、cookie名に使う名前
        'name' => '',

        // form として出力する際の name属性
        'form_win_id_name' => '',
        'form_nonce_name' => '',

        'session_timeout' => 0
    ];

    // リクエストに含まれる変数
    private $win_id;
    private $token;

    // 検証結果
    public $verify;

    // コンストラクタ
    //      パラメータ未設定で生成した場合、ランダムにwindow_id を生成
    function __construct(
        $nonce_name = 'my_nonce',
        $form_win_id = 'my_win_id',
        $form_nonce = 'my_nonce',
        $session_timeout = 0
    ) {
        $this->config['name'] = $nonce_name;
        $this->config['form_win_id_name'] = $form_win_id;
        $this->config['form_nonce_name'] = $form_nonce;
        $this->config['session_timeout'] = $session_timeout;

        // セッション開始（多重開始抑止）
        new session();

        // POSTでないと成立しないのでその場合は常に検証失敗
        // CLIの場合検証不能なので常に失敗
        $this->verify = (php_sapi_name() === 'cli') ? false : ($_SERVER['REQUEST_METHOD'] === 'POST');

        // 入力チェック
        $this->verify = ($this->verify) ? $this->check_input() : false;

        // フォーム、cookie、session を利用したpassword_verify
        $this->verify = ($this->verify) ? password_verify($this->token, $_SESSION[$this->config['name']][$this->win_id]) : false;

        // 1度検証したら破棄する
        $this->abandon();
    }

    // 入力値のチェック
    private function check_input()
    {
        // window_id がPOSTされてきている場合、取得（未設定の場合 ''）
        $this->win_id = (filter_input(INPUT_POST, $this->config['form_win_id_name']) ?? '');

        // hidden に設定したnonceがPOSTされている場合取得（設定値or''）
        $nonce = (filter_input(INPUT_POST, $this->config['form_nonce_name']) ?? '');

        // いずれかの入力値が '' 扱いの場合、 false
        if ($this->win_id === '' || $nonce === '') return false;

        // session / cookie に win_id がキーとなっているデータがなければ false
        if (!isset($_SESSION[$this->config['name']][$this->win_id])) return false;
        if (!isset($_COOKIE[$this->config['name']][$this->win_id])) return false;

        $this->token = $_COOKIE[$this->config['name']][$this->win_id] . $nonce;

        return true;
    }

    // session / cookie の破棄（合わせて整合性の整理も行う）
    private function abandon()
    {
        // リクエストに対するsession の破棄
        if ($this->win_id !== '')
        {
            if(isset($_SESSION[$this->config['name']][$this->win_id])) 
                unset($_SESSION[$this->config['name']][$this->win_id]);

            if(isset($_COOKIE[$this->config['name']][$this->win_id])) 
                $this->set_cookie($this->win_id, '', time() - (60 * 60 * 4));
        }

        // // session も cookie も存在する場合
        if (isset($_SESSION[$this->config['name']]) && isset($_COOKIE[$this->config['name']]))
        {
            // キーが一致しているもののみ残す
            //      session
            foreach(array_diff_key($_SESSION[$this->config['name']], $_COOKIE[$this->config['name']]) as $key => $val)
                { unset($_SESSION[$this->config['name']][$key]); }

            //  cookie
            foreach(array_diff_key($_COOKIE[$this->config['name']], $_SESSION[$this->config['name']]) as $key => $val)
                { $this->set_cookie($key, '', time() - (60 * 60 * 4)); }
        }

        // sessionがあってcookie がない場合セッション全消し
        if (isset($_SESSION[$this->config['name']]) && !isset($_COOKIE[$this->config['name']]))
            unset($_SESSION[$this->config['name']]);

        // セッションがなく cookie のみある場合cookie全消し
        if (!isset($_SESSION[$this->config['name']]) && isset($_COOKIE[$this->config['name']])) {
            foreach (array_keys($_COOKIE[$this->config['name']]) as $key) {
                $this->set_cookie($key, '', time() - (60 * 60 * 4));
            }
        }
    }

    // nonce の生成、設定
    //  $ret_input_html = true は input タグ、 false は配列で返す
    function generate_nonce($ret_input_html = true)
    {
        // 生成
        $win_id = functions::rnd_str(12);
        $nonce = functions::rnd_str(32);
        $cookie = functions::rnd_str(64);

        // セッションに保存
        $token = $cookie . $nonce;
        $_SESSION[$this->config['name']][$win_id] = password_hash($token, PASSWORD_DEFAULT);

        // cookie 保存
        $expire = (($this->config['session_timeout'] === 0) ? 0 : time() + $this->config['session_timeout']);
        $this->set_cookie($win_id, $cookie, $expire);


        if($ret_input_html)
        {
            // 戻り値はform部分のHTML
            // form
            $type='hidden';
            // $type='text';    // デバッグ用
            $html  = '<input type="%1$s" class="%4$s win_id" name="%2$s" value="%3$s">';
            $html .= '<input type="%1$s" class="%4$s nonce" name="%4$s" value="%5$s">';

            return sprintf($html, 
                        $type,
                        $this->config['form_win_id_name'],
                        $win_id,
                        $this->config['form_nonce_name'],
                        $nonce
                    );
        } else {
            // 戻り値は配列
            return ['win_id' => $win_id, 'nonce' => $nonce];
        }
    }

    // cookie 設定
    private function set_cookie($win_id, $data, $expire = 0)
    {
        if(php_sapi_name() !== 'cli')
        {
            // cookie 保存
            $secure = (bool) ($_SERVER['HTTPS'] ?? false);

            $key = $this->config['name'] . '[' . $win_id . ']';
            setcookie($key, $data, $expire, '', '', $secure, true);
        }
    }
}

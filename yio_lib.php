<?php

//共通ライブラリ
// ネットワーク、ファイル入出力等、IO関連をまとめたもの




//--------------------------------------------------------------------------//
// EXE execute
//--------------------------------------------------------------------------//

/*
$descriptorspec = array(
	0 => array("pipe", "r"),  // stdin は、子プロセスが読み込むパイプです。
	1 => array("pipe", "w"),  // stdout は、子プロセスが書き込むパイプです。
	2 => array("file", $GLOBALS['LALA_TEMP'] . '/error-output.txt', "a") // はファイルで、そこに書き込みます。
);
*/


class EXEIO
{

	function __construct()
	{
	
	}

	function open($ip, $pass)
	{
		$this->ip = $ip;
		$this->pass = $pass;
		
		// $pipes はこの時点で次のような形を取っています。
		// 0 => 子プロセスの stdin に繋がれた書き込み可能なハンドル
		// 1 => 子プロセスの stdout に繋がれた読み込み可能なハンドル
		// すべてのエラー出力は /tmp/error-output.txt に書き込みされます。
		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin は、子プロセスが読み込むパイプです。
			1 => array("pipe", "w"),  // stdout は、子プロセスが書き込むパイプです。
			2 => array("file", $GLOBALS['LALA_TEMP'] . '/error-output.txt', "a") // はファイルで、そこに書き込みます。
		);

		$ssh_cmd = 'ssh' . $this->ssh_opt . ' ' . $this->ip;
//exit($ssh_cmd);
		$this->process = proc_open($ssh_cmd, $descriptorspec, $this->pipes);
		//IMDL sshコマンドが無い場合の処理がいるかも。
		
		if (is_resource($this->process) === FALSE)
		{
			echo('proc_open failed'); //IMDL ログに書く
			return FALSE;
		}
		
		if($this->login() === FALSE)
			return FALSE;
		return TRUE;
	}
	
	
	function cmd($cmd)
	{
		$cmd = trim($cmd); //改行があったりなかったりするので、一旦消して再度付ける。
		$cmd .= "\n";
		fwrite($this->pipes[0], $cmd);
		$resp = $this->waitPrpt(array($this->prpt));
		if($resp === FALSE)
		{
			$info_msg = 'INFO:	Command time-out. command: ' . $cmd_str;
			writeLog(0, "200", $info_msg);
			return FALSE; //IMDL macPSTelnetではreturn 0 にしている。
		}
		
		return $this->chkResp($resp[0]); //継承元のmacPSTelnetのメソッド
	}
	
	// プロンプトを待つ関数
	//引数： $exp_ar === 期待（expect）する文字列の配列
	// 戻り値： array(レスポンスのデータ、マッチした文字列)
	function waitPrpt($exp_ar)
	{
		$resp = '';
		for($i=0; $i < $this->timeout; $i += $this->timeout_intarval, usleep($this->timeout_intarval))
		{
			$resp .= $this->readResp();
			foreach($exp_ar as $exp)
			{
				if(strpos($resp, $exp) !== FALSE)
					return array($resp, $exp);
			}
		}
		return FALSE;
	}
	
	function readResp(){
		//freadで応答が無いと止まってしまうので非同期モードにする
		stream_set_blocking($this->pipes[1],0);

		$read_str = fread($this->pipes[1], 8192);

		if($read_str === FALSE)
		{
			$this->close(FALSE);
			$info_msg = 'ERROR:	SSH fread failed.';
			writeLog(0, "59Y", $info_msg);
			return FALSE;
		}
		
		if($this->stdout) //デバッグ用
				echo $read_str, "\n";
		return $read_str;
	}
	
}






function HTTPGet($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); //結果をTRUE/FAULSEではなく、Body/FAULSEで返す
	


	$response = curl_exec($ch);
	exit("ERROR: cannot access $url");
	curl_close($ch);
	return $response;
}


function HTTPreq($method, $url, $proxy= FALSE,  $cookie_value = FALSE, $post_data = FALSE, $header_ar = FALSE, $set_cookie=FALSE, $use_cookie=FALSE)
{
	$curl_id = curl_init();
	curl_setopt($curl_id, CURLOPT_URL, $url);//URLをセット
	curl_setopt($curl_id, CURLOPT_RETURNTRANSFER, TRUE); //これが無いと標準出力に出力する。PHPマニュアルはウソが書いてある。

	curl_setopt($curl_id, CURLINFO_HEADER_OUT,TRUE); //Request Header
	curl_setopt($curl_id, CURLOPT_HEADER, TRUE); //Response Header



	if($method === 'POST')
	{
		curl_setopt($curl_id, CURLOPT_POST, 1);
		curl_setopt($curl_id, CURLOPT_POSTFIELDS, $post_data);
	}

	if($cookie_value)
		curl_setopt($curl_id, CURLOPT_COOKIE, $cookie_value);
	if($header_ar)
		curl_setopt($curl_id, CURLOPT_HTTPHEADER, $header_ar);

	if($set_cookie)
		curl_setopt($curl_id, CURLOPT_COOKIEJAR, $set_cookie);
	if($use_cookie)
		curl_setopt($curl_id, CURLOPT_COOKIEFILE, $use_cookie);

	if($proxy)
	{
		list($proxy_ip, $proxy_port) = explode(':', $proxy);
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		curl_setopt($ch, CURLOPT_PROXY, 'http://' . $proxy);	//プロキシアドレス設定（プロキシのアドレス:ポート名）
		curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);//念のためプロキシのポートを指定
	}

	//SSL
	list($scheme, $path) = explode(':', $url, 2);
	if($scheme === 'https')
	{
//		curl_setopt($curl_id, CURLOPT_SSLVERSION, 3);
		curl_setopt($curl_id, CURLOPT_SSL_VERIFYPEER, FALSE); //サーバ証明書検証を無視
		curl_setopt($curl_id, CURLOPT_SSL_VERIFYHOST, FALSE); //サーバ証明書検証を無視
	}


	$resp = curl_exec($curl_id);
	if(curl_errno($curl_id) != 0)
	{
		$error_msg = curl_error($curl_id);
		curl_close($curl_id);
		// 接続ができない場合の処理（エラー処理)
		die($error_msg);
	}
	else
	{
		echo curl_getinfo($curl_id, CURLINFO_HEADER_OUT);
		//$statusCode = curl_getinfo($curl_id, CURLINFO_HTTP_CODE);
		//var_dump($statusCode);

		curl_close($curl_id);
		return $resp;
	}
}







function writeStr2File($file_name, $str)
{
	$fp = fopen($file_name, "w");
	if($fp === FALSE)
		return FALSE;
	flock($fp, LOCK_EX);
        fwrite($fp, $str);
	flock($fp, LOCK_UN);
        fclose($fp);
	return TRUE;
}








?>

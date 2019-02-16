<?php

/*
echo '<PRE>';
var_dump($_POST);
echo '</PRE>';
exit;
*/


$url = $_POST['url'];


if(empty($url))
	exit('Error: No URL');

$url_parsed = parse_url($url);

if($url_parsed === FALSE)
	exit('Error: Invalid URL');

if(empty($url_parsed['scheme']))
{
	$url = 'http://' . $url;
	$url_parsed = parse_url($url);
}


if(empty($_POST['proxy']))
	$proxy = FALSE;
else
	$proxy = $_POST['proxy'];

//ヘッダ作成

$header_ar = mkHeader($_POST['req_hdr'], $url_parsed);




//POSTの場合
$post_str = $_POST['post_str'];
if(empty($post_str))
{
	$method = 'GET';
	$post_ar = FALSE;
}
elseif($post_str === 'HEAD') //POST文字列に「HEAD」と指定したらHEADメソッドとする。隠しオプション。
{
	$method = 'HEAD';
	$post_ar = FALSE;
}
else
{
	$method = 'POST';
//	$post_ar = mkPostData($post_str);
	$post_ar = $post_str;

}




//リクエスト
list($req_header, $res_header, $body) = HTTPreq($method, $url, $proxy,  FALSE, $post_ar, $header_ar);
echo '<pre>';
echo showSeparator('Request Header');
echo $req_header;

echo showSeparator('Response Header');
echo $res_header;

echo showSeparator('Body');
echo htmlspecialchars($body);
echo '</pre>';

//var_dump($req_header, $res_header, $body);




//----------------------------- libs --------------------------------------------------//


function showSeparator($str)
{
	return '<br>-------------------------------- ' . $str . ' ----------------------------------' . "\n";
}

//改行テキストを1行毎の配列に変換
function chTxt2Ar($txt)
{
	if(empty($txt))
		return FALSE;

	$array = explode("\n", $txt); // とりあえず行に分割
	$array = array_map('trim', $array); // 各要素をtrim()にかける
	$array = array_filter($array, 'strlen'); // 文字数が0のやつを取り除く
	$array = array_values($array); // これはキーを連番に振りなおしてるだけ


	return $array;
}




function HTTPreq($method, $url, $proxy= FALSE,  $cookie_value = FALSE, $post_data = FALSE, $header_ar = FALSE, $set_cookie=FALSE, $use_cookie=FALSE, $return_stats_code = FALSE)
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
	elseif($method === 'HEAD')
	{
		curl_setopt($curl_id, CURLOPT_CUSTOMREQUEST, 'HEAD');
		curl_setopt($curl_id, CURLOPT_NOBODY, true);
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

		$req_header = curl_getinfo($curl_id, CURLINFO_HEADER_OUT);
//		var_dump($req_header);exit;
		//この場合はステータスコードを返して終了する
		if($return_stats_code)
		{
			$stats_code = curl_getinfo($curl_id, CURLINFO_HTTP_CODE);
			curl_close($curl_id);
			//var_dump($statusCode);
			return $stats_code;
			
		}
		
		
		$header_size = curl_getinfo($curl_id, CURLINFO_HEADER_SIZE);
//var_dump($info);exit;
		// ヘッダ部分を取得
		$res_header = substr ($resp, 0, $header_size);

		// ボディ部分を取得
		$body = substr ($resp, $header_size);
//var_dump($resp);exit;

		curl_close($curl_id);
		return array($req_header, $res_header, $body);
	}
}



function mkPostData($str)
{
	$str_ar = explode('&', $str);
	foreach($str_ar as $part)
	{
		$part_ar = explode('=', $part);
		if(empty($part_ar[1]))
			exit('Error 1: invalid post string');
		if(isset($part_ar[2]))
			exit('Error 2: invalid post string');
		
		$post_ar[$part_ar[0]] = $part_ar[1];
	}
	return $post_ar;
}


function mkHeader($post_header_ar, $url_parsed)
{
	$post_header_ar = chTxt2Ar($_POST['req_hdr']);
	$accept_flg = FALSE;
	$add_host_hdr_flg = FALSE; 
	foreach($post_header_ar as $header_line)
	{
		//Hostヘッダの処理
		if(strpos($header_line, 'Host:') === 0)
		{
			$add_host_hdr_flg = TRUE;
			if($_POST['host_hdr'] === 'auto')
			{
				$header_ar[] = 'Host: ' . $url_parsed['host'];
				continue;
			}
			//($_POST['host_hdr'] === 'org') の場合は、そのまま。
		}
	
		//Acceptヘッダの処理。指定しないとCURLが勝手に付けちゃうので、ここで有無をチェックし、無ければ、後で「Accept:」を追加する。
		if(strpos($header_line, 'Accept:') === 0)
			$accept_flg = TRUE;
	
		$header_ar[] = $header_line;
	}
	
	if($add_host_hdr_flg === FALSE && $_POST['host_hdr'] === 'org') //ここがFALSEのままならHostヘッダは付けない
		$header_ar[] = 'Host:'; //こうするとHostヘッダが付かなくなる。


	if($accept_flg === FALSE)
		$header_ar[] = 'Accept:'; //これを付けとけばAccept:*/*は付かない

	return $header_ar;
}



?>

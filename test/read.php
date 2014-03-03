<?php

new READ();

class READ{
	private $url;
	private $path = "..";
	private $bbs;
	private $key;
	private $option;
	private $mode;


	function READ(){
		error_reporting(0);
		$this->url = $this->GenerateKizunaURL();

		if($_GET['bbs'] and $_GET['key']){
			$this->bbs = $_GET["bbs"];
			$this->key = $_GET["key"];
		}
		elseif ($_SERVER['PATH_INFO']){
			list(, $this->bbs, $this->key, $this->option) = explode("/", $_SERVER['PATH_INFO']);
		}
		elseif($_GET['PATH_INFO']){
			list(, $this->bbs, $this->key, $this->option) = explode("/", $_GET['PATH_INFO']);
		}
		else { $this->PrintError("不正なパラメータです。"); }

		if(preg_match("/[\.\/]/", "$this->bbs$this->key")){ $this->PrintError("不正なキーです。"); }
		if ($this->bbs and $this->key) { $this->PrintThread(); }
		else                           { $this->PrintError("キーが存在しません。"); }
	}

	
	function PrintThread(){
		$i = 0;
		$time = time();
		preg_match("/(\d*)([-]*)(\d*)/",$this->option,$matches);
		if($matches[1] == ""){
			$matches[1]= 1;
			$matches[3]= 1001;
		}elseif($matches[2] == "-" && $matches[3] == ""){
			$matches[3]= 1001;
		}elseif($matches[3] == ""){
			$matches[3]= $matches[1];
		}
		$file = "$this->path/$this->bbs/dat/$this->key.dat";
		if(!is_file($file)){
			$key4 = substr($this->key, 0, 4);
			$key5 = substr($this->key, 0, 5);
			$file = "$this->path/$this->bbs/kako/$key4/$key5/$this->key.dat";
			if(is_file($file)){ $this->mode = "readonly"; }
			else { $this->PrintError("スレッドが見つかりませんでした。"); }
		}
		$fp = fopen($file, 'rb');
		while(($line = fgets($fp)) !== false){
			$i++;
			list($name, $url, $date, $text, $subject) = explode("<>", $line);
			if($date == "あぼーん"){ continue; }
			$text = preg_replace("/\&gt;\&gt;(\d+)([-]*)(\d*)/", "<a href=\"#res$1$2$3\" class=\"anker\">&gt;&gt;$1$2$3</a>", $text);
			$text = preg_replace("/(https?:\/\/[a-zA-Z0-9\;\/\?\:\@\&\=\+\$\,\-\_\.\!\~\*\'\(\)\%\#]+)/", "<a href=\"\\1\" target=\"_blank\" rel=\"nofollow\">\\1</a>", $text);
			if ($url) {
				if (preg_match("/^https?:\/\//i", $url)){ $namestring = "<a href=\"$url\" target=\"_blank\" rel=\"nofollow\">$name</a>"; }
				else { $namestring = $name; }
			}
			else{
				$namestring = $name;
			}
			if ($i == 1){ $stock_subject = rtrim($subject); }
			$disp = "";
			if($i < $matches[1] || $i > $matches[3])$disp = ' style="display: none;" ';
			$dl .= "<dt id=\"res{$i}\" $disp>$i 名前：<span class=\"name\">$namestring</span> 投稿日：$date ";
			if(strlen($url))$dl .= "<span class=\"mail\">[<span class=\"url\">$url</span></span>]";
			$dl .= "</dt><dd id=\"mes{$i}\" $disp>$text</dd>\n";
		}

		$cookie_name = htmlspecialchars($_COOKIE["NAME"]);
		$cookie_mail = htmlspecialchars($_COOKIE["MAIL"]);
		
		$form = <<<PHPHereDocument
<script type="text/javascript">
var url   = "{$this->url}/test/bbs.cgi?guid=ON";
var bbs   = "{$this->bbs}";
var key   = "{$this->key}";
var cname = "{$cookie_name}";
var cmail = "{$cookie_mail}";
document.write('<form action="'+ url +'" method="POST" id="resform">');
document.write('<input type="hidden" name="bbs" value="'+ bbs +'">');
document.write('<input type="hidden" name="key" value="'+ key +'">');
document.write('名前 <input type="text" name="FROM" value="'+ cname +'"> ');
document.write('メール <input type="text" name="mail" value="'+ cmail +'"><br>');
document.write('<textarea name="MESSAGE"></textarea><br>');
document.write('<input type="submit" name="submit" value="書き込む">');
document.write('<input type="text" name="url" value="" id="trap1"><input type="password" name="password" value="" id="trap2">');
document.write('</form>');
document.getElementById('trap1').style.display = "none";
document.getElementById('trap2').style.display = "none";
</script>
PHPHereDocument;

		if ($i >= 1000) { $this->mode = "readonly"; }
		if ($this->mode == "readonly"){ $form = ""; }
		header("Content-type: text/html; charset=shift_jis");

		print <<< PHPHereDocument
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="shift_jis">
  <title>$stock_subject</title>
  <link href="{$this->url}/{$this->bbs}/index.css" rel="stylesheet">
  <link href="{$this->url}/test/css/style.css" rel="stylesheet">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
  <script src="{$this->url}/test/js/popup.js"></script>
</head>
<body>
<nav id="top"><a href="{$this->url}/{$this->bbs}/">掲示板に戻る</a></nav>

<h1>{$stock_subject}</h1>
<dl id="thread">
{$dl}</dl>

{$form}

</body>
</html>
PHPHereDocument;

		exit;
	}


	
	
	function PrintError($str){
		header("Cache-Control: no-cache");
		header("Content-type: text/html; charset=shift_jis");

		print "<html><!-- 2ch_X:error --><head><title>ＥＲＲＯＲ！</title></head>";
		print "<body><b>ＥＲＲＯＲ：$str</b>";
		print "<br><a href=\"javascript:history.back()\">戻る</a></body></html>";

		exit;
	}



	function GenerateKizunaURL(){
		if($_SERVER['HTTPS']=="on"){ $protocol = "https://"; }
		else{ $protocol = "http://"; }
		
		$request_uri = preg_replace("/\/test\/.*/", "", $_SERVER['REQUEST_URI']); // /test/以下を削除
		
		$url = $protocol . $_SERVER["HTTP_HOST"] . $request_uri;
		return $url;
	}

}
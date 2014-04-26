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
		
		if(is_numeric($this->option)){
			$outputIndex = $this->option + 1;
		}else{
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
		$res = array();
		$res_idx = 1;
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
			if($i < $matches[1] || $i > $matches[3]){
				$disp = ' style="display: none;" ';
			}
			$dl .= "<dt id=\"res{$i}\" $disp>$i 名前：<span class=\"name\">$namestring</span> 投稿日：$date ";
			$res[$res_idx]["resNo"] = $i;
			$res[$res_idx]["name"] = mb_convert_encoding($namestring, "UTF-8", "SJIS");
			$res[$res_idx]["date"] = $date;
			if(strlen($url))$res[$res_idx]["url"] = mb_convert_encoding($url, "UTF-8", "SJIS");
			$res[$res_idx]["text"] = mb_convert_encoding($text, "UTF-8", "SJIS");
			$res_idx++;
		}
		$newRes = array();
		while($outputIndex <= count($res))
		{
			$newRes[] = $res[$outputIndex];
			$outputIndex++;
		}
		$ajaxData = array();
		$ajaxData["resCount"] = count($res);
		$ajaxData["newRes"] = $newRes;
		echo json_encode($ajaxData);
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
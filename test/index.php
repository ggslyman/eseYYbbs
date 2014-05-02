<?php
new INDEX();

class INDEX{
	private $title = "掲示板";
	private $bbs;
	private $disabled;

	function INDEX(){
		//error_reporting(0);
		require_once "./common.php";
		require_once "./setting.php";

		$this->bbs = $_GET["bbs"];
		$settingFilePath = "../$this->bbs/";
		$testPath = "../test/";

		$this->disabled = file_exists($settingFilePath.'.disabled');

		$tmptitle = file_get_contents($settingFilePath.'.title');
		if(strlen($tmptitle))$this->title = $tmptitle;
		$cookie_name = "";
		$cookie_mail = "";
		if(isset($_COOKIE["NAME"]))$cookie_name = htmlspecialchars($_COOKIE["NAME"]);
		if(isset($_COOKIE["MAIL"]))$cookie_mail = htmlspecialchars($_COOKIE["MAIL"]);
		$description = "";
		if(file_exists($settingFilePath.'.description'))$description = file_get_contents($settingFilePath.'.description');
		if(file_exists($testPath.'.announce'))$announce = file_get_contents($testPath.'.announce');
		$skin = "2ch";
		if(file_exists($testPath.'.skin'))$skin = file_get_contents($testPath.'.skin');

		$subjectlist = file($settingFilePath."subject.txt");
		$header = getHeader();
		if($this->disabled){
			header("Content-type: text/html; charset=shift_jis");
			echo "現在、この板は停止中です。";
		}else{

			$link = '';
			$i = 0;
			foreach($subjectlist as $line){
				$i++;
				list($key, $subject) = explode("<>", $line);
				$subject = rtrim($subject);
				preg_match("/\((\d+)\)$/", $subject, $matches);
				$rescount = $matches[1];
				$subject = preg_replace("/\(\d+\)$/", "", $subject);
				$key = str_replace(".dat", "", $key);
				$link .= "<li><a href=\"../test/read.cgi/{$this->bbs}/{$key}/l50\">新着</a> <a href=\"../test/read.cgi/{$this->bbs}/{$key}/\">$subject</a> ({$rescount})</li>";
			}
			if(isset($_GET["d"]) && $_GET["d"]!=="1")$description = "";
			$script = <<<PHPHereDocument
<script type="text/javascript">
var url   = "../test/bbs.cgi?guid=ON";
var bbs   = "{$this->bbs}";
var cname = "{$cookie_name}";
var cmail = "{$cookie_mail}";
var formHtml =  '<form action="'+ url +'" method="POST" id="threadform">';
formHtml = formHtml + '<input type="hidden" name="bbs" value="'+ bbs +'">';
formHtml = formHtml + '<div id="formline1">題名 <input type="text" name="subject" id="formtitle"></div>';
formHtml = formHtml + '名前 <input type="text" name="FROM" value="'+ cname +'"> ';
formHtml = formHtml + 'メール <input type="text" name="mail" value="'+ cmail +'"><br>';
formHtml = formHtml + '<textarea name="MESSAGE"></textarea><br>';
formHtml = formHtml + '<input type="submit" name="submit" value="新規スレッド作成">';
formHtml = formHtml + '<input type="text" name="url" value="" id="trap1"><input type="password" name="password" value="" id="trap2">';
formHtml = formHtml + '</form>';
console.log(formHtml);
document.getElementById('newentry').innerHTML = formHtml;
document.getElementById('trap1').style.display = "none";
document.getElementById('trap2').style.display = "none";
</script>
PHPHereDocument;
			$footer = getFooter();
			$body .= "</div>";
		}
		header("Content-type: text/html; charset=shift_jis");
		print <<< PHPHereDocument
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="shift_jis">
  <title>{$this->title}</title>
  <link href="{$testPath}css/{$skin}/index.css" rel="stylesheet">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
</head>
<body id="top">
<header>
<div id="kanban"></div>
<div class="box menu">
 <div class="content aa" id="localrule">
 <h1>{$this->title}</h1>
 <div class="clear"><hr></div>
  <div id="headtxt">
{$description}
  </div>
 </div>
</div>
</header>

<aside>
<!-- 告知欄は別設定 -->
<div class="box menu">
 <div class="content">
 {$announce}
 </div>
</div>
</aside>

<div id="menu" class="box menu">
 <nav class="content">
  <ol id="threadindex">
{$link}
  </ol>
 </nav>
</div>

<div class="box mkthread">
 <div class="content" id="newentry">
 </div>
 <div class="clear"><hr></div>
</div>

<footer>
{$footer}
<div id="copyright">
</div>
{$script}
</footer>
</body>
PHPHereDocument;

	}
	
	
}

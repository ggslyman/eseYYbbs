<?php
new INDEX();

class INDEX{
	private $title = "掲示板";
	private $bbs;
	private $disabled;

	function INDEX(){
		//error_reporting(0);
		require_once "./common.php";

		$this->bbs = $_GET["bbs"];
		$settingFilePath = "../$this->bbs/";

		$this->disabled = file_exists($settingFilePath.'.disabled');

		$tmptitle = file_get_contents($settingFilePath.'.title');
		if(strlen($tmptitle))$this->title = $tmptitle;

		$cookie_name = "";
		$cookie_mail = "";
		if(isset($_COOKIE["NAME"]))$cookie_name = htmlspecialchars($_COOKIE["NAME"]);
		if(isset($_COOKIE["MAIL"]))$cookie_mail = htmlspecialchars($_COOKIE["MAIL"]);
		$description = "";
		if(file_exists($settingFilePath.'.description'))$description = file_get_contents($settingFilePath.'.description');


		$subjectlist = file($settingFilePath."subject.txt");
		$body = '<div id="container">';
		$body .= getHeader();
		if($this->disabled){
			$body = "現在、この板は停止中です。";
		}else{
			if(strlen($description))$body .= '<div id="description">'.$description.'</div>';
			$body .= '<table id="board">';
			$body .= '<tr><th id="no">No</th><th id="title">タイトル</th><th id="rescount">レス</th></tr>';

			$i = 0;
			foreach($subjectlist as $line){
				$i++;
				list($key, $subject) = explode("<>", $line);
				$subject = rtrim($subject);
				preg_match("/\((\d+)\)$/", $subject, $matches);
				$rescount = $matches[1];
				$subject = preg_replace("/\(\d+\)$/", "", $subject);
				$key = str_replace(".dat", "", $key);
				
				$body .= "<tr><td>$i</td><td><a href=\"../test/read.cgi/{$this->bbs}/{$key}/\">$subject</a></td><td>$rescount</td></tr>\n";
			}
			$body .= '</table>';
			if(isset($_GET["d"]) && $_GET["d"]!=="1")$description = "";
			$body .= <<<PHPHereDocument
<script type="text/javascript">
var url   = "../test/bbs.cgi?guid=ON";
var bbs   = "{$this->bbs}";
var cname = "{$cookie_name}";
var cmail = "{$cookie_mail}";
var heredoc = (function () {/*
$description
*/}).toString().match(/[^]*\/\*([^]*)\*\/\}$/)[1];
document.write('<form action="'+ url +'" method="POST" id="threadform">');
document.write('<input type="hidden" name="bbs" value="'+ bbs +'">');
document.write('<div id="formline1">題名 <input type="text" name="subject" id="formtitle"></div>');
document.write('名前 <input type="text" name="FROM" value="'+ cname +'"> ');
document.write('メール <input type="text" name="mail" value="'+ cmail +'"><br>');
document.write('<textarea name="MESSAGE">'+heredoc+'</textarea><br>');
document.write('<input type="submit" name="submit" value="新規スレッド作成">');
document.write('<input type="text" name="url" value="" id="trap1"><input type="password" name="password" value="" id="trap2">');
document.write('</form>');
document.getElementById('trap1').style.display = "none";
document.getElementById('trap2').style.display = "none";
</script>
PHPHereDocument;
			$body .= getFooter();
			$body .= "</div>";
		}
		header("Content-type: text/html; charset=shift_jis");
		print <<< PHPHereDocument
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="shift_jis">
  <title>{$this->title}</title>
  <link href="./index.css" rel="stylesheet">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
</head>
<body>

{$body}

</body>
</html>
PHPHereDocument;

	}
	
	
}

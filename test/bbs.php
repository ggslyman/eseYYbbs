<?php

new BBS();

class BBS{
	private $url;
	private $path = "..";
	private $time;
	private $bbs;
	private $key;
	private $name;
	private $mail;
	private $subject;
	private $message;
	private $trap1;
	private $trap2;
	private $now;
	private $active;

	function BBS(){
		//error_reporting(0);

		include_once("./setting.php");
		
		$this->url  = $this->GenerateKizunaURL();
		$this->now  = time();
		$this->bbs  = $_POST["bbs"];
		$this->key  = $_POST["key"];

		$this->active = file_exists("../".$this->bbs."/".'.active');
		if(file_exists("../".$this->bbs."/".'.nanashi')){
			$this->nanashi = file_get_contents("../".$this->bbs."/".'.nanashi');
		}else{
			$this->nanashi = "名無しさん";
		}

		$this->name    = $_POST["FROM"];
		$this->mail    = $_POST["mail"];
		$this->subject = $_POST["subject"];
		$this->message = $_POST["MESSAGE"];
		$this->time    = $_POST["time"];
		$this->trap1   = $_POST["url"];
		$this->trap2   = $_POST["password"];
		
		
		if(!$this->bbs){ $this->RecieveRawPost();} //クライアントにより$_POSTにデータが入らないバグ対策
		
		// 管理モードへの分岐
		if((
					$this->name == '復帰'
				or	$this->name == '削除'
				or	$this->name == '倉庫'
				or	$this->name == '2ch'
				// 以下、新規作成分岐
				or	$this->name == 'BAN'
				or	$this->name == 'IP確認'
				or	$this->name == 'IP確認全件'
				or	$this->name == 'BAN解除'
				or	$this->name == 'したらば'
				or	$this->name == 'YY'
				or	$this->name == 'Jane'
				or	$this->name == 'ID'
				or	$this->name == 'パスワード変更'
				or	$this->name == '板名変更'
				or	$this->name == '名無し設定'
				or	$this->name == '板説明更新'
				or	$this->name == '板停止'
				or	$this->name == '板再開'
				or	$this->name == '新規板作成'
				or	$this->name == 'dat修正'
			) and preg_match("/^#/", $this->mail) and $this->bbs){
			include_once("./admin.php");
			new ADMIN($this->name, $this->mail, $this->bbs, $this->key, $this->message);
		}elseif($this->name == '板説明取得'){
			header("Location: ../".$this->bbs."/?d=1");
		}elseif($this->name == 'ハッシュ生成'){
			// パスワードをMD5で保存する形に変更したので、ハッシュ生成機能を追加
			PrintSucess(md5($this->mail));
		}

		if($this->active){
			//通常処理
			$this->CheckInput();
			$this->BlockPost();
			if($this->key){ $this->WriteRes(); }
			else{ $this->WriteThread(); }
			$this->PrintOK();
		}else{
			PrintError("現在、この板は書き込みが禁止されています。");
		}
	}


	function CheckInput(){
		//板とスレッドの存在確認
		if(preg_match("/[\.\/]/", "$this->bbs$this->key")){ PrintError("キーが不正です。"); }
		if(!is_file("$this->path/$this->bbs/subject.txt")){ PrintError("そんな板ないです。"); }
		if($this->key and !is_file("$this->path/$this->bbs/dat/$this->key.dat")){
			$key4 = substr($this->key, 0, 4);
			$key5 = substr($this->key, 0, 5);
			if(is_file("$this->path/$this->bbs/kako/$key4/$key5/$this->key.dat")){ PrintError("このスレッドにはもう書きこめません。"); }
			else { PrintError("そんなスレッドないです。"); }
		}

		if(!strlen($this->message)){ PrintError("本文を入力してください。"); }
		if(!strlen($this->subject) and !$this->key){ PrintError("題名を入力してください。"); }
	}


	function BlockPost(){
		//規制処理もろもろ。作成中
		//ロボ体策。フォームのトラップ(passwordとurl)が入力されていたらロボットなので規制する
		if($this->trap1 or $this->trap2){ PrintError("投稿できません。101"); }
	}
	
	function WriteThread(){
		//BANチェック
		if($this->checkBan())PrintError("投稿できませんでした");
		$this->key = $this->now;
		// 掲示板ごとのスレ建て制限パスの実装
		// パスワード関連の処理の追加とそれに伴う処理のif文内への移動
		$bbs = $this->bbs;
		$file_name = "../".$this->bbs."/".'.password';
		$boardadminpass = file_get_contents($file_name);
		$adminpass = ADMIN_PASSWORD;
		// パスワードファイルが存在した場合のみ、パスワードチェック
		if(strlen($boardadminpass) && $boardadminpass !== md5($this->mail) && $adminpass !== md5($this->mail)){
			PrintError("スレッド作成は管理者のみが行えます");
		}else{
			//subject.txt：$subjectlistに全データ格納
			$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
			if(!$fp_subject){ PrintError("subject.txtが開けません。"); }

			flock($fp_subject, LOCK_EX);
			while(!feof($fp_subject)){
				$subjectlist[] = fgets($fp_subject);
			}

			//DATファイルに書き込む
			$name = $this->GenerateName($this->name);
			$mail = $this->GenerateMail($this->mail);
			$date = $this->GenerateDate();
			$message = $this->GenerateMessage($this->message);
			$subject = $this->GenerateSubject($this->subject);

			if(is_file("$this->path/$this->bbs/dat/$this->key.dat")){
				$this->CloseFile($fp_subject);
				PrintError("もう一度投稿してください");
			}
			$fp_dat = fopen("$this->path/$this->bbs/dat/$this->key.dat", "wb+");
			if(!$fp_dat){
				$this->CloseFile($fp_subject);
				PrintError("DATファイルが作成できません。");
			}
			
			$dat_line = "$name<>$mail<>$date<> $message <>$subject\n";
			$sub_line = "$this->key.dat<>$subject (1)\n";
			$match_count = substr_count("$dat_line$sub_line", "<>");
			if($match_count != 5){
				$this->CloseFile($fp_subject, $fp_dat);
				PrintError("投稿できませんでした");
			}
			
			flock($fp_dat, LOCK_EX);
			fputs($fp_dat, $dat_line);
			$this->CloseFile($fp_dat);

			//subject.txtに書き込む
			array_unshift($subjectlist, $sub_line);
			ftruncate($fp_subject,0);
			rewind($fp_subject);
			fputs($fp_subject, implode("", $subjectlist));
			$this->CloseFile($fp_subject);
		}
	}
	
	
	function WriteRes(){
		//BANチェック
		if($this->checkBan())PrintError("投稿できませんでした");
		$subject_num = $dat_num = 0;
		//subject.txt：書き込もうとするスレを見つける＋$subjectに全データ格納
		$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
		if(!$fp_subject){ PrintError("subject.txtが開けません。"); }

		flock($fp_subject, LOCK_EX);
		while(!feof($fp_subject)){
			$line = fgets($fp_subject);
			preg_match("/^(\d+)\.dat<>(.+)/", $line, $matched);
			$now_key = $matched[1];
			if ($this->key == $now_key) {
				$save_subject_num  = $subject_num;
			}
			$subject[] = $line;
			$subject_num++;
		}
		
		//DATファイル：$datに全データ格納
		$fp_dat = fopen("$this->path/$this->bbs/dat/$this->key.dat", "rb+");
		if(!$fp_dat){ PrintError("DATファイルが開けません。"); }


		flock($fp_dat, LOCK_EX);
		while(!feof($fp_dat)){
			$dat[] = fgets($fp_dat);
			$dat_num++;
		}
		//1000マデ
		if ($dat_num > 1000){
			$this->CloseFile($fp_subject, $fp_dat);
			PrintError("このスレッドにはもう書き込めません。");
		}
		//重複チェック
		list($d1, $d2, $d3, $d4) = explode("<>", $dat[$dat_num-2]);
		if($d4 == $this->message){ 
			$this->CloseFile($fp_subject, $fp_dat);
			PrintError("二重書き込みです。");
		}
		
		//DATに追加
		$name = $this->GenerateName($this->name);
		$mail = $this->GenerateMail($this->mail);
		$date = $this->GenerateDate();
		$message = $this->GenerateMessage($this->message);
		
		list(,,,,$title) = explode("<>", $dat[0]);
		$title = rtrim($title);

		$dat_line = "$name<>$mail<>$date<> $message <>\n";
		$sub_line = "$this->key.dat<>$title (".($dat_num+1).")\n";
		$match_count = substr_count("$dat_line$sub_line", "<>");
		if($match_count != 5){
			$this->CloseFile($fp_subject, $fp_dat);
			PrintError("投稿できませんでした。");
		}

		$dat[] = $dat_line;
		if ($dat_num = 1000){
			$name = $this->GenerateName("１００１");
			$mail = $this->GenerateMail("");
			$date = $this->GenerateDate();
			$message = $this->GenerateMessage("このスレッドは１０００を超えました。\nもう書けないので、新しいスレッドを立ててくださいです。。。");
			$dat_line = "$name<>$mail<>$date<> $message <>\n";
			$dat[] = $dat_line;
			$sub_line = "$this->key.dat<>$title (".($dat_num+1).")\n";
		}

		
		//DATファイルに書き込む
		ftruncate($fp_dat,0);
		rewind($fp_dat);
		fputs($fp_dat, implode("", $dat));
		$this->CloseFile($fp_dat);
		//IPログの作成
		$iplog_line = $dat_num."\t".$this->getHost()."\n";
		file_put_contents("$this->path/$this->bbs/iplog/$this->key.log",$iplog_line, FILE_APPEND | LOCK_EX);

		// $subject編集(sage対応)
		if(!preg_match("/.*sage.*/", $this->mail, $match)){
			array_splice($subject, $save_subject_num, 1);
			array_unshift($subject, $sub_line);
		}else{
			$subject[$save_subject_num] = $sub_line;
		}

		//subject.txtに書き込む
		ftruncate($fp_subject,0);
		rewind($fp_subject);
		fputs($fp_subject, implode("", $subject));
		$this->CloseFile($fp_subject);
	}


	function checkBan(){
		$banList = $this->getBanList();
		$host = $this->getHost();
		if(in_array($host,$banList)){
			return true;
		}
		return false;
	}


	function getBanList(){	
		$banIps = file_get_contents("$this->path/$this->bbs/iplog/ban.log");
		return explode("\n",$banIps);
	}


	function CloseFile($fp1, $fp2 = null){
		if($fp1){
			flock($fp1, LOCK_UN);
			fclose($fp1);
		}
		if($fp2){
			flock($fp2, LOCK_UN);
			fclose($fp2);
		}
	}

	function PrintOK(){
		$url = "$this->url/test/read.cgi/$this->bbs/$this->key/";

		setcookie("NAME", $_POST["FROM"], $this->now+60*60*24*180);
		setcookie("MAIL", $_POST["mail"], $this->now+60*60*24*180);

		header("Cache-Control: no-cache");
		header("Content-type: text/html; charset=shift_jis");

		print "<html><!-- 2ch_X:true --><head><title>書きこみました。</title><meta http-equiv=\"refresh\" content=\"1;URL=$url\"></head>";
		print "<body>書きこみが終わりました。<br><br><a href=\"$url\">画面を切り替える</a>までしばらくお待ち下さい。</body></html>";

		exit;
	}

	function GenerateKizunaURL(){
		if($_SERVER['HTTPS']=="on"){ $protocol = "https://"; }
		else{ $protocol = "http://"; }
		
		$request_uri = preg_replace("/\/test\/.*/", "", $_SERVER['REQUEST_URI']); // /test/以下を削除
		
		$url = $protocol . $_SERVER["HTTP_HOST"] . $request_uri;
		return $url;
	}


	function GenerateDate(){
		$week = array('日','月','火','水','木','金','土');
		$id = "";
		$date1 = date("y/m/d", $this->now);
		$date2 = $week[date("w", $this->now)];
		$date3 = date("H:i:s", $this->now);
		if($this->getID()!=""){
			$id = " ID:".$this->getID();
		}
		return "$date1 $date3".$id;
	}


	function getID() {
		### PC/携帯判別 有効時 ###
		//if($S_MARK == "on") {
		//	# PC/0、携帯/o
		//	$Mark = (GUID != "ON"? " 0": " o");
		//}
		$bbsname = $this->name;
		$bbs = $this->bbs;
		$disp_flag = file_exists("../".$this->bbs."/.id");

		if($disp_flag) {
			$Time = localtime();
			# 掲示板名を後ろから3文字取得
			$BBS = (strlen($bbsname) > 3? substr($bbsname, -3): $bbsname);
			# PCから
			if(GUID != "ON") {
				$IP = explode(".", $_SERVER["REMOTE_ADDR"]);
				$Key = substr($IP[3], -3).substr($IP[2], -1).substr($IP[1], -1).$BBS;
			# 携帯電話から
			} else {
				$Key = $this->getHost().$BBS;
			}
			$ID = preg_replace("/\./", "+", substr(crypt(crypt($Key, substr($Time[5], -2)),($Time[4] + 1) + $Time[3] + 31), -11));

			return $ID.$Mark;

		}

		return "???";
	}


	function getHost() {
		### PC ###
		if(GUID != "ON") {
			$Host = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
		### 携帯 ###
		} else {
			# docomo
			if(isset($_SERVER["HTTP_X_DCMGUID"])) {
				$Host = $_SERVER["HTTP_X_DCMGUID"];
			# au
			} elseif(isset($_SERVER["HTTP_X_UP_SUBNO"])) {
				$Host = $_SERVER["HTTP_X_UP_SUBNO"];
			# SoftBank
			} elseif(isset($_SERVER["HTTP_X_JPHONE_UID"])) {
				$Host = $_SERVER["HTTP_X_JPHONE_UID"];
			# iPhone
			} else {
				$Host = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
				# 未対応
				if(!preg_match("/(panda-world.ne.jp$)/", $Host)) {
					PrintError("その携帯には対応してないかも･･･");
				}
			}
		}

		return $Host;
	}

	function GenerateName($name){
		$name = str_replace(array("\r\n","\r","\n"), "", $name);
		$name = str_replace("&", "&amp;", $name);
		$name = str_replace("<", "&lt;", $name);
		$name = str_replace(">", "&gt;", $name);
		$name = str_replace("\"", "&quot;", $name);


		$name = str_replace("★", "☆", $name);
		$name = str_replace("◆", "◇", $name);
		$name = str_replace("●", "○", $name);
		
		if(!$name){ $name = $this->nanashi; }
		if (preg_match("/#(.+)$/", $name, $match)) {
			$tripkey = $match[1];
			$salt = substr($tripkey . 'H.', 1, 2);
			$salt = preg_replace('/[^\.-z]/', '.', $salt);
			$salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');

			$trip = crypt($tripkey, $salt);
			$trip = substr($trip, -10);
			$name = preg_replace("/#(.+)$/", "", $name);
			$name = $name . ' ◆' . $trip;
		}

		return $name;
	}

	function GenerateMail($mail){
		$mail = str_replace(array("\r\n","\r","\n"), "", $mail);
		$mail = str_replace("&", "&amp;", $mail);
		$mail = str_replace("<", "&lt;", $mail);
		$mail = str_replace(">", "&gt;", $mail);
		$mail = str_replace("\"", "&quot;", $mail);
		$mail = preg_replace('/#(.*)/', '', $mail);

		return $mail;
	}


	function GenerateSubject($subject){
		$subject = str_replace(array("\r\n","\r","\n"), "", $subject);
		$subject = str_replace("&", "&amp;", $subject);
		$subject = str_replace("<", "&lt;", $subject);
		$subject = str_replace(">", "&gt;", $subject);
		$subject = str_replace("\"", "&quot;", $subject);

		return $subject;
	}


	function GenerateMessage($message){
		$message = str_replace("<", "&lt;", $message);
		$message = str_replace(">", "&gt;", $message);
		$message = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $message);
		$message = str_replace(array("\r\n","\r","\n"), "<br>", $message);
		
		return $message;
	}

	
	
	function RecieveRawPost(){
		$posts = explode('&', file_get_contents('php://input'));
		foreach($posts as $buf){
			list($key, $val) = explode('=', $buf);
			$val = urldecode($val);
			if    ($key == 'bbs')     { $this->bbs = $val; }
			elseif($key == 'key')     { $this->key = $val; }
			elseif($key == 'FROM')    { $this->name = $val; }
			elseif($key == 'mail')    { $this->mail = $val; }
			elseif($key == 'MESSAGE') { $this->message = $val; }
			elseif($key == 'subject') { $this->subject = $val; }
			elseif($key == 'url')     { $this->trap1 = $val; }
			elseif($key == 'password'){ $this->trap2 = $val; }
			elseif($key == 'time')    { $this->time = $val; }
		}
	}
}

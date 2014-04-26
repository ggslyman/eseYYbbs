<?php

class ADMIN{
	private $name;
	private $mail;
	private $message;
	private $bbs;
	private $key;
	private $path = "..";
	
	function ADMIN($name, $mail, $bbs, $key = "", $message = ""){
		$this->name = $name;
		$this->mail = $mail;
		$this->bbs  = $bbs;
		$this->key  = $key;
		$this->message = $message;
		
		include_once("./setting.php");
		//板管理パスワード対応
		$adminpass = ADMIN_PASSWORD;
		$file_name = "../".$this->bbs."/".'.password';
		$boardadminpass = file_get_contents($file_name);

		if(strlen($boardadminpass)){
			$pass = $boardadminpass;
		}
		if(password_verify($this->mail,$adminpass) || (isset($pass) && password_verify($this->mail,$pass))){
			switch ($this->name){
				case "復帰":
					$this->Fukki();
					PrintSucess("$this->bbs/subject.txtの復帰完了!");
					braek;
				case "削除":
					if(preg_match("/^http/", $this->message)){
						$this->DeleteThread();
						PrintSucess("スレッドを削除しました。");
					}
					elseif(preg_match("/^>>\d/", $this->message)){
						if(!$this->key){ PrintError("レスの削除は該当のスレッドから行う必要があります。"); }
						$this->DeleteRes();
						PrintSucess("レスを削除しました。");
					}
					else{ PrintError("スレッド削除なら「スレのURL」を、レス削除なら「>>数字」と入力してください"); }
					braek;
				case "BAN":
					$this->banIp();
					PrintSucess("指定レスのIPをBANしました");
					braek;
				case "IP確認":
					$ip = $this->getIpLog();
					PrintSucess("指定レスのIPを取得しました:".$ip);
					braek;
				case "IP確認全件":
					$ip = $this->getIpLogAll();
					PrintSucess("IPを全件取得しました:".$ip);
					braek;
				case "BAN解除":
					$this->liftBan();
					PrintSucess("指定IPをBAN解除しました");
					braek;
				case "NGワード取得":
					$ngwords = $this->getNgWord();
					PrintSucess("NGワードを取得しました<br />".$ngwords);
					braek;
				case "NGワード更新":
					$ip = $this->setNgWord();
					PrintSucess("NGワードを更新しました。");
					braek;
				case "NGワード追加":
					$ip = $this->addNgWord();
					PrintSucess("NGワードを追加しました。");
					braek;
				case "倉庫":
					if(preg_match("/^http/", $this->message)){
						$this->DatOchi();
						PrintSucess("スレッドがDAT落ちしました");
					}
					else{ PrintError("スレッドを落とすなら「スレのURL」を入力してください"); }
					braek;
				case "2ch":
					$this->Import2ch();
					PrintSucess("2chからスレを輸入しました");
					braek;
				case "したらば":
					$this->ImportShitaraba();
					PrintSucess("したらばからスレを輸入しました");
					braek;
				case "YY":
					$this->ImportYy();
					PrintSucess("YYからスレを輸入しました");
					braek;
				case "Jane":
					$this->ImportJaneDat();
					PrintSucess("Janeのdatファイルをインポートしました");
					braek;
				case "ID":
					$res = $this->toggleId();
					switch ($res) {
					case 1:
						PrintSucess("IDが強制表示されるようになりました。");
						break;
					case -1:
						PrintSucess("IDが非表示になりました。");
						break;
					}
					braek;
				case "スレ建て制限":
					$res = $this->restrictBuildThread();
					switch ($res) {
					case 1:
						PrintSucess("管理者のみがスレッドを建てられるように変更しました。");
						break;
					case -1:
						PrintSucess("だれでもスレッドを建てられるように変更しました。");
						break;
					}
					braek;
				case "dat修正":
					$this->convertDat();
					PrintSucess("dat修正完了");
					braek;
				case "パスワード変更":
					if(preg_match("/^#[a-zA-Z0-9]{8,}$/",$this->message,$matches)){
						$this->changePassword($this->message);
						PrintSucess("板管理パスワードを変更しました。");
					}else{
						PrintError("パスワードには#[英数字8文字以上]を設定してください。");
					}
					braek;
				case "板名変更":
					$this->changeTitle($this->message);
					PrintSucess("板名を変更しました。");
					braek;
				case "板説明更新":
					$this->changeDescription($this->message);
					PrintSucess("板説明を更新しました。");
					braek;
				case "名無し設定":
					$this->changeNanashi($this->message);
					PrintSucess("名無し設定を更新しました。");
					braek;
				case "板停止":
					$this->stopBoard();
					PrintSucess("板を停止しました。");
					braek;
				case "板再開":
					$this->startBoard();
					PrintSucess("板を再開しました。");
					braek;
				case "新規板作成":
					if(password_verify($this->mail,$adminpass)){
						$this->createThread($this->message);
						PrintSucess("新規板を作成しました。");
					}
					braek;
				case "追加コマンド":
					braek;
				default :
				PrintError("コマンドが違います。");
			}
		}
		else{
			PrintError("パスワードが違います。");
		}
	}


	function DeleteThread(){
		$command = str_replace("\r", "", $this->message);
		$urllist = explode("\n", $command);
		foreach ($urllist as $url){
			preg_match("/\/(\d+)\/$/", $url, $matched);
			$key = $matched[1];
			$filepath = "$this->path/$this->bbs/dat/$key.dat";
			if(file_exists($filepath)){
				unlink($filepath);
				$dellist[] = $key;
			}
		}
		$this->DeleteFromSubject($dellist);
	}


	function DeleteRes(){
		preg_match("/^>>(\d+)\-?(\d+)?/", $this->message, $matched);
		$start = $matched[1];
		$end   = $matched[2];
		
		if($start == 1){ PrintError("1番目のレスは削除できません。"); }
		if($start <  1){ PrintError("その番号は削除できません。"); }

		if($end > $start){
			for($i=$start; $i<=$end; $i++){
				$delres[] = $i;
			}
		}
		else{
			$delres[] = $start;
		}

		$fp_dat = fopen("$this->path/$this->bbs/dat/$this->key.dat", "rb+");
		if(!$fp_dat){ PrintError("DATファイルが開けません。"); }
		
		flock($fp_dat, LOCK_EX);
		while(!feof($fp_dat)){
			$j++;
			$pad = "";
			$line = fgets($fp_dat);
			foreach($delres as $no){
				if($no == $j){
					$padlength = strlen($line) - strlen("あぼーん<>あぼーん<>あぼーん<>あぼーん<>\n");
					if($padlength > 0){
						$pad  = str_repeat(" ", $padlength);
					}
					$line = "あぼーん<>あぼーん<>あぼーん<>あぼーん<>$pad\n";
					break;
				}
			}
			$dat[] = $line;
		}
		
		ftruncate($fp_dat,0);
		rewind($fp_dat);
		fputs($fp_dat, implode("", $dat));
		flock($fp_dat, LOCK_UN);
		fclose($fp_dat);
	}


	function DatOchi(){
		$command = str_replace("\r", "", $this->message);
		$urllist = explode("\n", $command);
		foreach ($urllist as $url){
			preg_match("/\/(\d+)\/$/", $url, $matched);
			$key = $matched[1];
			$filepath = "$this->path/$this->bbs/dat/$key.dat";
			if(file_exists($filepath)){//DatOchi()はDeleteThread()とほとんど同じだが、ここが違う。
				$key4 = substr($key,0,4);
				$key5 = substr($key,0,5);
				$this->MakeDir("$this->path/$this->bbs", "kako");
				$this->MakeDir("$this->path/$this->bbs/kako", $key4);
				$this->MakeDir("$this->path/$this->bbs/kako/$key4", $key5);
				rename($filepath, "$this->path/$this->bbs/kako/$key4/$key5/$key.dat");
				$dellist[] = $key;
			}
		}
		$this->DeleteFromSubject($dellist);
	}


	function DeleteFromSubject($dellist){ //subject.txtから削除するキーを配列で頂戴
		$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
		flock($fp_subject, LOCK_EX);

		while(!feof($fp_subject)){
			$line = fgets($fp_subject);
			foreach($dellist as $key){
				if(preg_match("/^$key\.dat<>/", $line)){ continue 2; }
			}
			$newsubject[] = $line;
		}

		ftruncate($fp_subject,0);
		rewind($fp_subject);
		fputs($fp_subject, implode("", $newsubject));
		flock($fp_subject, LOCK_UN);
		fclose($fp_subject);
	}

	//BAN登録
	function banIp(){
		$iplogdir  = "$this->path/$this->bbs/iplog";
		//IPログからIPを取得
		$ips = array();
		$tmpips = file_get_contents("$this->path/$this->bbs/iplog/$this->key.log");
		$tmpips = explode("\n",$tmpips);
		foreach($tmpips as $ip_line){
			list($num,$ip) = explode("\t",$ip_line);
			$ips[$num] = $ip;
		}
		//BANリストに追加
		file_put_contents("$this->path/$this->bbs/iplog/ban.log",$ips[$this->message]."\n", FILE_APPEND | LOCK_EX);
	}


	//IP確認
	function getIpLog(){
		$iplogdir  = "$this->path/$this->bbs/iplog";
		//IPログの取得
		$ips = array();
		$tmpips = file_get_contents("$this->path/$this->bbs/iplog/$this->key.log");
		$tmpips = explode("\n",$tmpips);
		foreach($tmpips as $ip_line){
			list($num,$ip) = explode("\t",$ip_line);
			$ips[$num] = $ip;
		}
		return $ips[$this->message];
	}

	//IP確認
	function getIpLogAll(){
		$iplogdir  = "$this->path/$this->bbs/iplog";
		//IPログの取得
		$ips = array();
		$tmpips = file_get_contents("$this->path/$this->bbs/iplog/$this->key.log");
		$tmpips = explode("\n",$tmpips);
		foreach($tmpips as $ip_line){
			list($num,$ip) = explode("\t",$ip_line);
			if($num)$ips[$num] = $ip;
		}

		$datdir  = "$this->path/$this->bbs/dat";
		//IPログの取得
		$dats = array();
		$tmpdats = file_get_contents("$this->path/$this->bbs/dat/$this->key.dat");
		$tmpdats = explode("\n",$tmpdats);
		$idx = 1;
		foreach($tmpdats as $dat_line){
			list($name,$url,$time,$body,$title) = explode("<>",$dat_line);
			if($name){
				$dats[$idx] = "<tr><td style=\"border-bottom:solid 1px\">$idx</td><td style=\"border-bottom:solid 1px\">$name</td><td style=\"border-bottom:solid 1px\">$body</td><td style=\"border-bottom:solid 1px\">$ips[$idx]</td></tr>";
				$idx++;
			}
		}

		$html = "<table>";
		$html .= implode("\r\n",$dats);
		$html .= "</table>";
		return $html;
	}

	//BAN解除
	function liftBan(){
		//BANリストの取得
		$banList = file_get_contents("$this->path/$this->bbs/iplog/ban.log");
		$banList = explode("\n",$banList);
		$newBanList = array();
		//BAN除外処理
		foreach($banList as $ip){
			if($ip !== $this->message)$newBanList[] = $ip;
		}
		//リストの再構築
		$banList = file_put_contents("$this->path/$this->bbs/iplog/ban.log",implode("\n",$newBanList), LOCK_EX);
	}

	// NGワード取得
	function getNgWord(){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.ngword';
		if(!file_exists($file_name)) return "NGワードが登録されていません。";
		//NGワードの取得
		$ngWords = array();
		$tmpngWords = file_get_contents($file_name);
		$tmpngWords = explode("\n",$tmpngWords);
		foreach($tmpngWords as $ngword_line){
			$ngWords[] = $ngword_line;
		}
		return implode("<br />\n",$ngWords);
	}

	// NGワード設定
	function setNgWord(){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.ngword';
		if( !file_exists($file_name) ){
			// ファイル作成
			touch($file_name);
			chmod( $file_name, 0600 );
			return 1;
		}
		// NGワードの取得
		$ngWords = array();
		$tmpngWords = file_get_contents(trim($file_name));
		$newNgWords = explode("\n",trim($this->message));
		foreach($newNgWords as $ngWords_line){
			if(
					trim($ngWords_line) != ""
				&&	!in_array(trim($ngWords_line),$ngWords)
			)
			{
				$ngWords[] = trim($ngWords_line);
			}
		}
		//BANリストに追加
		$ngWordList = file_put_contents($file_name,implode("\n",$ngWords), LOCK_EX);
	}


	// NGワード追加
	function addNgWord(){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.ngword';
		if( !file_exists($file_name) ){
			// ファイル作成
			touch($file_name);
			chmod( $file_name, 0600 );
			return 1;
		}
		// NGワードの取得
		$ngWords = array();
		$tmpngWords = file_get_contents(trim($file_name));
		$tmpngWords = explode("\n",$tmpngWords);
		foreach($tmpngWords as $ngWords_line){
			if(
					trim($ngWords_line) != ""
				&&	!in_array(trim($ngWords_line),$ngWords)
			)
			{
				$ngWords[] = trim($ngWords_line);
			}
		}
		$newNgWords = explode("\n",trim($this->message));
		foreach($newNgWords as $ngWords_line){
			if(
					trim($ngWords_line) != ""
				&&	!in_array(trim($ngWords_line),$ngWords)
			)
			{
				$ngWords[] = trim($ngWords_line);
			}
		}
		//BANリストに追加
		$ngWordList = file_put_contents($file_name,implode("\n",$ngWords), LOCK_EX);
	}


	function Fukki(){
		$datdir  = "$this->path/$this->bbs/dat";

		$dp = opendir($datdir);
		while (($filename = readdir($dp)) !== false) {
			if (preg_match("/^\d+\.dat$/", $filename)) {
				$mtime = filemtime("$datdir/$filename");
				$lastupdate[$mtime.$filename] = $filename;
			}
		} 
		closedir($dp);
		krsort($lastupdate);
		foreach ($lastupdate as $key => $filename){
			$dat = file("$datdir/$filename");
			$count = count($dat);
			list(,,,,$title) = explode("<>", $dat[0]);
			$title = rtrim($title);
			$subject[] = "$filename<>$title ($count)\n";
		}


		$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
		if(!$fp_subject){ PrintError("subject.txtが開けません。"); }
		flock($fp_subject, LOCK_EX);
		ftruncate($fp_subject,0);
		rewind($fp_subject);
		fputs($fp_subject, implode("", $subject));
		flock($fp_subject, LOCK_UN);
		fclose($fp_subject);
	}

	function convertDat(){
		$datdir  = "$this->path/$this->bbs/dat";

		$dp = opendir($datdir);
		while (($filename = readdir($dp)) !== false) {
			if (preg_match("/^\d+\.dat$/", $filename)) {
				$mtime = filemtime("$datdir/$filename");
				$lastupdate[$mtime.$filename] = $filename;
			}
		} 
		closedir($dp);
		krsort($lastupdate);

		var_dump($lastupdate);

		foreach ($lastupdate as $key => $filename){
			$dat = file("$datdir/$filename");
			$newdat = array();
			foreach($dat as $datline){
				list($name,$url,$time,$body,$title) = explode("<>", $datline);
				$newdat[] = $name."<>".$url."<>".rtrim(str_replace(array('(日)','(月)','(火)','(水)','(木)','(金)','(土)'),"",ltrim($time,"20"))," ID:")."<>".$body."<>".$title;
			}
			$fp = fopen("$datdir/$filename", "w");
			fwrite($fp,implode($newdat));
			fclose($fp);
		}
	}

	function Import2ch(){
		$command = str_replace("\r", "", $this->message);
		$urllist = explode("\n", $command);

		foreach ($urllist as $url){
			$host = $bbs = $key = $i = $flag = "";
			$newdat     = array();
			$newsubject = array();
			
			list(,,$host,,,$bbs,$key,$option) = explode("/", $url);
			if(preg_match("/\.2ch\.net/", $host) and $bbs and $key){
				$daturl = "http://$host/$bbs/dat/$key.dat";
			}
			else{ continue; }

			$dat = @file($daturl);
			$delimcount = substr_count($dat[0], '<>');
			if($delimcount != 4){ continue; }
			
			foreach($dat as $line){
				$i++;
				list($name, $mail, $date, $text, $title) = explode("<>", $line);
				$name = strip_tags($name);
				$date = strip_tags($date);
				$text = strip_tags($text, '<br><br />');
				$newdat[] = "$name<>$mail<>$date<>$text<>$title";
				if($i == 1){
					if(!$title){ continue 2; }
					$save_title = rtrim($title);
					if($option == 1){ break; }
				}
			}
			
			$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
			if(!$fp_subject){ PrintError("subject.txtが開けません。"); }
			flock($fp_subject, LOCK_EX);
			while(!feof($fp_subject)){
				$line = fgets($fp_subject);
				if(preg_match("/^$key\.dat<>/", $line)){
					$line = "$key.dat<>$save_title ($i)\n";
					$flag = 1;
				}
				$newsubject[] = $line;
			}
			if(!$flag){ array_unshift($newsubject, "$key.dat<>$save_title ($i)\n"); }
			
			file_put_contents("$this->path/$this->bbs/dat/$key.dat", implode("", $newdat));

			ftruncate($fp_subject,0);
			rewind($fp_subject);
			fputs($fp_subject, implode("", $newsubject));
			flock($fp_subject, LOCK_UN);
			fclose($fp_subject);
			
		}
	}


	function ImportShitaraba(){
		$command = str_replace("\r", "", $this->message);
		$urllist = explode("\n", $command);

		foreach ($urllist as $url){
			$host = $bbs = $key = $i = $flag = "";
			$newdat     = array();
			$newsubject = array();

			if(preg_match("/jbbs\.[livedoor\.jp|sjitaraba.net]/", $url)){
				list(,,$host,,,$cat,$bbs,$key,$option) = explode("/", $url);
				$daturl = "http://$host/bbs/rawmode.cgi/$cat/$bbs/$key/";
				$charcode = "euc";
			}else{ continue; }

			$dat = @file($daturl);
			//$delimcount = substr_count($dat[0], '<>');
			//if($delimcount != 4){ continue; }
			foreach($dat as $line){
				$line = mb_convert_encoding($line,"sjis",$charcode);
				$i++;
				list($num,$name,$mail,$date,$text,$title,$id) = explode("<>", $line);
				$name = strip_tags($name);
				$date = strip_tags($date);
				$text = strip_tags($text, '<br><br />');
				$id = str_replace("\n","",str_replace("\r","",$id));
				$newdat[] = "$name<>$mail<>$date ID:$id<>$text<>$title";
				if($i == 1){
					if(!$title){ continue 2; }
					$save_title = rtrim($title);
					if($option == 1){ break; }
				}
			}
			
			$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
			if(!$fp_subject){ PrintError("subject.txtが開けません。"); }
			flock($fp_subject, LOCK_EX);
			while(!feof($fp_subject)){
				$line = fgets($fp_subject);
				if(preg_match("/^$key\.dat<>/", $line)){
					$line = "$key.dat<>$save_title ($i)\n";
					$flag = 1;
				}
				$newsubject[] = $line;
			}
			if(!$flag){ array_unshift($newsubject, "$key.dat<>$save_title ($i)\n"); }
			
			file_put_contents("$this->path/$this->bbs/dat/$key.dat", implode("\r\n", $newdat)."\r\n");

			ftruncate($fp_subject,0);
			rewind($fp_subject);
			fputs($fp_subject, implode("", $newsubject));
			flock($fp_subject, LOCK_UN);
			fclose($fp_subject);
			
		}
	}


	function ImportYy(){
		$command = str_replace("\r", "", $this->message);
		$urllist = explode("\n", $command);

		foreach ($urllist as $url){
			$host = $bbs = $key = $i = $flag = "";
			$newdat     = array();
			$newsubject = array();
			
			list(,,$host,,,$bbs,$key,$option) = explode("/", $url);
			if(preg_match("/\.[60\.kg|kakiko\.com]/", $host) and $bbs and $key){
				$daturl = "http://$host/$bbs/dat/$key.dat";
			}
			else{ continue; }

			$dat = @file($daturl);
			$delimcount = substr_count($dat[0], '<>');
			if($delimcount != 4){ continue; }
			
			foreach($dat as $line){
				$i++;
				list($name, $mail, $date, $text, $title) = explode("<>", $line);
				$name = strip_tags($name);
				$date = strip_tags($date);
				$text = strip_tags($text, '<br><br />');
				$newdat[] = "$name<>$mail<>$date<>$text<>$title";
				if($i == 1){
					if(!$title){ continue 2; }
					$save_title = rtrim($title);
					if($option == 1){ break; }
				}
			}
			
			$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
			if(!$fp_subject){ PrintError("subject.txtが開けません。"); }
			flock($fp_subject, LOCK_EX);
			while(!feof($fp_subject)){
				$line = fgets($fp_subject);
				if(preg_match("/^$key\.dat<>/", $line)){
					$line = "$key.dat<>$save_title ($i)\n";
					$flag = 1;
				}
				$newsubject[] = $line;
			}
			if(!$flag){ array_unshift($newsubject, "$key.dat<>$save_title ($i)\n"); }
			
			file_put_contents("$this->path/$this->bbs/dat/$key.dat", implode("", $newdat));

			ftruncate($fp_subject,0);
			rewind($fp_subject);
			fputs($fp_subject, implode("", $newsubject));
			flock($fp_subject, LOCK_UN);
			fclose($fp_subject);
			
		}
	}


	function ImportJaneDat(){
		$newdat = rtrim($this->message);
		$lines = explode("\r",$newdat);
		$datcount = count($lines);
		$line = explode("<>",$lines[0]);
		$save_title = $line[4];
		$this->key = time();
		$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
		if(!$fp_subject){ PrintError("subject.txtが開けません。"); }
		flock($fp_subject, LOCK_EX);
		while(!feof($fp_subject)){
			$line = fgets($fp_subject);
			if(preg_match("/^$key\.dat<>/", $line)){
				$line = "$key.dat<>$save_title ($i)\n";
				$flag = 1;
			}
			$newsubject[] = $line;
		}
		array_unshift($newsubject, "$this->key.dat<>$save_title ($datcount)\n");
		file_put_contents("$this->path/$this->bbs/dat/$this->key.dat", $newdat);
		ftruncate($fp_subject,0);
		rewind($fp_subject);
		fputs($fp_subject, implode("", $newsubject));
		flock($fp_subject, LOCK_UN);
		fclose($fp_subject);
	}


	// IDの表示非表示切り替え
	function toggleId(){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.id';

		// ファイルの存在確認
		if( !file_exists($file_name) ){
			// ファイル作成
			touch($file_name);
			chmod( $file_name, 0600 );
			return 1;
		}else{
			// ファイルの削除
			unlink($file_name);
			return -1;
		}
		return 0;
	}


	// 板管理パスワード変更
	function changePassword($message){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.password';
		// ファイルポインタを開く
		$newPassHash = password_hash(trim($message),PASSWORD_DEFAULT, array('cost' => 10));
		$fp = fopen($file_name,'w');
		fputs($fp,$newPassHash);
		// 開いたファイルポインタを閉じる
		fclose($fp);
		chmod($file_name,0600);
		return true;
	}

	// スレ建て制限
	function restrictBuildThread(){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.buildThreadAdminOnly';
		// ファイルの存在確認
		if( !file_exists($file_name) ){
			// ファイル作成
			touch($file_name);
			chmod( $file_name, 0600 );
			return 1;
		}else{
			// ファイルの削除
			unlink($file_name);
			return -1;
		}
		return 0;
	}

	// 板名変更
	function changeTitle($message){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.title';
		// ファイルポインタを開く
		$fp = fopen($file_name,'w');
		fputs($fp,$message);
		// 開いたファイルポインタを閉じる
		fclose($fp);
		chmod($file_name,0600);
		return true;
	}


	// 板説明更新
	function changeDescription($message){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.description';
		// ファイルポインタを開く
		$fp = fopen($file_name,'w');
		fputs($fp,strip_tags($message,'<div><span><b><h1><h2><h3><h4><h5><a><img><br><object>'));
		// 開いたファイルポインタを閉じる
		fclose($fp);
		chmod($file_name,0600);
		return true;
	}

	// 名無し設定の更新
	function changeNanashi($message){
		// 作成するファイル名の指定
		if(trim($message) == "") $message = "名無しさん";
		$file_name = "../".$this->bbs."/".'.nanashi';
		// ファイルポインタを開く
		$fp = fopen($file_name,'w');
		fputs($fp,strip_tags($message));
		// 開いたファイルポインタを閉じる
		fclose($fp);
		chmod($file_name,0600);
		return true;
	}

	// 板停止
	function stopBoard(){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.active';
		// ファイルの存在確認
		if(file_exists($file_name)){
			// ファイルの削除
			unlink($file_name);
		}
	}


	// 板再開
	function startBoard(){
		// 作成するファイル名の指定
		$file_name = "../".$this->bbs."/".'.active';
		// ファイルの存在確認
		if(!file_exists($file_name)){
			// ファイル作成
			touch($file_name);
			chmod( $file_name, 0600 );
		}
	}


	// 新規板作成
	function createThread($message){
		$param = split("\n",str_replace("\r","",$message));
		$bbs = $param[0];
		$title = $param[1];
		$password = $param[2];
		//オリジナルフォルダを新規コピー
		$this->copyDirectory(DEPLOY_DIR.ORG_BBS_DIR,DEPLOY_DIR.$bbs);
		//タイトルの作成
		if(strlen($title)){
			$file_name = "../".$bbs."/".'.title';
			$fp = fopen($file_name,'w');
			fputs($fp,$title);
			fclose($fp);
			chmod($file_name,0600);
		}
		// パスワードの設定
		if(strlen($password) && preg_match("/^#[a-zA-Z0-9]{8,}$/",$password,$matches)){
			$file_name = "../".$bbs."/".'.password';
			$fp = fopen($file_name,'w');
			fputs($fp,md5($password));
			fclose($fp);
			chmod($file_name,0600);
		}
		// 書き込み有効化ファイルの作成
		touch("../".$bbs."/".'.active');
		chmod("../".$bbs."/".'.active', 0600 );
		// 無効化ファイルの削除
		if(file_exists("../".$bbs."/".'.disabled'))unlink("../".$bbs."/".'.disabled');
		return true;
	}


	//新規板作成用ディレクトリコピー処理
	function copyDirectory($fromDir, $toDir){
		$handle=opendir($fromDir);
		if(!file_exists($toDir)){
			mkdir("$toDir");
			chmod($toDir,fileperms($fromDir));
			while($filename=readdir($handle)){
				if(
					strcmp($filename,".")!=0
					&& strcmp($filename,"..")!=0
				){
					if(is_dir("$fromDir/$filename")){
						if(!empty($filename) && !file_exists("$toDir/$filename")){
							mkdir("$toDir/$filename");
							chmod("$toDir/$filename",fileperms("$fromDir/$filename"));
						}
						$this->copyDirectory("$fromDir/$filename","$destDir/$filename");
					}else{
						if(file_exists("$toDir/$filename"))
						unlink("$toDir/$filename");
						copy("$fromDir/$filename","$toDir/$filename");
						chmod("$toDir/$filename",fileperms("$fromDir/$filename"));
					}
				}
			}
		}
	}



	function MakeDir($path, $name){//ディレクトリを作るパスとディレクトリ名を頂戴
		if(!is_dir("$path/$name")){
			$permission = substr(decoct(fileperms("$this->path/$this->bbs/dat")), 1);
			$flag = mkdir("$path/$name", octdec($permission));// umaskでパーミッションが減るから
			chmod("$path/$name", octdec($permission));// ここで元に戻す
			if(!$flag){ PrintError("「$path」にディレクトリが作成できません"); }
		}
	}


}
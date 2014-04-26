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
		//�Ǘ��p�X���[�h�Ή�
		$adminpass = ADMIN_PASSWORD;
		$file_name = "../".$this->bbs."/".'.password';
		$boardadminpass = file_get_contents($file_name);

		if(strlen($boardadminpass)){
			$pass = $boardadminpass;
		}
		if(password_verify($this->mail,$adminpass) || (isset($pass) && password_verify($this->mail,$pass))){
			switch ($this->name){
				case "���A":
					$this->Fukki();
					PrintSucess("$this->bbs/subject.txt�̕��A����!");
					braek;
				case "�폜":
					if(preg_match("/^http/", $this->message)){
						$this->DeleteThread();
						PrintSucess("�X���b�h���폜���܂����B");
					}
					elseif(preg_match("/^>>\d/", $this->message)){
						if(!$this->key){ PrintError("���X�̍폜�͊Y���̃X���b�h����s���K�v������܂��B"); }
						$this->DeleteRes();
						PrintSucess("���X���폜���܂����B");
					}
					else{ PrintError("�X���b�h�폜�Ȃ�u�X����URL�v���A���X�폜�Ȃ�u>>�����v�Ɠ��͂��Ă�������"); }
					braek;
				case "BAN":
					$this->banIp();
					PrintSucess("�w�背�X��IP��BAN���܂���");
					braek;
				case "IP�m�F":
					$ip = $this->getIpLog();
					PrintSucess("�w�背�X��IP���擾���܂���:".$ip);
					braek;
				case "IP�m�F�S��":
					$ip = $this->getIpLogAll();
					PrintSucess("IP��S���擾���܂���:".$ip);
					braek;
				case "BAN����":
					$this->liftBan();
					PrintSucess("�w��IP��BAN�������܂���");
					braek;
				case "NG���[�h�擾":
					$ngwords = $this->getNgWord();
					PrintSucess("NG���[�h���擾���܂���<br />".$ngwords);
					braek;
				case "NG���[�h�X�V":
					$ip = $this->setNgWord();
					PrintSucess("NG���[�h���X�V���܂����B");
					braek;
				case "NG���[�h�ǉ�":
					$ip = $this->addNgWord();
					PrintSucess("NG���[�h��ǉ����܂����B");
					braek;
				case "�q��":
					if(preg_match("/^http/", $this->message)){
						$this->DatOchi();
						PrintSucess("�X���b�h��DAT�������܂���");
					}
					else{ PrintError("�X���b�h�𗎂Ƃ��Ȃ�u�X����URL�v����͂��Ă�������"); }
					braek;
				case "2ch":
					$this->Import2ch();
					PrintSucess("2ch����X����A�����܂���");
					braek;
				case "�������":
					$this->ImportShitaraba();
					PrintSucess("������΂���X����A�����܂���");
					braek;
				case "YY":
					$this->ImportYy();
					PrintSucess("YY����X����A�����܂���");
					braek;
				case "Jane":
					$this->ImportJaneDat();
					PrintSucess("Jane��dat�t�@�C�����C���|�[�g���܂���");
					braek;
				case "ID":
					$res = $this->toggleId();
					switch ($res) {
					case 1:
						PrintSucess("ID�������\�������悤�ɂȂ�܂����B");
						break;
					case -1:
						PrintSucess("ID����\���ɂȂ�܂����B");
						break;
					}
					braek;
				case "�X�����Đ���":
					$res = $this->restrictBuildThread();
					switch ($res) {
					case 1:
						PrintSucess("�Ǘ��҂݂̂��X���b�h�����Ă���悤�ɕύX���܂����B");
						break;
					case -1:
						PrintSucess("����ł��X���b�h�����Ă���悤�ɕύX���܂����B");
						break;
					}
					braek;
				case "dat�C��":
					$this->convertDat();
					PrintSucess("dat�C������");
					braek;
				case "�p�X���[�h�ύX":
					if(preg_match("/^#[a-zA-Z0-9]{8,}$/",$this->message,$matches)){
						$this->changePassword($this->message);
						PrintSucess("�Ǘ��p�X���[�h��ύX���܂����B");
					}else{
						PrintError("�p�X���[�h�ɂ�#[�p����8�����ȏ�]��ݒ肵�Ă��������B");
					}
					braek;
				case "���ύX":
					$this->changeTitle($this->message);
					PrintSucess("����ύX���܂����B");
					braek;
				case "�����X�V":
					$this->changeDescription($this->message);
					PrintSucess("�������X�V���܂����B");
					braek;
				case "�������ݒ�":
					$this->changeNanashi($this->message);
					PrintSucess("�������ݒ���X�V���܂����B");
					braek;
				case "��~":
					$this->stopBoard();
					PrintSucess("���~���܂����B");
					braek;
				case "�ĊJ":
					$this->startBoard();
					PrintSucess("���ĊJ���܂����B");
					braek;
				case "�V�K�쐬":
					if(password_verify($this->mail,$adminpass)){
						$this->createThread($this->message);
						PrintSucess("�V�K���쐬���܂����B");
					}
					braek;
				case "�ǉ��R�}���h":
					braek;
				default :
				PrintError("�R�}���h���Ⴂ�܂��B");
			}
		}
		else{
			PrintError("�p�X���[�h���Ⴂ�܂��B");
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
		
		if($start == 1){ PrintError("1�Ԗڂ̃��X�͍폜�ł��܂���B"); }
		if($start <  1){ PrintError("���̔ԍ��͍폜�ł��܂���B"); }

		if($end > $start){
			for($i=$start; $i<=$end; $i++){
				$delres[] = $i;
			}
		}
		else{
			$delres[] = $start;
		}

		$fp_dat = fopen("$this->path/$this->bbs/dat/$this->key.dat", "rb+");
		if(!$fp_dat){ PrintError("DAT�t�@�C�����J���܂���B"); }
		
		flock($fp_dat, LOCK_EX);
		while(!feof($fp_dat)){
			$j++;
			$pad = "";
			$line = fgets($fp_dat);
			foreach($delres as $no){
				if($no == $j){
					$padlength = strlen($line) - strlen("���ځ[��<>���ځ[��<>���ځ[��<>���ځ[��<>\n");
					if($padlength > 0){
						$pad  = str_repeat(" ", $padlength);
					}
					$line = "���ځ[��<>���ځ[��<>���ځ[��<>���ځ[��<>$pad\n";
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
			if(file_exists($filepath)){//DatOchi()��DeleteThread()�ƂقƂ�Ǔ��������A�������Ⴄ�B
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


	function DeleteFromSubject($dellist){ //subject.txt����폜����L�[��z��Œ���
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

	//BAN�o�^
	function banIp(){
		$iplogdir  = "$this->path/$this->bbs/iplog";
		//IP���O����IP���擾
		$ips = array();
		$tmpips = file_get_contents("$this->path/$this->bbs/iplog/$this->key.log");
		$tmpips = explode("\n",$tmpips);
		foreach($tmpips as $ip_line){
			list($num,$ip) = explode("\t",$ip_line);
			$ips[$num] = $ip;
		}
		//BAN���X�g�ɒǉ�
		file_put_contents("$this->path/$this->bbs/iplog/ban.log",$ips[$this->message]."\n", FILE_APPEND | LOCK_EX);
	}


	//IP�m�F
	function getIpLog(){
		$iplogdir  = "$this->path/$this->bbs/iplog";
		//IP���O�̎擾
		$ips = array();
		$tmpips = file_get_contents("$this->path/$this->bbs/iplog/$this->key.log");
		$tmpips = explode("\n",$tmpips);
		foreach($tmpips as $ip_line){
			list($num,$ip) = explode("\t",$ip_line);
			$ips[$num] = $ip;
		}
		return $ips[$this->message];
	}

	//IP�m�F
	function getIpLogAll(){
		$iplogdir  = "$this->path/$this->bbs/iplog";
		//IP���O�̎擾
		$ips = array();
		$tmpips = file_get_contents("$this->path/$this->bbs/iplog/$this->key.log");
		$tmpips = explode("\n",$tmpips);
		foreach($tmpips as $ip_line){
			list($num,$ip) = explode("\t",$ip_line);
			if($num)$ips[$num] = $ip;
		}

		$datdir  = "$this->path/$this->bbs/dat";
		//IP���O�̎擾
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

	//BAN����
	function liftBan(){
		//BAN���X�g�̎擾
		$banList = file_get_contents("$this->path/$this->bbs/iplog/ban.log");
		$banList = explode("\n",$banList);
		$newBanList = array();
		//BAN���O����
		foreach($banList as $ip){
			if($ip !== $this->message)$newBanList[] = $ip;
		}
		//���X�g�̍č\�z
		$banList = file_put_contents("$this->path/$this->bbs/iplog/ban.log",implode("\n",$newBanList), LOCK_EX);
	}

	// NG���[�h�擾
	function getNgWord(){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.ngword';
		if(!file_exists($file_name)) return "NG���[�h���o�^����Ă��܂���B";
		//NG���[�h�̎擾
		$ngWords = array();
		$tmpngWords = file_get_contents($file_name);
		$tmpngWords = explode("\n",$tmpngWords);
		foreach($tmpngWords as $ngword_line){
			$ngWords[] = $ngword_line;
		}
		return implode("<br />\n",$ngWords);
	}

	// NG���[�h�ݒ�
	function setNgWord(){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.ngword';
		if( !file_exists($file_name) ){
			// �t�@�C���쐬
			touch($file_name);
			chmod( $file_name, 0600 );
			return 1;
		}
		// NG���[�h�̎擾
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
		//BAN���X�g�ɒǉ�
		$ngWordList = file_put_contents($file_name,implode("\n",$ngWords), LOCK_EX);
	}


	// NG���[�h�ǉ�
	function addNgWord(){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.ngword';
		if( !file_exists($file_name) ){
			// �t�@�C���쐬
			touch($file_name);
			chmod( $file_name, 0600 );
			return 1;
		}
		// NG���[�h�̎擾
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
		//BAN���X�g�ɒǉ�
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
		if(!$fp_subject){ PrintError("subject.txt���J���܂���B"); }
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
				$newdat[] = $name."<>".$url."<>".rtrim(str_replace(array('(��)','(��)','(��)','(��)','(��)','(��)','(�y)'),"",ltrim($time,"20"))," ID:")."<>".$body."<>".$title;
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
			if(!$fp_subject){ PrintError("subject.txt���J���܂���B"); }
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
			if(!$fp_subject){ PrintError("subject.txt���J���܂���B"); }
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
			if(!$fp_subject){ PrintError("subject.txt���J���܂���B"); }
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
		if(!$fp_subject){ PrintError("subject.txt���J���܂���B"); }
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


	// ID�̕\����\���؂�ւ�
	function toggleId(){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.id';

		// �t�@�C���̑��݊m�F
		if( !file_exists($file_name) ){
			// �t�@�C���쐬
			touch($file_name);
			chmod( $file_name, 0600 );
			return 1;
		}else{
			// �t�@�C���̍폜
			unlink($file_name);
			return -1;
		}
		return 0;
	}


	// �Ǘ��p�X���[�h�ύX
	function changePassword($message){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.password';
		// �t�@�C���|�C���^���J��
		$newPassHash = password_hash(trim($message),PASSWORD_DEFAULT, array('cost' => 10));
		$fp = fopen($file_name,'w');
		fputs($fp,$newPassHash);
		// �J�����t�@�C���|�C���^�����
		fclose($fp);
		chmod($file_name,0600);
		return true;
	}

	// �X�����Đ���
	function restrictBuildThread(){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.buildThreadAdminOnly';
		// �t�@�C���̑��݊m�F
		if( !file_exists($file_name) ){
			// �t�@�C���쐬
			touch($file_name);
			chmod( $file_name, 0600 );
			return 1;
		}else{
			// �t�@�C���̍폜
			unlink($file_name);
			return -1;
		}
		return 0;
	}

	// ���ύX
	function changeTitle($message){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.title';
		// �t�@�C���|�C���^���J��
		$fp = fopen($file_name,'w');
		fputs($fp,$message);
		// �J�����t�@�C���|�C���^�����
		fclose($fp);
		chmod($file_name,0600);
		return true;
	}


	// �����X�V
	function changeDescription($message){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.description';
		// �t�@�C���|�C���^���J��
		$fp = fopen($file_name,'w');
		fputs($fp,strip_tags($message,'<div><span><b><h1><h2><h3><h4><h5><a><img><br><object>'));
		// �J�����t�@�C���|�C���^�����
		fclose($fp);
		chmod($file_name,0600);
		return true;
	}

	// �������ݒ�̍X�V
	function changeNanashi($message){
		// �쐬����t�@�C�����̎w��
		if(trim($message) == "") $message = "����������";
		$file_name = "../".$this->bbs."/".'.nanashi';
		// �t�@�C���|�C���^���J��
		$fp = fopen($file_name,'w');
		fputs($fp,strip_tags($message));
		// �J�����t�@�C���|�C���^�����
		fclose($fp);
		chmod($file_name,0600);
		return true;
	}

	// ��~
	function stopBoard(){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.active';
		// �t�@�C���̑��݊m�F
		if(file_exists($file_name)){
			// �t�@�C���̍폜
			unlink($file_name);
		}
	}


	// �ĊJ
	function startBoard(){
		// �쐬����t�@�C�����̎w��
		$file_name = "../".$this->bbs."/".'.active';
		// �t�@�C���̑��݊m�F
		if(!file_exists($file_name)){
			// �t�@�C���쐬
			touch($file_name);
			chmod( $file_name, 0600 );
		}
	}


	// �V�K�쐬
	function createThread($message){
		$param = split("\n",str_replace("\r","",$message));
		$bbs = $param[0];
		$title = $param[1];
		$password = $param[2];
		//�I���W�i���t�H���_��V�K�R�s�[
		$this->copyDirectory(DEPLOY_DIR.ORG_BBS_DIR,DEPLOY_DIR.$bbs);
		//�^�C�g���̍쐬
		if(strlen($title)){
			$file_name = "../".$bbs."/".'.title';
			$fp = fopen($file_name,'w');
			fputs($fp,$title);
			fclose($fp);
			chmod($file_name,0600);
		}
		// �p�X���[�h�̐ݒ�
		if(strlen($password) && preg_match("/^#[a-zA-Z0-9]{8,}$/",$password,$matches)){
			$file_name = "../".$bbs."/".'.password';
			$fp = fopen($file_name,'w');
			fputs($fp,md5($password));
			fclose($fp);
			chmod($file_name,0600);
		}
		// �������ݗL�����t�@�C���̍쐬
		touch("../".$bbs."/".'.active');
		chmod("../".$bbs."/".'.active', 0600 );
		// �������t�@�C���̍폜
		if(file_exists("../".$bbs."/".'.disabled'))unlink("../".$bbs."/".'.disabled');
		return true;
	}


	//�V�K�쐬�p�f�B���N�g���R�s�[����
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



	function MakeDir($path, $name){//�f�B���N�g�������p�X�ƃf�B���N�g�����𒸑�
		if(!is_dir("$path/$name")){
			$permission = substr(decoct(fileperms("$this->path/$this->bbs/dat")), 1);
			$flag = mkdir("$path/$name", octdec($permission));// umask�Ńp�[�~�b�V���������邩��
			chmod("$path/$name", octdec($permission));// �����Ō��ɖ߂�
			if(!$flag){ PrintError("�u$path�v�Ƀf�B���N�g�����쐬�ł��܂���"); }
		}
	}


}
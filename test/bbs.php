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
			$this->nanashi = "����������";
		}

		$this->name    = $_POST["FROM"];
		$this->mail    = $_POST["mail"];
		$this->subject = $_POST["subject"];
		$this->message = $_POST["MESSAGE"];
		$this->time    = $_POST["time"];
		$this->trap1   = $_POST["url"];
		$this->trap2   = $_POST["password"];
		
		
		if(!$this->bbs){ $this->RecieveRawPost();} //�N���C�A���g�ɂ��$_POST�Ƀf�[�^������Ȃ��o�O�΍�
		
		// �Ǘ����[�h�ւ̕���
		if((
					$this->name == '���A'
				or	$this->name == '�폜'
				or	$this->name == '�q��'
				or	$this->name == '2ch'
				// �ȉ��A�V�K�쐬����
				or	$this->name == 'BAN'
				or	$this->name == 'IP�m�F'
				or	$this->name == 'IP�m�F�S��'
				or	$this->name == 'BAN����'
				or	$this->name == '�������'
				or	$this->name == 'YY'
				or	$this->name == 'Jane'
				or	$this->name == 'ID'
				or	$this->name == '�p�X���[�h�ύX'
				or	$this->name == '���ύX'
				or	$this->name == '�������ݒ�'
				or	$this->name == '�����X�V'
				or	$this->name == '��~'
				or	$this->name == '�ĊJ'
				or	$this->name == '�V�K�쐬'
				or	$this->name == 'dat�C��'
			) and preg_match("/^#/", $this->mail) and $this->bbs){
			include_once("./admin.php");
			new ADMIN($this->name, $this->mail, $this->bbs, $this->key, $this->message);
		}elseif($this->name == '�����擾'){
			header("Location: ../".$this->bbs."/?d=1");
		}elseif($this->name == '�n�b�V������'){
			// �p�X���[�h��MD5�ŕۑ�����`�ɕύX�����̂ŁA�n�b�V�������@�\��ǉ�
			PrintSucess(md5($this->mail));
		}

		if($this->active){
			//�ʏ폈��
			$this->CheckInput();
			$this->BlockPost();
			if($this->key){ $this->WriteRes(); }
			else{ $this->WriteThread(); }
			$this->PrintOK();
		}else{
			PrintError("���݁A���̔͏������݂��֎~����Ă��܂��B");
		}
	}


	function CheckInput(){
		//�ƃX���b�h�̑��݊m�F
		if(preg_match("/[\.\/]/", "$this->bbs$this->key")){ PrintError("�L�[���s���ł��B"); }
		if(!is_file("$this->path/$this->bbs/subject.txt")){ PrintError("����ȔȂ��ł��B"); }
		if($this->key and !is_file("$this->path/$this->bbs/dat/$this->key.dat")){
			$key4 = substr($this->key, 0, 4);
			$key5 = substr($this->key, 0, 5);
			if(is_file("$this->path/$this->bbs/kako/$key4/$key5/$this->key.dat")){ PrintError("���̃X���b�h�ɂ͂����������߂܂���B"); }
			else { PrintError("����ȃX���b�h�Ȃ��ł��B"); }
		}

		if(!strlen($this->message)){ PrintError("�{������͂��Ă��������B"); }
		if(!strlen($this->subject) and !$this->key){ PrintError("�薼����͂��Ă��������B"); }
	}


	function BlockPost(){
		//�K�������������B�쐬��
		//���{�̍�B�t�H�[���̃g���b�v(password��url)�����͂���Ă����烍�{�b�g�Ȃ̂ŋK������
		if($this->trap1 or $this->trap2){ PrintError("���e�ł��܂���B101"); }
	}
	
	function WriteThread(){
		//BAN�`�F�b�N
		if($this->checkBan())PrintError("���e�ł��܂���ł���");
		$this->key = $this->now;
		// �f�����Ƃ̃X�����Đ����p�X�̎���
		// �p�X���[�h�֘A�̏����̒ǉ��Ƃ���ɔ���������if�����ւ̈ړ�
		$bbs = $this->bbs;
		$file_name = "../".$this->bbs."/".'.password';
		$boardadminpass = file_get_contents($file_name);
		$adminpass = ADMIN_PASSWORD;
		// �p�X���[�h�t�@�C�������݂����ꍇ�̂݁A�p�X���[�h�`�F�b�N
		if(strlen($boardadminpass) && $boardadminpass !== md5($this->mail) && $adminpass !== md5($this->mail)){
			PrintError("�X���b�h�쐬�͊Ǘ��҂݂̂��s���܂�");
		}else{
			//subject.txt�F$subjectlist�ɑS�f�[�^�i�[
			$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
			if(!$fp_subject){ PrintError("subject.txt���J���܂���B"); }

			flock($fp_subject, LOCK_EX);
			while(!feof($fp_subject)){
				$subjectlist[] = fgets($fp_subject);
			}

			//DAT�t�@�C���ɏ�������
			$name = $this->GenerateName($this->name);
			$mail = $this->GenerateMail($this->mail);
			$date = $this->GenerateDate();
			$message = $this->GenerateMessage($this->message);
			$subject = $this->GenerateSubject($this->subject);

			if(is_file("$this->path/$this->bbs/dat/$this->key.dat")){
				$this->CloseFile($fp_subject);
				PrintError("������x���e���Ă�������");
			}
			$fp_dat = fopen("$this->path/$this->bbs/dat/$this->key.dat", "wb+");
			if(!$fp_dat){
				$this->CloseFile($fp_subject);
				PrintError("DAT�t�@�C�����쐬�ł��܂���B");
			}
			
			$dat_line = "$name<>$mail<>$date<> $message <>$subject\n";
			$sub_line = "$this->key.dat<>$subject (1)\n";
			$match_count = substr_count("$dat_line$sub_line", "<>");
			if($match_count != 5){
				$this->CloseFile($fp_subject, $fp_dat);
				PrintError("���e�ł��܂���ł���");
			}
			
			flock($fp_dat, LOCK_EX);
			fputs($fp_dat, $dat_line);
			$this->CloseFile($fp_dat);

			//subject.txt�ɏ�������
			array_unshift($subjectlist, $sub_line);
			ftruncate($fp_subject,0);
			rewind($fp_subject);
			fputs($fp_subject, implode("", $subjectlist));
			$this->CloseFile($fp_subject);
		}
	}
	
	
	function WriteRes(){
		//BAN�`�F�b�N
		if($this->checkBan())PrintError("���e�ł��܂���ł���");
		$subject_num = $dat_num = 0;
		//subject.txt�F�����������Ƃ���X����������{$subject�ɑS�f�[�^�i�[
		$fp_subject = fopen("$this->path/$this->bbs/subject.txt", "rb+");
		if(!$fp_subject){ PrintError("subject.txt���J���܂���B"); }

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
		
		//DAT�t�@�C���F$dat�ɑS�f�[�^�i�[
		$fp_dat = fopen("$this->path/$this->bbs/dat/$this->key.dat", "rb+");
		if(!$fp_dat){ PrintError("DAT�t�@�C�����J���܂���B"); }


		flock($fp_dat, LOCK_EX);
		while(!feof($fp_dat)){
			$dat[] = fgets($fp_dat);
			$dat_num++;
		}
		//1000�}�f
		if ($dat_num > 1000){
			$this->CloseFile($fp_subject, $fp_dat);
			PrintError("���̃X���b�h�ɂ͂����������߂܂���B");
		}
		//�d���`�F�b�N
		list($d1, $d2, $d3, $d4) = explode("<>", $dat[$dat_num-2]);
		if($d4 == $this->message){ 
			$this->CloseFile($fp_subject, $fp_dat);
			PrintError("��d�������݂ł��B");
		}
		
		//DAT�ɒǉ�
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
			PrintError("���e�ł��܂���ł����B");
		}

		$dat[] = $dat_line;
		if ($dat_num = 1000){
			$name = $this->GenerateName("�P�O�O�P");
			$mail = $this->GenerateMail("");
			$date = $this->GenerateDate();
			$message = $this->GenerateMessage("���̃X���b�h�͂P�O�O�O�𒴂��܂����B\n���������Ȃ��̂ŁA�V�����X���b�h�𗧂ĂĂ��������ł��B�B�B");
			$dat_line = "$name<>$mail<>$date<> $message <>\n";
			$dat[] = $dat_line;
			$sub_line = "$this->key.dat<>$title (".($dat_num+1).")\n";
		}

		
		//DAT�t�@�C���ɏ�������
		ftruncate($fp_dat,0);
		rewind($fp_dat);
		fputs($fp_dat, implode("", $dat));
		$this->CloseFile($fp_dat);
		//IP���O�̍쐬
		$iplog_line = $dat_num."\t".$this->getHost()."\n";
		file_put_contents("$this->path/$this->bbs/iplog/$this->key.log",$iplog_line, FILE_APPEND | LOCK_EX);

		// $subject�ҏW(sage�Ή�)
		if(!preg_match("/.*sage.*/", $this->mail, $match)){
			array_splice($subject, $save_subject_num, 1);
			array_unshift($subject, $sub_line);
		}else{
			$subject[$save_subject_num] = $sub_line;
		}

		//subject.txt�ɏ�������
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

		print "<html><!-- 2ch_X:true --><head><title>�������݂܂����B</title><meta http-equiv=\"refresh\" content=\"1;URL=$url\"></head>";
		print "<body>�������݂��I���܂����B<br><br><a href=\"$url\">��ʂ�؂�ւ���</a>�܂ł��΂炭���҂��������B</body></html>";

		exit;
	}

	function GenerateKizunaURL(){
		if($_SERVER['HTTPS']=="on"){ $protocol = "https://"; }
		else{ $protocol = "http://"; }
		
		$request_uri = preg_replace("/\/test\/.*/", "", $_SERVER['REQUEST_URI']); // /test/�ȉ����폜
		
		$url = $protocol . $_SERVER["HTTP_HOST"] . $request_uri;
		return $url;
	}


	function GenerateDate(){
		$week = array('��','��','��','��','��','��','�y');
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
		### PC/�g�є��� �L���� ###
		//if($S_MARK == "on") {
		//	# PC/0�A�g��/o
		//	$Mark = (GUID != "ON"? " 0": " o");
		//}
		$bbsname = $this->name;
		$bbs = $this->bbs;
		$disp_flag = file_exists("../".$this->bbs."/.id");

		if($disp_flag) {
			$Time = localtime();
			# �f��������납��3�����擾
			$BBS = (strlen($bbsname) > 3? substr($bbsname, -3): $bbsname);
			# PC����
			if(GUID != "ON") {
				$IP = explode(".", $_SERVER["REMOTE_ADDR"]);
				$Key = substr($IP[3], -3).substr($IP[2], -1).substr($IP[1], -1).$BBS;
			# �g�ѓd�b����
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
		### �g�� ###
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
				# ���Ή�
				if(!preg_match("/(panda-world.ne.jp$)/", $Host)) {
					PrintError("���̌g�тɂ͑Ή����ĂȂ����॥�");
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


		$name = str_replace("��", "��", $name);
		$name = str_replace("��", "��", $name);
		$name = str_replace("��", "��", $name);
		
		if(!$name){ $name = $this->nanashi; }
		if (preg_match("/#(.+)$/", $name, $match)) {
			$tripkey = $match[1];
			$salt = substr($tripkey . 'H.', 1, 2);
			$salt = preg_replace('/[^\.-z]/', '.', $salt);
			$salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');

			$trip = crypt($tripkey, $salt);
			$trip = substr($trip, -10);
			$name = preg_replace("/#(.+)$/", "", $name);
			$name = $name . ' ��' . $trip;
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

<?php
// �Ǘ��҂̃p�X���[�h�BMD5�`���֕ύX
define('ADMIN_PASSWORD', 'b9b7c289a0e002654a62ef568cc9314c');
// �ݒu�f�B���N�g��
define('DEPLOY_DIR','/var/www/html/bbs/');
// �R�s�[���f�B���N�g��
define('ORG_BBS_DIR','bbs');
// ���ʏ����̈ړ��A��X�ʃt�@�C���ɐ؂蕪��������������������Ȃ�
function PrintError($str){
	header("Cache-Control: no-cache");
	header("Content-type: text/html; charset=shift_jis");

	print "<html><!-- 2ch_X:error --><head><title>�d�q�q�n�q�I</title>\n</head>";
	print "<body><b>�d�q�q�n�q�F$str</b>\n";
	print "<br><a href=\"javascript:history.back()\">�߂�</a></body></html>";

	exit;
}

function PrintSucess($str){
	header("Cache-Control: no-cache");
	header("Content-type: text/html; charset=shift_jis");

	print "<html><!-- 2ch_X:error --><head><title>�r�t�b�d�r�r�I</title>\n</head>";
	print "<body><b>�r�t�b�d�r�r�F$str</b>\n";
	print "<br><a href=\"javascript:history.back()\">�߂�</a></body></html>";

	exit;
}

<?php
// ŠÇ—Ò‚ÌƒpƒXƒ[ƒh
define('ADMIN_PASSWORD', '');
// ”Âİ’uƒfƒBƒŒƒNƒgƒŠ
define('DEPLOY_DIR','/var/www/html/bbs/');
// ƒRƒs[Œ³”ÂƒfƒBƒŒƒNƒgƒŠ
define('ORG_BBS_DIR','bbs');
// ‹¤’Êˆ—‚ÌˆÚ“®AŒãX•Êƒtƒ@ƒCƒ‹‚ÉØ‚è•ª‚¯‚½•û‚ª‚¢‚¢‚©‚à‚µ‚ê‚È‚¢
function PrintError($str){
	header("Cache-Control: no-cache");
	header("Content-type: text/html; charset=shift_jis");

	print "<html><!-- 2ch_X:error --><head><title>‚d‚q‚q‚n‚qI</title>\n</head>";
	print "<body><b>‚d‚q‚q‚n‚qF$str</b>\n";
	print "<br><a href=\"javascript:history.back()\">–ß‚é</a></body></html>";

	exit;
}

function PrintSucess($str){
	header("Cache-Control: no-cache");
	header("Content-type: text/html; charset=shift_jis");

	print "<html><!-- 2ch_X:error --><head><title>‚r‚t‚b‚d‚r‚rI</title>\n</head>";
	print "<body><b>‚r‚t‚b‚d‚r‚rF$str</b>\n";
	print "<br><a href=\"javascript:history.back()\">–ß‚é</a></body></html>";

	exit;
}

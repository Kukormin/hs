<?
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

//
// Отладка
//

/*$res = \Local\Data\Ad::getList(array(), array(
	'max' => 1202,
	'count' => 3,
));
debugmessage($res);*/

/*$userId = 54;
$follow = \Local\User\Follower::get($userId);

$res = \Local\Data\Feed::getList(array(
	//'max' => 1207,
	//'count' => 3,
	'publishers' => $follow['publishers'],
), true);
debugmessage($res);*/

/*$deal['history'] = \Local\Data\History::get(1364, true);
debugmessage($deal['history']);*/

//iconv_set_encoding("internal_encoding", "ISO-8859-1");
//iconv_set_encoding("output_encoding", "ISO-8859-1");
//iconv_set_encoding("input_encoding", "ISO-8859-1");

$dt = 'd8f598df3b4f56c4a1e87a6092b10a34971892f64b255d990e287f0551a74136';
\Local\User\Push::testMessage($dt, 'test');

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
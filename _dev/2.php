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

$dt = '506d0a18f5f0ad244f441f6db3aa94d57d6a5923ba897e24ca6dbae8dc21f8d1';
\Local\User\Push::testMessage($dt, 'test');

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
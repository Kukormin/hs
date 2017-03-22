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

$dt = 'ad6cebc031755b4a2c0292ad57d13304fa9d58c95ef4641ceba20a176e973f3c';
\Local\User\Push::testMessage($dt, 'кириллица');

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
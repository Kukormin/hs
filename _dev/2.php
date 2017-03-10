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

$dt = 'fc99552bfe7d7d91c8cce259a04d9a0f39a955d2aeaac970399e4368cd5ae31f';
\Local\User\Push::testMessage($dt, 'test');


require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
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

$userId = 54;
$follow = \Local\User\Follower::get($userId);

$res = \Local\Data\Feed::getList(array(
	//'max' => 1207,
	//'count' => 3,
	'publishers' => $follow['publishers'],
), true);
debugmessage($res);


require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
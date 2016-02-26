<?
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

//
// Отладка
//

/*$res = \Local\Data\Ad::getList(array(), array(
	'max' => 1202,
	'count' => 3,
));
debugmessage($res);

$res = \Local\Data\Feed::getList(array(
	//'max' => 1207,
	'count' => 3,
), true);
debugmessage($res);*/

$ad = \Local\Data\Ad::shortById(1208);
debugmessage($ad);
$ad = \Local\Data\Ad::getById(1208);
debugmessage($ad);
$ar = \Local\Data\Ad::getList(array('payment' => 'application'));
debugmessage($ad);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
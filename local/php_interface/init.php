<?
// TODO: DEBUG:
include 'debug.php';
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

$lib = '/local/php_interface/lib/';
\CModule::AddAutoloadClasses(
	'',
	array(
		'Local\\Common\\StaticCache' => $lib . 'common/StaticCache.php',
		'Local\\Common\\ExtCache' => $lib . 'common/ExtCache.php',
		'Local\\Common\\Utils' => $lib . 'common/Utils.php',
		'Local\\Common\\Handlers' => $lib . 'common/Handlers.php',
		'Local\\Common\\Agents' => $lib . 'common/Agents.php',
		'Local\\Common\\Tracking' => $lib . 'common/Tracking.php',
		'Local\\Common\\Sms' => $lib . 'common/Sms.php',
		'Local\\Api\\Api' => $lib . 'api/api.php',
		'Local\\Api\\v1' => $lib . 'api/v1.php',
		'Local\\Api\\ApiException' => $lib . 'api/ApiException.php',
		'Local\\Data\\Faq' => $lib . 'data/faq.php',
		'Local\\Data\\Ad' => $lib . 'data/ad.php',
		'Local\\Data\\Feed' => $lib . 'data/feed.php',
		'Local\\Data\\Claim' => $lib . 'data/claim.php',
		'Local\\Data\\Comments' => $lib . 'data/comments.php',
		'Local\\Data\\Messages' => $lib . 'data/messages.php',
		'Local\\Data\\Status' => $lib . 'data/status.php',
		'Local\\Data\\Deal' => $lib . 'data/deal.php',
		'Local\\Data\\History' => $lib . 'data/history.php',
		'Local\\Data\\News' => $lib . 'data/news.php',
		'Local\\Data\\Options' => $lib . 'data/options.php',
		'Local\\User\\User' => $lib . 'user/user.php',
		'Local\\User\\Auth' => $lib . 'user/auth.php',
		'Local\\User\\Session' => $lib . 'user/session.php',
		'Local\\User\\Follower' => $lib . 'user/follower.php',
		'Local\\User\\Favorite' => $lib . 'user/favorite.php',
		'Local\\User\\Push' => $lib . 'user/push.php',
		'Local\\Catalog\\Condition' => $lib . 'catalog/condition.php',
		'Local\\Catalog\\Color' => $lib . 'catalog/color.php',
		'Local\\Catalog\\Catalog' => $lib . 'catalog/catalog.php',
		'Local\\Catalog\\Size' => $lib . 'catalog/size.php',
		'Local\\Catalog\\Payment' => $lib . 'catalog/payment.php',
		'Local\\Catalog\\Delivery' => $lib . 'catalog/delivery.php',
		'Local\\Catalog\\Brand' => $lib . 'catalog/brand.php',
		'Local\\Catalog\\Gender' => $lib . 'catalog/gender.php',
	)
);

// Модуль ИБ в основном всегда нужен
\Bitrix\Main\Loader::includeModule('iblock');

// Обработчики событий
Local\Common\Handlers::addEventHandlers();

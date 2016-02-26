<?
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
		'Local\\Api\\Api' => $lib . 'api/api.php',
		'Local\\Api\\v1' => $lib . 'api/v1.php',
		'Local\\Api\\ApiException' => $lib . 'api/ApiException.php',
		'Local\\Data\\Faq' => $lib . 'data/faq.php',
		'Local\\Data\\Ad' => $lib . 'data/ad.php',
		'Local\\Data\\Feed' => $lib . 'data/feed.php',
		'Local\\User\\User' => $lib . 'user/user.php',
		'Local\\User\\Auth' => $lib . 'user/auth.php',
		'Local\\User\\Session' => $lib . 'user/session.php',
		'Local\\User\\Follower' => $lib . 'user/follower.php',
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

\Bitrix\Main\Loader::includeModule('iblock');

Local\Common\Handlers::addEventHandlers();

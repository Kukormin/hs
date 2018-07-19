<!doctype html>
<html lang="ru">
<head><?

	/** @var CMain $APPLICATION */
	/** @var CUser $USER */

	$showBxPanel = 0;

	?>
	<title><?$APPLICATION->ShowTitle()?></title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="format-detection" content="telephone=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="shortcut icon" href="/i/favicon.png" type="image/x-icon" /><?

	$assetInstance = \Bitrix\Main\Page\Asset::getInstance();
	$assetInstance->addCss(SITE_TEMPLATE_PATH . '/css/bootstrap.min.css');
	$assetInstance->addCss(SITE_TEMPLATE_PATH . '/css/style.css');
	$assetInstance->addJs(SITE_TEMPLATE_PATH . '/js/jquery-1.11.0.min.js');
	$assetInstance->addJs(SITE_TEMPLATE_PATH . '/js/bootstrap.min.js');

	if ($showBxPanel)
		$APPLICATION->ShowHead();
	else
	{
		$bx = 'var bxSession={mess:{},Expand:function(){}};';
		?><script type="text/javascript"><?= $bx ?></script><?

		$APPLICATION->ShowCSS();
		$APPLICATION->ShowHeadScripts();
	}

	?>
</head>
<body><?

//
// Админская панель
//
if ($showBxPanel)
	$APPLICATION->ShowPanel();

?>
<nav class="navbar navbar-inverse">
	<div class="container">
		<div class="navbar-header">
		</div>
	</div>
</nav>
<div class="container"><?

<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

/** @global CMain $APPLICATION */

?><!DOCTYPE html>
<html>
<head>
	<title>Служба поддержки</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="style.css" rel="stylesheet">
</head>
<body>
<div class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container-fluid">
			<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<span class="brand">Служба поддержки</span>
			<div class="nav-collapse collapse">
				<ul class="nav">
					<li><a href="/bitrix/admin/">Админка</a></li>
				</ul>
			</div>
		</div>
	</div>
</div>

<div class="container-fluid">
	<div class="row-fluid">
		<div class="span3">
			<div class="well sidebar-nav">
				<ul id="chats-menu" class="nav nav-list"><?

					$maxXmlId = 0;

					include('menu.php');

					?>
				</ul>
			</div>
		</div>
		<div class="span9" id="messages-cont" data-max="<?= $maxXmlId ?>"></div>
	</div>

	<hr>

	<footer>
		<p></p>
	</footer>

<script src="jquery.js"></script>
<script src="bootstrap.min.js"></script>
<script src="newchat.js"></script>
</body>
</html><?

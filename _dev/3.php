<?
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$s = file_get_contents('logs.json');
//$s = '[{"Id":1,"Text":"<a target=\"_blank\" class=\"profileLink\" href=\"https://www.facebook.com/laturage?fref=gs&amp;dti=239574542856710&amp;hc_location=group\" data-hovercard=\"/ajax/hovercard/user.php?id=100027253621501&amp;extragetparams={\"groups_location\":null,\"directed_target_id\":239574542856710,\"fref\":\"gs\",\"dti\":239574542856710,\"hc_location\":\"group\"}\" data-hovercard-prefer-more-content-show=\"1\">Ольга Теплова</a> одобрила <a target=\"_blank\" href=\"https://www.facebook.com/groups/239574542856710/permalink/1233456100135211/?sale_post_id=1233456100135211\">публикацию</a> <a target=\"_blank\" class=\"profileLink\" href=\"https://www.facebook.com/alpashkina.irina?fref=gs&amp;dti=239574542856710&amp;hc_location=group\" data-hovercard=\"/ajax/hovercard/user.php?id=1301949147&amp;extragetparams={\"groups_location\":null,\"directed_target_id\":239574542856710,\"fref\":\"gs\",\"dti\":239574542856710,\"hc_location\":\"group\"}\" data-hovercard-prefer-more-content-show=\"1\">Alpashkina Irina</a> на рассмотрении.","Date":"&#x41f;&#x44f;&#x442;&#x43d;&#x438;&#x446;&#x430;, 13 &#x438;&#x44e;&#x43b;&#x44f; 2018 &#x433;. &#x432; 14:33","DateD":"2018-07-13T14:33:45+03:00","DateUnix":1531481625.0,"FacebookId":1233456100135211,"Period":"day"}]';
$data = json_decode($s, true);
debugmessage($data);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
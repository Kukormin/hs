<?

/*include('config.php');
	
$hash = md5($_POST['action'].';'.$_POST['orderSumAmount'].';'.$_POST['orderSumCurrencyPaycash'].';'.$_POST['orderSumBankPaycash'].';'.$configs['shopId'].';'.$_POST['invoiceId'].';'.$_POST['customerNumber'].';'.$configs['ShopPassword']);		
if (strtolower($hash) != strtolower($_POST['md5'])){ 
	$code = 1;
}
else {
	$code = 0;
}

print '<?xml version="1.0" encoding="UTF-8"?>';
print '<paymentAvisoResponse performedDatetime="'. $_POST['requestDatetime'] .'" code="'.$code.'" invoiceId="'. $_POST['invoiceId'] .'" shopId="'. $configs['shopId'] .'"/>';
*/

$shopId = 53177;
$scid = 536453;
$action = 'https://demomoney.yandex.ru/eshop.xml';
//$action = 'https://money.yandex.ru/eshop.xml';

$userId = 1;
$orderId = 11;
$sum = 150;

?>
<form method="POST" action="<?= $action ?>">

<input name="scId" value="<?= $scid ?>" required=""> scId * - Counterparty's payment form ID. </br>
<input name="shopId" value="<?= $shopId ?>" required=""> shopId * - Counterparty’s ID.</br>
<input name="customerNumber" value="<?= $userId ?>" required=""> customerNumber * - Payer ID in the Counterparty IS. The ID can be the payer’s contract number, login, etc.</br>
<input name="orderNumber" value="<?= $orderId ?>" required=""> orderNumber</br>
<input name="Sum" type="text" value="<?= $sum ?>" required=""> Sum * - Order total.</br><br>

<input type="radio" name="paymentType" value=""><b>Empty</br>
<input type="radio" name="paymentType" value="PC"><b>Payment purse</b>; paymentType=PC</br>
<input type="radio" name="paymentType" value="AC"><b>Payment with any credit card</b>; paymentType=AC</br>
<input type="radio" name="paymentType" value="MC"><b>Payment from the mobile phone account</b>; paymentType=MC</br>
<input type="radio" name="paymentType" value="GP"><b>Payment via cash and cash terminals</b>; paymentType=GP</br>
<input type="radio" name="paymentType" value="WM"><b>Payment of the purse in system WebMoney</b>; paymentType=WM</br>
<input type="radio" name="paymentType" value="SB"><b>Online Payment through Sberbank</b>; paymentType=SB</br>
<input type="radio" name="paymentType" value="AB"><b>Online Payment through AlphaClick</b>; paymentType=AB</br><br>

<button type="submit">PAY</button>

</form>
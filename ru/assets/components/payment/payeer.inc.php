<?php
require_once MODX_BASE_PATH . "assets/components/payment/config/payeer.php";
$modx->addPackage('shopkeeper3', $modx->getOption('core_path') . 'components/shopkeeper3/model/');

if ($_SESSION['shk_lastOrder']['payment'] != 'payeer' && !$_GET['ord_id'])
{
	$modx->sendRedirect('/moi-zakazyi.html', 0, 'REDIRECT_HEADER');
}

if ($_GET['ord_id'] && $_GET['payment'] == 'payeer')
{
	$m_orderid = $_GET['ord_id'];
}
else
{
	$m_orderid = $_SESSION['shk_lastOrder']['id'];
}

$order = $modx->getObject('shk_order', $m_orderid);

if ($order)
{
	$change_status = $order->set('status', 2);
	$order->save();
	$modx->invokeEvent('OnSHKChangeStatus', array(
		'order_id' => $m_orderid,
		'status' => 2
	));
	
	$m_shop = PAYEER_MERCHANT_ID;
	$m_curr = PAYEER_CURRENCY_CODE;
	$m_key = PAYEER_SECRET_KEY;
	$amount = $order->get('price');
	$m_desc = base64_encode("Оплата счета №" . $m_orderid);
	$m_amount = number_format($amount, 2, '.', '');

	$arHash = array(
		$m_shop,
		$m_orderid,
		$m_amount,
		$m_curr,
		$m_desc,
		$m_key
	);
	
	$sign = strtoupper(hash('sha256', implode(':', $arHash)));
	
	$form = '
		<form id="payeer_form" name="payeer_form" method="GET" action="' . PAYEER_MERCHANT_URL . '">
		<input type="hidden" name="m_shop" value="' . $m_shop . '">
		<input type="hidden" name="m_orderid" value="' . $m_orderid . '">
		<input type="hidden" name="m_amount" value="' . $m_amount . '">
		<input type="hidden" name="m_curr" value="' . $m_curr . '">
		<input type="hidden" name="m_desc" value="' . $m_desc . '">
		<input type="hidden" name="m_sign" value="' . $sign . '">
		<input type="submit" name="m_process" value="Оплатить">
		<script language="javascript" type="text/javascript">document.getElementById("payeer_form").submit();</script>';
	
	echo $form;
	die;
}
?>
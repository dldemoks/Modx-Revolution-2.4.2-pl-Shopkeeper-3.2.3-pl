<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/config.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
require_once MODX_CORE_PATH . '../assets/components/payment/config/payeer.php';

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	if (isset($_POST['m_operation_id']) && isset($_POST['m_sign']))
	{
		$modx = new modX();
		$modx->initialize('web');
		$modx->addPackage('shopkeeper3', $modx->getOption('core_path') . 'components/shopkeeper3/model/');
		$order_id = $_POST['m_orderid'];
		$order = $modx->getObject('shk_order', $order_id);
		
		if (isset($order) && $order > 0) 
		{
			$m_key = PAYEER_SECRET_KEY;
		
			$arHash = array(
				$_POST['m_operation_id'],
				$_POST['m_operation_ps'],
				$_POST['m_operation_date'],
				$_POST['m_operation_pay_date'],
				$_POST['m_shop'],
				$_POST['m_orderid'],
				$_POST['m_amount'],
				$_POST['m_curr'],
				$_POST['m_desc'],
				$_POST['m_status'],
				$m_key
			);

			$sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
			
			// check the list of trusted ip
			
			$valid_ip = TRUE;
			$list_ip_str = str_replace(' ', '', PAYEER_IPFILTER);
			
			if (!empty($list_ip_str)) 
			{
				$list_ip = explode(',', $list_ip_str);
				$this_ip = $_SERVER['REMOTE_ADDR'];
				$this_ip_field = explode('.', $this_ip);
				$list_ip_field = array();
				$i = 0;
				$valid_ip = FALSE;
				foreach ($list_ip as $ip)
				{
					$ip_field[$i] = explode('.', $ip);
					if ((($this_ip_field[0] == $ip_field[$i][0]) or ($ip_field[$i][0] == '*')) and
						(($this_ip_field[1] == $ip_field[$i][1]) or ($ip_field[$i][1] == '*')) and
						(($this_ip_field[2] == $ip_field[$i][2]) or ($ip_field[$i][2] == '*')) and
						(($this_ip_field[3] == $ip_field[$i][3]) or ($ip_field[$i][3] == '*')))
						{
							$valid_ip = TRUE;
							break;
						}
					$i++;
				}
			}
			
			$log_text = 
				"--------------------------------------------------------\n".
				"operation id		" . $_POST["m_operation_id"] . "\n".
				"operation ps		" . $_POST["m_operation_ps"] . "\n".
				"operation date		" . $_POST["m_operation_date"] . "\n".
				"operation pay date	" . $_POST["m_operation_pay_date"] . "\n".
				"shop				" . $_POST["m_shop"] . "\n".
				"order id			" . $_POST["m_orderid"] . "\n".
				"amount				" . $_POST["m_amount"] . "\n".
				"currency			" . $_POST["m_curr"] . "\n".
				"description		" . base64_decode($_POST["m_desc"]) . "\n".
				"status				" . $_POST["m_status"] . "\n".
				"sign				" . $_POST["m_sign"] . "\n\n";

			if (PAYEER_LOGFILE != '')
			{	
				file_put_contents($_SERVER['DOCUMENT_ROOT'] . PAYEER_LOGFILE, $log_text, FILE_APPEND);
			}
			
			if ($_POST["m_sign"] != $sign_hash)
			{
				if (PAYEER_EMAILERR != '')
				{
					$to = PAYEER_EMAILERR;
					$subject = "Error payment";
					$message = "Failed to make the payment through Payeer for the following reasons:\n\n";
					$message .= " - Do not match the digital signature\n";
					$message .= "\n" . $log_text;
					$headers = "From: no-reply@" . $_SERVER['HTTP_SERVER'] . "\r\nContent-type: text/plain; charset=utf-8 \r\n";
					mail($to, $subject, $message, $headers);
				}

				exit($_POST['m_orderid'] . '|error');
			}
			
			if ($_POST['m_status'] == 'success' && $valid_ip)
			{
				$status = 5;
				$change_status = $order->set('status', $status);
				$order->save();
				$modx->invokeEvent('OnSHKChangeStatus', array(
					'order_id' => $order_id,
					'status' => $status
				));

				echo $_POST['m_orderid'] . '|success';
			}
			else
			{
				if (PAYEER_EMAILERR != '')
				{
					$to = PAYEER_EMAILERR;
					$subject = "Error payment";
					$message = "Failed to make the payment through Payeer for the following reasons:\n\n";
					
					if ($_POST['m_status'] != "success")
					{
						$message .= " - The payment status is not success\n";
					}
					
					if (!$valid_ip)
					{
						$message .= " - ip address of the server is not trusted\n";
						$message .= "   trusted ip: " . PAYEER_IPFILTER . "\n";
						$message .= "   ip of the current server: " . $_SERVER['REMOTE_ADDR'] . "\n";
					}
					
					$message .= "\n" . $log_text;
					$headers = "From: no-reply@" . $_SERVER['HTTP_SERVER'] . "\r\nContent-type: text/plain; charset=utf-8 \r\n";
					mail($to, $subject, $message, $headers);
				}

				echo $_POST['m_orderid'] . '|error';
			}
		}
		else 
		{
			echo $_POST['m_orderid'] . '|error';
		}
	}
}
?>
<?php

class ControllerPaymentzarinpalzg extends Controller {
	protected function index() {


    	$this->data['button_confirm'] = $this->language->get('button_confirm');
		$this->data['button_back'] = $this->language->get('button_back');
		
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$this->load->library('encryption');
		
		$encryption = new Encryption($this->config->get('config_encryption'));
		
		if($this->currency->getCode()!='RLS') {
		$this->currency->set("RLS");
		echo('<html><head><meta http-equiv="refresh" CONTENT="1; url=index.php?route=checkout/confirm"></head><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">&#1578;&#1606;&#1592;&#1740;&#1605; &#1575;&#1585;&#1586; &#1576;&#1607; &#1585;&#1740;&#1575;&#1604;...<br /><br /><a href="index.php?route=checkout/cart"><b>&#1576;&#1575;&#1586;&#1711;&#1588;&#1578; &#1576;&#1607; &#1601;&#1585;&#1608;&#1588;&#1711;&#1575;&#1607;</b></a></td><td>&nbsp;</td></tr></table></body></html>');
		die();
		}
		
		$this->data['Amount'] = @$this->currency->format($order_info['total'], $order_info['currency'], $order_info['value'], FALSE);
		$this->data['PIN']=$this->config->get('zarinpalzg_PIN');
		
		$this->data['return'] = $this->url->https('checkout/success');
		$this->data['cancel_return'] = $this->url->https('checkout/payment');

		$this->data['back'] = $this->url->https('checkout/payment');

		$client = new SoapClient("https://de.zarinpal.com/pg/services/WebGate/wsdl");

	if((!$client))
		die( "Can not connect to zarinpal.<br>" );

		$amount = intval($this->data['Amount'])/10;
		$callbackUrl = $this->url->https('payment/zarinpalzg/callback&order_id=' . $encryption->encrypt($this->session->data['order_id']));
		
		$res=$client->PaymentRequest(
			array(
					'MerchantID' 	=> $this->data['PIN'] ,
					'Amount' 		=> $amount ,
					'Description' 	=> ' خريد شماره: '.$order_info['order_id'] ,
					'Email' 		=> '' ,
					'Mobile' 		=> '' ,
					'CallbackURL' 	=> $callbackUrl

				));
		
		if($res->Status == 100){

		$this->data['action'] = "https://www.zarinpal.com/pg/StartPay/" . $res->Authority . "/ZarinGate";
		
		} else {
			
			$this->CheckState($res->Status);
			die();
		}

//
		
		$this->id       = 'payment';
		$this->template = $this->config->get('config_template') . 'payment/zarinpalzg.tpl';
		
		$this->render();		
}

	private function CheckState($status) {

	switch($status){
	
		case "-1" :
			echo "اطلاعات ارسالی ناقص می باشند";
			break;
		case "-2" :
			echo "وب سرويس نا معتبر می باشد";
			break;
		case "0" :
			echo "عمليات پرداخت طی نشده است";
			break;
		case "1" :
			break;
		case "-11" :
			echo "مقدار تراکنش تطابق نمی کند";
			break;
			
		case "-12" :
			echo "زمان پرداخت طی شده و کاربر اقدام به پرداخت صورتحساب ننموده است";
			break;
		
		default :
			echo "&#1582;&#1591;&#1575;&#1740; &#1606;&#1575;&#1605;&#1588;&#1582;&#1589;";
			break;
	}	
	
	return true;
}

function verify_payment($authority, $amount){

	if($authority){
		$client = new SoapClient("https://de.zarinpal.com/pg/services/WebGate/wsdl");
		
		if ((!$client))
			{echo  "Error: can not connect to zarinpal.<br>";return false;}
		
		else {
			$this->data['PIN'] = $this->config->get('zarinpalzg_PIN');
			$res = $client->PaymentVerification(
			array(
					'MerchantID'	 => $this->data['PIN'] ,
					'Authority' 	 => $authority ,
					'Amount'	 	=> $amount
				));
			
			$this->CheckState($res->Status);
			
			if($res->Status == 100)
				return true;

			else {
				return false;
			}
		
		}
	} 
	
	else {
		return false;
	}
	
	
	return false;
}

	public function callback() {
		$this->load->library('encryption');

		$encryption = new Encryption($this->config->get('config_encryption'));
		$au = @$this->request->get['Authority'];
		$st = @$this->request->get['Status'];
		$order_id = $encryption->decrypt(@$this->request->get['order_id']);
		$MerchantID=$this->config->get('zarinpalzg_PIN');
		$debugmod=false;
		
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$Amount = @$this->currency->format($order_info['total'], $order_info['currency'], $order_info['value'], FALSE);
		$amount = $Amount/10;
		if ($order_info && $st == "OK") {
		
		if(($this->verify_payment($au, $amount)) ) {
		
						$this->model_checkout_order->confirm($order_id, $this->config->get('zarinpalzg_order_status_id'),'&#1605;&#1575;&#1585;&#1607; &#1585;&#1587;&#1610;&#1583; &#1583;&#1610;&#1580;&#1610;&#1578;&#1575;&#1604;&#1610; &#1576;&#1575;&#1606;&#1705; &#1587;&#1575;&#1605;&#1575;&#1606; Authority: '.$au);
						
						
						
						$this->response->setOutput('<html><head><meta http-equiv="refresh" CONTENT="2; url=index.php?route=checkout/success"></head><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">&#1576;&#1575; &#1578;&#1588;&#1705;&#1585; &#1662;&#1585;&#1583;&#1575;&#1582;&#1578; &#1578;&#1705;&#1605;&#1740;&#1604; &#1588;&#1583;. &#1604;&#1591;&#1601;&#1575; &#1670;&#1606;&#1583; &#1604;&#1581;&#1592;&#1607; &#1589;&#1576;&#1585; &#1705;&#1606;&#1740;&#1583; &#1608; &#1740;&#1575; <a href="index.php?route=checkout/success"><b>&#1575;&#1740;&#1606;&#1580;&#1575; &#1705;&#1604;&#1740;&#1705; &#1606;&#1605;&#1575;&#1740;&#1740;&#1583;</b></a></td><td>&nbsp;</td></tr></table></body></html>');
						
						
					
        			
		
						
					}
		
		} else {
			
						$this->response->setOutput('<html><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">&#1582;&#1591;&#1575; &#1583;&#1585; &#1662;&#1585;&#1583;&#1575;&#1582;&#1578;.<br /><br /><a href="index.php?route=checkout/cart"><b>&#1576;&#1575;&#1586;&#1711;&#1588;&#1578; &#1576;&#1607; &#1601;&#1585;&#1608;&#1588;&#1711;&#1575;&#1607;</b></a></td><td>&nbsp;</td></tr></table></body></html>');
						
		}
	}
}
?>

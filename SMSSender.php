<?php

/**
 * SMSSender
 *
 * Odeslání SMS prostřednictvím O2 SMS Senderu
 *
 * @todo ZKONTROLOVAT PRIPOJENI, jinak exception (pokud nebyl internet, vyhodilo nejaky chyby.:
 *
 * Odesílám frontu…
 * ---------------------------------------------------------------
 * Odesílám SMS #790 Notice Error: Undefined offset: 1 in [/Users/cikorka/Sites/irisys/Lib/SMSSender.php, line 119]
 *
 * Notice Error: Undefined offset: 1 in [/Users/cikorka/Sites/irisys/Lib/SMSSender.php, line 120]
 *
 * Notice Error: Undefined offset: 1 in [/Users/cikorka/Sites/irisys/Lib/SMSSender.php, line 121]
 *
 *
 *
 * @param $username - optional
 * @param $password - optional
 *
 * Použití:
 * $sms =&new SMSSender();
 * $sms->send("telefonní číslo", "Ahoj");
 * $sms->sendArray(array("telefonní číslo 1" => "SMS 1", "telefonní číslo 2" => "SMS 2"));
 *
 * @copyright	  Copyright 2005-2012, Irisatel plus s.r.o.
 * @link		  http://www.irisatel.cz
 * @throws Exception
 * @throws Exception
 */

class SMSSender {

	public $username;

	public $password;

	protected $_curl;

	public $hiddenFields;

	public function __construct($username = '', $password = '') {
		if ($username == '' || $password == '') {
			if (!defined(SMS_SENDER_USERNAME) || !defined(SMS_SENDER_PASSWORD))
				throw new Exception('Chybí nastavení připojení.', 500);

			$this->username = SMS_SENDER_USERNAME;
			$this->password = SMS_SENDER_PASSWORD;
		} else {
			$this->username = $username;
			$this->password = $password;
		}
		$this->__initCurl();
		$this->__login();
	}

	public function __destruct() {
		curl_close($this->_curl);
	}

	public function sendArray($data) {
		foreach ($data as $phone => $text){
			$this->send($phone, $text);
		}
	}

	public function send($number, $text) {
		if ($number == "" || $text == "") {
			throw new Exception('Chybí telefonní číslo nebo text zprávy.',"0");
		}
		if ($number[0] != '+') {
			$number = '+420' . $number;
		}
		$data = array(
			'ctl00$data$mobilenum' 	=> $number,
			'ctl00$data$sms_text' 	=> $text,
			'ctl00$data$submit_sms' => 'Odeslat',
			'__VIEWSTATE' 			=> $this->hiddenFields[1],
			'__PREVIOUSPAGE' 		=> $this->hiddenFields[2],
			'__EVENTVALIDATION' 	=> $this->hiddenFields[3],
		);
		curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($this->_curl, CURLOPT_URL, 'https://smsender.o2.cz/web/Gate.aspx');
 		$result = curl_exec($this->_curl);
		$this->__hiddenFields($result);
		return $this->__isSend($result);
	}

	private function __login() {
		curl_setopt($this->_curl, CURLOPT_URL, 'https://smsender.o2.cz/web/default.aspx');
		$this->__hiddenFields(curl_exec($this->_curl));
		curl_setopt($this->_curl, CURLOPT_POST, true);
		$data = array(
			'txtUserName' 		=> $this->username,
			'txtPassword' 		=> $this->password,
			'btnLogon' 			=> 'Přihlásit',
			'__VIEWSTATE' 		=> $this->hiddenFields[1],
			'__PREVIOUSPAGE' 	=> $this->hiddenFields[2],
			'__EVENTVALIDATION' => $this->hiddenFields[3],
		);
		curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($this->_curl, CURLOPT_URL, 'https://smsender.o2.cz/web/Logon.aspx');
		$ret = curl_exec($this->_curl);
		$this->__hiddenFields($ret);
	}

	private function __isSend($result) {
		return preg_match("/Zpráva byla úspěšně odeslána na číslo/i", $result);
	}

	private function __hiddenFields($result) {
		$hid[1] = preg_match("<input type=\"hidden\" name=\"__VIEWSTATE\" id=\"__VIEWSTATE\" value=\"(.*?)\" />", $result, $out[1]);
		$hid[2] = preg_match("<input type=\"hidden\" name=\"__PREVIOUSPAGE\" id=\"__PREVIOUSPAGE\" value=\"(.*?)\" />", $result, $out[2]);
		$hid[3] = preg_match("<input type=\"hidden\" name=\"__EVENTVALIDATION\" id=\"__EVENTVALIDATION\" value=\"(.*?)\" />", $result, $out[3]);

		$out[1] = $out[1][1];
		$out[2] = $out[2][1];
		$out[3] = $out[3][1];
		$this->hiddenFields = $out;
		if ($hid[1] && $hid[2] && $hid[3]) {
			return true;
		} else {
			return false;
		}
	}

	private function __initCurl() {
		$this->_curl = curl_init();
		curl_setopt($this->_curl, CURLOPT_HEADER, false);
		curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->_curl, CURLOPT_COOKIESESSION, true);
		curl_setopt($this->_curl, CURLOPT_COOKIEFILE, "tmp/cookiefile");
		curl_setopt($this->_curl, CURLOPT_COOKIEJAR, "tmp/cookiefile");
		curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);
	}
}
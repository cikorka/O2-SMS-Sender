<?php

/**
 * ODESÍLÁNÍ SMS PROSTŘEDNICTVÍM O2 SMS SENDER
 * 
 * @param $username - optional
 * @param $password - optional
 *
 * Použití:
 * $sms =&new SMSSender();
 * $sms->send("telefonní číslo", "Ahoj");
 * $sms->sendArray(array("telefonní číslo 1" => "SMS 1", "telefonní číslo 2" => "SMS 2"));
 *
 */	
 
class SMSSender {

	var $username;
	var $password;
	var $curl;
	var $hidden_fields;

	function __construct($username = '', $password = '') {
		global $kc_config;
		
		if ($username == "" || $password == "") {
			
			if (SMS_SENDER_USERNAME == "" || SMS_SENDER_PASSWORD == "")
				throw new IException('Chybí nastavení připojení v souboru config.php',"0");
						
			$this->username = SMS_SENDER_USERNAME;
			$this->password = SMS_SENDER_PASSWORD;
		}
		else {
			$this->username = $username;
			$this->password = $password;
		}
	
		$this->initCurl();
		$this->login();

	}
	
	function __destruct() {
		curl_close($this->curl);
	}
	
	public function sendArray($data) {
		foreach ($data as $phone => $text) $this->send($phone, $text);
	}
		
	public function send($number, $text) {
	
		if ($number == "" || $text == "")
			throw new IException('Chybí telefonní číslo nebo text zprávy.',"0");
			
		$data = array(
			'ctl00$data$mobilenum' 	=> '+420'.$number, 
			'ctl00$data$sms_text' 	=> $text, 
			'ctl00$data$submit_sms' => 'Odeslat', 
			'__VIEWSTATE' 			=> $this->hidden_fields[1], 
			'__PREVIOUSPAGE' 		=> $this->hidden_fields[2], 
			'__EVENTVALIDATION' 	=> $this->hidden_fields[3],
		);
	
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($this->curl, CURLOPT_URL, 'https://smsender.cz.o2.com/web/Gate.aspx'); 

 		$result = curl_exec($this->curl); 	
		$this->hiddenFields($result); 
		
		if ($this->isSend($result)) return true;
		else return false;
	
		}
		
		private function login() {
		
		curl_setopt($this->curl, CURLOPT_URL, 'https://smsender.cz.o2.com/web/default.aspx'); 
		$this->hiddenFields(curl_exec($this->curl));
	
		$data = array(
			'txtUserName' 		=> $this->username, 
			'txtPassword' 		=> $this->password, 
			'btnLogon' 			=> 'Přihlásit',
			'__VIEWSTATE' 		=> $this->hidden_fields[1], 
			'__PREVIOUSPAGE' 	=> $this->hidden_fields[2], 
			'__EVENTVALIDATION' => $this->hidden_fields[3],
		);
		
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);	
		curl_setopt($this->curl, CURLOPT_URL, 'https://smsender.cz.o2.com/web/Logon.aspx'); 
		$this->hiddenFields(curl_exec($this->curl)); 
	}


	private function isSend($result) {
		if (preg_match("/Zpráva byla úspěšně odeslána na číslo/i", $result)) return true;
		else return false;
	}

	private function hiddenFields($result) {
	
		$hid[1] = preg_match("<input type=\"hidden\" name=\"__VIEWSTATE\" id=\"__VIEWSTATE\" value=\"(.*?)\" />", $result, $out[1]);
		$hid[2] = preg_match("<input type=\"hidden\" name=\"__PREVIOUSPAGE\" id=\"__PREVIOUSPAGE\" value=\"(.*?)\" />", $result, $out[2]);
		$hid[3] = preg_match("<input type=\"hidden\" name=\"__EVENTVALIDATION\" id=\"__EVENTVALIDATION\" value=\"(.*?)\" />", $result, $out[3]);
	
		$out[1] = $out[1][1];
		$out[2] = $out[2][1];
		$out[3] = $out[3][1];
		
		$this->hidden_fields = $out;
		
		if ($hid[1] and $hid[2] and $hid[3]) return true;
		else return false;
	}

	private function initCurl() {
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_HEADER, false); 
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($this->curl, CURLOPT_COOKIESESSION, TRUE); 	
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, DIR_TMP . "/cookiefile"); 
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, DIR_TMP . "/cookiefile"); 
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1); 
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
	}
}

?>
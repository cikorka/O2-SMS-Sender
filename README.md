# O2 CZ SMSSender

## How to Use

### Download

```
git clone https://github.com/cikorka/O2-SMS-Sender.git
```

### Use

$sms = new SMSSender();
$sms->send('phoneNumber', 'Ahoj');
$sms->sendArray(array('phoneNumber' => 'SMS 1', 'nextPhoneNumber' => 'SMS 2'));

<?
	define("TEST_MODE", false);

	// load the sapphire base
	require_once("sapphire/core/Core.php");

	// this prevents some kind of error in the core
	$_SESSION = null;

	// connect to this site's database
	global $databaseConfig;
	require_once("sapphire/core/model/DB.php");
	DB::connect($databaseConfig);

	// parse post variables, reformat the data to be sent back via socket
	$data = "cmd=_notify-validate";
	foreach( $_POST as $key => $value )
	{
		$value = urlencode(stripslashes($value));
		$data .= "&".$key."=".$value;
	}

	$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: ".strlen($data)."\r\n\r\n";

	$response = "NONE";

	// send back the info
	$socket_handle = fsockopen( TEST_MODE ? "ssl://www.sandbox.paypal.com" : "ssl://www.paypal.com", 443, $errno, $errstr, 30 );
	if( $socket_handle )
	{
		fputs( $socket_handle, $header.$data );
		while( !feof($socket_handle) )
		{
			$response = fgets($socket_handle, 1024);
			if( strcmp($response, "VERIFIED") == 0 )
			{
				$response = "VERIFIED";
			}
			else if( strcmp($response, "INVALID") == 0 )
			{
				$response = "INVALID";
			}
		}
		fclose($socket_handle);
	}
	
//	$fh = fopen("logfile.txt", "a+");
//	fwrite( $fh, print_r($_POST, true) );
//	fclose($fh);	
	
	// save data into our SS model
	$payment = new PaypalPayment();
	$payment->Amount = $_POST['payment_gross'];
	$payment->TransactionID = $_POST['verify_sign'];
	$payment->Date = SS_Datetime::now();
	$payment->Status = ($response == "VERIFIED" ? "payment accepted" : "payment denied");
	$payment->ItemID = $_POST['item_number'];
	$payment->ItemName = $_POST['item_name'];
	$payment->Name = $_POST['address_name'];
	$payment->Street = $_POST['address_street'];
	$payment->City = $_POST['address_city'];
	$payment->State = $_POST['address_state'];
	$payment->Country = $_POST['address_country'];
	$payment->Zip = $_POST['address_zip'];
	$payment->Email = $_POST['payer_email'];
	$payment->PayerID = $_POST['payer_id'];	
	$payment->write();

?>
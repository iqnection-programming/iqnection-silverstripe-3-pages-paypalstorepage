<?php

class PaypalItemOption extends DataObject
{
	private static $db = array(
		"SortOrder" => "Int", 
		"ItemID" => "Varchar(255)",
		"Name" => "Varchar(255)",
		"Price" => "Currency",
	);
	
	private static $has_one = array(
		"PaypalStoreItem" => "PaypalStoreItem"
	);
	
	private static $summary_fields = array(
		"Price" => "Price",
		"ItemID" => "Item / Invoice Number",
		"Name" => "Option Name",
	);
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$fields->push( new HiddenField('SortOrder',null,$fields->dataFieldByName('SortOrder')->Value()) );
		$fields->dataFieldByName("ItemID")->setTitle("Item / Invoice Number");
		$fields->dataFieldByName("Name")->setTitle("Item Name");
		$fields->replaceField('Price', CurrencyField::create("Price", "Price") );
		
		$this->extend('updateCMSFields',$fields);
		
		return $fields;
	}

	public function validate()
	{
		$result = parent::validate();
		if (PaypalItemOption::get()->exclude('ID',$this->ID)->find("ItemID",$this->ItemID)) { $result->error('Item Number must be unique'); }
		return $result;
	}
	
	public function FormID()
	{
		return "form".$this->PaypalStoreItemID;
	}

	public function OptionName()
	{
		return $this->FormID()."_options";
	}
	
	public function OptionID()
	{
		return $this->FormID()."_option".$this->ID;
	}
	
	public function Get_ItemID()
	{
		if ( (empty($this->ItemID)) && ($this->PaypalStoreItemID) )
		{
			return $this->PaypalStoreItem()->ItemID;
		}			
		return $this->ItemID;
	}

	public function Get_Price()
	{
		if ( ($this->Price == 0) && ($this->PaypalStoreItemID) )
		{
			return $this->PaypalStoreItem()->Price;
		}
		return $this->Price;
	}
	
	public function canCreate($member = null) { return true; }
	public function canDelete($member = null) { return true; }
	public function canEdit($member = null) { return true; }
	public function canView($member = null) { return true; }
}

class PaypalPayment extends DataObject
{
	private static $db = array( 
		"Amount" => "Currency",
		"TransactionID" => "Varchar(255)",
		"GatewayResponse" => "Text",
		"Date" => "SS_Datetime",
		"Status" => "Varchar(255)",
		"ItemID" => "Varchar(255)",
		"ItemName" => "Varchar(255)",
		"Name" => "Varchar(255)",
		"Street" => "Varchar(255)",
		"City" => "Varchar(255)",
		"State" => "Varchar(255)",
		"Country" => "Varchar(255)",
		"Zip" => "Varchar(255)",
		"Email" => "Varchar(255)",
		"PayerID" => "Varchar(255)"
	);
	
	private static $has_one = array(
		"PaypalStorePage" => "PaypalStorePage"
	); 		
	
	private static $summary_fields = array(
		'Date.NiceUS' => 'Date',
		'Name' => 'Name',
		'ItemID' => 'Item ID',
		'ItemName' => 'Item Name',
		'Amount' => 'Price'
	);
	
	private static $default_sort = 'Date DESC';
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		if (Permission::check('ADMIN'))
		{
			$fields->addFieldToTab('Root.GatewayResponse', TextareaField::create('GatewayResponse','Gateway Response') );
		}
		else
		{
			$fields->removeByName('GatewayResponse');
		}

		$this->extend('updateCMSFields',$fields);
		
		return $fields;
	}
	
	public function OnSuccessfulPayment()
	{
		$this->extend('AfterSuccessfulPayment');
	}
	
	public function canCreate($member = null) { return (Permission::check('ADMIN')); }
	public function canDelete($member = null) { return true; }
	public function canEdit($member = null)   { return (Permission::check('ADMIN')); }
	public function canView($member = null)   { return true; }
}

class PaypalStoreItem extends DataObject
{
	private static $db = array(
		"SortOrder" => "Int", 
		"Price" => "Currency",
		"ItemID" => "Varchar(255)",
		"Name" => "Varchar(255)",
		"Description" => "Text"
	);
	
	private static $has_one = array(
		"Image" => "Image",
		"PaypalStorePage" => "PaypalStorePage"
	); 	
	
	private static $has_many = array(
		"PaypalItemOptions" => "PaypalItemOption",
	);	
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$fields->push( new HiddenField('SortOrder',null,$fields->dataFieldByName('SortOrder')->Value()) );
		
		$fields->dataFieldByName("ItemID")->setTitle("Item / Invoice Number");
		$fields->dataFieldByName("Name")->setTitle("Item Name");
		$fields->replaceField('Image', UploadField::create("Image")->setAllowedExtensions(array('jpg','jpeg','png','gif')) );
		$fields->dataFieldByName("Description")->setTitle("Description of Item");
		
		$fields->removeByName('PaypalItemOptions');
		if (!$this->ID)
		{
			$fields->addFieldToTab('Root.Main', HeaderField::create('note1','You must first save this product before adding options.'));
		}
		else
		{
			$fields->addFieldToTab('Root.Main', GridField::create(
				'PaypalItemOptions',
				'Item Options',
				$this->PaypalItemOptions(),
				GridFieldConfig_RecordEditor::create()->addComponent(
					new GridFieldSortableRows('SortOrder')
				))
			);
		}
		
		$this->extend('updateCMSFields',$fields);
		
		return $fields;
	}
	
	public function validate()
	{
		$result = parent::validate();
		$extra = ($this->ID) ? " AND ID <> ".$this->ID : null;
		if (PaypalStoreItem::get()->exclude('ID',$this->ID)->find("ItemID",$this->ItemID)) { $result->error('Item Number must be unique'); }
		return $result;
	}
			
	function FindPrice()
	{
		return ($this->PaypalItemOptions()->Count()) ? $this->PaypalItemOptions()->First()->Price : $this->Price;
	}
	
	function FindItemID()
	{
		return ($this->PaypalItemOptions()->Count()) ? $this->PaypalItemOptions()->First()->ItemID : $this->ItemID;
	}
	
	public function canCreate($member = null) { return true; }
	public function canDelete($member = null) { return true; }
	public function canEdit($member = null)   { return true; }
	public function canView($member = null)   { return true; }
}

class PaypalStorePage extends Page
{
	static $icon = "iq-paypalstorepage/images/icon-paypalstorepage";
	
	private static $db = array(
		"PayPalUsername" => 'Varchar(255)',
		"ThankYouText" => "HTMLText"
	);
		
	private static $has_many = array(
		"PaypalStoreItems" => "PaypalStoreItem",
		"PaypalPayments" => "PaypalPayment"
	);
	
	private static $paypal_url = "https://www.paypal.com/cgi-bin/webscr";

	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		 
		$fields->addFieldToTab('Root.Main', new TextField('PayPalUsername','PayPal Username'),'Content' );
		
		// Paypal Items
		$fields->addFieldToTab('Root.Items', new GridField(
			'PaypalStoreItems',
			'Paypal Items',
			$this->PaypalStoreItems(),
			GridFieldConfig_RecordEditor::create()->addComponent(
				new GridFieldSortableRows('SortOrder')
			)
		));
		
		// Paypal Payments
		$fields->addFieldToTab('Root.Payments', new GridField(
			'PaypalPayments',
			'Paypal Payments',
			$this->PaypalPayments(),
			GridFieldConfig_RecordEditor::create()
		));
		
		$fields->addFieldToTab("Root.Main", new HTMLEditorField("ThankYouText", "Text on Submission")); 

		$this->extend('updateCMSFields',$fields);
		
		return $fields;
	}
			
	public function IPNLink()
	{
		$Link = $this->AbsoluteLink('process_ipn_response');
		$this->extend('updateIPNLink',$Link);
		return $Link;
	}
	
	public function PaypalUrl()
	{
		return $this->config()->get('paypal_url');
	}
	
	public function PaypalUser()
	{
		return $this->PayPalUsername;
	}
	
}

class PaypalStorePage_Controller extends Page_Controller
{
	static $allowed_actions = array(
		'process_ipn_response'
	);
		
	public function init()
	{
		parent::init();
	}	
	
	function CustomJS()
	{
		$JS = parent::CustomJS();
		if ( ($PaypalStoreItems = $this->PaypalStoreItems()) && ($PaypalStoreItems->Count()) )
		{
			$JS .= '$(document).ready(function(){';
			foreach($PaypalStoreItems as $PaypalStoreItem)
			{
				if ( ($ProductOptions = $PaypalStoreItem->PaypalItemOptions()) && ($ProductOptions->Count()) )
				{	
					$JS .= '$("#'.$ProductOptions->First()->OptionID().'").attr("checked", "checked");';
						
					foreach($ProductOptions as $ProductOption)
					{
						$JS .= '
							$("#'.$ProductOption->OptionID().'").click(function(){
							$("#showprice_'.$ProductOption->FormID().'").html("$ '.$ProductOption->Get_Price().'");
							$("#num_'.$ProductOption->FormID().'").val("'.$ProductOption->Get_ItemID().'");
							$("#price_'.$ProductOption->FormID().'").val("'.$ProductOption->Get_Price().'");
						});';
					}
				}
			}
			$JS .= '});';
		}
		$this->extend('updateCustomJS',$JS);
		return $JS;
	}
	
	function process_ipn_response($request=null)
	{
		if (!defined('PAYPAL_DEBUG_MODE')) define("PAYPAL_DEBUG_MODE", false);	
	
		// this prevents some kind of error in the core
		$_SESSION = null;
		
		if( DEBUG_MODE ) SS_Log::add_writer(new SS_LogFileWriter(__DIR__."/log/paypal.transactions.txt"), SS_Log::WARN, '>');
		if( DEBUG_MODE ) SS_Log::log("IPN Started!",SS_Log::DEBUG);
	
		
		// parse post variables, reformat the data to be sent back via socket
		$data = "cmd=_notify-validate";
		foreach( $_POST as $key => $value )
		{
			$value = urlencode(stripslashes($value));
			$data .= "&".$key."=".$value;
		}
		
		// post back to PayPal system to validate
		$header =  "POST /cgi-bin/webscr HTTP/1.1\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Host: www.paypal.com\r\n"; 
		$header .= "Connection: close\r\n";
		$header .= "Content-Length: ".strlen($data)."\r\n\r\n";
	
		$response = "NONE";
	
		// send back the info
		$socket_handle = fsockopen( /*PAYPAL_TEST_MODE ? "ssl://www.sandbox.paypal.com" :*/ "ssl://www.paypal.com", 443, $errno, $errstr, 30 );
		if( DEBUG_MODE ) SS_Log::log("header_debug:\n".print_r($header, true)."\n\n",SS_Log::DEBUG);
		if( DEBUG_MODE ) SS_Log::log("data_debug:\n".print_r($data, true)."\n\n",SS_Log::DEBUG);
		if( $socket_handle )
		{
			fputs( $socket_handle, $header.$data );
			while( !feof($socket_handle) )
			{
				$response = fgets($socket_handle, 1024);
				$response = trim($response);	
				if( DEBUG_MODE ) SS_Log::log("response_debug:\n".print_r($response, true)."\n\n",SS_Log::DEBUG);
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
		
		if( DEBUG_MODE ) SS_Log::log("paypal response: ".$response,SS_Log::DEBUG);
		if( DEBUG_MODE ) SS_Log::log(print_r($_POST,true),SS_Log::DEBUG);
		
		if( $response == "INVALID" )	// we only care about completed interactions
			exit(0);
		
		// SUCCESS - Do something with the data		
		$Payment = new PaypalPayment();
		$Payment->PaypalStorePageID = $this->ID;
		$Payment->Date = SS_Datetime::now();
		$Payment->TransactionID = isset($_POST['txn_id']) ? $_POST['txn_id'] : $_POST['ipn_track_id'];
		$Payment->GatewayResponse = implode("\n",$_POST);
		$Payment->Amount = isset($_POST['amount3']) ? $_POST['amount3'] : (isset($_POST['payment_gross']) ? $_POST['payment_gross'] : (isset($_POST['mc_gross']) ? $_POST['mc_gross'] : 0) );
		$Payment->ItemID = isset($_POST['item_number']) ? $_POST['item_number'] : null;
		$Payment->ItemName = isset($_POST['item_name']) ? $_POST['item_name'] : null;
		$Payment->Email = isset($_POST['payer_email']) ? $_POST['payer_email'] : null;
		$Payment->Status = isset($_POST['payment_status']) ? $_POST['payment_status'] : null;
		$Payment->Name = (isset($_POST['first_name']) ? $_POST['first_name'] : null).' '.(isset($_POST['last_name']) ? $_POST['last_name'] : null);
		$Payment->Street = isset($_POST['address_street']) ? $_POST['address_street'] : null;
		$Payment->City = isset($_POST['address_city']) ? $_POST['address_city'] : null;
		$Payment->State = isset($_POST['address_state']) ? $_POST['address_state'] : null;
		$Payment->Country = isset($_POST['address_country']) ? $_POST['address_country'] : null;
		$Payment->Zip = isset($_POST['address_zip']) ? $_POST['address_zip'] : null;
		$Payment->PayerID = isset($_POST['payer_id']) ? $_POST['payer_id'] : null;
		$Payment->write();
		$Payment->OnSuccessfulPayment();
		return $Payment;


	}
}	

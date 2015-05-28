<?
	define("TEST_MODE", true);
	
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
			$fields = new FieldList();

			$fields->push( new HeaderField('parentName','Parent Item: '.$this->PaypalStoreItem()->ItemID) );
			$fields->push( new TextField("ItemID", "Item / Invoice Number") );
			$fields->push( new TextField("Name", "Item Name") );
			$fields->push( new CurrencyField("Price", "Price") );
			
			return $fields;
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
	}
	
	class PaypalPayment extends DataObject
	{
		private static $db = array( 
			"Amount" => "Currency",
			"TransactionID" => "Varchar(255)",
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
		
        public function getCMSFields()
        {
			$fields = new FieldList();

			$fields->push( new CurrencyField("Amount", "Transaction Amount") );
			$fields->push( new TextField("TransactionID", "Transaction ID") );			
			$fields->push( new DatetimeField_Readonly("Date", "Transaction Date") );
			$fields->push( new TextField("Status", "Status") );
			$fields->push( new TextField("ItemID", "Item ID") );
			$fields->push( new TextField("ItemName", "Item Name") );
			$fields->push( new TextField("Name", "Payer Name") );
			$fields->push( new TextField("Street", "Payer Address") );
			$fields->push( new TextField("City", "Payer City") );
			$fields->push( new TextField("State", "Payer State") );
			$fields->push( new TextField("Country", "Payer Country") );
			$fields->push( new TextField("Zip", "Payer Zip Code") );
			$fields->push( new TextField("Email", "Payer Paypal Email") );
			$fields->push( new TextField("PayerID", "Paypal Payer ID") );

			return $fields;
        }
		
		public function canCreate($member = null) { return false; }
		public function canDelete($member = null) { return true; }
		public function canEdit($member = null) { return false; }
	}

	class PaypalStoreItem extends DataObject
	{
		private static $db = array(
			"SortOrder" => "Int", 
			"Price" => "Decimal",
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
			$fields = new FieldList();

			$fields->push( new CurrencyField("Price", "Price") );
			$fields->push( new TextField("ItemID", "Item / Invoice Number") );
			$fields->push( new TextField("Name", "Item Name") );
			$fields->push( $UploadField = new UploadField("Image") );
			$fields->push( new TextAreaField("Description", "Description of Item") );
			$UploadField->setAllowedExtensions(array('jpg','jpeg','png','gif'));
			
			if (!$this->ID) $fields->push( new HeaderField('note1','You must first save this product before adding options.'));
			$PaypalItemOptions_config = GridFieldConfig::create()->addComponents(
				new GridFieldSortableRows('SortOrder'),
				new GridFieldToolbarHeader(),
				($this->ID)?new GridFieldAddNewButton('toolbar-header-right'):null,
				new GridFieldSortableHeader(),
				new GridFieldDataColumns(),
				new GridFieldPaginator(10),
				new GridFieldEditButton(),
				new GridFieldDeleteAction(),
				new GridFieldDetailForm()				
			);
			$fields->push( new GridField('PaypalItemOptions','Paypal Item Options',$this->PaypalItemOptions(),$PaypalItemOptions_config));
			
			return $fields;
        }
		
		public function GetItemImage()
		{
			$output = "";
			
			$image_src = false;			
			if ( ($this->ImageID) && ($img = $this->Image()) )
			{
				if ($img->getWidth() > 250)
				{
					if ($resized = $img->SetRatioSize(250,999))
						$image_src = $resized->Filename;
				}
				else
				{
					$image_src = $img->Filename;
				}
			}
			
			if ( $image_src && $image_src != "assets/")
			{
				$output .= "<img src=\"".$image_src."\" alt=\"".$this->Name."\" />";
			}
			
			return $output;
		}		
		
		public function canCreate($member = null) { return true; }
		public function canDelete($member = null) { return true; }
		public function canEdit($member = null) { return true; }
	}

	class PaypalStorePage extends Page
	{
		static $icon = "iq-audiogallerypage/images/icon-paypalstorepage";
		
		private static $db = array(
			"PayPalUsername" => 'Varchar(255)',
			"ThankYouText" => "HTMLText"
		);
			
		private static $has_many = array(
			"PaypalStoreItems" => "PaypalStoreItem",
			"PaypalPayments" => "PaypalPayment"
		);

		public function getCMSFields()
		{
			$fields = parent::getCMSFields();
			 
			$fields->addFieldToTab('Root.Content.Main', new TextField('PayPalUsername','PayPal Username'),'Content' );
			
			$PaypalStoreItems_config = GridFieldConfig::create()->addComponents(				
				new GridFieldSortableRows('SortOrder'),
				new GridFieldToolbarHeader(),
				new GridFieldAddNewButton('toolbar-header-right'),
				new GridFieldSortableHeader(),
				new GridFieldDataColumns(),
				new GridFieldPaginator(10),
				new GridFieldEditButton(),
				new GridFieldDeleteAction(),
				new GridFieldDetailForm()				
			);
			$fields->addFieldToTab("Root.Content.Items", new GridField('PaypalItemOptions','Paypal Item Options',$this->PaypalStoreItems(),$PaypalStoreItems_config)); 
			$fields->addFieldToTab("Root.Content.Main", new HTMLEditorField("ThankYouText", "Text on Submission")); 

			return $fields;
		}
		
		/* Out dated and possibly not used
		private function createPaymentManager()
		{
			$tf = new HasManyComplexTableField(
				$this,
				"PaypalPayments",
				"PaypalPayment",
				array(
					"Amount" => "Transaction Amount",
					"Date" => "Transaction Date",
					"ItemID" => "Item ID",
					"ItemName" => "Item Name",
					"Name" => "Payer Name",
					"Email" => "Payer Paypal Email"
				),
				"getCMSFields_forPopup"
			);
			
			// disables multi-select functionality (kinda redundant for this page)
			$tf->Markable = false;
			
			// use a custom query to display newest submissions first
			$instance = singleton("PaypalPayment");
			$query = $instance->buildSQL("", "PaypalPayment.Date DESC", null, "");			
			$tf->setCustomQuery($query); 			
			
			// pretty up the date field
			$tf->setFieldCasting(array(
				"Date" => "SS_Datetime->Nice"
			));

			// pagination
			$tf->setShowPagination(true);
			$tf->setPageSize(20);
			
			// use the complete data set for the CSV export
			$tf->setFieldListCsv(array(
				"Amount" => "Transaction Amount",
				"TransactionID" => "Transaction ID",
				"Date" => "Transaction Date",
				"ItemID" => "Item ID",
				"ItemName" => "Item Name",
				"Name" => "Payer Name",
				"Street" => "Payer Address",
				"City" => "Payer City",
				"State" => "Payer State",
				"Country" => "Payer Country",
				"Zip" => "Payer Zip Code",
				"Email" => "Payer Paypal Email",
				"PayerID" => "Paypal Payer ID"
			));			
			
			// don't allow adding. first in list is the default click action.
			$tf->setPermissions(array(
				"show",
				"export",
				"delete"
			));
			
			return $tf;
		}*/
		
		/*public function IPNLink()
		{
			return $this->BaseHref()."paypal.notify.php";
		}*/
		
		public function PaypalUrl()
		{
			return "https://www.paypal.com/cgi-bin/webscr";
		}
		
		public function PaypalUser()
		{
			return $this->PayPalUsername;
		}
		
	}

	class PaypalStorePage_Controller extends Page_Controller
	{
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
						$JS .= '
							$("#'.$ProductOptions->First()->OptionID().'").attr("checked", "checked");
							$("#showprice_'.$ProductOptions->First()->FormID().'").html("$ '.$ProductOptions->First()->Get_Price().'");
							$("#num_'.$ProductOptions->First()->FormID().'").val("'.$ProductOptions->First()->Get_ItemID().'");
							$("#price_'.$ProductOptions->First()->FormID().'").val("'.$ProductOptions->First()->Get_Price().'");';
						
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
			return $JS;
		}
		
	}	
?>
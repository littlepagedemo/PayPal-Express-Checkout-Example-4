<?php

include_once("config.php");


	/*	
	' Define the PayPal Redirect URLs.  
	' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
	' 	change the URL depending if you are testing on the sandbox or the live PayPal site
	'
	' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
	' For the live site, the URL is     https://www.paypal.com/webscr&cmd=_express-checkout&token=
	*/	
	if ($SandboxFlag == true) 
	{
		$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
		$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
	}
	else
	{
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
	}

	
	/*
	'-------------------------------------------------------------------------------------------
	' Shipping Calculation
	' Insert your Shipping formula here
	'-------------------------------------------------------------------------------------------
	*/	
	
	$shipping_amt = '0';	// no shipping amount
	//$shipping_amt = '5.00';	// or Flat rate
	
		
	
	/*
	'-------------------------------------------------------------------------------------------
	' Tax Calculation
	' Insert your Tax formula here
	'-------------------------------------------------------------------------------------------
	*/	
	
	$tax_amt = '0';		// no tax amount
	$tax_amt = '2.00';	// or Flat rate

	//-------------------------------------------	
	// Check cart item exits
	//-------------------------------------------
	function item_exist($ItemNumber){
	
		$cid_exist = 0;
		if ($_SESSION['cart_item_arr']) 
		{
			$cart_item_arr = $_SESSION['cart_item_arr'];	
			foreach ($cart_item_arr as $c) {
				if ($c[2] == $ItemNumber )
					$cid_exist = 1;
			}
		}
		return $cid_exist;
	}
	

	//-------------------------------------------	
	// Update cart item qty and amount
	//-------------------------------------------
	function update_cart($ItemNumber,$ItemQty) {
		
		if ($_SESSION['cart_item_arr']) 
		{				
			foreach ($_SESSION['cart_item_arr'] as $c) 
			{
				if ($c[2] == $ItemNumber){					
					$c[4] += $ItemQty;
					$c[5] += $ItemQty * $c[3];
					$c_arr[] = array($c[0], $c[1], $c[2], $c[3], $c[4], number_format($c[5],2));
				}else
					$c_arr[] = $c;	
			}
			return $c_arr;
		}	
	}
	
	
	//-------------------------------------------	
	// Update cart item session
	// "$ItemName","$ItemDesc","$ItemNumber","$ItemPrice","$ItemQty"
	//-------------------------------------------
	function cart_process($cart_item){

		// Existing cart
		if ($_SESSION['cart_item_arr']) 
		{
			$cart_item_arr = $_SESSION['cart_item_arr'];
	
			// check $ItemNumber exist?
			if(item_exist($cart_item[2]))
				$cart_item_arr = update_cart($cart_item[2],$cart_item[4]); // update ItemQty 
			else
				$cart_item_arr[] = $cart_item;
		
			$cart_no = $_SESSION['cart_no']+$cart_item[4];
	
			$cart_item_total_amt = $_SESSION['cart_item_total_amt']; // amount of all items
			$cart_item_total_amt += $cart_item[5];
	
			$_SESSION['cart_item_arr'] =  $cart_item_arr;
			$_SESSION['cart_no'] = $cart_no;
			$_SESSION['cart_item_total_amt'] = $cart_item_total_amt;		
			//print_r($cart_item_arr);
		} 
		// New cart
		else {
			$cart_item_arr[] = $cart_item;
			$cart_no = 1;
	
			$_SESSION['cart_item_arr'] =  $cart_item_arr;
			$_SESSION['cart_no'] = $cart_no;
			$_SESSION['cart_item_total_amt'] = $cart_item[5]; 
			//print_r($cart_item_arr);	
		}
	}

	//-------------------------------------------
	// Prepare url for items details information
	//-------------------------------------------
	function get_payment_request(){

		$payment_request='';
		$cart_item_arr = $_SESSION['cart_item_arr'];	
		$cart_no = count($cart_item_arr);
		//print_r($cart_item_arr);
	
		$n=0;
		$payment_request='';
		foreach ($cart_item_arr as $c) 
		{
			$payment_request .=	'&L_PAYMENTREQUEST_0_NAME'.$n.'='. urlencode($c[0]).
								'&L_PAYMENTREQUEST_0_DESC'.$n.'='.urlencode($c[1]).
								'&L_PAYMENTREQUEST_0_NUMBER'.$n.'='.urlencode($c[2]).
								'&L_PAYMENTREQUEST_0_AMT'.$n.'='.urlencode($c[3]).
								'&L_PAYMENTREQUEST_0_QTY'.$n.'='.urlencode($c[4]);												
			$n++;
		}
		
		$payment_request .= '&PAYMENTREQUEST_0_ITEMAMT='.urlencode(number_format($_SESSION['cart_item_total_amt'],2));
		return $payment_request;
	}
					
			
	/* An express checkout transaction starts with a token, that
	   identifies to PayPal your transaction
	   In this example, when the script sees a token, the script
	   knows that the buyer has already authorized payment through
	   paypal.  If no token was found, the action is to send the buyer
	   to PayPal to first authorize payment
	   */

	/*   
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
	' From:		Checkout from Shopping Cart
	' Inputs:  
	'		paymentAmount:  	Total value of the shopping cart
	'		currencyCodeType: 	Currency code value the PayPal API
	'		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
	'		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
	'		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
	'       padata:				cart items details
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	function CallShortcutExpressCheckout( $paymentAmount, $padata) 
	{
		global $PayPalCurrencyCode, $paymentType, $PayPalReturnURL, $PayPalCancelURL, $tax_amt;
		
		//------------------------------------------------------------------------------------------------------------------------------------
		// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
		
		$nvpstr="&PAYMENTREQUEST_0_AMT=". urlencode(number_format($paymentAmount,2));
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . urlencode("$paymentType");
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . urlencode("$PayPalCurrencyCode");		
		$nvpstr = $nvpstr . "&RETURNURL=" . urlencode("$PayPalReturnURL");
		$nvpstr = $nvpstr . "&CANCELURL=" . urlencode("$PayPalCancelURL");

		if($tax_amt > 0)
			$nvpstr = $nvpstr . '&PAYMENTREQUEST_0_TAXAMT='.urlencode($tax_amt);
					
		$nvpstr = $nvpstr . '&ALLOWNOTE=1';
		$nvpstr = $nvpstr . '&REQCONFIRMSHIPPING=1'; // To require that the shipping address be a PayPal confirmed address
		$nvpstr = $nvpstr . $padata;
				
		//echo $nvpstr;
		//exit();
		
		//'--------------------------------------------------------------------------------------------------------------- 
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.  
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
	    $resArray=hash_call("SetExpressCheckout", $nvpstr);
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{
			$token = urldecode($resArray["TOKEN"]);
			$_SESSION['TOKEN']=$token;
		}
		   
	    return $resArray;
	}



	/*
	'-------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
	' After Come back from PayPal
	'
	' Inputs:  
	'		None
	' Returns: 
	'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
	'-------------------------------------------------------------------------------------------
	*/
	function GetShippingDetails( $token )
	{
		//'--------------------------------------------------------------
		//' At this point, the buyer has completed authorizing the payment
		//' at PayPal.  The function will call PayPal to obtain the details
		//' of the authorization, incuding any shipping information of the
		//' buyer.  Remember, the authorization is not a completed transaction
		//' at this state - the buyer still needs an additional step to finalize
		//' the transaction
		//'--------------------------------------------------------------
	   
	    //'---------------------------------------------------------------------------
		//' Build a second API request to PayPal, using the token as the
		//'  ID to get the details on the payment authorization
		//'---------------------------------------------------------------------------
	    $nvpstr="&TOKEN=" . $token;

		//'---------------------------------------------------------------------------
		//' Make the API call and store the results in an array.  
		//'	If the call was a success, show the authorization details, and provide
		//' 	an action to complete the payment.  
		//'	If failed, show the error
		//'---------------------------------------------------------------------------
	    $resArray=hash_call("GetExpressCheckoutDetails",$nvpstr);
	    $ack = strtoupper($resArray["ACK"]);
		if($ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{	
			$_SESSION['payer_id'] =	$resArray['PAYERID'];
		} 
		return $resArray;
	}
		
	

	/*
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the DoExpressCheckoutPayment API Call.
	'
	' Inputs:  
	'		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
	' Returns: 
	'		The NVP Collection object of the DoExpressCheckoutPayment Call Response.
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	function ConfirmPayment( $FinalPaymentAmt )
	{
		global $PayPalCurrencyCode, $paymentType;
		
		/* 	Gather the information to make the final call tofinalize the PayPal payment. 
			The variable nvpstr holds the name value pairs		  
		*/
		

		//Format the other parameters that were stored in the session from the previous calls			
		$token 				= urlencode($_SESSION['TOKEN']);
		$payerID 			= urlencode($_SESSION['payer_id']);		
		$paymentType 		= urlencode($paymentType);
		$currencyCodeType 	= urlencode($currencyCodeType);
		$serverName 		= urlencode($_SERVER['SERVER_NAME']);		
		
		$nvpstr  =  '&TOKEN=' . $token . 
					'&PAYERID=' . $payerID . 
					'&PAYMENTREQUEST_0_PAYMENTACTION=' . $paymentType . 
					'&PAYMENTREQUEST_0_AMT=' . $FinalPaymentAmt;
		$nvpstr .=  '&PAYMENTREQUEST_0_CURRENCYCODE=' . $PayPalCurrencyCode . 
					'&IPADDRESS=' . $serverName; 


		 /* Make the call to PayPal to finalize payment
		    If an error occured, show the resulting errors
		*/
		$resArray=hash_call("DoExpressCheckoutPayment",$nvpstr);

		/* Display the API response back to the browser.
		   If the response from PayPal was a success, display the response parameters'
		   If the response was an error, display the errors received using APIError.php.
		   */
		$ack = strtoupper($resArray["ACK"]);

		return $resArray;
	}
		

	/**
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	  * hash_call: Function to perform the API call to PayPal using API signature
	  * @methodName is name of API  method.
	  * @nvpStr is nvp string.
	  * returns an associtive array containing the response from the server.
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	*/
	function hash_call($methodName,$nvpStr)
	{
		//declaring of global variables
		global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
		global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
		global $gv_ApiErrorURL;
		global $sBNCode;

		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
	    //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
	   //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
		if($USE_PROXY)
			curl_setopt ($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT); 

		//NVPRequest for submitting to server
		$nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode);


		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

		//getting response from server
		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray=deformatNVP($response);
		$nvpReqArray=deformatNVP($nvpreq);
		$_SESSION['nvpReqArray']=$nvpReqArray;

		if (curl_errno($ch)) 
		{
			// moving to display page to display curl errors
			  $_SESSION['curl_error_no']=curl_errno($ch) ;
			  $_SESSION['curl_error_msg']=curl_error($ch);

			  //Execute the Error handling module to display errors. 
		} 
		else 
		{
			 //closing the curl
		  	curl_close($ch);
		}

		return $nvpResArray;
	}

	/*'----------------------------------------------------------------------------------
	 Purpose: Redirects to PayPal.com site.
	 Inputs:  NVP string.
	 Returns: 
	----------------------------------------------------------------------------------
	*/
	function RedirectToPayPal ( $token )
	{
		global $PAYPAL_URL;
			
		// Redirect to paypal.com here
		$payPalURL = $PAYPAL_URL . $token;
		
		if($_SESSION['useraction'])
			$payPalURL = $PAYPAL_URL . $token.'&useraction=commit';	
		//echo $payPalURL;
				
		header("Location: ".$payPalURL);
		exit;
	}

	
	/*'----------------------------------------------------------------------------------
	 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
	  * It is usefull to search for a particular key and displaying arrays.
	  * @nvpstr is NVPString.
	  * @nvpArray is Associative Array.
	   ----------------------------------------------------------------------------------
	  */
	function deformatNVP($nvpstr)
	{
		$intial=0;
	 	$nvpArray = array();

		while(strlen($nvpstr))
		{
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
	     }
		return $nvpArray;
	}
	
	
	//-------------------------------------------	
	// reformat GetEC result data
	//-------------------------------------------	
	function reformat_arr($data_arr) {
	
		$result ='';
		foreach ($data_arr as $key => $value) {
			$result .='<br>'.$key.'='.$value;
		}
		return $result;		
	}
	
	

	//-------------------------------------------
	// Display error from EC return result
	//-------------------------------------------
	function DisplayErrorMessage($ECAction,$resArray,$padata) {
	
			$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
			$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
			$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
			$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
	
			echo "<b>$ECAction API</b> call failed. <br>$padata";
			echo "<br>Detailed Error Message: " . $ErrorLongMsg;
			echo "<br>Short Error Message: " . $ErrorShortMsg;
			echo "<br>Error Code: " . $ErrorCode;
			echo "<br>Error Severity Code: " . $ErrorSeverityCode;	
	
	}

	

	
	
	
	//------------------------------------------------
	// Save Checkout information (SetExressCheckout)
	// Recommend to save it to track the drop off rate
	//------------------------------------------------	
	function SaveCheckoutInfo($padata){
		
		$res_arr = explode("&",$padata);
		$r = count($res_arr);
		for ($i=1;$i<$r;$i++) {
			$resdata = explode("=",$res_arr[$i]);
			$resArray[$resdata[0]] = $resdata[1];
		}
		//print_r($resArray);


		// Setup your DB connection to save it
			
	}
	
	
	
	//-----------------------------------------------------------
	// Save Shipping Addr information (GetExpressCheckoutDetails)
	//-----------------------------------------------------------	
	function SaveShipping_addr($resArray){

		/*
		' The information that is returned by the GetExpressCheckoutDetails call should be integrated by the partner 
		' into his Order Review page		
		*/
		$email 				= $resArray["EMAIL"]; // ' Email address of payer.
		$payerId 			= $resArray["PAYERID"]; // ' Unique PayPal customer account identification number.
		$payerStatus		= $resArray["PAYERSTATUS"]; // ' Status of payer. Character length and limitations: 10 single-byte alphabetic characters.
		$salutation			= $resArray["SALUTATION"]; // ' Payer's salutation.
		$firstName			= $resArray["FIRSTNAME"]; // ' Payer's first name.
		$middleName			= $resArray["MIDDLENAME"]; // ' Payer's middle name.
		$lastName			= $resArray["LASTNAME"]; // ' Payer's last name.
		$suffix				= $resArray["SUFFIX"]; // ' Payer's suffix.
		$cntryCode			= $resArray["COUNTRYCODE"]; // ' Payer's country of residence in the form of ISO standard 3166 two-character country codes.
		$business			= $resArray["BUSINESS"]; // ' Payer's business name.
		$shipToName			= $resArray["PAYMENTREQUEST_0_SHIPTONAME"]; // ' Person's name associated with this address.
		$shipToStreet		= $resArray["PAYMENTREQUEST_0_SHIPTOSTREET"]; // ' First street address.
		$shipToStreet2		= $resArray["PAYMENTREQUEST_0_SHIPTOSTREET2"]; // ' Second street address.
		$shipToCity			= $resArray["PAYMENTREQUEST_0_SHIPTOCITY"]; // ' Name of city.
		$shipToState		= $resArray["PAYMENTREQUEST_0_SHIPTOSTATE"]; // ' State or province
		$shipToCntryCode	= $resArray["PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE"]; // ' Country code 
		$shipToCntryName	= $resArray["PAYMENTREQUEST_0_SHIPTOCOUNTRYNAME"]; // ' Country Name
		$shipToZip			= $resArray["PAYMENTREQUEST_0_SHIPTOZIP"]; // ' U.S. Zip code or other country-specific postal code.
		$addressStatus 		= $resArray["ADDRESSSTATUS"]; // ' Status of street address on file with PayPal   
		$invoiceNumber		= $resArray["INVNUM"]; // ' Your own invoice or tracking number, as set by you in the element of the same name in SetExpressCheckout request .
		$phonNumber			= $resArray["PHONENUM"]; // ' Payer's contact telephone number. Note:  PayPal returns a contact telephone number only if your Merchant account profile settings require that the buyer enter one. 
		


		// setup your DB connection to save it


	}	
	
	
	//-----------------------------------------------------------
	// Save Transaction information (DoExpressCheckoutPayment)
	//-----------------------------------------------------------		
	function SaveTransaction($resArray){
	
		/*
		'********************************************************************************************************************
		'
		' THE PARTNER SHOULD SAVE THE KEY TRANSACTION RELATED INFORMATION LIKE 
		'                    transactionId & orderTime 
		'  IN THEIR OWN  DATABASE
		' AND THE REST OF THE INFORMATION CAN BE USED TO UNDERSTAND THE STATUS OF THE PAYMENT 
		'
		'********************************************************************************************************************
		*/

		$transactionId		= $resArray["PAYMENTINFO_0_TRANSACTIONID"]; // ' Unique transaction ID of the payment. Note:  If the PaymentAction of the request was Authorization or Order, this value is your AuthorizationID for use with the Authorization & Capture APIs. 
		$transactionType 	= $resArray["PAYMENTINFO_0_TRANSACTIONTYPE"]; //' The type of transaction Possible values: l  cart l  express-checkout 
		$paymentType		= $resArray["PAYMENTINFO_0_PAYMENTTYPE"];  //' Indicates whether the payment is instant or delayed. Possible values: l  none l  echeck l  instant 
		$orderTime 			= $resArray["PAYMENTINFO_0_ORDERTIME"];  //' Time/date stamp of payment
		$amt				= $resArray["PAYMENTINFO_0_AMT"];  //' The final amount charged, including any shipping and taxes from your Merchant Profile.
		$currencyCode		= $resArray["PAYMENTINFO_0_CURRENCYCODE"];  //' A three-character currency code for one of the currencies listed in PayPay-Supported Transactional Currencies. Default: USD. 
		$feeAmt				= $resArray["PAYMENTINFO_0_FEEAMT"];  //' PayPal fee amount charged for the transaction
		$settleAmt			= $resArray["PAYMENTINFO_0_SETTLEAMT"];  //' Amount deposited in your PayPal account after a currency conversion.
		$taxAmt				= $resArray["PAYMENTINFO_0_TAXAMT"];  //' Tax charged on the transaction.
		$exchangeRate		= $resArray["PAYMENTINFO_0_EXCHANGERATE"];  //' Exchange rate if a currency conversion occurred. Relevant only if your are billing in their non-primary currency. If the customer chooses to pay with a currency other than the non-primary currency, the conversion occurs in the customer's account.
		
		/*
		' Status of the payment: 
				'Completed: The payment has been completed, and the funds have been added successfully to your account balance.
				'Pending: The payment is pending. See the PendingReason element for more information. 
		*/
		
		$paymentStatus	= $resArray["PAYMENTINFO_0_PAYMENTSTATUS"]; 

		/*
		'The reason the payment is pending:
		'  none: No pending reason 
		'  address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile. 
		'  echeck: The payment is pending because it was made by an eCheck that has not yet cleared. 
		'  intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview. 		
		'  multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment. 
		'  verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment. 
		'  other: The payment is pending for a reason other than those listed above. For more information, contact PayPal customer service. 
		*/
		
		$pendingReason	= $resArray["PAYMENTINFO_0_PENDINGREASON"];  

		/*
		'The reason for a reversal if TransactionType is reversal:
		'  none: No reason code 
		'  chargeback: A reversal has occurred on this transaction due to a chargeback by your customer. 
		'  guarantee: A reversal has occurred on this transaction due to your customer triggering a money-back guarantee. 
		'  buyer-complaint: A reversal has occurred on this transaction due to a complaint about the transaction from your customer. 
		'  refund: A reversal has occurred on this transaction because you have given the customer a refund. 
		'  other: A reversal has occurred on this transaction due to a reason not listed above. 
		*/
		
		$reasonCode		= $resArray["PAYMENTINFO_0_REASONCODE"];  
		
		
		// setup your DB connection to save it
		
			
	}
	
	
?>
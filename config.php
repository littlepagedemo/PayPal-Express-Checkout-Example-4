<?php

/********************************************	 
	Defines all the global variables 
********************************************/
	
	
$SandboxFlag = true;	// sandbox live


//'------------------------------------
//' PayPal API Credentials
//'------------------------------------
$API_UserName=""; //PayPal API Username
$API_Password=""; //Paypal API password
$API_Signature=""; //Paypal API Signature


//'------------------------------------
//' BN Code 	is only applicable for partners
$sBNCode = "PP-ECxxxxx";
	
	
//'------------------------------------	
//' API version
$version = urlencode('84.0'); // 76.0


//'------------------------------------
//' The currencyCodeType and paymentType 
//' are set to the selections made on the Integration Assistant 
//'------------------------------------
$PayPalCurrencyCode = "SGD";		// Paypal Currency Code
$paymentType = "Sale";				// or 'Sale' or 'Order' or 'Authorization'

	
$domain = 'http://'.$_SERVER['SERVER_NAME'];
$domainhttps = 'https://'.$_SERVER['SERVER_NAME'];
//'------------------------------------
//' The returnURL is the location where buyers return to when a
//' payment has been succesfully authorized.
//'
//' This is set to the value entered on the Integration Assistant 
//'------------------------------------		
$PayPalReturnURL		= $domain.'/paypal/ecsection4/order_shipping_success.php'; //Return URL after user sign in from Paypal
	
//'------------------------------------
//' The cancelURL is the location buyers are sent to when they hit the
//' cancel button during authorization of payment during the PayPal flow
//'
//' This is set to the value entered on the Integration Assistant 
//'------------------------------------	
$PayPalCancelURL 		= $domain.'/paypal/ecsection4/cart.php'; // Cancel URL if user clicks cancel



$CallBackURL = $domainhttps.'/paypal/ecsection4/checkout_sendshipping_options.php?action=callbackSet';


//'--------------------------------------
//' Shipping Methods
//' To be sent to SetExpressCheckout  
//'---------------------------------------
$shipping_method0="Method A";
$shipping_label0="Label A";
$shipping_amount0="21.00";
$shipping_isdef0="false";
$shipping_tax0="2.00";

$shipping_method1="Method B";
$shipping_label1="Label B";
$shipping_amount1="10.00";
$shipping_isdef1="true";
$shipping_tax1="1.00";

$shipping_method2="Method C";
$shipping_label2="Label C";
$shipping_amount2="30.00";
$shipping_isdef2="false";
$shipping_tax2="3.00";



?>
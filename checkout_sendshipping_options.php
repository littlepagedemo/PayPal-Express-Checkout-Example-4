<?php

session_start();
include_once("config.php");
include_once("paypal_ecfunctions.php");



/* ----------------------------------------------------
//  PayPal Express Checkout Call - SetExpressCheckout()
//  and redirect to paypal side
//
//  Checkout from Shopping Cart 
//  Callback URL - to update the shipping
//-----------------------------------------------------
*/


$_SESSION['useraction'] = 'commit';	


// Callback URL from PayPal
// Paypal will post user's shipping address
if ($_REQUEST['action'] == "callbackSet")  
{
    
	// get shipping address from paypal callbackrequest
	$SHIPTOSTREET = $_POST['SHIPTOSTREET'];
	$SHIPTOSTREET2 = $_POST['SHIPTOSTREET2'];
	$SHIPTOCITY = $_POST['SHIPTOCITY'];
	$SHIPTOSTATE = $_POST['SHIPTOSTATE'];
	$SHIPTOZIP = $_POST['SHIPTOZIP'];
	$SHIPTOCOUNTRY = $_POST['SHIPTOCOUNTRY'];		
    
    
    // apply your shipping calculation here
	if ($SHIPTOCOUNTRY == 'US') {
		$shipping_amount0='22.00';
		$shipping_amount1='12.00';		
		$shipping_amount2='32.00';	
	}
	                        
	$shipping_option .= '&L_SHIPPINGOPTIONNAME0='.urlencode($shipping_method0).
							'&L_SHIPINGPOPTIONLABEL0='.urlencode($shipping_label0).
							'&L_SHIPPINGOPTIONAMOUNT0='.urlencode($shipping_amount0).
							'&L_TAXAMT0='.urlencode($shipping_tax0).
							'&L_SHIPPINGOPTIONISDEFAULT0='.urlencode($shipping_isdef0).
							'&L_SHIPPINGOPTIONNAME1='.urlencode($shipping_method1).
							'&L_SHIPINGPOPTIONLABEL1='.urlencode($shipping_label1).
							'&L_SHIPPINGOPTIONAMOUNT1='.urlencode($shipping_amount1).
							'&L_TAXAMT1='.urlencode($shipping_tax1).
							'&L_SHIPPINGOPTIONISDEFAULT1='.urlencode($shipping_isdef1).
							'&L_SHIPPINGOPTIONNAME2='.urlencode($shipping_method2).
							'&L_SHIPINGPOPTIONLABEL2='.urlencode($shipping_label2).
							'&L_SHIPPINGOPTIONAMOUNT2='.urlencode($shipping_amount2).
							'&L_TAXAMT2='.urlencode($shipping_tax2).
							'&L_SHIPPINGOPTIONISDEFAULT2='.urlencode($shipping_isdef2);                        

	$currencyCodeType = '&CURRENCYCODE=' . $PayPalCurrencyCode;
	
	$nvpstr = 'METHOD=CallbackResponse&OFFERINSURANCEOPTION=false';
	$nvpstr .= $currencyCodeType.$shipping_option;
	echo $nvpstr;


}else {

    
	//-------------------------------------------
	// Prepare url for items details information
	//-------------------------------------------
	if ($_SESSION['cart_item_arr']) 
	{
		
		
		//-------------------------------------------------
		// Data to be sent to paypal - in SetExpressCheckout
		//--------------------------------------------------
		$shipping_option .= '&L_SHIPPINGOPTIONNAME0='.urlencode($shipping_method0).
							'&L_SHIPINGPOPTIONLABEL0='.urlencode($shipping_label0).
							'&L_SHIPPINGOPTIONAMOUNT0='.urlencode($shipping_amount0).
							'&L_TAXAMT0='.urlencode($shipping_tax0).
							'&L_SHIPPINGOPTIONISDEFAULT0='.urlencode($shipping_isdef0).
							'&L_SHIPPINGOPTIONNAME1='.urlencode($shipping_method1).
							'&L_SHIPINGPOPTIONLABEL1='.urlencode($shipping_label1).
							'&L_SHIPPINGOPTIONAMOUNT1='.urlencode($shipping_amount1).
							'&L_TAXAMT1='.urlencode($shipping_tax1).
							'&L_SHIPPINGOPTIONISDEFAULT1='.urlencode($shipping_isdef1).							
							'&L_SHIPPINGOPTIONNAME2='.urlencode($shipping_method2).
							'&L_SHIPINGPOPTIONLABEL2='.urlencode($shipping_label2).
							'&L_SHIPPINGOPTIONAMOUNT2='.urlencode($shipping_amount2).
							'&L_TAXAMT2='.urlencode($shipping_tax2).
							'&L_SHIPPINGOPTIONISDEFAULT2='.urlencode($shipping_isdef2);
							

		$insurance_data = '&OFFERINSURANCEOPTION=false';
										 
		// Set cheapest shipping amount set as default
		$shipping_data = '&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($shipping_amount1);

		// Cart items
		$payment_request = get_payment_request();
		
		$paymentAmount = $_SESSION["cart_item_total_amt"] + $shipping_amount1 + $tax_amt;	// update Total payment
		
		$max_amount = '&MAXAMT=100'; 				// required for Instant Update API
		$callbackversion = '&CALLBACKVERSION=61'; 	// required for Instant Update API

		// call back url		
		$callbackurl = '&CALLBACK='.urlencode($CallBackURL);
		$calltimeout = '&CALLBACKTIMEOUT=5';

	
		
		$padata = 	$insurance_data.	
					$shipping_option.
					$callbackurl.
					$calltimeout.
					$callbackversion.
					$max_amount.
					$shipping_data.										
				 	$payment_request;				
		//echo '<br>'.$padata;							
					
		//'--------------------------------------------------------------------		
		//'	Tips:  It is recommend that to save this info for the drop off rate 
		///	Function to save data into DB
		//'--------------------------------------------------------------------
		SaveCheckoutInfo($padata);		
		
									
		//'-------------------------------------------------------------
		//' Calls the SetExpressCheckout API call
		//' Prepares the parameters for the SetExpressCheckout API Call
		//'-------------------------------------------------------------		
		$resArray = CallShortcutExpressCheckout ($paymentAmount, $padata);
		
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{
			//print_r($resArray);
			RedirectToPayPal ( $resArray["TOKEN"] );	// redirect to PayPal side to login
		} 
		else  
		{
			//Display a user friendly Error on the page using any of the following error information returned by PayPal
			DisplayErrorMessage('SetExpressCheckout', $resArray, $padata.' payAmount'.$paymentAmount);
			
		}
			
		
	
	}else {
	
		header("Location: cart.php"); // back to cart if don't have cart items 
		exit;
	
	}
	
} // action != callbackset

			
?>

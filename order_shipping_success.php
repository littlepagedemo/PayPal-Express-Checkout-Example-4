<?php

session_start();
include_once("config.php");
include_once("paypal_ecfunctions.php");


/* ==================================================================
'  Order Review Page
'
'  User come back from Paypal site after login - return URL
'  PayPal Express Checkout Call - GetExpressCheckoutDetails()
   ===================================================================
*/

// Check to see if the Request object contains a variable named 'token'	
$token = "";
if (isset($_REQUEST['token']))
{
	$token = $_REQUEST['token'];
	$_SESSION['token'] = $token;	// save in session for DoExpressCheckoutPayment()
}



// If the Request object contains the variable 'token' then it means that the user is coming from PayPal site.	
if ( $token != "" )
{

	/*
	'----------------------------------------------------------------------------
	' Calls the GetExpressCheckoutDetails API call 
	' - must do if you have callback to get the selected shipping option
	'
	' The GetShippingDetails function is defined in paypal_ecfunctions.php
	' included at the top of this file.
	'-----------------------------------------------------------------------------
	*/	

	$resArray = GetShippingDetails( $token );
	$ack = strtoupper($resArray["ACK"]);
	if( $ack == "SUCCESS" || $ack == "SUCESSWITHWARNING") 
	{
			
		$resGetArray = $resArray;
		
		$shipping_opt 		= $resArray['SHIPPINGOPTIONNAME'];
		$shipping_opt_amt	= $resArray['SHIPPINGOPTIONAMOUNT'];
		$shipping_amt		= $resArray['PAYMENTREQUEST_0_SHIPPINGAMT'];
		
		
		//---------------------------------------
		// Save user's shipping address into DB
		//--------------------------------------- 
		SaveShipping_addr($resGetArray);
		
		
		/*
		'-------------------------------------------------------------------------
		' The paymentAmount is the total value of the shopping cart, that was set 
		' earlier in a session variable by the shopping cart page
		'-------------------------------------------------------------------------
		*/
	
		$finalPaymentAmount =  $_SESSION["cart_item_total_amt"] + $shipping_amt + $tax_amt; 
		
		/*
		'-------------------------------------------------
		' Calls the DoExpressCheckoutPayment API call
		'
		' The ConfirmPayment function is defined in the file paypal_ecfunctions.php,
		' that is included at the top of this file.
		'-------------------------------------------------
		*/

		$resArray = ConfirmPayment ( $finalPaymentAmount );
		$ack = strtoupper($resArray["ACK"]);
		if( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" )
		{		
			//Getting transaction ID from API responce. 
            $TransactionID = urldecode($resArray["PAYMENTINFO_0_TRANSACTIONID"]);
            
			//---------------------------------------
			// Save Transaction Information into DB
			//--------------------------------------- 
			SaveTransaction($resArray);
				
		
			// Clear Session
			$_SESSION = array();
		
		
			include("header.php");
?>
			<div id="content-container">
	
			<div id="content">		
			<h2>Success</h2>
			<BR>Payment Received! Your product will be sent to you very soon!
			<br><br> Transaction ID: <?php echo $TransactionID; ?>
			<br><br>	
			<h3>
				DoEC Return results:
			</h3>						
			<?php 				
				$resData = reformat_arr($resArray); 
				echo '<p style="font-size:10px">'.$resData.'</p>';			
			?>	
			
			<br><br>User has select shipping option as below:
			<Br><?php echo $shipping_opt . ' ($' . $shipping_opt_amt . ')';?>				

<?php			
		}
		else  
		{
			//Display a user friendly Error on the page using any of the following error information returned by PayPal
			DisplayErrorMessage('DoExpressCheckoutDetails', $resArray, $token);
		}	

		
	} 
	else  
	{
		//Display a user friendly Error on the page using any of the following error information returned by PayPal
		DisplayErrorMessage('GetExpressCheckoutDetails',$resArray, $token);
	}


?>	
	
		</div>
		<!-- content -->
		
		
		<div id="aside">
			<h3>
				GetEC Return results:
			</h3>						
			<?php 				
				$resGetData = reformat_arr($resGetArray); 
				echo '<p style="font-size:10px">'.$resGetData.'</p>';			
			?>						
		</div>

<?php

		include("footer.php");

 } 

 // no token
 else {
	
		header("Location: index.php"); // back to cart if don't have cart items 
		exit;

 }
	




?>

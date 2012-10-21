<?php

session_start();
include_once("config.php");
include_once("paypal_ecfunctions.php");


//Post Data received from product list page.
if($_POST) 
{
	
	//Mainly we need 5 variables from an item, Item Name, Item Desc, Item Price, Item Number and Item Quantity.
	$ItemName = $_POST["itemname"]; //Item Name
	$ItemDesc = $_POST["itemdesc"]; //Item Desc
	$ItemPrice = $_POST["itemprice"]; //Item Price
	$ItemNumber = $_POST["itemnumber"]; //Item Number
	$ItemQty = $_POST["itemQty"]; // Item Quantity
	$ItemTotalPrice = number_format(($ItemPrice*$ItemQty),2); //(Item Price x Quantity = Total) Get total amount of product; 
	
	// Keep in array
	$cart_item = array("$ItemName","$ItemDesc","$ItemNumber","$ItemPrice","$ItemQty","$ItemTotalPrice"); 
	
	// update cart
	cart_process($cart_item);
		
} 
//--------------------------------------------
// Display cart items
// 1. From Cart menu
// 2. From Paypal site - Click on Cancel url
//--------------------------------------------
else {

	// (2) 	If the Request object contains the variable 'token',
	// 		then it means that the user is coming from PayPal site (Cancel URL).
	//if (isset($_REQUEST['token']))
		//$cancel_token = $_REQUEST['token'];


	// Check have existing product
	if ($_SESSION['cart_item_arr']) 
	{
		$cart_item_arr = $_SESSION['cart_item_arr'];	
		$cart_no = count($cart_item_arr);
	}
	else { 
		$cart_item_arr[] = array();
		$cart_no=0;
	}
}


//====================================
// Cart items amount + Shipping + Tax
//------------------------------------
$paymentAmount = $_SESSION['cart_item_total_amt'] + $shipping_amt + $tax_amt;	
			
$_SESSION["Payment_Amount"] = $paymentAmount; // will be used at checkout.php (SET) & confirm_payment.php (DO)



include("header.php");

?>

	<div id="content-container">
	
		<div id="content">
			<h2>
				Shopping Cart
			</h2>
			<div class="carttitle">
				<div class="col1">Product</div>
				<div class="col2">Item Price <?php echo $PayPalCurrencyCode; ?> </div>
				<div class="col3">Item Qty</div>
				<div class="col4">Item Amt <?php echo $PayPalCurrencyCode; ?> </div>
			</div>	
<?php 

	//-----------------------
	// Display shopping cart
	//-----------------------
	if($_SESSION['cart_no']) // have cart
	{	
		foreach ($_SESSION['cart_item_arr'] as $c) 
		{
			//print_r($c);
?>					
			<div class="cartrow">
				<div class="col1"><?php echo $c[0];?> (<?php echo $c[2]; ?>)
					<br><?php echo $c[1]; ?>
				</div>
				<div class="col2"> $<?php echo $c[3]; ?></div>
				<div class="col3" style="text-align:center"><?php echo $c[4]; ?></div>
				<div class="col4">$<?php echo $c[5]; ?></div>
			</div>								
<?php
		} // foreach



		//--------------------------------
		// Shopping Cart Item Total Amount
?>			
				<div id="subtotalamt">
					<div class="colspan">&nbsp;</div>
					<div class="col3">Items Total:</div> 
					<div class="col4">$<?php echo number_format($_SESSION['cart_item_total_amt'],2);?></div>
				</div>
				
<?php 
		//---------------------------
		// Show Shipping Amount 
		//===========================
		if($shipping_amt) {		
?>				
				<div id="shippingamt">
					<div class="colspan">&nbsp;</div>
					<div class="col3">Shipping:</div> 
					<div class="col4">$<?php echo $shipping_amt; ?></div>
				</div>
<?php 
		} 
	
		//---------------------------
		// Show Tax Amount
		//===========================
		if($tax_amt) { 
?>				
				<div id="tax_amt">
					<div class="colspan">&nbsp;</div>
					<div class="col3">Tax:</div> 
					<div class="col4">$<?php echo $tax_amt; ?></div>
				</div>
<?php 	} ?>				
				<div id="totalamt">
					<div class="colspan">&nbsp;</div>
					<div class="col3">Total Amount:</div> 
					<div class="col4"><b>$<?php echo number_format($_SESSION["Payment_Amount"],2); ?></b></div>
				</div>
		
		
								
				<!-- First entry point - Shopping cart express checkout 4 -->
				<div id='paypalcheckoutbtn'>
					<br><br><form action='checkout_sendshipping_options.php' METHOD='POST'>
					<input type="hidden" name="useraction" value="commit">
					<input type='image' name='submit' src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' border='0' align='top' alt='Check out with PayPal'/>
					</form>
				</div>									
					
<?php 
	} 
	// no cart items
	else {
		echo '<div class="cartrow">Your cart is empty</div>';
	}
?>					
					
		</div>
		<!-- content -->
		
		<div id="aside">
			<h3>
				Checkout Methods
			</h3>
			<p>User will be directed to the PayPal site to select shipping method and make payment.
			<br>Note: update shipping methods at config.php 
			</p>
			
			<br> <a href="clearcart.php">Clear Cart</a>
		</div>

<?php

include("footer.php");

?>
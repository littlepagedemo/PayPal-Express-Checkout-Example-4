<?php

session_start();
include("header.php");
include("config.php");

?>
  	
	<div id="content-container">
	
		<div id="content">
			<h2>
				Product
			</h2>


			<div class="thumbnail">
				<img src="images/flower2.jpeg" alt="" width="120"><br>
				Flower A <?php echo $PayPalCurrencyCode; ?> $10.00<br>
				<form method="post" action="cart.php">
					<input type="hidden" name="itemname" value="Flower A" /> 
					<input type="hidden" name="itemdesc" value="Flower A Desc" /> 
					<input type="hidden" name="itemnumber" value="p1001" /> 
					<input type="hidden" name="itemprice" value="10.00" />
        			Quantity : 
        			<select name="itemQty">
        			<option value="1">1</option>
        			<option value="2">2</option>
        			<option value="3">3</option>
        			</select> 
        			<br><input class="dw_button" type="submit" name="submit" value="Add to Cart" />
    			</form>
			</div>
			<div class="thumbnail">
				<img src="images/flower2.jpeg" alt="" width="120"><br>
				Flower B <?php echo $PayPalCurrencyCode; ?> $15.00<br>
				<form method="post" action="cart.php">
					<input type="hidden" name="itemname" value="Flower B" /> 
					<input type="hidden" name="itemdesc" value="Flower B Desc" /> 
					<input type="hidden" name="itemnumber" value="p1002" /> 
					<input type="hidden" name="itemprice" value="15.00" />
        			Quantity : 
        			<select name="itemQty">
        			<option value="1">1</option>
        			<option value="2">2</option>
        			<option value="3">3</option>
        			</select> 
        			<br><input class="dw_button" type="submit" name="submit" value="Add to Cart" />
    			</form>
			</div>

		</div>
		
		<div id="aside">
			<h3>
				Express Checkout
			</h3>
			<p>Checkout with Paypal and pay at PayPal.
			<br>Note: Shipping methods can be selected at PayPal.
		</div>

<?php
	include("footer.php");
?>
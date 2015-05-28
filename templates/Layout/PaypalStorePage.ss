<div id="page_type" class="internal typography">
    <h1>$Title</h1>
    $Content
	<% if PaypalStoreItems %>
		<ul id="paypal_items">
		<% loop PaypalStoreItems %>
			<li class="paypal_item">
				<% if GetItemImage %>
					<div class="paypal_image_holder">$GetItemImage</div>
				<% end_if %>
				<div class="paypal_item_details">
					<% if Name %>
						<h2>$Name</h2>					
					<% end_if %>
					<div class="paypal_item_price" id="showprice_form$ID">$ $Price</div>
					$Description
					
					<form target="_self" action="$PaypalStorePage.PaypalUrl" method="post" style="margin-top: 10px;" id="form$ID">
						<input type="hidden" name="business" value="$PaypalStorePage.PaypalUser" />
						<input type="hidden" name="cmd" value="_xclick" /> 
						<input type="hidden" name="item_name" id="name_form$ID" value="$Name" />
						<input type="hidden" name="item_number" id="num_form$ID" value="$ItemID" />
						<input type="hidden" name="amount" id="price_form$ID" value="$Price" />     
						<input type="hidden" name="return" value="{$PaypalStorePage.AbsoluteLink}thanks">
						<input type="hidden" name="cancel_return" value="$PaypalStorePage.AbsoluteLink">
						<%--<input type="hidden" name="notify_url" value="$PaypalStore.IPNLink">--%>
						<input type="hidden" name="currency_code" value="USD" />
						<input type="hidden" name="lc" value="US" />
						
						<% if PaypalItemOptions %>
							<% loop PaypalItemOptions %>
								<div><input type="radio" name="$OptionName" id="$OptionID" /><label for="$OptionID">$Name</label></div>
							<% end_loop %>
							<br />
						<% end_if %>
						
						<label for="quantity">Quantity:</label>
						<input type="text" class="paypal_qty" name="quantity" value="1" size="3" /><br /><br /> 
								
						<input type="submit" value="Continue to PayPal" class="paypal_submit" />
					</form>
				</div>
			</li>
		<% end_loop %>
		</ul><!--paypal_items-->
	<% end_if %>
</div>

  
Oh hey.

This PaypalStorePage version does not support product attributes (ex. sizes, colors, etc.).
You'd have to set these up as different products.

Also, we don't typically use the IPN stuff (paypal.notify.php) anymore.  We've been just letting
paypal store its own info, and telling clients to look there for any data regarding payments.
BUT, the IPN stuff is there in case you need it.
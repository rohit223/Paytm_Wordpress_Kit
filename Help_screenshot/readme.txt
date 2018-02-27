Steps to setup PayTM donation plugin

1. Upload the plugin folder "paytm-donation" in /wp-content/plugins/ directory using FTP (Filezila / Live FTP).
2. Activate the plugin from wordpress admin panel. (refer screen shot activate.png")
3. Select PayTM Settings from menu list and insert PayTM configuration values provided by PayTM team.(refer screen shot settings.png")
4. Create a new post or page with [paytmcheckout]. (refer screen shot add post.png)
5. To check all the donations select PayTM Donation from menu list. (refer screen shot donation list.png)

#Paytm PG URL Details
	Staging	
		Transaction URL             => https://securegw-stage.paytm.in/theia/processTransaction
		Transaction Status Url      => https://securegw-stage.paytm.in/merchant-status/getTxnStatus

	Production
		Transaction URL             => https://securegw.paytm.in/theia/processTransaction
		Transaction Status Url      => https://securegw.paytm.in/merchant-status/getTxnStatus
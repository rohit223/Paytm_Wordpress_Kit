# WordPress Donation

  1. Upload the plugin folder "paytm-donation" into /wp-content/plugins/directory using FTP (Filezilla/Live FTP).
  2. Activate the plugin from WordPress admin panel.(refer the screenshot: "Help_screenshot/activate.png").
  3. Select Paytm Settings from menu list and insert Paytm configuration values provided by Paytm team.(refer the screenshot: "Help_screenshot/settings.png").
  4. Create a new post or page with [paytmcheckout].(refer the screenshot: "Help_screenshot/add_post.png").
  5. To check all the donations, select Paytm Donation from menu list.(refer the screenshot: "Help_screenshot/donation_list.png").

      * Merchant ID               - Staging/Production MID provided by Paytm
      * Merchant Key              - Staging/Production Key provided by Paytm
      * Website                   - Provided by Paytm
      * Industry type             - Provided by Paytm
      * Channel ID                - WEB/WAP
      * Transaction URL           
        * Staging     - https://securegw-stage.paytm.in/theia/processTransaction
        * Production  - https://securegw.paytm.in/theia/processTransaction
      * Transaction Status URL    
        * Staging     - https://securegw-stage.paytm.in/merchant-status/getTxnStatus
        * Production  - https://securegw.paytm.in/merchant-status/getTxnStatus
      * Default Amount            - 100
      * Default Button/Link Test  - Paytm
      * Set callback URL          - Yes (only for version 3 To 4.x)

  6. Your Wordpress Donation plug-in is now installed. You can accept payment through Paytm.

See Video : https://www.youtube.com/watch?v=topfwOfUlOE

# In case of any query, please contact to Paytm.

<?php
/**
 * Plugin Name: Paytm Donate with Check Status
 * Plugin URI: https://github.com/Paytm-Payments/
 * Description: This plugin allows site owners to have a donate buttons for visitors to donate via Paytm in either set or custom amounts
 * Version: 0.3
 * Author: Paytm
 * Author URI: https://github.com/Paytm-Payments/
 * Text Domain: Paytm Payments
 */

//ini_set('display_errors','On');
register_activation_hook(__FILE__, 'paytm_activation');
register_deactivation_hook(__FILE__, 'paytm_deactivation');

// do not conflict with WooCommerce Paytm Plugin Callback
if(!isset($_GET["wc-api"])){
	add_action('init', 'paytm_donation_response');
}

add_shortcode( 'paytmcheckout', 'paytm_donation_handler' );
// add_action('admin_post_nopriv_paytm_donation_request','paytm_donation_handler');
// add_action('admin_post_paytm_donation_request','paytm_donation_handler');


if(isset($_GET['donation_msg'])){
	if($_GET['donation_msg']!=''){
		 add_action('the_content', 'paytmDonationShowMessage');
	}
}

function paytmDonationShowMessage($content){
	return '<div class="box">'.htmlentities(urldecode($_GET['donation_msg'])).'</div>'.$content;
}
		
function paytm_activation() {
	global $wpdb, $wp_rewrite;
	$settings = paytm_settings_list();
	foreach ($settings as $setting) {
		add_option($setting['name'], $setting['value']);
	}
	add_option( 'paytm_donation_details_url', '', '', 'yes' );
	$post_date = date( "Y-m-d H:i:s" );
	$post_date_gmt = gmdate( "Y-m-d H:i:s" );

	$ebs_pages = array(
		'paytm-page' => array(
			'name' => 'Paytm Transaction Details page',
			'title' => 'Paytm Transaction Details page',
			'tag' => '[paytm_donation_details]',
			'option' => 'paytm_donation_details_url'
		),
	);
	
	$newpages = false;
	
	$paytm_page_id = $wpdb->get_var("SELECT id FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%" . $paytm_pages['paytm-page']['tag'] . "%'	AND `post_type` != 'revision'");
	if(empty($paytm_page_id)){
		$paytm_page_id = wp_insert_post( array(
			'post_title'	=>	$paytm_pages['paytm-page']['title'],
			'post_type'		=>	'page',
			'post_name'		=>	$paytm_pages['paytm-page']['name'],
			'comment_status'=> 'closed',
			'ping_status'	=>	'closed',
			'post_content' =>	$paytm_pages['paytm-page']['tag'],
			'post_status'	=>	'publish',
			'post_author'	=>	1,
			'menu_order'	=>	0
		));
		$newpages = true;
	}

	update_option( $paytm_pages['paytm-page']['option'], _get_page_link($paytm_page_id) );
	unset($paytm_pages['paytm-page']);
	
	$table_name = $wpdb->prefix . "paytm_donation";
	 $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) CHARACTER SET utf8 NOT NULL,
				`phone` varchar(255) NOT NULL,
				`email` varchar(255) NOT NULL,
				`address` varchar(255) CHARACTER SET utf8 NOT NULL,
				`city` varchar(255) CHARACTER SET utf8 NOT NULL,
				`country` varchar(255) CHARACTER SET utf8 NOT NULL,
				`state` varchar(255) CHARACTER SET utf8 NOT NULL,
				`zip` varchar(255) CHARACTER SET utf8 NOT NULL,
				`amount` varchar(255) NOT NULL,
				`comment` text NOT NULL,
				`payment_status` varchar(255) NOT NULL,
				`payment_method` varchar(255) NOT NULL,
				`date` datetime NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `id` (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);

	if($newpages){
		wp_cache_delete( 'all_page_ids', 'pages' );
		$wp_rewrite->flush_rules();
	}
}

function paytm_deactivation() {
	$settings = paytm_settings_list();
	foreach ($settings as $setting) {
		delete_option($setting['name']);
	}
}

function paytm_settings_list(){
	$settings = array(
		array(
			'display' => 'Merchant ID',
			'name'    => 'paytm_merchant_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Id Provided by Paytm'
		),
		array(
			'display' => 'Merchant Key',
			'name'    => 'paytm_merchant_key',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Secret Key Provided by Paytm'
		),
		array(
			'display' => 'Website',
			'name'    => 'paytm_website',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Website Name Provided by Paytm'
		),
		array(
			'display' => 'Industry Type',
			'name'    => 'paytm_industry_type_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Industry Type Provided by Paytm'
		),
		array(
			'display' => 'Channel ID',
			'name'    => 'paytm_channel_id',
			'value'   => 'WEB',
			'type'    => 'textbox',
			'hint'    => 'Channel ID Provided by Paytm e.g. WEB/WAP'
		),
		array(
			'display' => 'Transaction URL',
			'name'    => 'transaction_url',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Transaction URL Provided by Paytm'
		),
		array(
			'display' => 'Transaction Status URL',
			'name'    => 'transaction_status_url',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Transaction Status URL Provided by Paytm'
		),
		array(
			'display' => 'Default Amount',
			'name'    => 'paytm_amount',
			'value'   => '100',
			'type'    => 'textbox',
			'hint'    => 'the default donation amount, WITHOUT currency signs -- ie. 100'
		),
		array(
			'display' => 'Default Button/Link Text',
			'name'    => 'paytm_content',
			'value'   => 'Paytm',
			'type'    => 'textbox',
			'hint'    => 'the default text to be used for buttons or links if none is provided'
		)				
	);
	return $settings;
}


if (is_admin()) {
	add_action( 'admin_menu', 'paytm_admin_menu' );
	add_action( 'admin_init', 'paytm_register_settings' );
}


function paytm_admin_menu() {
	add_menu_page('Paytm Settings', 'Paytm Settings', 'manage_options', 'paytm_options_page', 'paytm_options_page');
	add_menu_page('Paytm Payment Details', 'Paytm Payment Details', 'manage_options', 'wp_paytm_donation', 'wp_paytm_donation_listings_page');
	require_once(dirname(__FILE__) . '/paytm-donation-listings.php');
}


function paytm_options_page() {
	echo	'<div class="wrap">
				<h1>Paytm Configuarations</h1>
				<form method="post" action="options.php">';
					wp_nonce_field('update-options');
					echo '<table class="form-table">';
						$settings = paytm_settings_list();
						foreach($settings as $setting){
						echo '<tr valign="top"><th scope="row">'.$setting['display'].'</th><td>';

							if ($setting['type']=='radio') {
								echo $setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" '.(get_option($setting['name']) == 1 ? 'checked="checked"' : "").' />';
								echo $setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" '.(get_option($setting['name']) == 0 ? 'checked="checked"' : "").' />';
		
							} elseif ($setting['type']=='select') {
								echo '<select name="'.$setting['name'].'">';
								foreach ($setting['values'] as $value=>$name) {
									echo '<option value="'.$value.'" ' .(get_option($setting['name'])==$value? '  selected="selected"' : ''). '>'.$name.'</option>';
								}
								echo '</select>';

							} else {
								echo '<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" />';
							}

							echo '<p class="description" id="tagline-description">'.$setting['hint'].'</p>';
							echo '</td></tr>';
						}

						echo '<tr>
									<td colspan="2" align="center">
										<input type="submit" class="button-primary" value="Save Changes" />
										<input type="hidden" name="action" value="update" />';
										echo '<input type="hidden" name="page_options" value="';
										foreach ($settings as $setting) {
											echo $setting['name'].',';
										}
										echo '" />
									</td>
								</tr>

								<tr>
								</tr>
							</table>
						</form>';

			$last_updated = "";
			$path = plugin_dir_path( __FILE__ ) . "/paytm_version.txt";
			if(file_exists($path)){
				$handle = fopen($path, "r");
				if($handle !== false){
					$date = fread($handle, 10); // i.e. DD-MM-YYYY or 25-04-2018
					$last_updated = '<p>Last Updated: '. date("d F Y", strtotime($date)) .'</p>';
				}
			}

			include( ABSPATH . WPINC . '/version.php' );
			$footer_text = '<hr/><div class="text-center">'.$last_updated.'<p>Wordpress Version: '. $wp_version .'</p></div><hr/>';

			echo $footer_text.'</div>';
}


function paytm_register_settings() {
	$settings = paytm_settings_list();
	foreach ($settings as $setting) {
		register_setting($setting['name'], $setting['value']);
	}
}

function paytm_donation_handler(){

	if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "paytm_donation_request"){
		return paytm_donation_submit();
	} else {
		return paytm_donation_form();
	}
}

function paytm_donation_form(){
	$current_url = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$html = ""; 
	$html = '<form name="frmTransaction" method="post">
					<p>
						<label for="donor_name">Name:</label>
						<input type="text" name="donor_name" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_email">Email:</label>
						<input type="text" name="donor_email" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_phone">Phone:</label>
						<input type="text" name="donor_phone" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_amount">Amount:</label>
						<input type="text" name="donor_amount" maxlength="255" value="'.trim(get_option('paytm_amount')).'"/>
					</p>
					<p>
						<label for="donor_address">Address:</label>
						<input type="text" name="donor_address" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_city">City:</label>
						<input type="text" name="donor_city" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_state">State:</label>
						<input type="text" name="donor_state" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_postal_code">Postal Code:</label>
						<input type="text" name="donor_postal_code" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_country">Country:</label>
						<input type="text" name="donor_country" maxlength="255" value=""/>
					</p>
					<p>
						<input type="hidden" name="action" value="paytm_donation_request">
						<input type="submit" value="' . trim(get_option('paytm_content')) .'"/>
					</p>
				</form>';
	
	return $html;
}


function paytm_donation_submit(){

	$valid = true; // default input validation flag
	$html = '';
	$msg = '';
			
	if( trim($_POST['donor_name']) != ''){
		$donor_name = $_POST['donor_name'];
	} else {
		$valid = false;
		$msg.= 'Name is required </br>';
	}
			
	if( trim($_POST['donor_email']) != ''){
		$donor_email = $_POST['donor_email'];
		if( preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/" , $donor_email)){}
		else{
			$valid = false;
			$msg.= 'Invalid email format </br>';
		}
	} else {
		$valid = false;
		$msg.= 'E-mail is required </br>';
	}
				
	if( trim($_POST['donor_amount']) != ''){
		$donor_amount = $_POST['donor_amount'];
		if( (is_numeric($donor_amount)) && ( (strlen($donor_amount) > '1') || (strlen($donor_amount) == '1')) ){}
		else{
			$valid = false;
			$msg.= 'Amount cannot be less then $1</br>';
		}
	} else {
		$valid = false;
		$msg.= 'Amount is required </br>';
	}

	if($valid){
		
		require_once(dirname(__FILE__) . '/encdec_paytm.php');
		global $wpdb;

		$table_name = $wpdb->prefix . "paytm_donation";
		$data = array(
					'name' => sanitize_text_field($_POST['donor_name']),
					'email' => sanitize_text_field($_POST['donor_email']),
					'phone' => sanitize_text_field($_POST['donor_phone']),
					'address' => sanitize_text_field($_POST['donor_address']),
					'city' => sanitize_text_field($_POST['donor_city']),
					'country' => sanitize_text_field($_POST['donor_country']),
					'state' => sanitize_text_field($_POST['donor_state']),
					'zip' => sanitize_text_field($_POST['donor_postal_code']),
					'amount' => sanitize_text_field($_POST['donor_amount']),
					'payment_status' => 'Pending Payment',
					'date' => date('Y-m-d H:i:s'),
				);
					
					
		$wpdb->insert($table_name, $data);
		$order_id = $wpdb->insert_id;

		// $order_id = 'TEST_'.strtotime("now").'-'.$order_id; //just for testing

		$post_params = array(
			'MID' => trim(get_option('paytm_merchant_id')),
			'WEBSITE' => trim(get_option('paytm_website')),
			'CHANNEL_ID' =>  trim(get_option('paytm_channel_id')),
			'INDUSTRY_TYPE_ID' =>  trim(get_option('paytm_industry_type_id')),
			'ORDER_ID' => $order_id,
			'TXN_AMOUNT' => $_POST['donor_amount'],
			'CUST_ID' => $_POST['donor_email'],
			'EMAIL' => $_POST['donor_email'],
			'CALLBACK_URL' => get_permalink(),
		);		
	
		$post_params["CHECKSUMHASH"] = getChecksumFromArray(	$post_params,
																				trim(get_option('paytm_merchant_key'))
																			);

		$form_action = trim(get_option('transaction_url'))."?orderid=".$order_id;


		$html = "<center><h1>Please do not refresh this page...</h1></center>";

		$html .= '<form method="post" action="'.$form_action.'" name="f1">';

		foreach($post_params as $k=>$v){
			$html .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
		}

		$html .= "</form>";
		$html .= '<script type="text/javascript">document.f1.submit();</script>';

		return $html;

	} else {
		return $msg;
	}
}

function paytm_donation_meta_box() {
	$screens = array( 'paytmcheckout' );
	
	foreach ( $screens as $screen ) {
		add_meta_box(  'myplugin_sectionid', __( 'Paytm', 'myplugin_textdomain' ),'paytm_donation_meta_box_callback', $screen, 'normal','high' );
	}
}

add_action( 'add_meta_boxes', 'paytm_donation_meta_box' );

function paytm_donation_meta_box_callback($post) {
	echo "admin";
}

function paytm_donation_response(){
	
	if(! empty($_POST) && isset($_POST['ORDERID'])){

		require_once(dirname(__FILE__) . '/encdec_paytm.php');
		global $wpdb;

		$paytm_merchant_key = trim(get_option('paytm_merchant_key'));
		$paytm_merchant_id = trim(get_option('paytm_merchant_id'));
		$transaction_status_url = trim(get_option('transaction_status_url'));

		if(verifychecksum_e($_POST, $paytm_merchant_key, $_POST['CHECKSUMHASH']) === "TRUE") {
			
			if($_POST['RESPCODE'] == "01"){

				// Create an array having all required parameters for status query.
				$requestParamList = array("MID" => $paytm_merchant_id, "ORDERID" => $_POST['ORDERID']);

				// $_POST['ORDERID'] = substr($_POST['ORDERID'], strpos($_POST['ORDERID'], "-") + 1); // just for testing
				$StatusCheckSum = getChecksumFromArray($requestParamList, $paytm_merchant_key);

				$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;

				$responseParamList = callNewAPI($transaction_status_url, $requestParamList);

				if($responseParamList['STATUS'] == 'TXN_SUCCESS' && $responseParamList['TXNAMOUNT'] == $_POST['TXNAMOUNT']) {
					$wpdb->query($wpdb->prepare("UPDATE FROM " . $wpdb->prefix . "paytm_donation WHERE id = %d", $_POST['ORDERID']));
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Complete Payment' WHERE  id = %d", $_POST['ORDERID']));
					$msg= "Thank you for your order. Your transaction has been successful.";
				
				} else  {
					$msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Fraud Payment' WHERE id = %d", $_POST['ORDERID']));
				}

			} else {
				$msg = "Thank You. However, the transaction has been Failed For Reason: " . sanitize_text_field($_POST['RESPMSG']);
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Canceled Payment' WHERE  id = %d", $_POST['ORDERID']));
			}
		} else {
			$msg = "Security error!";
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Payment Error' WHERE  id = %d", $_POST['ORDERID']));
		}

		$redirect_url = get_site_url() . '/' . get_permalink(get_the_ID());
		//echo $redirect_url ."<br />";
		$redirect_url = add_query_arg( array('donation_msg'=> urlencode($msg)));
		wp_redirect( $redirect_url,301 );
		exit;
	}
}


/*
* Code to test Curl
*/
if(isset($_GET['paytm_action']) && $_GET['paytm_action'] == "curltest"){
	add_action('the_content', 'curltest_donation');
}

function curltest_donation($content){

	// phpinfo();exit;
	$debug = array();

	if(!function_exists("curl_init")){
		$debug[0]["info"][] = "cURL extension is either not available or disabled. Check phpinfo for more info.";

	// if curl is enable then see if outgoing URLs are blocked or not
	} else {

		// if any specific URL passed to test for
		if(isset($_GET["url"]) && $_GET["url"] != ""){
			$testing_urls = array($_GET["url"]);   
		
		} else {

			// this site homepage URL
			$server = get_site_url();

			$settings = get_option( "woocommerce_paytm_settings", null );

			$testing_urls = array(
											$server,
											"www.google.co.in",
											$settings["transaction_status_url"]
										);
		}

		// loop over all URLs, maintain debug log for each response received
		foreach($testing_urls as $key=>$url){

			$debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$res = curl_exec($ch);

			if (!curl_errno($ch)) {
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$debug[$key]["info"][] = "cURL executed succcessfully.";
				$debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";

				// $debug[$key]["content"] = $res;

			} else {
				$debug[$key]["info"][] = "Connection Failed !!";
				$debug[$key]["info"][] = "Error Code: <b>" . curl_errno($ch) . "</b>";
				$debug[$key]["info"][] = "Error: <b>" . curl_error($ch) . "</b>";
				break;
			}

			curl_close($ch);
		}
	}

	$content = "<center><h1>cURL Test for Paytm Donation Plugin</h1></center><hr/>";
	foreach($debug as $k=>$v){
		$content .= "<ul>";
		foreach($v["info"] as $info){
			$content .= "<li>".$info."</li>";
		}
		$content .= "</ul>";

		// echo "<div style='display:none;'>" . $v["content"] . "</div>";
		$content .= "<hr/>";
	}

	return $content;
}
/*
* Code to test Curl
*/
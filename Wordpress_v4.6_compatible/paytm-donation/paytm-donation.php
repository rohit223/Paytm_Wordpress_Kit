<?php
/*
Plugin Name: PayTM Donate with Check Status.
Version: 0.2
Description: This plugin allows site owners to have a donate buttons for visitors to donate via PayTM in either set or custom amounts
*/

//ini_set('display_errors','On');
require_once(dirname(__FILE__) . '/encdec_paytm.php');
register_activation_hook(__FILE__, 'paytm_activation');
register_deactivation_hook(__FILE__, 'paytm_deactivation');

add_action('init', 'paytm_donation_response');

if($_GET['donation_msg']!=''){
    add_action('the_content', 'paytmDonationShowMessage');
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
			'post_title' 	=>	$paytm_pages['paytm-page']['title'],
			'post_type' 	=>	'page',
			'post_name'		=>	$paytm_pages['paytm-page']['name'],
			'comment_status'=>	'closed',
			'ping_status' 	=>	'closed',
			'post_content' 	=>	$paytm_pages['paytm-page']['tag'],
			'post_status' 	=>	'publish',
			'post_author' 	=>	1,
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
      'hint'    => 'Merchant ID'
		),
		array(
			'display' => 'Merchant Key',
			'name'    => 'paytm_merchant_key',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant key'
		),
		array(
			'display' => 'Website',
			'name'    => 'paytm_website',
			'value'   => '',
			'type'    => 'textbox',
      'hint'    => 'Website'
		),
		array(
			'display' => 'Industry Type ID',
			'name'    => 'paytm_industry_type_id',
			'value'   => '',
			'type'    => 'textbox',
      'hint'    => 'Industry Type ID'
		),
		array(
			'display' => 'Channel ID',
			'name'    => 'paytm_channel_id',
			'value'   => '',
			'type'    => 'textbox',
      'hint'    => 'Channel ID e.g. WEB/WAP'
		),
		array(
			'display' => 'Mode',
			'name'    => 'paytm_mode',
			'value'   => 'TEST',
			'values'  => array('TEST'=>'TEST','LIVE'=>'LIVE'),
			'type'    => 'select',
      'hint'    => 'Change the mode of the payments'
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
		),
		array(
			'display' => 'Set CallBack URL',	
			'name'    => 'paytm_callback',
			'value'   => 'YES',
			'values'  => array('YES'=>'YES','NO'=>'NO'),
			'type'    => 'select',
			'hint'    => 'Select No to disable CallBack URL'
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
	add_menu_page('Paytm Paymet Details', 'Paytm Paymet Details', 'manage_options', 'wp_paytm_donation', 'wp_paytm_donation_listings_page');
	require_once(dirname(__FILE__) . '/paytm-donation-listings.php');
}


function paytm_options_page() {
	echo'
	<div class="wrap" style="width:950px;">
		<h2>Paytm Configuarations</h2>
			<form method="post" action="options.php" style="width:738px; float:left; clear:none;">';
				wp_nonce_field('update-options');
				echo '<table class="form-table">';
				$settings = paytm_settings_list();
				foreach ($settings as $setting) {
					echo '<tr><th scope="row">'.$setting['display'].'</th><td>';
					if ($setting['type']=='radio') {
						echo $setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" ';
						if (get_option($setting['name'])==1) { echo 'checked="checked" />'; } else { echo ' />'; }
						echo $setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" ';
						if (get_option($setting['name'])==0) { echo 'checked="checked" />'; } else { echo ' />'; }
					} elseif ($setting['type']=='select') {
						$values=$setting['values'];
						echo '<select name="'.$setting['name'].'">';
						foreach ($values as $value=>$name) {
							echo '<option value="'.$value.'" ';
							if (get_option($setting['name'])==$value) { echo ' selected="selected" ';}
							echo '>'.$name.'</option>';
						}
						echo '</select>';
					} else { echo '<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" />'; }
					echo ' (<em>'.$setting['hint'].'</em>)</td></tr>';
				}
				echo '<tr><th style="text-align:center;"><input type="submit" class="button-primary" value="Save Changes" />';
				echo '<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="';
				foreach ($settings as $setting) {
					echo $setting['name'].',';
				}
				echo '" /></th><td></td></tr></table></form>';
		
	echo '</div>';
}


function paytm_register_settings() {
	$settings = paytm_settings_list();
	foreach ($settings as $setting) {
		register_setting($setting['name'], $setting['value']);
	}
}


add_shortcode( 'paytmcheckout', 'paytm_donate_button' );
add_action('admin_post_paytm_donation_request','paytm_donate_button');

function paytm_donate_button() {
	if( ! isset($_POST['ORDERID']) && ! isset($_GET['donation_msg'])){
		global $wpdb;
		extract(
					array(
						'paytm_merchant_id' => trim(get_option('paytm_merchant_id')),
						'paytm_merchant_key' => trim(get_option('paytm_merchant_key')),
						'paytm_website' => trim(get_option('paytm_website')),
						'paytm_industry_type_id' => trim(get_option('paytm_industry_type_id')),
						'paytm_channel_id' => trim(get_option('paytm_channel_id')),
						'paytm_mode' => trim(get_option('paytm_mode')),
						'paytm_callback' => trim(get_option('paytm_callback')),
						'paytm_amount' => trim(get_option('paytm_amount')),		
						'paytm_content' => trim(get_option('paytm_content'))						
					)
				);
		if(isset($_POST['paytmcheckout'])){		
			$valid = true;
			$html='';
			$msg='';
			
			
				if( $_POST['donor_name'] != ''){
					$donor_name = $_POST['donor_name'];
				}
				else{
					$valid = false;
					$msg.= 'Name is required </br>';
				}
			
				if( $_POST['donor_email'] != ''){
					$donor_email = $_POST['donor_email'];
					if( preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/" , $donor_email)){}
					else{
						$valid = false;
						$msg.= 'Invalid email format </br>';
					}
				}
				else{
					$valid = false;
					$msg.= 'E-mail is required </br>';
				}
				
				if( $_POST['donor_amount'] != ''){
					$donor_amount = $_POST['donor_amount'];
					if( (is_numeric($donor_amount)) && ( (strlen($donor_amount) > '1') || (strlen($donor_amount) == '1')) ){}
					else{
						$valid = false;
						$msg.= 'Amount cannot be less then $1</br>';
					}
				}
				else{
					$valid = false;
					$msg.= 'Amount is required </br>';
				}
				
				
				if($valid){
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
												'date' =>date('Y-m-d H:i:s'),
					);
					
					
					$wpdb->insert($table_name, $data);
					$order_id = $wpdb->insert_id;
					
					$post_params = array(
						'MID' => $paytm_merchant_id,
						'ORDER_ID' => $order_id,
						'WEBSITE' => $paytm_website,
						'CHANNEL_ID' => $paytm_channel_id,
						'INDUSTRY_TYPE_ID' => $paytm_industry_type_id,
						'TXN_AMOUNT' => $_POST['donor_amount'],
						'CUST_ID' => $_POST['donor_email'],
						'EMAIL' => $_POST['donor_email'],
					);		
	
					if($paytm_callback=='YES')
					{
						$post_params["CALLBACK_URL"] = get_permalink();
					}
					
						
						$checkSum = getChecksumFromArray ($post_params,$paytm_merchant_key);
						$call = get_permalink();
						$action_url="https://pguat.paytm.com/oltp-web/processTransaction?orderid=$order_id";
						if($paytm_mode == 'LIVE'){
							$action_url="https://secure.paytm.in/oltp-web/processTransaction?orderid=$order_id";
						}
						
						if($paytm_callback=='YES')
						{
						
						$html= <<<EOF
						
								<center><h1>Please do not refresh this page...</h1></center>
									<form method="post" action="$action_url" name="f1">
									<table border="1">
										<tbody>
											<input type="hidden" name="MID" value="$paytm_merchant_id">
											<input type="hidden" name="WEBSITE" value="$paytm_website">
											<input type="hidden" name="CHANNEL_ID" value="$paytm_channel_id">
											<input type="hidden" name="ORDER_ID" value="$order_id">
											<input type="hidden" name="INDUSTRY_TYPE_ID" value="$paytm_industry_type_id">									
											<input type="hidden" name="TXN_AMOUNT" value="{$_POST['donor_amount']}">
											<input type="hidden" name="CUST_ID" value="{$_POST['donor_email']}">
											<input type="hidden" name="EMAIL" value="{$_POST['donor_email']}">
											<input type="hidden" name="CALLBACK_URL" value="$call">
											<input type="hidden" name="CHECKSUMHASH" value="$checkSum">
										</tbody>
									</table>
									<script type="text/javascript">
										document.f1.submit();
									</script>
								</form>
							
		
EOF;
						}
						else
						{
							$html= <<<EOF
						
								<center><h1>Please do not refresh this page...</h1></center>
									<form method="post" action="$action_url" name="f1">
									<table border="1">
										<tbody>
											<input type="hidden" name="MID" value="$paytm_merchant_id">
											<input type="hidden" name="WEBSITE" value="$paytm_website">
											<input type="hidden" name="CHANNEL_ID" value="$paytm_channel_id">
											<input type="hidden" name="ORDER_ID" value="$order_id">
											<input type="hidden" name="INDUSTRY_TYPE_ID" value="$paytm_industry_type_id">									
											<input type="hidden" name="TXN_AMOUNT" value="{$_POST['donor_amount']}">
											<input type="hidden" name="CUST_ID" value="{$_POST['donor_email']}">
											<input type="hidden" name="EMAIL" value="{$_POST['donor_email']}">											
											<input type="hidden" name="CHECKSUMHASH" value="$checkSum">
										</tbody>
									</table>
									<script type="text/javascript">
										document.f1.submit();
									</script>
								</form>
							
		
EOF;
						}
		
				return $html;
			}else{
				return $msg;
			}
		}else{
			
			$html =""; 
			$html='<form name="frmTransaction" method="post" >
							<p><label for="name"> Name:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> <input type="text" name="donor_name"  maxlength="255" value=""/> </p>
							<p>	<label for="email"> Email:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label><input type="text" name="donor_email"  maxlength="40" value=""/> </p>
							<p>	<label for="phone"> Phone:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> <input type="text" name="donor_phone"  maxlength="255" value=""/> </p>								    
							<p> <label for="amount" >Amount:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label><input type="'.$total_type.'" name="donor_amount" value="'.$paytm_amount.'"/> </p>
							<p>	<label for="address"> Address:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</lable><input type="text" name="donor_address" maxlength="255" value=""/> </p>
							<p>	<label for="city" > City:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label><input type="text" name="donor_city"  maxlength="255" value=""/> </p>
							<p>	<label for="state" > State:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label><input type="text" name="donor_state"  maxlength="255" value=""/> </p>
							<p>	<label for="postal_code" > Postal Code: &nbsp;</lable> <input type="text" name="donor_postal_code"  maxlength="255" value=""/> </p>
							<p>	<label for="state" > Country:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label><input type="text" name="donor_country"  maxlength="255" value=""/> </p>
							';
							
							$html .= '<input name="paytmcheckout" type="submit" value="' . $paytm_content .'"/>';
			
			return $html;
		}
	}	
}


function paytm_donation_meta_box()
{
   $screens = array( 'paytmcheckout' );
	
   foreach ( $screens as $screen ) {
      add_meta_box(  'myplugin_sectionid', __( 'Paytm', 'myplugin_textdomain' ),'paytm_donation_meta_box_callback', $screen, 'normal','high' );
   }
}

add_action( 'add_meta_boxes', 'paytm_donation_meta_box' );

function paytm_donation_meta_box_callback($post)
{

echo "admin";
}

/*
add_action( 'init', 'paytmcheckout_menu_type', 0 );

function paytmcheckout_menu_type() {

	$labels = array(
		'name'                => _x( 'Donations', 'Post Type General Name', 'paytmcheckout_menu' ),
		'singular_name'       => _x( 'Paytm Donation', 'Post Type Singular Name', 'paytmcheckout_menu' ),
		'menu_name'           => __( 'Paytm Donation ', 'paytmcheckout_menu' ),
		'parent_item_colon'   => __( 'Parent Donation', 'paytmcheckout_menu' ),
		'all_items'           => __( 'All Donation', 'paytmcheckout_menu' ),
		'view_item'           => __( 'View Donation', 'paytmcheckout_menu' ),
		'edit_item'           => __( 'Edit Donation', 'paytmcheckout_menu' ),
		'update_item'         => __( 'Update Donation', 'paytmcheckout_menu' ),
		'search_items'        => __( 'Search Donation', 'paytmcheckout_menu' ),
		'not_found'           => __( 'Not found', 'paytmcheckout_menu' ),
		'not_found_in_trash'  => __( 'Not found in Trash', 'paytmcheckout_menu' ),
	);
	$args = array(
		'label'               => __( 'paytmcheckout_menu', 'paytmcheckout_menu' ),
		'description'         => __( 'list of donations', 'paytmcheckout_menu' ),
		'labels'              => $labels,
		'supports'            => array('title', 'custom-fields' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => false,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);
	register_post_type( 'paytmcheckout_menu', $args );

}

*/

function paytm_donation_response(){
	
	if(! empty($_POST) && isset($_POST['ORDERID'])){
		global $wpdb;
		extract(
					array(
						'paytm_merchant_id' => get_option('paytm_merchant_id'),
						'paytm_merchant_key' => get_option('paytm_merchant_key'),
						'paytm_website' => get_option('paytm_website'),
						'paytm_industry_type_id' => get_option('paytm_industry_type_id'),
						'paytm_channel_id' => get_option('paytm_channel_id'),
						'paytm_mode' => get_option('paytm_mode'),
						'paytm_callback' => get_option('paytm_callback'),
						'paytm_amount' => get_option('paytm_amount')												
					)
				);
//vidisha
		if(verifychecksum_e($_POST,$paytm_merchant_key,$_POST['CHECKSUMHASH']) === "TRUE"){
			if($_POST['RESPCODE'] =="01"){
				// Create an array having all required parameters for status query.
				$requestParamList = array("MID" => $paytm_merchant_id , "ORDERID" => $_POST['ORDERID']);
				
				// Call the PG's getTxnStatus() function for verifying the transaction status.
				
				$check_status_url = 'https://pguat.paytm.com/oltp/HANDLER_INTERNAL/TXNSTATUS';
				if($paytm_mode == 'LIVE')
				{
					$check_status_url = 'https://secure.paytm.in/oltp/HANDLER_INTERNAL/TXNSTATUS';
				}
				$responseParamList = callAPI($check_status_url, $requestParamList);
				if($responseParamList['STATUS']=='TXN_SUCCESS' && $responseParamList['TXNAMOUNT']==$order_amount)
				{
					$wpdb->query($wpdb->prepare("UPDATE FROM " . $wpdb->prefix . "paytm_donation WHERE id = %d", $_POST['ORDERID']));
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Complete Payment' WHERE  id = %d", $_POST['ORDERID']));
					$msg= "Thank you for your order . Your transaction has been successful.";
				}
				else 
				{
					$msg= "Thank You. However, the transaction has been Failed";
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Fraud Payment' WHERE  id = %d", $_POST['ORDERID']));
				}
			}else{
				$msg= "Thank You. However, the transaction has been Failed For Reason  : "  . sanitize_text_field($_POST['RESPMSG']);
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Canceled Payment' WHERE  id = %d", $_POST['ORDERID']));
	
			}
		}else{
				$msg= "Security error!";
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Payment Error' WHERE  id = %d", $_POST['ORDERID']));
		}
		$redirect_url =get_site_url() . '/' . get_permalink(get_the_ID());//echo $redirect_url ."<br />";
		$redirect_url = add_query_arg( array('donation_msg'=> urlencode($msg)));
		wp_redirect( $redirect_url,301 );exit;
	}
	
	
}

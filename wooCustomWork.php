<?php
/*
Plugin Name: Core Customizations
Plugin URI: https://xpertzgroup.com
Description: Customize woo theme core work
Author: LeadSoft
Version: 1.0
Author URI: https://xpertzgroup.com
*/

defined('ABSPATH') or die('<h1 style="top: 50%;width: 100%;text-align: center;position: absolute;">Sorry! You can not access direct file, please contact with admin for furthor detail.</h1>');
define( 'PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PLUGIN_URL_RY', plugin_dir_url( __FILE__ ) ); 
define( 'site_url', site_url() );  

add_filter('woocommerce_checkout_update_customer_data', '__return_false' );


add_action('init', 'testingfunction');
function testingfunction(){global $current_uuser;
		
		$default_price = get_option('default_price');
		$member_fee = assign_member_fee();		
		define( 'member_fee', $member_fee );
		define( 'signup_fee', $default_price['default_signup'] );  
		
	if( (isset($_GET['uap_act']) and $_GET['uap_act'] == 'admin_panel') and (isset($_GET['uid'])) ){
		echo '<script>window.location.href="'.admin_url('/admin.php?page=imprint_info&uid='.$_GET['uid']).'";</script>';exit;
		} 	
	
	if( isset($_GET['logout']) and $_GET['logout'] == 'true' ){
		wp_logout();
		} 	
	
	}
	
function reverseUser($decoded){
	$encoded = base64_decode($decoded);
	return $user_id = ($encoded/2) - 35;
	}

// add_action('wp', 'root_function', 999);
// add_action('init', 'root_function_init', 999); 
// include(PLUGIN_DIR_RY.'template/success.php');

/**
 * assign staff role if clinic user add user
 **/
// add_action( 'profile_update', 'zana_role_assign_to_staff', 10, 1 ); 
add_action( 'user_register', 'zana_role_assign_to_staff', 100 );
function zana_role_assign_to_staff( $user_id ) {global $current_user, $wpdb; 
		
		if(isset($_POST['stripeToken'])){
		 require_once('lib/init.php');
		 
		 $keys = get_option('gravityformsaddon_gravityformsstripe_settings');
		 
		 if($keys['api_mode'] != 'live'){
			 $secret_key = $keys['test_secret_key'];
			 $publishable_key = $keys['test_publishable_key']; 
			 }
		if($keys['api_mode'] == 'live'){
			 $secret_key = $keys['live_secret_key']; 
			 $publishable_key = $keys['live_publishable_key']; 
			 }
		 
		$stripe = [
		  "secret_key"      => $secret_key,
		  "publishable_key" => $publishable_key,
		];
		
		\Stripe\Stripe::setApiKey($stripe['secret_key']);
	
		$token  = $_POST['stripeToken']; 
		
		$customer = \Stripe\Customer::create([
			"email" => $_POST['user_email'],
			 'source'  => $token,
		]);
		
		try {
			$affiliate_user = '';
			if(isset($_POST['offer'])){
				$offer = base64_decode($_POST['offer']);
				$affiliate_user = str_replace('shwanzana', '', $offer);
				}
			$prices = affiliate_prices($affiliate_user);
			
			// assign affiliate category to new signup user
		    if($prices['affiliate'] == 'true'){
			  $affiliate_category_id = get_user_meta($affiliate_user, 'affiliate_category', true);
			  update_user_meta($user_id, 'reffer_affiliate_category', $affiliate_category_id);
			  }
		  
	   if(isset($_POST['member_reg_fee']) and $_POST['member_reg_fee'] != ''){
		   $signup_fee = ($_POST['member_reg_fee'] * 100) ;
		   }else{
			   $signup_fee = $prices['signup'] * 100;
			   }
			   
	  update_user_meta($user_id, 'fee_paid', 'true');
	  update_user_meta($user_id, 'signup_fee', $prices['signup']);
	  
	  $response = coupon_codes_custom(array('signup' => $_POST['couponCode'], 'member' => $_POST['memberCouponCode'], 'sel_member' => $_POST['required_member']));
	  if($response['member'] == 'true'){
		  $coupon = $_POST['memberCouponCode'];
		  $type = 'memberdiscount'; 
		  $signup_fee = ($signup_fee) ;
		  update_user_meta($user_id, 'member_coupon_code', $coupon);
		  update_user_meta($user_id, 'free_member_flag', 'true');
		  }
	  if($response['signup'] == 'true'){
		  $coupon = $_POST['couponCode'];
		  $type = 'discount'; 
		  $signup_fee = ($signup_fee) ;
		  }
	  elseif(isset($_POST['offer']) and !empty($_POST['offer'])){
		  $affiliate = check_affiliate_user($_POST['offer']);
		 if($affiliate == 1){
			 $coupon = $user_id;
			 $type = 'affiliate'; 
			 $signup_fee = ($signup_fee) ;
			 }
		  }
	  else{
		  	$type = 'premium'; 
			$coupon = '';
		  }
				
	  		$charge = \Stripe\Charge::create([
			'customer' => $customer->id,
			'amount'   => (int)$signup_fee,
			'currency' => 'usd',
		]); 
			$data = serialize(array('id' =>$charge->id, 
								'object' =>$charge->source->object, 
								'address_zip' =>$charge->source->address_zip, 
								'brand' =>$charge->source->brand, 
								));
									
		if(!empty($customer->id)){
			
			$wpdb->insert( 
						'payment_detail', 
							  array(
									'user_id'		=> $user_id, 
									'customer'		=> $customer->id, 
									'amount' 		=> $signup_fee/100,
									'type' 			=> $type,
									'coupon'		=> $coupon,
									'status'		=> 'succeeded',
									'data' 			=> $data,
									'dated' 		=> date('Y-m-d H:i:s'),
									)
							); 
			} 
			$error = 'successs';
			} 
			
		catch(Stripe_CardError $e) {
			  $error = $e->getMessage();
			} catch (Stripe_InvalidRequestError $e) {
			  // Invalid parameters were supplied to Stripe's API
			  $error = $e->getMessage();
			} catch (Stripe_AuthenticationError $e) {
			  // Authentication with Stripe's API failed
			  $error = $e->getMessage();
			} catch (Stripe_ApiConnectionError $e) {
			  // Network communication with Stripe failed
			  $error = $e->getMessage();
			} catch (Stripe_Error $e) {
			  // Display a very generic error to the user, and maybe send
			  // yourself an email
			  $error = $e->getMessage();
			} catch (Exception $e) {
			  // Something else happened, completely unrelated to Stripe
			  $error = $e->getMessage();
			} 
		
		if($error != 'successs'){
				update_user_meta($user_id, 'uap_verification_status', -1);
				update_user_meta($user_id, 'uap_payment_status', $error);
				}
		// only client can come in this condition so we can remove other role and assign him client role
		// suppose on registeration user not subscribed as client then manuaaly assign role			
		
		if($current_user->roles[0] != 'subscriber'){
			  $user = new WP_User( $user_id );
			  $user->remove_role( 'author' ); 
			  $user->add_role( 'subscriber' );
			  update_user_meta($user_id, 'zabardasti_uwser', $current_user->roles[0]); 
		  }	
		  
		}
		
		// when client adding the staff member 
		if($current_user->roles[0] == 'subscriber'){
			 
			  $user = new WP_User( $user_id );
			  $user->remove_role( 'subscriber' ); 
			  $user->add_role( 'author' ); 
			   
			  update_user_meta($user_id, 'signup_fee', member_fee);
			  
			  // if clinic have facility to add one free member then after adding then update table
			  
			  $balance_amount = balance_amount( array('user_id' => $current_user->ID) );
              $free_member_flag = get_coupon_flag($current_user->ID); 
			  
			  if($balance_amount < member_fee && $free_member_flag == 'true'){
				  //TODO: change later
			/*	  $wpdb->query("UPDATE coupon_records 
								SET flag = 'false'
								WHERE user_id = '".$current_user->ID."'
									  AND flag = 'true'
								");*/
				  //update_user_meta($current_user->ID, 'free_member_flag', 'false'); 
				  }
			  
			   update_user_meta($user_id, 'clinic_user', $current_user->ID);  
		  }	  
		  
		require_once(PLUGIN_PATH.'lib/stripe/vendor/ontraport/sdk-php/src/Ontraport.php');
		$client = new Ontraport("2_183927_0IzR4K0Jl","uc77wUei0KXzz6T");
		
		$requestParams = array(
			"firstname" 	=> $_POST['first_name'],
			"lastname"  	=> $_POST['last_name'],
			"email"     	=> $_POST['user_email'],
			"office_phone"  => $_POST['phone'],
			
		);
		
		$response = $client->contact()->create($requestParams);
		/*echo '<script>window.location.href="'.home_url('/thanks-joing-myzana/').'";</script>';exit;*/
	}

function get_used_amount($param = array()){
	$total_registered_price = total_registered_staff_in_clinic(array('user_id' => $param['user_id'])); 
	return $total_registered_price;
	}

/**
	* add staff member user registration form
**/
add_shortcode("clinic_add_staff_member", "clinic_add_staff_member"); 
function clinic_add_staff_member( $atts ){ob_start();global $current_user;$user_id = $current_user->ID;
		  $extract =  shortcode_atts( array(
								  'user_error_msg'		=> '',
								  'fee_error_msg'		=> '',
								  'success_msg'		=> ''
							  ), $atts ) ; 
	if(isset($_GET['message']) and $_GET['message'] == 'success'){}else{
				echo '<script>document.getElementById("display_msg").style.display = "none";</script>';
				}
	
	if($current_user->roles[0] == 'subscriber' ){
		
		$balance_amount = balance_amount( array('user_id' => $current_user->ID) );
		// $free_member_flag = get_user_meta($current_user->ID, 'free_member_flag', true);
        $free_member_flag = get_coupon_flag($current_user->ID);
		if($free_member_flag == 'true'){ $free_member = 1; $msg = ' <br />You can add 1 free member.';}else{$free_member = 0; $msg = '';}
	
		if($balance_amount >= member_fee OR $free_member_flag == 'true'){ 
			 
			echo clinic_current_balance_box(array('amount' => $balance_amount, 'success_msg' => $extract['success_msg']));
			echo do_shortcode('[uap-register]');  
		}
		else{?>
        <div class="uap-wrapp-the-errors">
            <div class="uap-register-error">
				<h3>Your current balance: $<?php echo $balance_amount > 0 ? number_format($balance_amount) : 0; ?></h3>
				<?php echo $extract['fee_error_msg'] ?></div>
		</div>
        <?php } ?>
		
	<?php }else{?>
    	<div class="uap-wrapp-the-errors">
            <div class="uap-register-error"><?php echo $extract['user_error_msg'] ?></div>
		</div>
	<?php } ?>
          
	<?php return ob_get_clean();
  }

function get_coupon_flag($user_id, $col = NULL){
	//TODO: Change later
    global $wpdb;
	$sel_column = empty($col) ? 'flag' : $col; 
    $query = "SELECT ".$sel_column." FROM `coupon_records` where user_id = $user_id and flag = 'true' ";
	return true;
	//$flag = $wpdb->get_var($query);
}

function balance_amount( $param = array() ){
	$Paid_amount = doctor_paid_amount(array('user_id' => $param['user_id'])); 
	$used_amount = get_used_amount(array('user_id' => $param['user_id']));
	
	return $balance_amount = $Paid_amount - $used_amount;
	}
/**
	* Balance Remaining
**/
//add_shortcode("clinic_current_balance_box", "clinic_current_balance_box"); 
function clinic_current_balance_box( $param = array() ){global $current_user; 
	$prices = affiliate_prices($current_user->ID);
	//$free_member_flag = get_user_meta($current_user->ID, 'free_member_flag', true);
    $free_member_flag = get_coupon_flag($current_user->ID);
	if($free_member_flag == 'true'){ $free_member = 1; $msg = ' <br />You can add 1 free member.';}else{$free_member = 0; $msg = '';}?>
    
    <div class="uap-wrapp-the-errors" style="background-color: #dff0d8;border: 1px solid green;">
        <div class="uap-register-error" style="color: #3c763d;">
        	<?php echo $param['success_msg'] ?> $<strong><?php echo $param['amount'] > 0 ? number_format($param['amount']) : 0;?></strong>. <?php echo $msg;?>
            <br />
            You can add <?php echo ($param['amount'] >= member_fee || $free_member_flag == 'true') ? number_format(floor($param['amount']/member_fee))+$free_member : 0; ?> staff members.
            </div> 
    </div>
	<?php 
  }
 
function total_registered_staff_in_clinic($param = array()){global $wpdb;
	$query = "SELECT user_id FROM `".$wpdb->prefix."usermeta` where meta_key = 'clinic_user' and meta_value = '".$param['user_id']."' ";
	$query = $wpdb->get_results($query);
	$sum = 0;
	if(!empty($query)){
		foreach($query as $data){
			$signup_fee = get_user_meta($data->user_id, 'signup_fee', true);
			if( $signup_fee != '' ){
				$sum = $sum + $signup_fee;
				}
			else{
				$sum = $sum + member_fee;
				}
			}
		}
	return $sum;
	}  

function detail_registered_staff_in_clinic($param = array()){global $wpdb;
	$query = "SELECT user_id, user_registered reg FROM `".$wpdb->prefix."usermeta` um 
					 INNER JOIN `".$wpdb->prefix."users` u
					 	ON (um.user_id = u.ID) 	
					 WHERE meta_key = 'clinic_user' 
					 AND meta_value = '".$param['user_id']."' 
					 order by ID desc
				   ";
	return $wpdb->get_results($query);
	}  
	
	add_action( 'gform_validation_message_7', 'pre_submission', 0, 2 );
	add_action( 'gform_validation_message_7', 'pre_submission', 2200, 2 );
    function pre_submission($message, $form){
			//return "<div class='validation_error'>Failed Validation - " . $form['input_17'] . '</div>';
		}
	
    add_action( 'gform_after_submission', 'after_submission', 10, 2 );
    function after_submission(){
        global $wpdb;
        //echo '<pre>';print_r($_POST);
        
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        $couponCode = $_POST['input_17'];
        $flag = get_coupon_flag($user_id);
        
        if($flag!='true' && !empty($couponCode)){
        $wpdb->insert( 
                    'coupon_records', 
                          array(
                                'user_id'		=> $user_id, 
                                'coupon_code'	=> $couponCode
                                )
                        );
        }
    }

add_shortcode("button_add_staff_member", "button_add_staff_member"); 
function button_add_staff_member( $atts ){ob_start();global $current_user;$user_id = $current_user->ID;
		  extract( shortcode_atts( array(
								  'login_slug' 	=> '#'
							  ), $atts ) ); 
	if($current_user->roles[0] == 'subscriber'){?> 
    	<div id="add-staff-button" class="option-button">
						<a href="<?php echo home_url() ?>/add-staff/?go=<?php echo $current_user->user_login ?>&shape=2">
							<i class="fas fa-plus"></i>
							<span>Add New Staff Members</span>
						</a>
					<div id="buy-credits-button" class="option-button">
						<a href="<?php echo home_url() ?>/member-fee">
							<i class="fas fa-plus"></i>
							<span>Buy More Staff Credits</span>
						</a>
					</div>
				</div>  
	<?php } ?>
          
	<?php return ob_get_clean();
  }

function check_posted_coupon_code(){
	$data = json_decode(stripslashes($_POST['coupon_code']), true);  
	$result = coupon_codes_custom($data, $data);
	echo json_encode($result); exit;
	die(0);}
add_action('wp_ajax_check_posted_coupon_code' , 'check_posted_coupon_code');
add_action('wp_ajax_nopriv_check_posted_coupon_code' , 'check_posted_coupon_code');	
  
add_action('wp_head', 'header_js_script', 9);
function header_js_script(){global $post,$current_user, $wpdb;$user_coupon = '';
	$free_member_flag = get_coupon_flag($current_user->ID); 
	$result = $wpdb->get_var("SELECT meta FROM `".$wpdb->prefix."gf_addon_feed` 
										where form_id = 7 
										and is_active = 1
                                        and id = 19
										"); 
	if(!empty($result)){
		$result = json_decode($result);
		//TODO: Change later

		$user_coupon = "";
		/* $wpdb->get_var("SELECT coupon_code FROM `coupon_records` 
											  where user_id = '".$current_user->ID."' 
											  AND coupon_code = '".$result->couponCode."'
										");*/
		}
	 ?>
	<script>
    <?php // redirect social mediav 
	if( isset($_GET['z']) and !empty($_GET['z']) ){
		//$user_id = reverseUser($_GET['z']);?>
	createCookie('social_referal', '<?php echo $_GET['z'] ?>', 30);
	<?php } ?>
	function readCookie(e){for(var t=e+"=",r=document.cookie.split(";"),n=0;n<r.length;n++){for(var a=r[n];" "==a.charAt(0);)a=a.substring(1,a.length);if(0==a.indexOf(t))return a.substring(t.length,a.length)}return null}
	function createCookie(e,t,r){if(r){var n=new Date;n.setTime(n.getTime()+24*r*60*60*1e3);var a="; expires="+n.toGMTString()}else var a="";document.cookie=e+"="+t+a+"; path=/"}
	<?php if(($post->ID == 4019 and $current_user->ID != 0) and !empty($user_coupon)){?>
		jQuery('.free_member_coupon').remove();
	 <?php } ?>
    /*jQuery('body').on('click', '.added_to_cart', function(e) {
	 window.location = "<?php echo wc_get_cart_url() ?>";return false
    });*/
    </script>
    <style><?php if(($post->ID == 4019 and $current_user->ID != 0) and !empty($user_coupon)){?>.free_member_coupon{display:none;} <?php } ?></style>
<?php }

add_action('wp_footer', 'footer_js_script', 100);
function footer_js_script(){global $post, $current_user;;
	if(($post->ID == 11 and $current_user->ID == 0)){?>
	<script src="https://js.stripe.com/v3/"></script>  
    <script>
		jQuery(function(){
			if (jQuery(".uap-tos-wrap")[0]){
				jQuery('.uap-tos-wrap').before('<input type="text" style="display:none" value="" name="member_reg_fee" id="member_reg_fee" />\
				<div class="form-row" style="position: relative;">\
			<label for="card-element" class="uap-labels-register" ></label><br />\
			<div id="card-element">\
			  <!-- A Stripe Element will be inserted here. -->\
			</div>\
			<!-- Used to display form errors. -->\
			<div class="" id="card-errors" role="alert"></div>\
		  </div>');
				}
		jQuery('.required_member').after('<span class="fee_box"></span>');
		
		jQuery('.couponCode').after('<input type="button" value="Apply" data-type="signupType" id="check_coupon_code" class="button Couponbtn"><input type="hidden" name="offer" value="<?php echo $_GET['offer'] ?>" id="member_offer" /><div class="validationBox"></div>');
            
        jQuery('.memberCouponCode').after('<input type="button" value="Apply" data-type="memberType" id="check_member_coupon_code" class="button Couponbtn"><input type="hidden" name="meber_offer" value="" id="" /><div class="memberValidation"></div>');    
		
		calculate_total_fee(0);
		
		jQuery('body').on('click', '.Couponbtn', function(){
            var couponType = jQuery(this).data('type');
            if(couponType == 'signupType'){
                jQuery('#check_coupon_code').addClass('loaderchk');jQuery('.validationBox').html('');
            }else{
                jQuery('#check_member_coupon_code').addClass('loaderchk');jQuery('.memberValidation').html('');
            }
			var signupCode = jQuery('.couponCode').val();
			var memberCode = jQuery('.memberCouponCode').val(); 
            //console.log(couponType);
            //console.log(couponCode);
			var sel_member = jQuery('#uap_required_member_field').val();
			ApplyCouponCode('coupon', couponType, {signup:signupCode, member:memberCode, sel_member:sel_member, offer:'<?php echo $_GET['offer'] ?>'});
			});
		
		jQuery('#uap_required_member_field').change(function(e) {
            jQuery('.Couponbtn').each(function(i, e){
                jQuery(this).trigger('click');
            });
		});
		
		function ApplyCouponCode(ddr, type, code){
			var  code= JSON.stringify(code);
			console.log(code)
			<?php  $offer = base64_decode($_GET['offer']);
			   $user_id = str_replace('shwanzana', '', $offer);
			   $prices = affiliate_prices($user_id); ?>
            
			jQuery.ajax({
			  type: 'POST',
			  datatype:'JSON',
			  ContentType: "application/json", 
			  data: "action=check_posted_coupon_code&coupon_code="+code+"&coupon_type="+type,
			  url:"<?php echo admin_url(); ?>admin-ajax.php",
			  success: function(r) {var r = jQuery.parseJSON(r); console.log(r);
				  
				  <?php // For discount signup price will be zero for special offer then signup price will be category price;  ?> 
				  if(r.signup == 'true' && type == 'signupType'){
					  jQuery('.validationBox').html('<strong>Coupon Code is Correct.</strong>');
				  }else if(r.signup == 'false' && type == 'signupType'){
					  jQuery('.validationBox').html('<strong>Invalid Coupon Code.</strong>');
					  jQuery('.fee_box span:eq(1)').text('$'+jQuery('#member_reg_fee').val());
				  }
				  
				  if(r.member == 'true' && type == 'memberType'){
					  jQuery('.memberValidation').html('<strong>Coupon Code is Correct.</strong>');
				  }else if(r.member == 'false' && type == 'memberType'){
					  jQuery('.memberValidation').html('<strong>Invalid Coupon Code.</strong>');
					  jQuery('.fee_box span:eq(1)').text('$'+jQuery('#member_reg_fee').val());
				  }
				  
					jQuery('#member_reg_fee').val(r.price);
					  
					  jQuery('.fee_box span:eq(1)').text('$'+r.price);
					  
					jQuery('#check_coupon_code').removeClass('loaderchk');
					jQuery('#check_member_coupon_code').removeClass('loaderchk');
				  }
		});
			}
		
		function calculate_total_fee(val){
			//if(val > 0)
			{
				var member_fee = <?php echo member_fee ?> * val + <?php echo $prices['signup'] ?>;
				jQuery('#member_reg_fee').val(member_fee);
				jQuery('.fee_box').html('<span style="float: left;padding-left: 1em;width: 100%;text-align: left;">\
														<label><strong style="color: #000;">Total</strong></label>\
														<br>\
														<span style="color: #060;">$'+member_fee+'.00</span>\
													</span>');
				}
			//else{ jQuery('#fee_desc').html(''); }
			}
	  }) 
    </script>
    <style>div.uap-form-select{margin-bottom:3em !important;}.fee_box{font-family:'PT Sans',sans-serif;font-size:18px;}.loaderchk{background-image: url("<?php echo PLUGIN_URL_RY ?>/assets/loading.gif") !important;background-repeat: no-repeat !important;background-position: 18px !important;background-size: 18px !important;}</style>
<?php }
	else{?><style>#uap_required_member_field, .uap-labels-register, .couponCode,.memberCouponCode{display:none;}</style><script>jQuery(function(){ jQuery('#uap_required_member_field, .couponCode, .memberCouponCode').parent().css('display', 'none'); jQuery('#uap_required_member_field, .couponCode, .memberCouponCode').parent().remove(); });</script><?php }?>
  	<script>
	jQuery(function(){jQuery('form#uap_createuser').attr('novalidate', 'novalidate');});</script>
  <?php
	if($post->ID == 11 ){?>
<style>.StripeElement {background-color: white;height: 40px;padding: 10px 12px;border-radius: 20px;border: 1px solid rgba(0, 0, 0, 0.15);}.StripeElement--focus {box-shadow: 0 1px 3px 0 #cfd7df;}.StripeElement--invalid {border-color: #fa755a;}.StripeElement--webkit-autofill {background-color: #fefde5 !important;}#uap_couponCode_field.couponCode{width: 76% !important;border-top-right-radius: unset !important;float: left;border-bottom-right-radius: unset !important;border-right: unset;}input#check_coupon_code {padding-top: 5px !important;padding-bottom: 7px !important;border-top-right-radius: 20px;border-bottom-right-radius: 20px;width: 24%;text-align: center;padding-left:0px;padding-right:0px;}
#uap_memberCouponCode_field.memberCouponCode{width: 76% !important;border-top-right-radius: unset !important;float: left;border-bottom-right-radius: unset !important;border-right: unset;}input#check_member_coupon_code {padding-top: 5px !important;padding-bottom: 7px !important;border-top-right-radius: 20px;border-bottom-right-radius: 20px;width: 24%;text-align: center;padding-left:0px;padding-right:0px;}    
</style>
<script>
	setTimeout(function(){
		// Create a Stripe client.
		<?php 
		
		$keys = get_option('gravityformsaddon_gravityformsstripe_settings');
		 
		 if($keys['api_mode'] != 'live'){
			 $publishable_key = $keys['test_publishable_key']; 
			 }
		if($keys['api_mode'] == 'live'){
			 $publishable_key = $keys['live_publishable_key']; 
			 }
		 ?>
		var stripe = Stripe('<?php echo $publishable_key ?>');
		
		// Create an instance of Elements.
		var elements = stripe.elements();
		
		// Custom styling can be passed to options when creating an Element.
		// (Note that this demo uses a wider set of styles than the guide below.)
		var style = {
		  base: {
			color: '#32325d',
			lineHeight: '18px',
			fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
			fontSmoothing: 'antialiased',
			fontSize: '16px',
			'::placeholder': {
			  color: '#aab7c4'
			}
		  },
		  invalid: {
			color: '#fa755a',
			iconColor: '#fa755a'
		  }
		};
		
		// Create an instance of the card Element.
		var card = elements.create('card', {style: style});
		
		// Add an instance of the card Element into the `card-element` <div>.
		card.mount('#card-element');
		
		// Handle real-time validation errors from the card Element.
		card.addEventListener('change', function(event) {
			 
		  var displayError = document.getElementById('card-errors');
		  if (event.error) {
			displayError.textContent = event.error.message; 
			document.getElementById('card-errors').classList.add('uap-register-notice');
			
			var errorElement = document.getElementById('card-errors');
			errorElement.textContent = event.error.message;
			
			jQuery('#uap_submit_bttn').attr('disabled', 'disabled')
			//console.log('saleem-', event.error.message);
		  } else {
			document.getElementById('card-errors').classList.remove('uap-register-notice');  
			jQuery('#uap_submit_bttn').attr('disabled', 'disabled');
			
		  stripe.createToken(card).then(function(result) {
			if (result.error) {
				 
			  displayError.textContent = result.error.message; 
			  document.getElementById('card-errors').classList.add('uap-register-notice');
			  
			  var errorElement = document.getElementById('card-errors');
			  errorElement.textContent = result.error.message;
			  
			} else {
				jQuery('#uap_submit_bttn').removeAttr('disabled');
				displayError.textContent = ''; 
			  // Send the token to your server.
			  stripeTokenHandler(result.token);
			}
		  });
		
		  }
		});
		
		// Handle form submission.
		var form = document.getElementById('uap_createuser');
		form.addEventListener('submit', function(event) {/* alert(1234);return false;
		  event.preventDefault();
		
		  stripe.createToken(card).then(function(result) {
			if (result.error) {alert(1312312);return false;
			  // Inform the user if there was an error.
			  var errorElement = document.getElementById('card-errors');
			  errorElement.textContent = result.error.message;
			} else {
			  // Send the token to your server.
			  stripeTokenHandler(result.token);
			}
		  });
		*/});
		}, 2000)

	// Submit the form with the token ID.
	function stripeTokenHandler(token) { 
	  // Insert the token ID into the form so it gets submitted to the server
	  var form = document.getElementById('uap_createuser');
	  var hiddenInput = document.createElement('input');
	  hiddenInput.setAttribute('type', 'hidden');
	  hiddenInput.setAttribute('name', 'stripeToken');
	  hiddenInput.setAttribute('value', token.id);
	  //console.log('token id', token.id);
	  form.appendChild(hiddenInput);
	
	} 
	
</script>
    
<?php }
	}
	
/**
	* Payment add page
**/  
add_shortcode("add_staff_member_fee", "add_staff_member_fee"); 
function add_staff_member_fee( $atts ){ob_start();global $current_user;$user_id = $current_user->ID;
		  extract( shortcode_atts( array(
								  'fee' 	=> '$'.member_fee,
								  'id'		=> '7',
							  ), $atts ) ); 
							  
	if($current_user->roles[0] == 'subscriber' || $current_user->roles[0] == 'administrator'){ 
		$amount = doctor_paid_amount(array('user_id' => $current_user->ID));
		$used_amount = get_used_amount(array('user_id' => $current_user->ID));
		$amount = $amount - $used_amount;?>
        
		<div class="uap-wrapp-the-errors" style="background-color: #dff0d8;border: 1px solid green;">
        <div class="uap-register-error" style="color: #3c763d;">
            Currently your account balance is $<?php echo $amount > 0 ? number_format($amount) : 0; ?>
            </div> 
    </div>
        <br />
        <div class="add_fee">
        	<?php echo do_shortcode('[gravityform id=7 title=false description=false ajax=true]'); ?>
        </div>
	<?php } ?>
          
	<?php return ob_get_clean();
  }

add_shortcode("payment_error_shortcode", "payment_error_shortcode"); 
function payment_error_shortcode( $atts ){ob_start();global $current_user;$user_id = $current_user->ID;
		  extract( shortcode_atts( array(
								  'fee' 	=> '$'.member_fee,
								  'id'		=> '7',
							  ), $atts ) ); 
							  
	if(isset($_GET['message']) and $_GET['message'] == 'error_payment'){?>
        
		<div class="uap-wrapp-the-errors" style="background-color: #fff6f4;border-color: #f8cdcd;border: 1px solid #F0DBB4;">
        <div class="uap-register-error" style="color:#9b4449;    text-align: center;">
        	Your payment detail is not correct please try again.
        </div> 
    </div>
        
	<?php } ?>
          
	<?php return ob_get_clean();
  }
  
/**
	* Total amount which doctor paid to zana and return after subtract 150 signup fee
**/
function doctor_paid_amount($param = array()){global $wpdb, $current_user;
	
	$query = "SELECT amount, type FROM `payment_detail` where user_id = '".$current_user->ID."' ";
	$query_exe = $wpdb->get_row($query);
	$amount = $query_exe->amount > 0 ? $query_exe->amount : 0;
	
	$query = "SELECT entmt.meta_value FROM `".$wpdb->prefix."gf_entry` ent
					  inner join ".$wpdb->prefix."gf_entry_meta entmt
					  on (ent.form_id = entmt.form_id 
						  AND ent.id = entmt.entry_id 
						  AND entmt.meta_key = 'gform_product_info__')
					  where ent.form_id = 7
					  and created_by = '".$param['user_id']."'
					  group by ent.id";
	$sql_data = $wpdb->get_col($query);
	$sum = 0;
	if(!empty($sql_data)){
		foreach($sql_data as $col){
			$return = unserialize($col);
			foreach($return as $product => $value ){
				foreach($value as $val => $key){
					$sum = $sum + str_replace('$', '', $key['price']);
					}
				}
			
			}
		
		}
	
	if($query_exe->type == 'discount'){
		$tota = $sum + $amount ; 
		}
	elseif($query_exe->type == 'affiliate'){
		$signup_fee = get_user_meta($current_user->ID, 'signup_fee', true);
		$signup_fee = $signup_fee != '' ? $signup_fee : signup_fee;
		$tota = $sum + $amount - $signup_fee;  
		}
	else{
		  $signup_fee = get_user_meta($current_user->ID, 'signup_fee', true);
		  $signup_fee = $signup_fee != '' ? $signup_fee : signup_fee;
		  $tota = $sum + $amount - $signup_fee;  
		  }
	
	return	$tota;
	}

/**
 * Add the field to the checkout page
 */
add_action('woocommerce_before_order_notes', 'customise_checkout_field');
 
function customise_checkout_field($checkout){global $wpdb, $current_user;
	if($current_user->ID != 0){
	if($current_user->roles[0] == 'subscriber' || $current_user->roles[0] == 'administrator'){
		$query = "SELECT user_id FROM `".$wpdb->prefix."usermeta` where meta_key = 'clinic_user' and meta_value = '".$current_user->ID."' ";
		$array = array($current_user->ID => wp_user_name($current_user->ID));
		}
	else{
		$client_id = get_user_meta($current_user->ID, 'clinic_user', true);
		$query = "SELECT user_id FROM `".$wpdb->prefix."usermeta` where meta_key = 'clinic_user' and meta_value = '".$client_id."' ";
		$array = array($client_id => wp_user_name($client_id));
		}
	
	  $result = $wpdb->get_results($query); 
	  
	  if(!empty($query)){
			foreach($result as $data){ 
				$array[$data->user_id] = wp_user_name($data->user_id);
				}
            }
            
	echo '<div id="customise_checkout_field"><h3>' . __('Who made this sale?') . '</h3>';
	woocommerce_form_field( 'staff_list', array(
        'type'          => 'select',
        'class'         => array('staff_list form-row-wide'),
        'label'         => __('Select Staff member'),
        'required'    => true,
        'options'     => $array), $checkout->get_value( 'staff_list' ), 0);
	echo '</div>';
	}
	elseif( isset($_COOKIE['social_referal']) and !empty($_COOKIE['social_referal'])){
		$referal_user = reverseUser($_COOKIE['social_referal']);
		$clinic_user = get_user_by( 'ID', $referal_user ); 
		if ( $clinic_user === false ) {}
		else{
			$array[$referal_user] = wp_user_name($referal_user);
			echo '<div id="customise_checkout_field"><h3>' . __('Who made this sale?') . '</h3>';
			woocommerce_form_field( 'staff_list', array(
				'type'          => 'select',
				'class'         => array('staff_list form-row-wide'),
				'label'         => __('Select Staff member'),
				'required'    => true,
				'options'     => $array), $checkout->get_value( 'staff_list' ), 0);
			echo '</div>';
			}
		}
}

add_action( 'woocommerce_checkout_update_order_meta', 'saving_seller_staff_member');
function saving_seller_staff_member( $order_id ) {

    $staff_list = $_POST['staff_list'];
    if ( ! empty( $staff_list ) )
        update_post_meta( $order_id, 'seller_staff_member', sanitize_text_field( $staff_list ) );

}
function wp_user_name($user_id){
	$user = get_user_meta($user_id);
	$name = $user['first_name'][0].' '.$user['last_name'][0];
	if(str_replace(' ', '', $name) != ''){return $name;}
	else{$user = get_user_by('ID', $user_id); return $user->display_name;}
	}

// **************** NOT IN USE **************************//
 
/**
 * Add the order_comments field to the cart
 **/
// add_action('woocommerce_cart_collaterals', 'order_comments_custom_cart_field', 0);
function order_comments_custom_cart_field() {global $current_user, $wpdb;
	if($current_user->ID != 0 and $current_user->ID > 0){ ?>

<div class="zana_member" style="float: left;width: 50%;">
  <div class="cart_totals " style="float: left;width:100%;">
    <h2>Select Zana Member</h2>
<?php $query = "SELECT user_id FROM `".$wpdb->prefix."usermeta` where meta_key = 'clinic_user' and meta_value = '".$current_user->ID."' ";
	  $result = $wpdb->get_results($query); ?>    
    <table cellspacing="0" class="shop_table shop_table_responsive" style="border: none;">
      <tbody>
        <tr class="cart-subtotal">
          <th>Select Current Member: </th>
          <td data-title="Subtotal">
          	<select name="zana_member" id="zana_member">
            	<option value="<?php echo $current_user->ID ?>"><?php echo wp_user_name($current_user->ID); ?></option>
            <?php if(!empty($query)){
            		foreach($result as $data){ ?>    
                    	<option value="<?php echo $data->user_id ?>"><?php echo wp_user_name($data->user_id); ?></option>
                    <?php }
            } ?>
            </select>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
<?php
	  }
	}
	
/*
	* get user role
*/
function get_user_role($user_ID ){
	$user = new WP_User( $user_ID );
	if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
		foreach ( $user->roles as $role )
			return $role;
	}}	

function users_redirct_after_login(){
  return home_url('/dashboard/');
}
add_filter('login_redirect', 'users_redirct_after_login', 100);


add_action( 'login_form' , 'glue_login_redirect', 100 );
function glue_login_redirect() {
    global $redirect_to;
    if (!isset($_GET['redirect_to'])) {
        $redirect_to = home_url('/dashboard/');;
    }
    else{
        $redirect_to = $_GET['redirect_to'];
    }
}


function message_code($code){
	if($code == 1){return array('#46b450', '<strong>Password Uploaded Successfully.</strong>');}
	elseif($code == 2){return array('red', '<strong>Old password not matched try again.</strong>');}
	elseif($code == 3){return array('#46b450', '<strong>Record Uploaded Successfully.</strong>');}
	elseif($code == 4){return array('red', '<strong>Sorry, referred agent email address already exists.</strong>');}
	elseif($code == 5){return array('red', '<strong>Sorry, This user already exists.</strong>');}
	
	}

function return_query_string(){
	$query_param = array();
	if(isset($_GET)){
		foreach($_GET as $key => $value){
			$query_param[$key] = $value;
			}
		}
	return $query_param;
	}

/*
	* backend reporting
*/
add_action("admin_menu", "affiliate_reporting_menu", 100);
function affiliate_reporting_menu() {
   add_submenu_page("ultimate_affiliates_pro", "Imprint Info", "Imprint Info", 10, "imprint_info", "imprint_info_page"); 
   //add_submenu_page("ultimate_affiliates_pro", "Payment Email", "Payment Email", 10, "payment_email", "payment_email_page"); 
   add_submenu_page("ultimate_affiliates_pro", "Switch Affiliate", "Switch Affiliate", 10, "affiliate_switch", "affiliate_switch_page"); 
   add_submenu_page("ultimate_affiliates_pro", "Affiliate Category", "Affiliate Category", 10, "affiliate_category", "affiliate_category_page"); 
 }
function imprint_info_page(){include(PLUGIN_PATH.'/template-parts/imprint_info_page.php');}
//function payment_email_page(){include(PLUGIN_PATH.'/template-parts/payment_email_page.php');}
function affiliate_switch_page(){include(PLUGIN_PATH.'/template-parts/affiliate_switch.php');}
function affiliate_category_page(){include(PLUGIN_PATH.'/template-parts/affiliate_category.php');}

function coupon_codes_custom($coupon, $type = NULL){global $wpdb;
	$signup_code = $member_code = 'false';
	
	if(!empty($coupon['signup'])){
    	$signup = $wpdb->get_col("SELECT meta FROM `".$wpdb->prefix."gf_addon_feed` 
										where form_id = 7 
										and is_active = 1
                                        and id = 14
                                        and meta like '%".$coupon['signup']."%'
										");
		if(!empty($signup) and count($signup) > 0){
		foreach($signup as $result){
			$result = json_decode($result); 
			if($result->couponCode === $coupon['signup'] and strtotime($result->endDate) > time()){
				$signup_code = 'true';
				}
			}
		}
	}
	if(!empty($coupon['member'])){
		$member = $wpdb->get_col("SELECT meta FROM `".$wpdb->prefix."gf_addon_feed` 
										where form_id = 7 
										and is_active = 1
                                        and id = 19
                                        and meta like '%".$coupon['member']."%'
										");
		if(!empty($member) and count($member) > 0){
		foreach($member as $result){
			$result = json_decode($result); 
			if($result->couponCode === $coupon['member'] and strtotime($result->endDate) > time()){
				$member_code = 'true';
				}
			}
		}
	}
	
	$offer = base64_decode($coupon['offer']);
	$user_id = str_replace('shwanzana', '', $offer);
	$prices = affiliate_prices($user_id);
	
	$affiliate = check_affiliate_user($coupon['offer']);
	$signup_price = ($signup_code == 'true' OR $affiliate == 1) ? 0 : $prices['signup'];
	
	$total_price = member_fee  * $coupon['sel_member'] + $signup_price;
	$total_price = ($member_code == 'true' ) ? ($total_price - member_fee) : $total_price; // if member coupon is correct then subtract member price
	$total_price = $total_price > 0 ? $total_price : 0;
	
	return array('price' => $total_price, 'signup' => $signup_code, 'member' => $member_code);
	}

function admin_emial_imprint_info(){global $current_user;
	$url = site_url.'/?uap_act=admin_panel&amp;uid='.$current_user->ID;
	echo $html = '
		<html>
 <head>
     <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 </head>
 <body>
     Hello Zana Team<br /><br />

			'.$current_user->user_login.' has uploaded their imprint information. Please <a href="'.$url.'">click here to view</a><br /><br />
			
			Thanks
 </body>
 </html>
 ';
	$mail_info = get_option('wp_mail_smtp'); 
	$mail_from = $mail_info['mail']['from_email'];
	$from_name = $mail_info['mail']['from_name'];
	
	$headers[] = "From: $from_name <$mail_from>";
	$headers[] = 'Content-Type: text/html; charset=UTF-8'; 
	wp_mail('shawnzajas@gmail.com', 'New Imprint Information', $html, $headers);
	
	// shawnzajas@gmail.com

	}
	

//add_action( 'template_redirect', 'bbloomer_add_product_to_cart' );
function bbloomer_add_product_to_cart() {
          
$product_id = 581;
if ( WC()->cart->get_cart_contents_count() == 0 ) { 
	WC()->cart->add_to_cart( $product_id );
}
     
}

function uap_totoal_purchased($user_id){global $wpdb, $post;

	$id = $post->ID;//581; 
    $units_bought = $wpdb->get_var( "
        SELECT SUM(woim2.meta_value)
        FROM {$wpdb->prefix}woocommerce_order_items AS woi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim ON woi.order_item_id = woim.order_item_id
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim2 ON woi.order_item_id = woim2.order_item_id
        INNER JOIN {$wpdb->prefix}postmeta pm ON woi.order_id = pm.post_id
        INNER JOIN {$wpdb->prefix}posts AS p ON woi.order_id = p.ID
        WHERE woi.order_item_type LIKE 'line_item'
        AND p.post_type LIKE 'shop_order'
        AND p.post_status IN ('wc-completed','wc-processing')
        AND pm.meta_key = '_customer_user'
        AND pm.meta_value = '$user_id'
        AND woim.meta_key = '_product_id'
        AND woim.meta_value = '$id'
        AND woim2.meta_key = '_qty'
    ");
	return $units_bought == NULL ? 0 : $units_bought; 
	}

function load_staff_user_dentist(){global $indeed_db, $wpdb; $options = '';
	
	$sql = $wpdb->get_results("SELECT uap.affiliate_id FROM `".$wpdb->prefix."users` u 
									   INNER JOIN `".$wpdb->prefix."uap_affiliates` af 
										 ON (u.ID = af.uid) 
									   INNER JOIN ".$wpdb->prefix."uap_mlm_relations uap 
										 ON (af.id = uap.parent_affiliate_id)  
								   WHERE u.ID = '".$_POST['dentist']."' 
							");
	if(!empty($sql)){
		foreach($sql as $data){
			$staff_user = $indeed_db->get_uid_by_affiliate_id($data->affiliate_id);
			if(get_user_role($staff_user) != 'subscriber'){
				$display_name = $wpdb->get_var("SELECT u.display_name FROM `".$wpdb->prefix."uap_affiliates` af 
													   INNER JOIN `".$wpdb->prefix."users` u ON (u.ID = af.uid) 
													   WHERE af.id = '".$data->affiliate_id."' 
													  ");
				// Check user exists
				if(!empty($display_name)){
					$options .='<option value="'.$data->affiliate_id.'">'.$display_name.'</option>';
				}
			}
			}
		}
	echo $options;exit;
	die(0);}
add_action('wp_ajax_load_staff_user_dentist' , 'load_staff_user_dentist');
add_action('wp_ajax_nopriv_load_staff_user_dentist' , 'load_staff_user_dentist');

// The first part //
add_filter('author_rewrite_rules', 'no_author_base_rewrite_rules');
function no_author_base_rewrite_rules($author_rewrite) { 
    global $wpdb;
    $author_rewrite = array();
    $authors = $wpdb->get_results("SELECT user_nicename AS nicename from $wpdb->users");    
    foreach($authors as $author) {
        $author_rewrite["({$author->nicename})/page/?([0-9]+)/?$"] = 'index.php?author_name=$matches[1]&paged=$matches[2]';
        $author_rewrite["({$author->nicename})/?$"] = 'index.php?author_name=$matches[1]';
    }   
    return $author_rewrite;
}

// The second part //
add_filter('author_link', 'no_author_base', 1000, 2);
function no_author_base($link, $author_id) {
    $link_base = trailingslashit(get_option('home'));
    $link = preg_replace("|^{$link_base}author/|", '', $link);
    return $link_base . $link;
}

add_action('wp', 'current_page');
function current_page(){
	if(is_author()){
		 $author_id = get_query_var( 'author' ) ;
		 $affiliate = get_user_meta($author_id, 'affiliate_user', true); 
		 if($affiliate != 1){
			 echo '<script>window.location.href="'.site_url.'";</script>';exit;
			 }
	}
	}

add_shortcode("current_user_name", "current_user_name"); 
function current_user_name( $atts ){ob_start();global $current_user;$user_id = $current_user->ID;
		  $extract =  shortcode_atts( array(
								  'user_error_msg'		=> '',
							  ), $atts ) ; 
	$userID = get_query_var( 'author' ) ;
	echo wp_user_name($userID);
		           
	return ob_get_clean();
  }
 
add_shortcode("affiliate_user_link", "affiliate_user_link"); 
function affiliate_user_link( $atts ){ob_start();global $current_user;$user_id = $current_user->ID;
		  $extract =  shortcode_atts( array(
								  'user_error_msg'		=> '',
							  ), $atts ) ; 
	$userID = get_query_var( 'author' ) ;
	$user = get_user_by('ID', $userID);
	$param = array('go' => $user->user_login, 'shape' => 1);
	if(is_author()){
		 $param['offer'] = base64_encode('shwanzana'.$userID);
	}
	echo add_query_arg($param, site_url.'/join/');
          
	return ob_get_clean();
  }
  
add_action( 'show_user_profile', 'extra_user_profile_fields' );
add_action( 'edit_user_profile', 'extra_user_profile_fields' );

function extra_user_profile_fields( $user ) {global $wpdb;$affiliate = get_user_meta($user->ID, 'affiliate_user', true); ?>
    <h3><?php _e("Set Affiliate Promotion", "myzana"); ?></h3>

    <table class="form-table">
    <tr>
        <th><label for="affiliate_user"><?php _e("Set Affiliate"); ?></label></th>
        <td>
            <input <?php if($affiliate == 1){echo 'checked="checked"';} ?> type="checkbox" name="affiliate_user" id="affiliate_user" value="0" class="regular-text" /><br /><span class="description"><?php _e("if its checked then affiliate user page will open if not checked then redirect to home page."); ?></span>
        </td>
    </tr>
    <tr>
        <th><label for="affiliate_category"><?php _e("Affiliate Category"); ?></label></th>
        <td>
        <?php 
			$affiliate_category = get_user_meta($user->ID, 'affiliate_category', true);
			$query = $wpdb->get_results("select * from affiliate_category order by id desc "); ?>
            <select id="affiliate_category" name="affiliate_category" style="min-width:200px;">
               <option value="">Select Category Type</option> 
        <?php if(!empty($query)){$options = ''; $sel = 'selected';
				foreach($query as $data){
					$selected = $data->id == $affiliate_category ? $sel : '';
					$options .='<option '.$selected.' value="'.$data->id.'">'.$data->name.'</option> ';
					}
				echo $options;
			} ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="my_sale_product"><?php _e("Select Product"); ?></label></th>
        <td>
        <?php 
			$my_sale_product = get_user_meta($user->ID, 'my_sale_product', true);
			$query = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->prefix."posts where post_type = 'product' AND post_status = 'publish' ");?>
            <select id="my_sale_product" name="my_sale_product" style="min-width:200px;">
               <option value="">Select Sale Product</option> 
        <?php if(!empty($query)){$options = ''; $sel = 'selected';
				foreach($query as $data){
					$selected = $data->ID == $my_sale_product ? $sel : '';
					$options .='<option '.$selected.' value="'.$data->ID.'">'.$data->post_title.'</option> ';
					}
				echo $options;
			} ?>
            </select>
        </td>
    </tr>
    </table>
<?php }

add_action( 'personal_options_update', 'save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_extra_user_profile_fields' );

function save_extra_user_profile_fields( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) { 
        return false; 
    }
	update_user_meta( $user_id, 'affiliate_category', $_POST['affiliate_category'] );
	update_user_meta( $user_id, 'my_sale_product', $_POST['my_sale_product'] );
	if(isset($_POST['affiliate_user'])){
		update_user_meta( $user_id, 'affiliate_user', 1 );
		}else{update_user_meta( $user_id, 'affiliate_user', 0 );}
    
}

add_shortcode("affiliate_user_media", "affiliate_user_media"); 
function affiliate_user_media( $atts ){ob_start();global $current_user;$user_id = $current_user->ID;
		  $extract =  shortcode_atts( array(
								  'user_error_msg'		=> '',
							  ), $atts ) ; 
	$userID = get_query_var( 'author' );
	$affiliate_media = get_user_meta($userID, 'affiliate_media', true); 
	$default_media = '<iframe src="https://player.vimeo.com/video/326888399" width="320" height="558" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>';
	
	if($affiliate_media['type'] == 'Video' ){
		if($affiliate_media['video_type'] == 'vimeo'){
			echo '<iframe src="'.$affiliate_media['vimeo_url'].'" width="100%" height="558" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>';
			}
		elseif($affiliate_media['video_type'] == 'youtube'){
			$video_id = get_youtube_video_ID($affiliate_media['youtube_url']);
			echo '<iframe width="100%" height="558" src="https://www.youtube.com/embed/'.$video_id.'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
			
			}
		else{echo $default_media;}
		}
	elseif($affiliate_media['type'] == 'Image' and $affiliate_media['image']){
		$post_thumbnail_url = wp_get_attachment_url( $affiliate_media['image'] );
		if(!empty($post_thumbnail_url)){
			echo '<img class="fl-photo-img wp-image-4902 size-full" src="'.$post_thumbnail_url.'" itemprop="image" >';
			}
		
	}else{
		echo $default_media;
		}
	return ob_get_clean();
  }

function media_upload(){
	if ($_FILES) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' ); 

        $files = $_FILES['upload_attachment'];
        $count = 0; 
		foreach ($files['name'] as $count => $value){
        
            if ($files['name'][$count]){ 
			
                $file = array(
                    'name'     => $files['name'][$count],
                    'type'     => $files['type'][$count],
                    'tmp_name' => $files['tmp_name'][$count],
                    'error'    => $files['error'][$count],
                    'size'     => $files['size'][$count]
                );
				$upload_overrides = array( 'test_form' => false );
                $upload = wp_handle_upload($file, $upload_overrides);
                // $filename should be the path to a file in the upload directory.
                $filename = $upload['file']; 

                // The ID of the post this attachment is for.
                //$parent_post_id = $post_id;
				
                // Check the type of tile. We'll use this as the 'post_mime_type'.
                $filetype = wp_check_filetype( basename( $filename ), null ); 

                // Get the path to the upload directory.
                $wp_upload_dir = wp_upload_dir();

                // Prepare an array of post data for the attachment.
                $attachment = array(
                    'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ), 
                    'post_status'    => 'inherit'
                );

                // Insert the attachment.
                $attach_id = wp_insert_attachment( $attachment, $filename ); 

                // Generate the metadata for the attachment, and update the database record.
                $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                wp_update_attachment_metadata( $attach_id, $attach_data );
				return $attach_id;	
            }
        }
  		}
	}
	
function check_affiliate_user($offer){
	$offer = base64_decode($offer);
	$user_id = str_replace('shwanzana', '', $offer);
	return $affiliate = get_user_meta($user_id, 'affiliate_user', true); 
	}

function get_youtube_video_ID($youtube_video_url) { 
  parse_str( parse_url( $youtube_video_url, PHP_URL_QUERY ), $my_array_of_vars );
  return $my_array_of_vars['v'];  
}

function affiliate_prices($user_id){global $wpdb;$affiliate_user = '';
	if(!empty($user_id)){
		$affiliate_category = get_user_meta($user_id, 'affiliate_category', true);
		$row = $wpdb->get_row("select name, signup_price, member_price from affiliate_category where id = '".$affiliate_category."' "); 
		$default_price = get_option('default_price');
		if(!empty($row)){
			$signup_price = $row->signup_price; $member_price = $row->member_price; $affiliate_user = 'true';
			}
			else{
				$signup_price = $default_price['default_signup']; $member_price = $default_price['default_member'];	
				}
		}else{ $default_price = get_option('default_price'); $signup_price = $default_price['default_signup']; $member_price = $default_price['default_member'];	}
	
	
		return array('signup' => $signup_price, 'member' => $member_price, 'affiliate' => $affiliate_user );
	}

function reffer_affiliate_prices($user_id){global $wpdb;$affiliate_user = '';
	if(!empty($user_id)){
		$affiliate_category = get_user_meta($user_id, 'reffer_affiliate_category', true);
		if(empty($affiliate_category)){
			$affiliate_category = get_user_meta($user_id, 'affiliate_category', true);
			}
		$row = $wpdb->get_row("select name, signup_price, member_price from affiliate_category where id = '".$affiliate_category."' ");
		$default_price = get_option('default_price');
		if(!empty($row)){
			$signup_price = $row->signup_price; $member_price = $row->member_price; $affiliate_user = 'true';
			}
			else{
			$signup_price = $default_price['default_signup']; $member_price = $default_price['default_member'];	
				}
		}else{ $default_price = get_option('default_price'); $signup_price = $default_price['default_signup']; $member_price = $default_price['default_member']; }
	
	
		return array('signup' => $signup_price, 'member' => $member_price, 'affiliate' => $affiliate_user );
	}
	
add_filter ('woocommerce_payment_complete', 'zana_send_seller_email'); 
function zana_send_seller_email( $order ) {
	ob_start();
	include 'template-parts/order.html';
	$email_temp = ob_get_flush();
	
    $data = array();
	$order = new WC_Order($order);
	//echo '<pre>'; print_r($order); echo '</pre>'; exit('Good-Smile');
    // Loop through each order item
    foreach ( $order->get_items() as $item_id => $item ) {
		 $order_id = $order->get_id();
		 $oder_meta = get_post_meta( $order_id );
		 
        if( $oder_meta['_sent_to_seller_'.$item_id][0] )
            continue; // Go to next loop iteration

        $product_id = $item->get_product_id();
		
        // Set the data in an array (avoiding seller email repetitions)
        $data[$product_id][] = array(
            'title'    => get_the_title($product_id),
			'price'	  => $oder_meta['_order_total'][0],
        );
        // Update order to avoid notification repetitions
        update_post_meta( $order_id, '_sent_to_seller_'.$item_id, true );
		$item_quantity = $order->get_item_meta($item_id, '_qty', true); 
    } 

    if( count($data) == 0 ) return;
	
	$seller_id = $oder_meta['seller_staff_member'][0];
	$clinic_user_id = get_user_meta( $seller_id, 'clinic_user', true );
	
	$clinic_user = get_user_by( 'ID', $clinic_user_id );
	
	$clinic_user_name = $seller_info = '';
	
	if ( $clinic_user === false ) {
		//user id does not exist
	} else {
		//$clinic_user_name = 'Practice Name: <strong>'.wp_user_name($clinic_user_id).'</strong><br />';
		$seller_info .= '<tr>
							<th class="m_-176182753551286847td" scope="row" colspan="2" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px">Practice Name:</th>
							<td class="m_-176182753551286847td" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px"><span class="m_-176182753551286847woocommerce-Price-amount m_-176182753551286847amount"><span class="m_-176182753551286847woocommerce-Price-currencySymbol">'.wp_user_name($clinic_user_id).'</span></span></td>
						  </tr>';
	}
	
	if(!empty($seller_id)){
		$seller_info .= '<tr>
							<th class="m_-176182753551286847td" scope="row" colspan="2" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px">Seller Name:</th>
							<td class="m_-176182753551286847td" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px"><span class="m_-176182753551286847woocommerce-Price-amount m_-176182753551286847amount"><span class="m_-176182753551286847woocommerce-Price-currencySymbol">'.wp_user_name($seller_id).'</span></span></td>
						  </tr>';
		}
	else{
		$fname = $oder_meta['_billing_first_name'][0];
		$lname = $oder_meta['_billing_last_name'][0];
		$company = $oder_meta['_billing_company'][0];
		$Email = $oder_meta['_billing_email'][0];
		
		$seller_info .= '<tr>
							<th class="m_-176182753551286847td" scope="row" colspan="2" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px">Seller Name:</th>
							<td class="m_-176182753551286847td" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px"><span class="m_-176182753551286847woocommerce-Price-amount m_-176182753551286847amount"><span class="m_-176182753551286847woocommerce-Price-currencySymbol">'.$fname .' '. $lname.'</span></span></td>
						  </tr>';
		$seller_info .= '<tr>
							<th class="m_-176182753551286847td" scope="row" colspan="2" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px">Email:</th>
							<td class="m_-176182753551286847td" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px"><span class="m_-176182753551286847woocommerce-Price-amount m_-176182753551286847amount"><span class="m_-176182753551286847woocommerce-Price-currencySymbol">'.$Email.'</span></span></td>
						  </tr>';
		$seller_info .= '<tr>
							<th class="m_-176182753551286847td" scope="row" colspan="2" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px">Company:</th>
							<td class="m_-176182753551286847td" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px;text-align:left;border-top-width:4px"><span class="m_-176182753551286847woocommerce-Price-amount m_-176182753551286847amount"><span class="m_-176182753551286847woocommerce-Price-currencySymbol">'.$company.'</span></span></td>
						  </tr>';
		}
	
	$date = date('F d, Y h:i A', strtotime($oder_meta['_paid_date'][0]));
	$quantity = $item_quantity;
	
	// $message = $clinic_user_name . $seller_info . $date . $quantity.'<br />';
	
    // Loop through custom data array to send mails to sellers
    foreach ( $data as $email_key => $values ) {
        $to = $email_key;
        $subject_arr = array();
        
        foreach ( $values as $value ) {
            $subject_arr[] = $value['title'];
            $product_detail  = '<td class="m_-176182753551286847td" style="color:#636363;border:1px solid #e5e5e5;padding:12px;text-align:left;vertical-align:middle;font-family:\'Helvetica Neue\',Helvetica,Roboto,Arial,sans-serif;word-wrap:break-word"> '.$value['title'].' </td>
                                  <td class="m_-176182753551286847td" style="color:#636363;border:1px solid #e5e5e5;padding:12px;text-align:left;vertical-align:middle;font-family:\'Helvetica Neue\',Helvetica,Roboto,Arial,sans-serif"> '.$quantity.' </td>
                                  <td class="m_-176182753551286847td" style="color:#636363;border:1px solid #e5e5e5;padding:12px;text-align:left;vertical-align:middle;font-family:\'Helvetica Neue\',Helvetica,Roboto,Arial,sans-serif"><span class="m_-176182753551286847woocommerce-Price-amount m_-176182753551286847amount"><span class="m_-176182753551286847woocommerce-Price-currencySymbol">'.$value['price'].'</span></span></td>';
        }
		
        $subject = 'Sold! ship now: '.implode( ', ', $subject_arr ); 
        
    }
	
	$email_temp = str_replace('{seller_detail}', $seller_info, $email_temp);
	$email_temp = str_replace('{order_id}', $order_id, $email_temp);
	$email_temp = str_replace('{date}', $date, $email_temp);
	$email_temp = str_replace('{site_url}', site_url, $email_temp);
	$email_temp = str_replace('{product_detail}', $product_detail, $email_temp);
	
	//echo $email_temp; exit('ssssssssssssssssssssssssssssss'); // orders@myzana.com 
	
	if (isset($_COOKIE['social_referal'])) {
		  unset($_COOKIE['social_referal']);
		  setcookie('social_referal', '', time() - 3600, '/'); // empty value and old timestamp
		}
		
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	wp_mail( 'orders@myzana.com', $subject, $email_temp, $headers );
}

//add_action('woocommerce_new_order', 'new_order_seller_notification', 10, 1 );
function new_order_seller_notification( $order_id ) {
    $order = wc_get_order( $order_id );

    if( ! ( $order->has_status('processing') || $order->has_status('completed') ) )
        return; // Exit
    zana_send_seller_email( $order );
}

//add_action( 'woocommerce_order_status_changed', 'order_status_seller_notification', 20, 4 );
function order_status_seller_notification( $order_id, $status_from, $status_to, $order ) {

    if( ! ( $status_to == 'processing' || $status_to == 'completed' ) )
        return; // Exit
    zana_send_seller_email( $order );
}

function assign_member_fee(){
	$user_id = get_current_user_id();
	if((empty($user_id) || $user_id == 0)){
			$offer = isset($_GET['offer']) ? $_GET['offer'] : '';
		   $offer = base64_decode($offer);
		   $user_id = str_replace('shwanzana', '', $offer);
		   $prices = affiliate_prices($user_id);
		}
	else{
		$prices = reffer_affiliate_prices($user_id);
		}
		return $prices['member'];
	}
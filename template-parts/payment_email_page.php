<div id="wrap"><?php global $wpdb, $indeed_db; ?>
  <h2>Users Payment Emails</h2>
  <table class="wp-list-table widefat " width="100%">
    <thead>
      <tr>
        <th scope="col" class="manage-column column-username show_user_name" ><strong>Name</strong></th>
        <th scope="col" class="manage-column column-username show_user_email" ><strong>Paypal Payment Email</strong></th>
        <th scope="col" class="manage-column column-username show_uniCourse"  ><strong>Phone</strong></th>
        <th scope="col" class="manage-column column-username show_act_name" ><strong>Position</strong></th>
        <th scope="col" class="manage-column column-username show_act_name" ><strong>Earning</strong></th>
        <th scope="col" class="manage-column column-username show_act_name" ><strong>Action</strong></th>
      </tr>
    </thead>
    <tbody id="the-list" data-wp-lists="list:user" >
      <?php $sql = $wpdb->get_results("SELECT user_id, meta_value,af.id af_id FROM `".$wpdb->prefix."users` u
											  inner join ".$wpdb->prefix."usermeta um
											  on (u.ID = um.user_id and meta_key = 'payment_detail')
											  inner join ".$wpdb->prefix."uap_affiliates af
											  on (u.ID = af.uid)
											  order by display_name desc
											"); 					
			if($sql and count($sql) > 0) { 
			  $uap_currency = get_option('uap_currency');
	  		  foreach($sql as $row){ 
			  	$name = wp_user_name($row->user_id);
				$user = get_user_by( 'id', $row->user_id ); 
				$data['stats'] = $indeed_db->get_stats_for_payments($row->af_id); 
				$data['stats']['currency'] = $uap_currency;
          		$earning = uap_format_price_and_currency($data['stats']['currency'], round($data['stats']['paid_payments_value']+$data['stats']['unpaid_payments_value'], 2));
		  
				if(!empty($user)){
					$user_detail = get_user_meta($row->user_id, 'payment_detail', true);
					$payment_detail = unserialize($user_detail);
					$payment_email = !empty($payment_detail['payment_email']) ? $payment_detail['payment_email'] : ''; 
					if($user->roles[0] == 'subscriber'){$position = 'Clinic'; }elseif($user->roles[0] == 'author'){$position = 'Staff'; } ?>
      <tr class="alternate">
        <td class="manage-column column-username show_user_name" ><?php echo $name; ?></td>
        <td class="manage-column column-username show_user_name" ><?php echo $payment_email; ?></td>
        <td class="manage-column column-username show_uniCourse"><?php echo get_user_meta($row->user_id, 'phone', true);; ?></td>
        <td class="manage-column column-username show_uniCourse"><?php echo $position; ?></td>
        <td class="manage-column column-username show_uniCourse"><?php echo $earning; ?></td>
        <td class="manage-column column-username show_uniCourse"><input type="button" value="Pay" class="button button-large button-primary"  /></td>
      </tr>
      <?php }	}
	   		}
	   		else{?>
      <tr>
        <th colspan="10"> No Result Found. </th>
      </tr>
      <?php }?>
      <tr>
        <th colspan="10"> <?php include('paging.inc_1.php');   ?>
        </th>
      </tr>
    </tbody>
  </table>
</div>

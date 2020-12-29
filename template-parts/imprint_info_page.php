<?php if(isset($_GET['uid']) and $_GET['uid'] > 0){ ?>
	<style>#profile<?php echo $_GET['uid'] ?>{background-color:#CFF;}</style>	
<?php } ?>
<div id="wrap"><?php global $wpdb; ?>
  <h2>Affiliate Users Imprint Informations</h2>
  <table class="wp-list-table widefat " width="100%">
    <thead>
      <tr>
        <th scope="col" class="manage-column column-username show_user_name" ><strong>Name</strong></th>
        <th scope="col" class="manage-column column-username show_user_email" ><strong>User Email</strong></th>
        <th scope="col" class="manage-column column-username show_user_email" ><strong>Practice Name</strong></th>
        <th scope="col" class="manage-column column-username show_uniCourse"  ><strong>Website</strong></th>
        <th scope="col" class="manage-column column-username show_uniCourse"  ><strong>Phone</strong></th>
        <th scope="col" class="manage-column column-username show_act_name" ><strong>Position</strong></th>
        <th scope="col" class="manage-column column-username show_act_name" ><strong>Last Update</strong></th>
      </tr>
    </thead>
    <tbody id="the-list" data-wp-lists="list:user" >
      <?php $sql = $wpdb->get_results("SELECT user_id, meta_value FROM `".$wpdb->prefix."users` u
											  inner join ".$wpdb->prefix."usermeta um
											  on (u.ID = um.user_id and meta_key = 'imprint_detail')
											"); 
			if($sql and count($sql) > 0) { 
	  		  foreach($sql as $row){ 
			  	$name = wp_user_name($row->user_id);
				$user = get_user_by( 'id', $row->user_id ); 
				if(!empty($user)){
					$imprint_detail = get_user_meta($row->user_id, 'imprint_detail', true);
					$imprint_detail = unserialize($imprint_detail);
					$practice_name = !empty($imprint_detail['practice_name']) ? $imprint_detail['practice_name'] : '';
					$website = !empty($imprint_detail['website']) ? $imprint_detail['website'] : '';
					$phone = !empty($imprint_detail['phone']) ? $imprint_detail['phone'] : '';
					if($user->roles[0] == 'subscriber'){$position = 'Clinic'; }elseif($user->roles[0] == 'author'){$position = 'Staff'; } ?>
      <tr class="alternate" id="profile<?php echo $row->user_id ?>">
        <td class="manage-column column-username show_user_name" ><?php echo $name; ?></td>
        <td class="manage-column column-username show_user_name" ><?php echo $user->user_email; ?></td>
        <td class="manage-column column-username show_user_email"><?php echo $practice_name ?></td>
        <td class="manage-column column-username show_user_email"><?php echo $website ?></td>
        <td class="manage-column column-username show_uniCourse"><?php echo $phone; ?></td>
        <td class="manage-column column-username show_uniCourse"><?php echo $position; ?></td>
        <td class="manage-column column-username show_uniCourse"><?php echo date('F d, Y', strtotime($imprint_detail['date'])); ?></td>
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
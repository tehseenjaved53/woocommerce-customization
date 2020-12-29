<?php 
global $wpdb; 
if(isset($_GET['btn_save']) and $_GET['btn_save'] == 'Save' and !empty($_GET['to_dentist']) ){ global $indeed_db;
	
	$dentist_affit = $indeed_db->get_affiliate_id_by_wpuid($_GET['from_dentist']);
	$staff_affit = $_GET['from_staff'];
	$staff_user_frm = $indeed_db->get_uid_by_affiliate_id($staff_affit); // get staff user id for deleting the clint id which we are using for denties
	delete_user_meta($staff_user_frm, 'clinic_user', $_GET['from_dentist']); // remove the meta clinic 
	
	// all row from mlm 
	$mlm_row = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."uap_mlm_relations 
										WHERE affiliate_id = '".$staff_affit."' 
											  AND parent_affiliate_id = '".$dentist_affit."'
										  ");
	
	// Insert all data before deletion to dummy mlm table which is not use only
	// for safty purpose if we face any issue we will import same data.
	$wpdb->query("INSERT INTO `uap_dummy_mlm` (`mlm_id`, `affiliate_id`, `parent_affiliate_id`) 
									   VALUES ('".$mlm_row->id."', '".$mlm_row->affiliate_id."', '".$mlm_row->parent_affiliate_id."');
									 ");
	
	/* For insertion new mlm */
	$to_dentist = $indeed_db->get_affiliate_id_by_wpuid($_GET['to_dentist']);
	$to_staff = $staff_affit; // $indeed_db->get_affiliate_id_by_wpuid($_GET['to_staff']);
	
	$wpdb->query("INSERT INTO ".$wpdb->prefix."uap_mlm_relations (`affiliate_id`, `parent_affiliate_id`) 
									   VALUES ('".$to_staff."', '".$to_dentist."')
									 ");
									 
	$wpdb->query("DELETE FROM ".$wpdb->prefix."uap_mlm_relations WHERE id = '".$mlm_row->id."' ");
	
	update_user_meta($staff_user_frm, 'clinic_user', $_GET['to_dentist']); // update the staff member in dentiest
	echo '<script>window.location.href="'.admin_url('/admin.php?page=affiliate_switch&message=2').'";</script>';exit;
	
	}
	 
  $client_user = get_users( 'orderby=nicename&role=subscriber' );
  //$staff_user = get_users( 'orderby=nicename&role=author' );
  
  $denties_list = $staff_list = '';
  foreach ( $client_user as $dentist ) {
	  $denties_list .= '<option value="'.$dentist->ID.'">'.$dentist->display_name.'</option>';
  }
  /*foreach ( $staff_user as $staff ) {
	  $staff_list .= '<option value="'.$staff->ID.'">'.$staff->display_name.'</option>';
  }*/
   ?>
<style>td.manage-column.column-username.show_user_name {border: none;}.loaderchk{background-image: url("<?php echo PLUGIN_URL_RY ?>/assets/loading.gif") !important;background-repeat: no-repeat !important;background-position:18px !important;background-size:18px !important;width:50px;height:32px;float: right;}</style>
  <div id="wrap">
	<h2>Switch Affiliate Users</h2>
  <?php if(isset($_GET['message']) and $_GET['message'] == 2){ ?>
  	<div id="message" class="updated" style="margin: 2em 0;">
    	<p><strong>Staff member switeched successfully.</strong></p>
    </div>
  <?php } ?>
	<form name="frm" action="" method="get">
    	<input type="hidden" name="page" value="affiliate_switch" />
    	<table class="wp-list-table widefat " width="100%"> 
        <thead>
          <tr>
            <td colspan="2" scope="col" class="manage-column column-username show_user_name" ><strong style="border-bottom:1px solid #ccc">From Dentist:</strong></td> 
          </tr>
          
          <tr>
            <td colspan="2" scope="col" class="manage-column column-username show_user_name" >
            	<label style="float:left" for="dentist_user">List of Dentist &nbsp;
                	<select id="from_dentist" name="from_dentist">
                            <option value="">Select Dentist</option>
                      <?php echo $denties_list ?>    
                      </select>
                      <span id="check_coupon_code"></span>
                </label>&nbsp;&nbsp;&nbsp;
                
                <label for="staff_user">List of Staff &nbsp;
                	<select id="from_staff" name="from_staff" style="min-width:200px;">
                        <option value="">Select Staff</option> 
                    </select>
                </label>
            </td>
            
          </tr>
          
          <tr><td colspan="2"></td></tr>
          
          <tr>
            <td colspan="2" scope="col" class="manage-column column-username show_user_name" ><strong style="border-bottom:1px solid #ccc">To Dentist:</strong></td> 
          </tr>
          
          <tr>
            <td colspan="2" scope="col" class="manage-column column-username show_user_name" >
            	<label for="to_dentist">List of Dentist &nbsp;<select name="to_dentist">
                    <option value="">Select Dentist</option>
                <?php echo $denties_list ?>    
                </select></label>&nbsp;&nbsp;&nbsp;
                <small style="width:100%;float:left">Above(From staff member) staff member will move to bottom(To dentist) dentist.</small>
                
                <?php /*?><label for="to_staff">List of Staff &nbsp;<select name="to_staff">
                    <option value="">Select Staff</option>
                <?php echo $staff_list ?>    
                </select></label><?php */?>
            </td>
            
          </tr>
          
          <tr>
            <td colspan="2" scope="col" class="manage-column column-username " >
                <input type="submit" name="btn_save" value="Save" class="button button-primary" />
            </td>
          </tr>
          
        </thead>
        
      </table>
    </form>
  </div> 
  
 <script>
 	jQuery(function(){
		jQuery('#from_dentist').change(function(){
			jQuery('#check_coupon_code').addClass('loaderchk');jQuery('#from_staff').html('');
			var dentist = jQuery(this).val();
			jQuery.ajax({
			  type: 'POST',
			  datatype:'json',
			  data: "action=load_staff_user_dentist&dentist="+dentist,
			  url:"<?php echo admin_url(); ?>admin-ajax.php",
			  success: function(r) {
				  jQuery('#from_staff').html(r);
				  jQuery('#check_coupon_code').removeClass('loaderchk');
				  }
		});
			})
		})
 </script>
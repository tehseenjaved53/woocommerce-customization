<?php 
global $wpdb; 
define( 'admin_url', admin_url('/admin.php?page=affiliate_category') );  
if(isset($_POST['btn_save']) and $_POST['btn_save'] == 'Save' ){
	
	$wpdb->insert( 
				  'affiliate_category', 
						array(
							  'name'				=> $_POST['category_name'], 
							  'signup_price'		=> $_POST['signup_price'], 
							  'member_price' 		=> $_POST['member_price'], 
							  )
					  ); 
	
	echo '<script>window.location.href="'.admin_url.'&message=2'.'";</script>';exit;
	
	}
	
if(isset($_POST['btn_default']) and $_POST['btn_default'] == 'Save' ){
	unset($_POST['btn_default']);unset($_POST['page']);
	
	$default_price = update_option('default_price', $_POST); 
	
	echo '<script>window.location.href="'.admin_url.'&message=2'.'";</script>';exit;
	
	}
		
if(isset($_POST['btn_update']) and $_POST['btn_update'] == 'Update' ){
	
	$wpdb->query("UPDATE `affiliate_category` SET 
						  `name` 		 = '".$_POST['category_name']."', 
						  `signup_price` = '".$_POST['signup_price']."', 
						  `member_price` = '".$_POST['member_price']."' 
				  WHERE `id` = '".$_GET['edit']."'
				");
	
	echo '<script>window.location.href="'.admin_url.'&message=2'.'";</script>';exit;
	
	}
if(isset($_GET['delete']) and is_numeric($_GET['delete']) ){
	
	$wpdb->query("Delete From `affiliate_category` WHERE `id` = '".$_GET['delete']."' ");
	
	echo '<script>window.location.href="'.admin_url.'&message=2'.'";</script>';exit;
	
	}	?>
  
<style>td.manage-column.column-username.show_user_name {border: none;}.loaderchk{background-image: url("<?php echo PLUGIN_URL_RY ?>/assets/loading.gif") !important;background-repeat: no-repeat !important;background-position:18px !important;background-size:18px !important;width:50px;height:32px;float: right;}</style>
  <div id="wrap">
	<h2>Affiliate Price Categories</h2>
  <?php if(isset($_GET['message']) and $_GET['message'] == 2){ ?>
  	<div id="message" class="updated" style="margin: 2em 0;">
    	<p><strong>Record updated successfully.</strong></p>
    </div>
  <?php } ?>
	
    <form name="frm" action="" method="post">
    	<input type="hidden" name="page" value="affiliate_category" />
    	<table class="wp-list-table widefat " width="100%"> 
        <thead>
          <tr>
            <th colspan="3" scope="col" class=" " > <h3>Default Prices</h3> </th> 
          </tr> 
           
          <tr>
            <td colspan="3" scope="col" class="manage-column column-username show_user_name" >
            <?php $default_price = get_option('default_price');  ?> 
            	  <h3></h3>
                <label for="default_signup">Signup Price: &nbsp;
               	  <input type="number" id="default_signup" name="default_signup" value="<?php echo $default_price['default_signup'] ?>" />
                </label>
                
                <label for="default_member">&nbsp;&nbsp;&nbsp;Member Price: 
               	  <input type="number" id="default_member" name="default_member" value="<?php echo $default_price['default_member'] ?>" />
                </label>
                &nbsp;&nbsp;&nbsp;
                <input type="submit" name="btn_default" value="Save" class="button button-primary" />
            </td>
            
          </tr>
          
          <tr><td colspan="3"></td></tr>
           
        </thead>
        
      </table>
    </form>
    
    <br /><br /><br />
    
    <form name="frm" action="" method="post">
    	<input type="hidden" name="page" value="affiliate_category" />
    	<table class="wp-list-table widefat " width="100%"> 
        <thead>
          <tr>
            <th colspan="3" scope="col" class=" " > <h3>Add Category Price</h3> </th> 
          </tr>
          <?php if(isset($_GET['edit']) and is_numeric($_GET['edit'])){
			  	$row = $wpdb->get_row("select name, signup_price, member_price from affiliate_category where id = '".$_GET['edit']."' ");
				$name = $row->name; $signup_price = $row->signup_price; $member_price = $row->member_price;
			  }else{$member_price = $signup_price = ''; } ?>
           
           
          
          <tr>
            <td colspan="2" scope="col" class="manage-column column-username show_user_name" ><h3></h3>
            	<label style="float:left" for="category_name">Category Name: &nbsp;
               	  <input type="text" name="category_name" id="category_name" value="<?php echo $name ?>"  /> 
                </label>&nbsp;&nbsp;&nbsp;
                
                <label for="signup_price">Signup Price: &nbsp;
               	  <input type="number" id="signup_price" name="signup_price" value="<?php echo $signup_price ?>" />
                </label>
                
                <label for="member_price">&nbsp;&nbsp;&nbsp;Member Price: 
               	  <input type="number" id="member_price" name="member_price" value="<?php echo $member_price ?>" />
                </label>&nbsp;&nbsp;&nbsp;
                
                <?php if(isset($_GET['edit']) and is_numeric($_GET['edit'])){ ?>
                	<input type="submit" name="btn_update" value="Update" class="button button-primary" />
                <?php }else{ ?>
               	  <input type="submit" name="btn_save" value="Save" class="button button-primary" />
               <?php } ?>
            </td>
            
          </tr>
          
          <tr><td colspan="2"></td></tr>
          
          <tr>
            <td colspan="2" scope="col" class="manage-column column-username " >
				
            </td>
          </tr>
          
        </thead>
        
      </table>
    </form>
    
    <br /><br /><br />
    
  <?php $i = 1; 
  	$query = $wpdb->get_results("select * from affiliate_category order by id desc ");?>  
    <table class="wp-list-table widefat " width="100%"> 
        <thead>
        <tr>
        	<td><strong>#</strong></td>
            <td><strong>Name</strong></td>
            <td><strong>Signup Price</strong></td>
            <td><strong>Member Price</strong></td>
            <td><strong>Dated</strong></td>
            <td><strong>Action</strong></td>
        </tr>
          <tr>
            <td colspan="2" scope="col" class="manage-column column-username show_user_name" ></td> 
          </tr>
   <?php foreach ( $query as $data ) {?>
	  	 <tr>
            <td><?php echo $i ?></td>
            <td><?php echo $data->name ?></td>
            <td><?php echo $data->signup_price ?></td>
            <td><?php echo $data->member_price ?></td>
            <td><?php echo date('F d, Y') ?></td>
            <td><a href="<?php echo admin_url.'&edit='.$data->id ?>">Edit</a> | <a href="<?php echo admin_url.'&delete='.$data->id ?>" onclick="return confirm('Are you sure?')">Delete</a></td>
         </tr>
	<?php $i++;} ?>       
          
          
          <tr><td colspan="2"></td></tr>
          
          
        </thead>
        
      </table>
  </div> 
  
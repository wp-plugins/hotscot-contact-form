<?php
	global $wpdb;

	$baseurl = 'admin.php?page=hcf_contact&amp;view_form_sub_id=' . $_GET['view_form_sub_id'];
	$url = $baseurl . '&amp;sub_id=' . $_GET['sub_id'];

	$submission = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME . " WHERE id = " . $_GET['sub_id']);
?>
<?php if(!$submission) exit("Error"); ?>
<div class="wrap">
	<h2>Form Submission <em><?php echo $submission->title; ?></em></h2>
	<p><a href="<?php echo $baseurl; ?>">Go Back</a></p>
	<?php if(!is_null($submission)): ?>
		<ul>
			<?php foreach(json_decode($submission->submission) as $k => $v): ?>
				<?php if($k != 'captchacode'): ?>
					<?php if(!is_array($v)): ?>
						<li><strong><?php echo stripslashes($k); ?></strong>: <?php echo stripslashes($v); ?></li>
					<?php else: ?>
						<?php
							//Format array for printing
							$arrStr = '';
							foreach ($v as $av){
								$arrStr .= stripslashes($av) . ', ';
							}

							$arrStr = substr($arrStr, 0, -2);
						?>
						<li><strong><?php echo stripslashes($k); ?></strong>: <?php echo $arrStr; ?></li>
					<?php endif; ?>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
		<a href="Javascript:del_itm(<?php echo $submission->id; ?>);" class="button-secondary">Delete Submission</a>
	<?php endif; ?>
	<script type="text/javascript">
		//<!--
			function del_itm(val){
	            if(confirm('Are you sure you want to delete this submission')){
	                window.location="<?php echo str_ireplace('&amp;','&',$baseurl); ?>&delitem=" + val;
	            }
	        }
	    //-->
	</script>
</div>


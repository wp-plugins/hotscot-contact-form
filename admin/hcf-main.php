<?php
	global $wpdb;

	//resolve main URL
	$baseurl = 'admin.php?page=hcf_contact';
	$formDeleted = false;


	//Check for deletion of a form
	if(isset($_GET['del_sub_id']) && is_numeric($_GET['del_sub_id'])){
		//Delete all submissions that belong to that form
		$wpdb->query('DELETE FROM ' . $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME . ' WHERE form_id = ' . $_GET['del_sub_id']);

		//Delete the form itself
		$wpdb->query('DELETE FROM ' . $wpdb->prefix . HCF_FORM_TABLE_NAME . ' WHERE id = ' . $_GET['del_sub_id']);

		$formDeleted = true;
	}

	//Get all created forms
    $qry = "SELECT *, (SELECT count(*) FROM " . $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME . " AS sub WHERE sub.form_id = form.id) AS entries FROM " . $wpdb->prefix . HCF_FORM_TABLE_NAME . " AS form ORDER BY form.name ASC";
    $savedForms = $wpdb->get_results($qry);

    $formCount = 0;
    $currentFormName = '';

?>

<div class="wrap">
	<h2>Contact Forms <small style="font-size: 12px;"><a href="admin.php?page=hcf_contact_create">Create New</a></small></h2>
	<?php if($formDeleted): ?>
		<div class="updated"><p>Form Deleted</p></div>
	<?php endif; ?>
	<?php
		if($savedForms): ?>
			<table class="table page widefat">
				<thead>
					<tr>
						<th>Form Name</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbdody>
			        <?php foreach($savedForms as $form): ?>
			        	<tr>
			        		<td><?php echo stripslashes($form->name); ?></td>
			        		<td align="right">
								<a href="<?php echo $baseurl; ?>&amp;edit_id=<?php echo $form->id; ?>">Edit</a>&nbsp;&nbsp;|&nbsp;
							    <a href="<?php echo $baseurl; ?>&amp;del_sub_id=<?php echo $form->id; ?>">Delete</a>&nbsp;&nbsp;|&nbsp;
							    <a href="<?php echo $baseurl; ?>&amp;view_form_sub_id=<?php echo $form->id; ?>">Entries(<?php echo $form->entries; ?>)</a>
							</td>
			        	</tr>
				    <?php endforeach; ?>
				</tbody>
			</table>
	<?php endif; ?>
</div>
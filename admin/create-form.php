<?php
global $wpdb;

//resolve main URL
$baseurl = 'admin.php?page=hcf_contact';

if(isset($_GET['edit_id'])){
	$url = 'admin.php?page=' . $_GET['page'] . '&amp;edit_id=' . $_GET["edit_id"];
}else{
	$url = 'admin.php?page=' . $_GET['page'] . '&amp;edit_id=0';
}

$newForm = true; //are we creating a new form?

if(isset($_GET["edit_id"]) && $_GET["edit_id"] != 0) $newForm = false;

//Form vars;
$name = '';
$data = '';
$thankspage = '';
$style = '';

$ownerHeaders = '';
$ownerEmail = '';
$ownerSubject = '';
$ownerTemplate = '';
$ownerUseHTMLEmail = false;

$clientHeaders = '';
$clientTemplate = '';
$clientUseHTMLEmail = false;

//form submitted
if(isset($_GET['edit_id']) && isset($_POST["frm-sbmt"])){
	$chkfrm = ''; //Keep track of any form submission errors.
	$action = ''; //Keep track of weather we're inserting or updating.

	if($chkfrm==''){
		$formObj = json_decode(stripslashes($_POST['hcf-form-object']));

		if(isset($formObj->formSettings)) $form['form_settings'] = json_encode($formObj->formSettings);
		if(isset($formObj->formElements)) $form['form_data'] = json_encode($formObj->formElements);
		if(isset($formObj->emailSettings)) $form['email_settings'] = json_encode($formObj->emailSettings);

		if($_GET["edit_id"] == 0){
			//add new
			$wpdb->insert($wpdb->prefix . HCF_FORM_TABLE_NAME, $form);
			$url = 'admin.php?page=' . $_GET['page'] . '&amp;edit_id=' . $wpdb->insert_id;
			$action = "insert";
		}else{
			//update
			$wpdb->update($wpdb->prefix . HCF_FORM_TABLE_NAME, $form, array('ID'=>$_GET["edit_id"]));
			$action = "update";
		}

		$wpdb->flush();
	}

	hcf_redirect_backtobase($url . "&amp;action=$action");
}

//If we're editing a form, be sure to get those form values from the database
if(isset($_GET["edit_id"]) && $_GET["edit_id"] != 0){
	$qry = $wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . HCF_FORM_TABLE_NAME . ' WHERE id = %d', $_GET["edit_id"]);
	$rowres = $wpdb->get_row($qry) ;
	$wpdb->flush();

	if($rowres->form_settings != ''){
		$formSettings = json_decode($rowres->form_settings);
		$name = stripslashes($formSettings->formName);
		$style = stripslashes($formSettings->formStyle);
		$thankspage = stripslashes($formSettings->thanksPage);
	}

	$form_data = stripslashes($rowres->form_data);
	if($rowres->email_settings != ''){
		$emailSettings = json_decode($rowres->email_settings);
		$clientTemplate = $emailSettings->clientTemplate;
		$ownerTemplate = $emailSettings->ownerTemplate;

		$clientHeaders = $emailSettings->clientHeaders;
		$ownerHeaders = $emailSettings->ownerHeaders;

		$clientUseHTMLEmail = $emailSettings->clientUseHTMLEmail;
		$ownerUseHTMLEmail = $emailSettings->ownerUseHTMLEmail;

		$clientSubject = ((property_exists($emailSettings, 'clientSubject')) ? $emailSettings->clientSubject : '');
		$ownerSubject = ((property_exists($emailSettings, 'ownerSubject')) ? $emailSettings->ownerSubject : '');


		$ownerEmail = stripslashes($emailSettings->ownerEmail);
	}
}

?>

<div class="wrap hcf-wrap">

	<?php if(isset($_GET['edit_id'])): ?>
		<h2>Edit contact form</h2>
	<?php else: ?>
		<h2>Create a new contact form</h2>
	<?php endif; ?>

	<?php if(isset($_GET['action'])): ?>
		<div class="updated">
			<?php
				switch ($_GET['action']) {
					case 'insert':
						echo '<p><strong>New Form Created.</strong></p>';
						break;

					case 'update':
						echo '<p><strong>Form Updated.</strong></p>';
						break;
				}
			?>
		</div>
	<?php endif; ?>

	<?php if(isset($chkfrm) && $chkfrm!='') echo '<div class="error"><p>'.$chkfrm.'</p></div>'; ?>
	<p><a href="<?php  echo $baseurl; ?>">Go Back</a></p>

	<div style="width: 100%;">
		<div class="postbox">
			<h3 class="box-header"><span>Basic Settings</span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th valign="top"><label for="form-name">Form Name:</label></th>
						<td><input type="text" size="50" class="regular-text" name="name" id="form-name" value="<?php echo (($newForm) ? 'Simple Enquiry Form' : $name); ?>" /></td>
					</tr>
					<tr>
						<th valign="top"><label for="form-style">Style:</label></th>
						<td>
							<select id="form-style" name="form-style">
								<option <?php if($style == 'none') echo 'selected="selected"' ;?> value="none">None</option>
								<option <?php if($style == 'stacked') echo 'selected="selected"' ;?> value="stacked">Stacked</option>
								<option <?php if($style == 'horizontal') echo 'selected="selected"' ;?> value="horizontal">Horizontal</opion>
							</select>
							<p class="description" ><strong>Stacked:</strong> - Shows label above Fields, <strong>Horizontal</strong> - Shows labels beside form fileds</p>
						</td>
					</tr>
					<tr>
						<th valign="top"><label for="thankspage">Submission Page:</label></th>
						<td>
							<?php
								wp_dropdown_pages(array(
								    'name' => 'submission_page',
								    'id' => 'thankspage',
								    'selected' => $thankspage
								));
							 ?>
							 <p class="description">The form will redirect to this page after it is verified and submitted</p>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>


	<div id="post-body" class="metabox-holder columns-2">
		<div class="postbox-container">
			<div class="postbox" id="control-box">
				<h3 class="box-header"><span>Form Controls<br/><small>drag labels to move controls into Form Builder</small></span></h3>
				<div class="inside">
					<?php

						//Below are the different option templates
						//Note that any changes here MUST be replicated in hcf_displayFormElement() in hotscot-contact-form.php
					?>
					<table id="control-list">
						<tr>
							<td class="name">&nbsp;</td>
							<td class="hcf-text-box">Textbox</td>
							<td class="actions">
								<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

								<div class="hcf-edit-box hcf-hidden">
									<form>
										<table>
											<tr>
												<td><label>Label: </label></td><td><input type="text" name="label"/></td>
											</tr>
											<tr>
												<td><label>Name: </label></td><td><input type="text" name="name"/></td>
											</tr>
											<tr>
												<td><label>ID: </label></td><td><input type="text" name="id"/></td>
											</tr>
											<tr>
												<td><label>Class: </label></td><td><input type="text" name="class"/></td>
											</tr>
											<tr>
												<td><label>Disallow Links: </label></td><td><input type="checkbox" name="nolinks"/></td>
											</tr>
											<tr>
												<td><label>Required: </label></td><td><input type="checkbox" name="required"/></td>
											</tr>
										</table>
									</form>
								</div>
							</td>
						</tr>
						<tr>
							<td class="name">&nbsp;</td>
							<td class="hcf-email-box">Email</td>
							<td class="actions">
								<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

								<div class="hcf-edit-box hcf-hidden">
									<form>
										<table>
											<tr>
												<td><label>Label: </label></td><td><input type="text" name="label"/></td>
											</tr>
											<tr>
												<td><label>Name: </label></td><td><input type="text" name="name"/></td>
											</tr>
											<tr>
												<td><label>ID: </label></td><td><input type="text" name="id"/></td>
											</tr>
											<tr>
												<td><label>Class: </label></td><td><input type="text" name="class"/></td>
											</tr>
											<tr>
												<td><label>Required: </label></td><td><input type="checkbox" name="required"/></td>
											</tr>
											<tr>
												<td><label>Send "Client Email Template"?: </label></td><td><input type="checkbox" name="clientemail"/></td>
											</tr>
										</table>
									</form>
								</div>
							</td>
						</tr>
						<tr>
							<td class="name">&nbsp;</td>
							<td class="hcf-checkbox">Checkbox Group</td>
							<td class="actions">
								<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>
								<div class="hcf-edit-box hcf-hidden">
									<form>
										<table>
											<tr>
												<td><label>Label: </label></td><td><input type="text" name="label"/></td>
											</tr>
											<tr>
												<td><label>Name: </label></td><td><input type="text" name="name"/></td>
											</tr>
											<tr>
												<td><label>Class: </label></td><td><input type="text" name="class"/></td>
											</tr>
											<tr>
												<td valign="top"><label>Options: </label></td><td><input type="text" name="options"/><br /><p class="description">Comma Seperated values e.g "bacon,egs,spam,spam,beans,spam"</p></td>
											</tr>
											<tr>
												<td><label>Required: </label></td><td><input type="checkbox" name="required"/></td>
											</tr>
										</table>
									</form>
								</div>
							</td>
						</tr>
						<tr>
							<td class="name">&nbsp;</td>
							<td class="hcf-select">Dropdown</td>
							<td class="actions">
								<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

								<div class="hcf-edit-box hcf-hidden">
									<form>
										<table>
											<tr>
												<td><label>Label: </label></td><td><input type="text" name="label"/></td>
											</tr>
											<tr>
												<td><label>Name: </label></td><td><input type="text" name="name"/></td>
											</tr>
											<tr>
												<td><label>ID: </label></td><td><input type="text" name="id"/></td>
											</tr>
											<tr>
												<td><label>Class: </label></td><td><input type="text" name="class"/></td>
											</tr>
											<tr>
												<td valign="top"><label>Options: </label></td><td><input type="text" name="options"/><br /><p class="description">Comma Seperated values e.g "bacon,egs,spam,spam,beans,spam"</p></td>
											</tr>
											<tr>
												<td><label>Required: </label></td><td><input type="checkbox" name="required"/></td>
											</tr>
										</table>
									</form>
								</div>
							</td>
						</tr>
						<tr>
							<td class="name">&nbsp;</td>
							<td class="hcf-textarea">Textarea</td>
							<td class="actions">
								<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>
								<div class="hcf-edit-box hcf-hidden">
									<form>
										<table>
											<tr>
												<td><label>Label: </label></td><td><input type="text" name="label"/></td>
											</tr>
											<tr>
												<td><label>Name: </label></td><td><input type="text" name="name"/></td>
											</tr>
											<tr>
												<td><label>Rows: </label></td><td><input type="text" name="rows"/><br /><p class="description">Leave blank for defaults</p></td>
											</tr>
											<tr>
												<td><label>Cols: </label></td><td><input type="text" name="cols"/><br/><p class="description">Leave blank for defaults</p></td>
											</tr>
											<tr>
												<td><label>ID: </label></td><td><input type="text" name="id"/></td>
											</tr>
											<tr>
												<td><label>Class: </label></td><td><input type="text" name="class"/></td>
											</tr>
											<tr>
												<td><label>Disallow Links: </label></td><td><input type="checkbox" name="nolinks"/></td>
											</tr>
											<tr>
												<td><label>Required: </label></td><td><input type="checkbox" name="required"/></td>
											</tr>
										</table>
									</form>
								</div>
							</td>
						</tr>
						<tr>
							<td class="name">&nbsp;</td>
							<td class="hcf-submit">Submit Button</td>
							<td class="actions">
								<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>
								<div class="hcf-edit-box hcf-hidden">
									<form>
										<table>
											<tr>
												<td><label>ID: </label></td><td><input type="text" name="id"/></td>
											</tr>
											<tr>
												<td><label>Class: </label></td><td><input type="text" name="class"/></td>
											</tr>
											<tr>
												<td><label>Value: </label></td><td><input type="text" name="value"/></td>
											</tr>
											<tr>
												<td><label>Use CAPTCHA: </label></td><td><input type="checkbox" name="captcha"/></td>
											</tr>
										</table>
									</form>
								</div>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<div class="postbox-container">
			<div id="the-form">
				<div class="postbox" id="form-box">
					<h3 class="box-header"><span>Form Builder<br/><small>drag labels to re-order elements</small></span></h3>
					<div class="inside">
						<table id="form-elements">
							<?php if($newForm): ?>
							<tr>
								<td class="name">Your Name</td>
								<td class="hcf-text-box">Textbox</td>
								<td class="actions">
									<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

									<div class="hcf-edit-box hcf-hidden">
										<form>
											<table>
												<tr>
													<td><label>Label: </label></td><td><input type="text" name="label" value="Your Name"/></td>
												</tr>
												<tr>
													<td><label>Name: </label></td><td><input type="text" name="name" value="name"/></td>
												</tr>
												<tr>
													<td><label>ID: </label></td><td><input type="text" name="id"/></td>
												</tr>
												<tr>
													<td><label>Class: </label></td><td><input type="text" name="class"/></td>
												</tr>
                                                <tr>
                                                    <td><label>Disallow Links: </label></td><td><input type="checkbox" name="nolinks"/></td>
                                                </tr>
												<tr>
													<td><label>Required: </label></td><td><input type="checkbox" name="required"/></td>
												</tr>
											</table>
										</form>
									</div>
								</td>
							</tr>
							<tr>
								<td class="name">Your Email</td>
								<td class="hcf-email-box">Email</td>
								<td class="actions">
									<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

									<div class="hcf-edit-box hcf-hidden">
										<form>
											<table>
												<tr>
													<td><label>Label: </label></td><td><input type="text" name="label" value="Your Email"/></td>
												</tr>
												<tr>
													<td><label>Name: </label></td><td><input type="text" name="name" value="email"/></td>
												</tr>
												<tr>
													<td><label>ID: </label></td><td><input type="text" name="id"/></td>
												</tr>
												<tr>
													<td><label>Class: </label></td><td><input type="text" name="class"/></td>
												</tr>
												<tr>
													<td><label>Required: </label></td><td><input type="checkbox" name="required" checked="checked" disabled="true"/></td>
												</tr>
												<tr>
													<td><label>Send "Client Email Template"?: </label></td><td><input type="checkbox" name="clientemail" checked="checked"/></td>
												</tr>
											</table>
										</form>
									</div>
								</td>
							</tr>
							<tr>
								<td class="name">Your Message</td>
								<td class="hcf-textarea">Textarea</td>
								<td class="actions">
									<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>
									<div class="hcf-edit-box hcf-hidden">
										<form>
											<table>
												<tr>
													<td><label>Label: </label></td><td><input type="text" name="label" value="Your Message"/></td>
												</tr>
												<tr>
													<td><label>Name: </label></td><td><input type="text" name="name" value="enquiry"/></td>
												</tr>
												<tr>
													<td><label>Rows: </label></td><td><input type="text" name="rows"/><br /><p class="description">Leave blank for defaults</p></td>
												</tr>
												<tr>
													<td><label>Cols: </label></td><td><input type="text" name="cols"/><br/><p class="description">Leave blank for defaults</p></td>
												</tr>
												<tr>
													<td><label>ID: </label></td><td><input type="text" name="id"/></td>
												</tr>
												<tr>
													<td><label>Class: </label></td><td><input type="text" name="class"/></td>
												</tr>
                                                <tr>
                                                    <td><label>Disallow Links: </label></td><td><input type="checkbox" name="nolinks"/></td>
                                                </tr>
												<tr>
													<td><label>Required: </label></td><td><input type="checkbox" name="required" checked="checked"/></td>
												</tr>
											</table>
										</form>
									</div>
								</td>
							</tr>
							<tr>
								<td class="name">Send</td>
								<td class="hcf-submit">Submit Button</td>
								<td class="actions">
									<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>
									<div class="hcf-edit-box hcf-hidden">
										<form>
											<table>
												<tr>
													<td><label>ID: </label></td><td><input type="text" name="id"/></td>
												</tr>
												<tr>
													<td><label>Class: </label></td><td><input type="text" name="class"/></td>
												</tr>
												<tr>
													<td><label>Value: </label></td><td><input type="text" name="value" value="Send"/></td>
												</tr>
												<tr>
													<td><label>Use CAPTCHA: </label></td><td><input type="checkbox" name="captcha" checked="checked"/></td>
												</tr>
											</table>
										</form>
									</div>
								</td>
							</tr>
							<?php else: ?>
								<?php if($form_data !== ""): ?>
									<?php foreach(json_decode($form_data) as $element): ?>
										<?php hcf_admin_displayFormElement($element); ?>
									<?php endforeach; ?>
								<?php else: ?>
									<tr><td>&nbsp;</td><td class="placeholder">Drag form elements here</td><td>&nbsp;</td></tr>
								<?php endif; ?>
							<?php endif; ?>
						</table>
					</div>
				</div>
			</div>
		</div>

		<div class="clear"><!-- clear --></div>

		<h2>Email Templates</h2>
		<p>Here you can customize the emails that are sent out.  To include form data in the email simply use the elements <strong>name</strong> wrapped in square brackets e.g. <em>[email]</em></p>
		<div style="width: 100%;">
			<div class="postbox">
				<h3 class="box-header"><span>Main Email</span><small> - This Email will be sent to <strong>all</strong> the <strong>email</strong> fields in the form that have the <strong>"Send Email"</strong> checkbox ticked.</small></h3>
				<div class="inside">
					<h3>Email Template</h3>
					<?php if($newForm): ?>
						<textarea size="50" rows="6" style="width: 100%" name="clientTemplate" id="clientTemplate">Hi [name], thank you for getting in touch. Your contact form submission has been received.</textarea>
					<?php else: ?>
						<textarea size="50" rows="6"  style="width: 100%" name="clientTemplate" id="clientTemplate"><?php echo $clientTemplate; ?></textarea>
					<?php endif; ?>
					<h3>Headers (Advanced - Leave blank if unsure)</h3>
					<textarea size="50" rows="6"  style="width: 100%" name="clientHeaders" id="clientHeaders"><?php echo $clientHeaders; ?></textarea><br/>
					<label for="clientSubject">Subject Line:</label><input type="text" size="50" style="width: 100%" name="clientSubject" id="clientSubject" value="<?php echo (($newForm) ? 'Submission Confirmation' : $clientSubject); ?>" /><br/>
					<label for="clientUseHTMLEmail">Send HTML Email:&nbsp;</label><input type="checkbox" name="clientUseHTMLEmail" id="clientUseHTMLEmail" <?php echo (($clientUseHTMLEmail) ? 'checked="checked"' : ''); ?> />
				</div>
			</div>
		</div>

		<div style="width: 100%;">
			<div class="postbox">
				<h3 class="box-header"><span>Notification Email</span><small> - You can use this email template to notify you when somone fills in the form</small></h3>
				<div class="inside">
					<h3>Email Template</h3>
					<?php if($newForm): ?>
						<textarea size="50" rows="6" style="width: 100%" name="ownerTemplate" id="ownerTemplate">Contact form submission
Your Name: [name]
Your Email: [email]
Your Message:
[enquiry]</textarea><br/>
					<?php else: ?>

						<textarea size="50" rows="6"  style="width: 100%" name="ownerTemplate" id="ownerTemplate"><?php echo stripslashes($ownerTemplate); ?></textarea><br/>
					<?php endif; ?>
					<h3>Headers (Advanced - Leave blank if unsure)</h3>
					<textarea size="50" rows="6"  style="width: 100%" name="ownerHeaders" id="ownerHeaders"><?php echo $ownerHeaders; ?></textarea>
					<label for="ownerSubject">Subject Line:</label><input type="text" size="50" style="width: 100%" name="ownerSubject" id="ownerSubject" value="<?php echo (($newForm) ? 'New Contact Form Submission' : $ownerSubject); ?>" /><br/>
					<label for="ownerEmail">Email Address:</label><input type="text" size="50" style="width: 100%" name="ownerEmail" id="ownerEmail" value="<?php echo (($newForm) ? 'you@example.com' : $ownerEmail); ?>" /><br/>
					<label for="ownerUseHTMLEmail">Send HTML Email:&nbsp;</label><input type="checkbox" name="ownerUseHTMLEmail" id="ownerUseHTMLEmail" <?php echo (($ownerUseHTMLEmail) ? 'checked="checked"' : ''); ?> />
				</div>
			</div>
		</div>

	</div>

	<form name="edit_item" id="edit_item" action="<?php echo $url; ?>" method="post">
		<input type="hidden" name="frm-sbmt" id="frm-sbmt" value="1" />
		<input type="hidden" id="hcf-form-object" name="hcf-form-object" value=""/>
		<input id="hcf-create-submit" type="submit" class="button-primary" value="Add / Update" />
	</form>
</div>
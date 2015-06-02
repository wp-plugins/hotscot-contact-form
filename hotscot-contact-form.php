<?php
/*
Plugin Name: Hotscot Contact Form
Plugin URI: http://wordpress.org/extend/plugins/hotscot-contact-form/
Description: Simple to use contact form
Version: 1.2
Author: Hotscot
Author URI: http://www.hotscot.net/
License: GPL2
*/
////////////////////////////////////////////////////////////////////////////////
/*  Copyright 2012 Huntly Cameron (email : huntly@hotscot.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
////////////////////////////////////////////////////////////////////////////////

/*
 * Following install update code based heavily on the examples provided
 * in the wordpress codex:
 * http://codex.wordpress.org/Creating_Tables_with_Plugins
 */
global $hcf_db_version;
$hcf_db_version = "1.2";

define( 'HCF_FORM_TABLE_NAME' , 'hcf_form' );
define( 'HCF_SUBMISSION_TABLE_NAME' , 'hcf_form_submission' );
define( 'HCF_CSV_EXPORT_URL', '/CSV_EXPORT/');

//When the plugin is activated, install the database
register_activation_hook( __FILE__ , 'hcf_plugin_install' );

//When the plugin is loaded, check for DB updates and first run
add_action( 'plugins_loaded' , 'hcf_update_db_check' );

//Build admin menu
add_action( 'admin_menu' , 'hcf_build_admin_menu' );

//Include Styles
add_action( 'admin_init' , 'hcf_setup_custom_assets' );
add_action( 'admin_enqueue_scripts', 'hcf_enqueue_admin_scripts');

//Include custom form picker in post
add_action('media_buttons_context',  'hcf_add_form_picker_button');

//Parse the short code to display the form
add_shortcode('hcf-form', 'hcf_display_form');

//Setup custom routing
remove_filter('template_redirect', 'redirect_canonical');
add_action('template_redirect', 'redirect_custom_urls');

//Dashboard setup
add_action('wp_dashboard_setup', 'hcf_add_dashboard_widget');

if(!isset($_SESSION)) session_start();

/**
 * Checks if we need to update the db schema
 *
 * If the site_option value doesn't match the version defined at the top of
 * this file, the install routine is run.
 *
 * @global string $hcf_db_version
 * @return void
 */
function hcf_update_db_check() {
    global $hcf_db_version;

    //Update the database if necessery
    if ( get_site_option( 'hcf_db_version' ) != $hcf_db_version ) hcf_plugin_install();
}

/**
 * Creates the db schema
 *
 * @global WPDB $wpdb
 * @global string $hcf_db_version
 * @return void
 */
function hcf_plugin_install(){
    global $wpdb;
    global $hcf_db_version;
    $form_table_name = $wpdb->prefix . HCF_FORM_TABLE_NAME;
    $entry_table_name = $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME;

    $dbv = get_site_option( 'hcf_db_version', '-1' );

    if(is_null($dbv)){
    	//New install
    	$sql = "CREATE TABLE $form_table_name (
            id integer NOT NULL AUTO_INCREMENT,
            form_settings text,
            form_data text,
            email_settings text,
            UNIQUE KEY id (id)
            );";

    	$sql .= "CREATE TABLE $entry_table_name (
            id integer NOT NULL AUTO_INCREMENT,
            form_id integer NOT NULL,
            date_submitted TIMESTAMP DEFAULT NOW() NOT NULL,
            title varchar(255),
            submission text,
            UNIQUE KEY id (id)
            );";

    }else{ //Update (comining colums)

    	$sql = "DROP TABLE IF EXISTS $form_table_name; CREATE TABLE $form_table_name (
            id integer NOT NULL AUTO_INCREMENT,
            form_settings text,
            form_data text,
            email_settings text,
            UNIQUE KEY id (id)
            );";

    	$sql .= "CREATE TABLE $entry_table_name (
            id integer NOT NULL AUTO_INCREMENT,
            form_id integer NOT NULL,
            date_submitted TIMESTAMP DEFAULT NOW() NOT NULL,
            title varchar(255),
            submission text,
            UNIQUE KEY id (id)
            );";

    }

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option( 'hcf_db_version' , $hcf_db_version );
}

/**
 * Sets up the main wp admin dashboard widget
 *
 * @return void
 */
function hcf_add_dashboard_widget(){
	wp_add_dashboard_widget('hcf_latest_posts', 'Recent Form Submissions', 'hcf_dashboard_widget');
}

/**
 * This function is executed when our widget is initialized
 *
 * @return void
 */
function hcf_dashboard_widget(){
	global $wpdb;
	$form_table_name = $wpdb->prefix . HCF_FORM_TABLE_NAME;
    $entry_table_name = $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME;

	$query = "SELECT e.id as sub_id, e.date_submitted, e.submission, f.form_settings, f.id as form_id FROM $entry_table_name as e LEFT JOIN $form_table_name as f on e.form_id = f.id ORDER BY date_submitted DESC LIMIT 5";

	$subs = $wpdb->get_results($query);

	if($subs):?>
		<table style="width: 100%">
			<tr>
				<th>Date</th><th>Title</th><th>Form</th>
			</tr>
			<?php foreach ($subs as $sub): ?>
			<?php
				$formSettings = json_decode(stripslashes($sub->form_settings));

			?>
			<tr>
				<td><?php echo $sub->date_submitted; ?></td>
				<td><a href="admin.php?page=hcf_contact&view_form_sub_id=<?php echo $sub->form_id; ?>&sub_id=<?php echo $sub->sub_id; ?>"><?php echo stripslashes(hcf_html_format_submission($sub->submission, true)); ?></td>
				<td><a href="admin.php?page=hcf_contact&view_form_sub_id=<?php echo $sub->form_id; ?>"><?php echo $formSettings->formName; ?></td>

			</tr>
			<?php endforeach; ?>
		</table>
	<?php else: ?>
		<p>No submissions yet</p>
	<?php endif;
}

/**
 * Redirects to a given url - a bit kludgy, but works
 *
 * @param url - url to redirect to
 * @return void
 */
function hcf_redirect_backtobase($url){
   ?>
   <script type="text/javascript">
       <!--
       window.location='<?php echo str_ireplace('&amp;','&',$url); ?>';
       //-->
   </script>
   <noscript>
       <div class="wrap"><p><a href="<?php echo $url; ?>">Form submitted click here to return to page</a></p></div>
   </noscript>
   <?php
   exit();
}

/**
 * takes a form submission and creates a readable string out of it
 *
 * will cut off submission after a number of chars
 *
 * @param str $strippedSubmission - JSON submission (stripped slashed)
 * @return str $formattedSubmission - nice submission i.e "Name: john, Email: john@hotsc..."
 */
function hcf_html_format_submission($strippedSubmission, $firstOnly = false, $charCutoff = 100){
	$formattedSubmission = '';

    //$strippedSubmission = str_replace("\'", "''", $strippedSubmission);
	$formFields = json_decode($strippedSubmission);


	$fieldCount = 0;

	foreach ($formFields as $k => $v) {
		if($k != 'captchacode'){
			if(! ($v == '' || $v == '-1')){
				//Special consideration for checkbox values
				if(is_array($v)){
					$arrStr = '';
					foreach ($v as $av) {
						$arrStr .= "$av, ";
					}
					//Remove ', ' from end
					if(strlen($arrStr) > 2) $arrStr = substr($arrStr, 0,-2);

					//If first only, we just want the value not the key
					if($firstOnly){
						$formattedSubmission .= $arrStr . ", ";
					}else{ //take it all
						$formattedSubmission .= '<strong>' . ucfirst($k) . "</strong>: " . $arrStr . ", ";
					}
				}else{
					//If first only, we just want the value not the key
					if($firstOnly){
						$formattedSubmission .= $v . ", ";
					}else{ //take it all
						$formattedSubmission .= '<strong>' . ucfirst($k) . "</strong>: " . $v . ", ";
					}
				}
			}

			if($firstOnly && ++$fieldCount) break;
		}
	}

	//Get rid of the last ", "
	$formattedSubmission = substr($formattedSubmission, 0, -2);

	$returnStr = '';
	if($firstOnly){
		return $formattedSubmission;
	}else{
		return substr($formattedSubmission, 0, $charCutoff) . "...";
	}
}

/**
 * returns a comma seperated list of form fields for that form
 *
 * @param int $formID - the form Id
 * @return str $fieldList - unique list of all submission fields
 */
function hcf_get_form_submission_fields($formID){
	global $wpdb;

	$fieldList = array();
	$tableName = $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME;
	$query = "SELECT submission FROM $tableName WHERE form_id = $formID ORDER BY date_submitted DESC";
	$res = $wpdb->get_results($query);

	if($res){
		foreach ($res as $row) {
			foreach (json_decode($row->submission) as $k => $v) {

				if(!in_array($k, $fieldList)) $fieldList[] = "$k";
			}
		}
	}

	return $fieldList;
}


/**
 * prints out the form element
 *
 * @param FormElement $formEment
 * @return void
 */
function hcf_displayFormElement($formElement, $postdata){
	$html = '';

	switch ($formElement->elementType) {
		case 'text':
			$html .= '<label class="hcf-label hcf-label-text">' .  ((property_exists($formElement, 'elementLabel')) ? $formElement->elementLabel : $formElement->elementName) . ':</label>';
			$elementClasses = (($formElement->isElementRequired) ? 'hcf_req_text ' : '') . (($formElement->nolinks) ? 'hcf_nolinks ' : '') . $formElement->elementClass;
			$html .= '<input type="text"' . (($formElement->elementName == '') ? '' : ' name="' . $formElement->elementName . '"') . (($elementClasses == '') ? '' : ' class="' . $elementClasses . '"') . (($formElement->elementID != '') ? ' id="' . $formElement->elementID . '"' : '') . ((isset($postdata[$formElement->elementName])) ? ' value="' . stripslashes($postdata[$formElement->elementName]) . '"' : '') . '/>';
			break;
		case 'email':
			$elementClasses = (($formElement->isElementRequired) ? 'hcf_req_text ' : '') . $formElement->elementClass;
			if($formElement->sendFormToThisAddress){
				$html .= '<input type="hidden" name="clientemail[]"	value="' . $formElement->elementName . '" />';
			}
			$html .= '<label class="hcf-label hcf-label-email">' . ((property_exists($formElement, 'elementLabel')) ? $formElement->elementLabel : $formElement->elementName) . ':</label>';
			$html .= '<input type="text"' . (($formElement->elementName == '') ? '' : ' name="' . $formElement->elementName . '"') . (($elementClasses == '') ? '' : ' class="' . $elementClasses . '"') . (($formElement->elementID != '') ? ' id="' . $formElement->elementID . '"' : '') .  ((isset($postdata[$formElement->elementName])) ? ' value="' . stripslashes($postdata[$formElement->elementName]) . '"' : '') . ' />';
			break;
		case 'submit':
			if($formElement->useCaptcha){

                if(get_option( 'hcf_use_recaptcha', 0) == 1  && get_option( 'hcf_recaptcha_site_key', '') != '' && get_option( 'hcf_recaptcha_secret_key', '') != ''){
                    $html .= '<div class="g-recaptcha" data-sitekey="' . get_option( 'hcf_recaptcha_site_key', '') . '"></div>';
                    $html .= '<div class="hcf-clear"><!-- clear form element --></div>';
                }else{
    				$html .= '<label class="hcf-label hcf-label-checkbox">Code:</label>';

    				$html .= '<div class="hcf-captcha-wrap"><input type="text" name="captchacode" id="captchacode" />';
    				$html .= '<img src="'. get_bloginfo( 'url') . '/HCF_CAPTCHA/" class="hcf-captcha" alt="captcha_img" /></div>';
    				$html .= '<div class="hcf-clear"><!-- clear form element --></div>';
                }
			}else{
				if(isset($_SESSION['captcha_key'])) unset($_SESSION['captcha_key']);
			}

			$elementClasses = $formElement->elementClass;
			$html .= '<input type="submit"' . (($formElement->elementClass == '') ? '': ' class="' . $formElement->elementClass . '" ') . (($formElement->elementID != '') ? ' id="' . $formElement->elementID . '"' : '') . ' value="' . (($formElement->elementValue != '') ?  $formElement->elementValue : 'Submit' ) . '" />';
			break;
		case 'checkbox':
			if(strpos($formElement->elementOptions, ',')){
				$options = explode(',', $formElement->elementOptions);
				$elementClasses = $formElement->elementClass;

				$count = 0;

				//Checkboxes are handled slightly differnetly, see theyre set and pull out the data
				$checkboxOptions = array();
				if(isset($postdata['checkbox'])) $checkboxOptions = $postdata['checkbox'];


                $html .= '<div class="check-group ' . (($formElement->isElementRequired) ? 'hcf_req_check ' : '') . '">';
				foreach($options as $option){
					$id = $formElement->elementName . ++$count;
					$html .= '<div class="hcf-clear"><!-- clear form element --></div>';
					$html .= '<label for="' . $id . '" class="hcf-label hcf-label-checkbox">' . $option . '</label>';
					$html .= '<input id="' . $id  . '" type="checkbox"' . (($formElement->elementName == '') ? ' name="checkbox[]"' : ' name="' . $formElement->elementName . '[]"') . (($elementClasses == '') ? '': ' class="' . $elementClasses .'" ') . ' value="' . $option . '"' . (in_array($option, $checkboxOptions) ? ' checked="checked"': '') . '/>';
				}
                $html .= '</div>';
			}elseif($formElement->elementOptions != ''){ //Single option field (i.e terms and conditions)
				$elementClasses = (($formElement->isElementRequired) ? 'hcf_req_text' : '') . $formElement->elementClass;
				$html .= '<label class="hcf-label hcf-label-checkbox">' . $formElement->elementOptions . '</label>' ;
				$html .= '<input type="checkbox"' . (($formElement->elementName == '') ? ' name="checkbox[]"' : ' name="' . $formElement->elementName . '[]"') . (($elementClasses == '') ? '': ' class="' . $elementClasses .'" ') . ((isset($postdata[$formElement->elementName])) ? ' checked="checked"': '') . ' />';
			}
			break;
		case 'select':
				$elementClasses = (($formElement->isElementRequired) ? 'hcf_req_text ' : '') . $formElement->elementClass;
				$html .= '<label class="hcf-label hcf-label-select">' . ((property_exists($formElement, 'elementLabel')) ? $formElement->elementLabel : $formElement->elementName) . ':</label>';
				$html .= '<select' . (($formElement->elementName == '') ? '' : ' name="' . $formElement->elementName . '"') . (($elementClasses == '') ? '': ' class="' . $elementClasses .'" ') . (($formElement->elementID != '') ? ' id="' . $formElement->elementID . '"' : '') . '>';
				$html .= '<option value="-1">Please Select...</option>';
				if(strpos($formElement->elementOptions, ',')){
					$options = explode(',', $formElement->elementOptions);

					foreach($options as $option){
						$html .= '<option value="' . $option . '"' . ((isset($postdata[$formElement->elementName]) && $postdata[$formElement->elementName] == $option) ? ' selected="selected"': '') .  ">$option</option>";
					}
				}elseif($formElement->elementOptions != ''){
					$html .= "<option value=\"{$formElement->elementOptions}\">{$formElement->elementOptions}</option>";
				}
				$html .= "</select>";
			break;
		case 'textarea':
				$elementClasses = (($formElement->isElementRequired) ? 'hcf_req_text ' : '') . (($formElement->nolinks) ? 'hcf_nolinks ' : '') . $formElement->elementClass;
				$html .= '<label class="hcf-label hcf-label-textarea">' . ((property_exists($formElement, 'elementLabel')) ? $formElement->elementLabel : $formElement->elementName) . ': </label>';
				$html .= '<textarea' . (($formElement->elementRows == '') ? ' rows="8"': ' rows="' . $formElement->elementRows . '"') . (($formElement->elementCols == '') ? ' cols="31"': ' cols="' . $formElement->elementCols . '"'). (($formElement->elementName == '') ? '': 'name="' . $formElement->elementName . '"') . (($elementClasses =='') ? '' : ' class="' .$elementClasses . '"' ) . (($formElement->elementID != '') ? ' id="' . $formElement->elementID . '"' : '') . '>' . ((isset($postdata[$formElement->elementName])) ? stripslashes($postdata[$formElement->elementName]) : '').'</textarea>';
			break;
	}

	$html .= '<div class="hcf-clear"><!-- clear form element --></div>';

	return $html;
}


/**
 * prints out the form element
 *
 * @param FormElement $formEment
 * @return void
 */
function hcf_admin_displayFormElement($formElement){
	//NOTE: any changes made to the templates here HAS to be replicated in create-form.php
	switch ($formElement->elementType) {
		case 'text':
			?>
			<tr>
				<td class="name"><?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?></td>
				<td class="hcf-text-box">Textbox</td>
				<td class="actions">
					<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>
					<div class="hcf-edit-box hcf-hidden">
						<form>
							<table>
								<tr>
									<td><label>Label: </label></td><td><input type="text" name="label" value="<?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?>"/></td>
								</tr>
								<tr>
									<td><label>Name: </label></td><td><input type="text" name="name" value="<?php echo $formElement->elementName; ?>"/></td>
								</tr>
								<tr>
									<td><label>ID: </label></td><td><input type="text" name="id" value="<?php echo $formElement->elementID; ?>"/></td>
								</tr>
								<tr>
									<td><label>Class: </label></td><td><input type="text" name="class" value="<?php echo $formElement->elementClass; ?>"/></td>
								</tr>
								<tr>
									<td><label>Disallow Links: </label></td><td><input type="checkbox" name="nolinks" <?php if($formElement->nolinks) echo 'checked="checked"'; ?>/></td>
								</tr>
								<tr>
									<td><label>Required: </label></td><td><input type="checkbox" name="required" <?php if($formElement->isElementRequired) echo 'checked="checked"'; ?>/></td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
			<?php
			break;
		case 'email':
			?>
			<tr>
				<td class="name"><?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?></td>
				<td class="hcf-email-box">Email</td>
				<td class="actions">
					<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

					<div class="hcf-edit-box hcf-hidden">
						<form>
							<table>
								<tr>
									<td><label>Label: </label></td><td><input type="text" name="label" value="<?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?>"/></td>
								</tr>
								<tr>
									<td><label>Name: </label></td><td><input type="text" name="name" value="<?php echo $formElement->elementName; ?>"/></td>
								</tr>
								<tr>
									<td><label>ID: </label></td><td><input type="text" name="id" value="<?php echo $formElement->elementID; ?>"/></td>
								</tr>
								<tr>
									<td><label>Class: </label></td><td><input type="text" name="class" value="<?php echo $formElement->elementClass; ?>"/></td>
								</tr>
								<tr>
									<?php if($formElement->sendFormToThisAddress): ?>
										<td><label>Required: </label></td><td><input type="checkbox" name="required" checked="checked" disabled="true"/></td>
									<?php else: ?>
										<td><label>Required: </label></td><td><input type="checkbox" name="required" <?php if($formElement->isElementRequired) echo 'checked="checked"'; ?>/></td>
									<?php endif; ?>
								</tr>
								<tr>
									<td><label>Send Email: </label></td><td><input type="checkbox" name="clientemail" <?php if($formElement->sendFormToThisAddress) echo 'checked="checked"'; ?>/></td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
			<?php
			break;
		case 'submit':
			?>
			<tr>
				<td class="name"><?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?></td>
				<td class="hcf-submit">Submit Button</td>
				<td class="actions">
					<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

					<div class="hcf-edit-box hcf-hidden">
						<form>
							<table>
								<tr>
									<td><label>ID: </label></td><td><input type="text" name="id" value="<?php echo $formElement->elementID; ?>"/></td>
								</tr>
								<tr>
									<td><label>Class: </label></td><td><input type="text" name="class" value="<?php echo $formElement->elementClass; ?>"/></td>
								</tr>
								<tr>
									<td><label>Button Text: </label></td><td><input type="text" name="value" value="<?php echo $formElement->elementValue; ?>"/></td>
								</tr>
								<tr>
									<td><label>Use CAPTCHA: </label></td><td><input type="checkbox" <?php if($formElement->useCaptcha) echo 'checked="checked"'; ?> name="captcha"/></td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
			<?php
			break;
		case 'checkbox':
			?>
			<tr>
				<td class="name"><?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?></td>
				<td class="hcf-checkbox">Checkbox Group</td>
				<td class="actions">
					<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

					<div class="hcf-edit-box hcf-hidden">
						<form>
							<table>
								<tr>
									<td><label>Label: </label></td><td><input type="text" name="label" value="<?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?>"/></td>
								</tr>
								<tr>
									<td><label>Name: </label></td><td><input type="text" name="name" value="<?php echo $formElement->elementName; ?>"/></td>
								</tr>
								<tr>
									<td><label>Class: </label></td><td><input type="text" name="class" value="<?php echo $formElement->elementClass; ?>"/></td>
								</tr>
								<tr>
									<td valign="top"><label>Options: </label></td><td><input type="text" name="options" value="<?php echo $formElement->elementOptions; ?>" /><br /><p class="description">Comma Seperated values e.g "bacon,egs,spam,spam,beans,spam"</p></td>
								</tr>
								<tr>
									<td><label>Required: </label></td><td><input type="checkbox" name="required" <?php if($formElement->isElementRequired) echo 'checked="checked"'; ?>/></td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
			<?php
			break;
		case 'select':
			?>
			<tr>
				<td class="name"><?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?></td>
				<td class="hcf-select">Dropdown</td>
				<td class="actions">
					<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

					<div class="hcf-edit-box hcf-hidden">
						<form>
							<table>
								<tr>
									<td><label>Label: </label></td><td><input type="text" name="label" value="<?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?>"/></td>
								</tr>
								<tr>
									<td><label>Name: </label></td><td><input type="text" name="name" value="<?php echo $formElement->elementName; ?>"/></td>
								</tr>
								<tr>
									<td><label>ID: </label></td><td><input type="text" name="id" value="<?php echo $formElement->elementID; ?>"/></td>
								</tr>
								<tr>
									<td><label>Class: </label></td><td><input type="text" name="class" value="<?php echo $formElement->elementClass; ?>"/></td>
								</tr>
								<tr>
									<td valign="top"><label>Options: </label></td><td><input type="text" name="options" value="<?php echo $formElement->elementOptions; ?>"/><br /><p class="description">Comma Seperated values e.g "bacon,egs,spam,spam,beans,spam"</p></td>
								</tr>
								<tr>
									<td><label>Required: </label></td><td><input type="checkbox" name="required" <?php if($formElement->isElementRequired) echo 'checked="checked"'; ?>/></td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
			<?php
			break;
		case 'textarea':
			?>
			<tr>
				<td class="name"><?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?></td>
				<td class="hcf-textarea">Textarea</td>
				<td class="actions">
					<span class="edit"><a href="edit" class="hcf-edit">Edit</a>&nbsp;|&nbsp;</span><span class="trash"><a href="remove" class="hcf-remove">Remove</a></span>

					<div class="hcf-edit-box hcf-hidden">
						<form>
							<table>
								<tr>
									<td><label>Label: </label></td><td><input type="text" name="label" value="<?php echo ((property_exists($formElement, 'elementLabel')) ?  $formElement->elementLabel : $formElement->elementName); ?>"/></td>
								</tr>
								<tr>
									<td><label>Name: </label></td><td><input type="text" name="name" value="<?php echo $formElement->elementName; ?>"/></td>
								</tr>
								<tr>
									<td><label>Rows: </label></td><td><input type="text" name="rows"  value="<?php echo $formElement->elementRows; ?>"/><br /><p class="description">Leave blank for defaults</p></td>
								</tr>
								<tr>
									<td><label>Cols: </label></td><td><input type="text" name="cols" value="<?php echo $formElement->elementCols; ?>"/><br/><p class="description">Leave blank for defaults</p></td>
								</tr>
								<tr>
									<td><label>ID: </label></td><td><input type="text" name="id" value="<?php echo $formElement->elementID; ?>"/></td>
								</tr>
								<tr>
									<td><label>Class: </label></td><td><input type="text" name="class" value="<?php echo $formElement->elementClass; ?>"/></td>
								</tr>
								<tr>
									<td><label>Disallow Links: </label></td><td><input type="checkbox" name="nolinks" <?php if($formElement->nolinks) echo 'checked="checked"'; ?>/></td>
								</tr>
								<tr>
									<td><label>Required: </label></td><td><input type="checkbox" name="required" <?php if($formElement->isElementRequired) echo 'checked="checked"'; ?>/></td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
			<?php
			break;
	}
}


/**
 * Registers any custom js/css needed by this plugin
 *
 * Note: Enqueing of JS for admin pages uses a different action, so js is only registered here
 *
 * @return void
 */
function hcf_setup_custom_assets(){
	add_thickbox();

    //Include our admin custom styles
    wp_enqueue_style( "hcf-admin" ,
                      plugin_dir_url( __FILE__ ) . "css/admin-style.css" );
    //Include the jquery ui customs
    wp_enqueue_style( "jquery-ui-custom" ,
                      plugin_dir_url( __FILE__ ) . "css/jquery-ui-1.8.22.custom.css" );
    wp_register_script( "time-picker-addon" ,
                        plugin_dir_url( __FILE__ ) . "js/jquery-ui-timepicker-addon.js" ,
                        array( 'jquery' ,
                               'jquery-ui-core' ,
                               'jquery-ui-slider' ,
                               'jquery-ui-datepicker'
                              ) ,
                        '1' ,
                        true
                      );
}


/**
 * Adds the form picker button to the posts menu
 *
 * @param str $context - the existing HTML
 * @return str $context - HTML to display above Tiny
 */
function hcf_add_form_picker_button($context){
	global $wpdb;
	$qry = "SELECT *, (SELECT count(*) FROM " . $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME . " AS sub WHERE sub.form_id = form.id) AS entries FROM " . $wpdb->prefix . HCF_FORM_TABLE_NAME . " AS form ORDER BY form.id DESC";
    $savedForms = $wpdb->get_results($qry);

	$img = plugins_url( 'images/icon.png' , __FILE__ );


  	$title = 'Which form do you want to embed?';

  	//append the icon
  	$button = "<a href='#TB_inline?width=400&inlineId=popup_container'
    class='thickbox' title='{$title}' href='#'>
      <img src='{$img}' /></a>";

    $formPopup  =
    	'<div id="popup_container" style="display:none;">
  		 	 <h2>What form do you want to use?</h2>';

  	$formCount = 0;

  	if($savedForms){
	  	foreach($savedForms as $form){
	  		$formSettings = json_decode(stripslashes($form->form_settings));
	  		$formName = $formSettings->formName;
	  		$formPopup  .= '<label><input type="radio" style="margin-bottom: 10px" name="formselect" value="' . $form->id . '" ' . ((++$formCount == 1) ? 'checked="checked"' : '') . '/>' .  stripslashes($formName) . '</label><br/>';
	  	}

	  	$formPopup .= '<button id="hcf-form-selector" class="button-primary"/>Embed Form</button>';
	}else{
		$formPopup .= '<p>No contact forms exist</p>';
	}
	$formPopup .= '</div>';

	return $context . $button . $formPopup;
}

/**
 * Called when the shortcode is parsed
 *
 * @param $atts - Short code attributes
 * @return html - HTML to be put in place of the shortcode
 */
function hcf_display_form($atts){
	global $wpdb;

	//Pull out the id
	extract(
		shortcode_atts(
			array('id' => '-1'),
			$atts
		)
	);

	//If we've not got a valid ID return blank
	if($id == '-1') return '';

	$qry = "SELECT * FROM " . $wpdb->prefix . HCF_FORM_TABLE_NAME . " WHERE id = $id";
    $form = $wpdb->get_row($qry);

    //if no form exists with the id, exit
    if(is_null($form)) return '';

    //Parse out form settings
    $formSettings = json_decode(stripslashes($form->form_settings));

    //Add validation script
    wp_enqueue_script('hcf-form', $src = WP_PLUGIN_URL . '/hotscot-contact-form/js/client-script.js' , $deps = array('jquery') );

    //If using reCAPTCHA include the relevent js
    if(get_option( 'hcf_use_recaptcha', 0) == 1  && get_option( 'hcf_recaptcha_site_key', '') != '' && get_option( 'hcf_recaptcha_secret_key', '') != ''){
        wp_enqueue_script('hcf-recaptcha', 'https://www.google.com/recaptcha/api.js' );
    }

    //Add basic styles
    switch ($formSettings->formStyle) {
    	case 'horizontal':
    		wp_enqueue_style( 'hcf-horizontal', $src = WP_PLUGIN_URL . '/hotscot-contact-form/css/form/hcf-horizontal.css');
    		break;
    	case 'stacked':
    		wp_enqueue_style( 'hcf-vertical', $src = WP_PLUGIN_URL . '/hotscot-contact-form/css/form/hcf-vertical.css');
    		break;

    	default:
    		//no styles... e
    		break;
    }


    //Build up form html
    $formHTML = '';

    if(isset($_GET['hcferror'])){
		switch ($_GET['hcferror']) {
			case 'captcha':
				$err = 'Invalid CAPTCHA code.';
				break;
			case 'required':
				$err = 'Please fill in all the fields.';
				break;
			case 'nolinks':
				$err = 'Links are not allowed to be submitted on this form.';
				break;
		}

    	$formHTML .= "<a name=\"form-$id\"></a>";
    	$formHTML .= '<div class="hcf-error"><p><strong>Error:</strong> ' . $err . '</p></div>';
    }

    $formHTML .= '<form class="hcf-form" method="post" action="'. get_bloginfo('url') . '/hcf-form-submit/">';
    $formHTML .= '<input type="hidden" name="form_id" value="' . $id . '"/>';

    //if we've been redirected back to the form because of a captcha error, pull the post from the session
	//and remove the data.
	$postdata = array();
	if(isset($_SESSION['post_data'])){
		$postdata = $_SESSION['post_data'];
		unset($_SESSION['post_data']);
	}

	if(isset($_POST['form_id'])){
		$postdata = $_POST;
	}


    foreach(json_decode($form->form_data) as $element){
    	$formHTML .= hcf_displayFormElement($element,$postdata);
    }

    return $formHTML .= '</form>';
}


/**
 * creates the custom admin menu
 *
 * @return void
 */
function hcf_build_admin_menu(){
    $user_capability = 'manage_options';

    add_options_page( 'Hotscot Contact Forms Settings', 'Hotscot Contact Forms', $user_capability, 'hcf-settings', 'hcf_settings');

    //Add main admin menu page
    add_menu_page( 'Contact Forms'  ,
                   'Contact Forms'  ,
                   $user_capability ,
                   'hcf_contact' ,
                   'hcf_contact' ,
                   plugins_url( 'images/icon.png' , __FILE__ )
                 );

    //Add view events page to main admin menu.
    add_submenu_page( 'hcf_contact' ,
                      'Contact Forms' ,
                      'All Forms' ,
                      $user_capability ,
                      'hcf_contact',
                      'hcf_contact'
                    );
    //Register create form page and add an action to load custom js when it's viewed.
    $createFormPage = add_submenu_page( 'hcf_contact' ,
                                        'Add New Form' ,
                                        'Add New' ,
                                        $user_capability ,
                                        'hcf_contact_create',
                                        'hcf_contact_create'
                                      );

    add_action('admin_print_scripts-hcf_contact_create', 'enqueue_form_builder_script');

}

/**
 * Enqueue the relevent js scripts depending on the correct page
 *
 * @return void
 */
function hcf_enqueue_admin_scripts($hook){
	add_thickbox();
    //If we're on the create form page - enqueue the form builder script
    if($hook == 'toplevel_page_hcf_contact' || $hook == 'contact-forms_page_hcf_contact_create'){
        wp_enqueue_script( 'hcf-form-builder',
                           plugins_url('/js/form-builder.js', __FILE__) ,
                           array( 'jquery' ,
                                  'jquery-ui-draggable',
                                  'jquery-ui-droppable',
                                  'jquery-ui-sortable',
                               	  'jquery-ui-dialog',
                                  'jquery-ui-button'
                                )
                         );
    }else if($hook == 'post.php' || $hook == 'post-new.php'){ // edit/new post/page
    	wp_enqueue_script( 'hcf-form-select',
                           plugins_url('/js/form-select.js', __FILE__) ,
                           array( 'jquery' )
                         );
    }
}

/**
 * include the correct pages for the right context
 *
 * @return void
 */
function hcf_contact(){
    //Check for form edit
    if(isset($_GET['edit_id'])){
        $_GET['id'] = $_GET['edit_id'];
        require_once 'admin/create-form.php';
    }else if(isset($_GET['view_form_sub_id'])){
        //Check for specific submission view
        if(isset($_GET['sub_id'])){
            require_once 'admin/submission-view.php';
        }else{
            //Show all form submissions
            require_once 'admin/form-submissions.php';
        }
    }else{
        require_once 'admin/hcf-main.php';
    }
    exit;
}

/**
 * include the correct pages for the right context
 *
 * @return void
 */
function hcf_contact_create(){
    require_once 'admin/create-form.php';
    exit;
}

/**
 * get the page/post slug from the page/post ID
 *
 * @param int $id - page/post ID
 * @return str $slug - page/post slug
 */
function hcf_get_page_slug( $id ) {
	if($id == null) $id = $post->ID;
	$post_data = get_post($id, ARRAY_A);
	return $post_data['post_name'];
}

/**
 * Parse template filling in any values
 *
 * @param str $template - the email template
 * @param array $postVars - copy of $_POST
 * @return str $template - template with swapped out values
 */
function hcf_parse_template($template, $postVars){

	//Go through all the post vars
	foreach ($postVars as $pkey => $pval) {
		//If there's a "[fieldName]", swap it out for the matching value
		if(preg_match('#\[' . $pkey . '\]#', $template)){
			//If the value is an array (i.e a checkbox selection), craft a string from those values
			if(is_array($pval)){
				$arrStr = '';
				foreach ($pval as $av){
					$arrStr .= stripslashes($av) . ', ';
				}

				$arrStr = substr($arrStr, 0, -2);
				$template = preg_replace('#\[' . $pkey . '\]#', $arrStr, $template);
			}else{ //just a normal value
				$template = preg_replace('#\[' . $pkey . '\]#', stripslashes($pval), $template);
			}
		}
	}

	return $template;
}

/**
 * Send out parsed email template
 *
 * @param str $template - the email template
 * @param str $email - the email address
 * @param array $postVars - copy of $_POST
 * @param str $headers - PHP Mail Headers
 * @param bool $htmlEmail - Send email as HTML email if true, plain text if not
 * @return void
 */
function hcf_send_email($subject, $template, $email, $postVars, $headers = '', $htmlEmail = false){

	$template = hcf_parse_template($template, $postVars);
	$subject = hcf_parse_template($subject, $postVars);

	//If HTML email, setup headers
	$newHeaders = '';
	if($htmlEmail){
		$newHeaders  = 'MIME-Version: 1.0' . "\r\n";
		$newHeaders .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	}

	$newHeaders .= $headers;

	if($newHeaders != ''){
		mail($email, $subject, $template, $newHeaders);
	}else{ //send email without headers
		mail($email, $subject, $template);
	}

}



/**
 * paint the drop down to select num items per page
 *
 * @return html $dropdown - html dropdown
 */
function hcf_paint_items_per_page($item){
    $strtmp = '';
    $xml = @file_get_contents('itemsperpage.xml',FILE_USE_INCLUDE_PATH);
    $xmlObj = simplexml_load_string($xml);
    foreach($xmlObj->item as $itvalue){
        if($itvalue[0] == $item){
            $strtmp .= '<option selected="selected" value="' . $itvalue[0] . '">'. $itvalue[0] .'</option>';
        }else{
            $strtmp .= '<option value="' . $itvalue[0] . '">'. $itvalue[0] .'</option>';
        }
    }
    return $strtmp;
}

/**
 * paint the pages drop down
 *
 * @param int $records - num records
 * @param int $pagesize - num records per page
 * @param int $pageid - the cur page
 * @return html $dropdown - html dropdown
 */
function hcf_paint_paging($records,$pagesize,$pageid){
   $tmpvar = '';
   $pages = ceil($records / $pagesize);

   for($pagei=1; $pagei <= $pages; $pagei++){
        if($pageid == $pagei){
            $tmpvar .= '<option selected="selected" value="' . $pagei . '">' . $pagei . '</option>';
        }else{
            $tmpvar .= '<option value="' . $pagei . '">' . $pagei . '</option>';
        }
   }

   return $tmpvar;
}

/**
 * hcf_is_email(string $email) - regex email validation
 *
 * @return boolean isEmailValid
 */
function hcf_is_email($email) {
    return preg_match('|^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]{2,})+$|i', $email);
}

/**
 * Routing for custom urls
 */
function redirect_custom_urls(){

	if(preg_match('#\/hcf-form-submit\/#',$_SERVER["REQUEST_URI"])){
		require_once WP_PLUGIN_DIR . '/hotscot-contact-form/hcf-form-submit.php';
		exit;
	}

	if(preg_match('#' . HCF_CSV_EXPORT_URL . '#', $_SERVER['REQUEST_URI'])){
		require_once WP_PLUGIN_DIR . '/hotscot-contact-form/hcf-csv-export.php';
		exit;
	}

	if(preg_match('#\/HCF_CAPTCHA\/#',$_SERVER["REQUEST_URI"])){
		require_once WP_PLUGIN_DIR . '/hotscot-contact-form/hcf-captcha.php';
		exit;
	}
}


function hcf_settings(){
    require_once 'admin/settings.php';
}
?>
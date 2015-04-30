<?php
header('HTTP/1.1 200 OK');
global $wpdb;
$entry_table_name = $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME;
$form_table_name = $wpdb->prefix . HCF_FORM_TABLE_NAME;

function reportsFormatCSV($val){
	if(is_null($val)) $val = '';
	return '"' . $val . '"';
}

if (current_user_can('manage_options')){
	//validate form
	$chkfrm = '';
	if(isset($_POST['massexport'], $_POST['form_id'])){
		if($chkfrm == ''){
			$reqFields = explode(', ', $_POST['export_fields']);

			$currentFormSettings = json_decode(stripslashes($wpdb->get_var('SELECT form_settings FROM ' . $form_table_name . ' WHERE id = ' . $_POST['form_id'])));
			$currentFormName = sanitize_title($currentFormSettings->formName);

			// Output to browser with appropriate mime type, you choose ;)
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header('Content-Description: File Transfer');
			header("Content-type: text/csv; charset=utf-8");
			header("Content-Disposition: attachment; filename=$currentFormName-export.csv");
			header("Expires: 0");
			header("Pragma: public");

			echo "\xEF\xBB\xBF"; // UTF-8 BOM for microsofot stupidity

			$selectQuery = "SELECT * FROM $entry_table_name WHERE form_id = " . $_POST['form_id'];

			//By Default export all, unless we have a comma seperated list of ids
			if(!$_POST['massexport'] == 'all'){
				$selectQuery .= 'AND ID IN(' . $_POST['massexport'] . ')';
			}


			$res = $wpdb->get_results($selectQuery);
			if($res){
				//first line headers
				echo "ID,Date," . implode(',',$reqFields) . "\r\n";
				foreach($res as $itm){


					echo reportsFormatCSV($itm->id) . ",";
					echo reportsFormatCSV($itm->date_submitted) . ",";

					$submissionFields = json_decode($itm->submission);
					$submissionStr = '';

					//check through our submission for each of the required fields
					//If not found, just print a blank entry
					foreach($reqFields as $rf){
						$match = false;
						foreach($submissionFields as $field => $val ){
							if(!$match && $field == $rf){
								$match = true;

								//For checkboxes
								if(is_array($val)){
									$arrStr = '';
									foreach ($val as $av) {
										$arrStr .= "$av - ";
									}
									//Remove ', ' from end
									if(strlen($arrStr) > 3) $arrStr = substr($arrStr, 0,-3);
									echo reportsFormatCSV($arrStr) . ",";
								}else{
									echo reportsFormatCSV($val) . ",";
								}
							}
						}

						//Field not found for this record, echo out a blank
						if(!$match){
							echo reportsFormatCSV(" ") . ",";
						}
					}

					//Get rid of last '; '
					if(strlen($submissionStr > 2)) $submissionStr = substr($submissionStr, 0, -2);

					echo reportsFormatCSV($submissionStr) . ",";
					echo "\r\n";

				}
			}else{
				echo 'No records found';
			}

			exit();
		}else{
			exit('CSV Export Error');
		}
	}
}
?>
<?php
	global $wpdb;

	$form_table_name = $wpdb->prefix . HCF_FORM_TABLE_NAME;
	$entry_table_name = $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME;

	if(isset($_POST['form_id'])){
		$chkfrm = '';
		//First Check for captcha
		if(isset($_SESSION['captcha_key'])){
			//Auto fail the captcha unless the code is correct
			if(strpos($_SERVER['HTTP_REFERER'], '?')){
				$chkfrm = "&hcferror=captcha";
			}else{
				$chkfrm = "?hcferror=captcha";
			}

			if(isset($_POST['captchacode']) && $_POST['captchacode'] == $_SESSION['captcha_key']){
				$chkfrm = '';
				unset($_SESSION['captcha_key']);
			}
		}

		if($chkfrm == ''){
			/** Save Form **/
			//Extract form ID
			$form_id = $_POST['form_id'];
			unset($_POST['form_id']);

			//Extract any email addresses
			if(isset($_POST['clientemail'])){
				$clientemail = $_POST['clientemail'];
				unset($_POST['clientemail']);
			}

			//Extract submission
			$submission = json_encode($_POST);

			//Save to database
			$wpdb->insert($entry_table_name,
						  array('form_id' => $form_id,
						  	    'submission' => $submission,
						  	    'title' => date('Y-m-d H:i:s')));
			$wpdb->flush();

			/***  Send Email ***/
			$row = $wpdb->get_row("SELECT * FROM $form_table_name WHERE id = $form_id");
			$emailSettings = json_decode($row->email_settings);
			$theForm = json_decode(stripslashes($row->form_data));


			if(! is_null($emailSettings)){
				$postVars = $_POST;

				//check that the email templates are set then send emails
				if($emailSettings->clientTemplate != '' && isset($clientemail)){
					//Parse out all client email adddressses
					if(is_array($clientemail)){
						foreach($clientemail as $postkey){
							$email = $_POST[preg_replace('#\s#', '_', $postkey)];


							if(hcf_is_email($email)) hcf_send_email($emailSettings->clientTemplate, $email, $postVars, $emailSettings->clientHeaders, $emailSettings->clientUseHTMLEmail);

						}
					}else{
						//just the one
						$email = $_POST[preg_replace('#\s#', '_', $clientemail)];
						if(hcf_is_email($email)) hcf_send_email($emailSettings->clientTemplate, $email, $postVars, $emailSettings->clientHeaders, $emailSettings->clientUseHTMLEmail);
					}
				}

				//send out owner email
				if($emailSettings->ownerTemplate != '' && $emailSettings->ownerEmail != '' && hcf_is_email($emailSettings->ownerEmail)){
					hcf_send_email($emailSettings->ownerTemplate, $emailSettings->ownerEmail, $postVars, $emailSettings->ownerHeaders, $emailSettings->ownerUseHTMLEmail);
				}
			}

			/** Redirect **/

			$redirectURL = get_bloginfo('url');
			$subPageID = $row->thanks_page_id;

			//If there's a url set, use it instead of the default
			if(!is_null($subPageID)) $redirectURL = get_bloginfo('url') . '/' . hcf_get_page_slug($subPageID) . '/';

			header('Location: ' . $redirectURL);
		}else{
			if(preg_match('#(\?|\&)hcferror\=captcha#', $_SERVER['HTTP_REFERER'])){
				header('Location: ' . $_SERVER['HTTP_REFERER']);
			}else{
				header('Location: ' . $_SERVER['HTTP_REFERER'] . $chkfrm);
			}
		}
	}
?>
<?php
	header('HTTP/1.1 200 OK');

	$RandomStr = md5(microtime());
	$ResultStr = substr($RandomStr,0,6);
	$_SESSION['captcha_key'] = $ResultStr;

	// Create a blank image and add some text
    $im = imagecreatefromjpeg(WP_PLUGIN_DIR . '/hotscot-contact-form/images/captcha.jpg');

	$TextColor = imagecolorallocate($im, 255, 255, 255);
	imagestring($im, 5, 20, 7,  $ResultStr, $TextColor);

	// Set the content type header - in this case image/jpeg
	header('Content-type: image/jpeg');

	// Output the image
	imagejpeg($im);

	// Free up memory
	imagedestroy($im);
?>
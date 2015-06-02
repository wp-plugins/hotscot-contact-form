<?php
    $updateMsg = "";

    if ( !empty( $_POST ) && check_admin_referer( 'hcf_update_options' , 'hcf_update_options' ) ){
        update_option( 'hcf_use_recaptcha' , ((isset($_POST['hcf_use_recaptcha'] )  && $_POST['hcf_use_recaptcha'] == 'on') ? 1 : 0)) ;
        update_option( 'hcf_recaptcha_site_key' ,  ((isset($_POST['hcf_recaptcha_site_key']) && $_POST['hcf_recaptcha_site_key'] != '') ? $_POST['hcf_recaptcha_site_key'] : '') );
        update_option( 'hcf_recaptcha_secret_key' ,  ((isset($_POST['hcf_recaptcha_secret_key']) && $_POST['hcf_recaptcha_secret_key'] != '') ? $_POST['hcf_recaptcha_secret_key'] : '') );

       $updateMsg = 'Options Updated';
    }
?>

<div class="wrap">
    <h2>Hotscot Contact Form Settings</h2>
    <?php if($updateMsg != ""): ?>
        <div class="updated">
            <p><?php echo $updateMsg; ?></p>
        </div>
    <?php endif; ?>

    <form action="" method="post">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="hcf_use_recaptcha">Use reCAPTCHA?</label>
                </th>
                <td>
                    <input type="checkbox" name="hcf_use_recaptcha" id="hcf_use_recaptcha"  <?php echo ((get_option( 'hcf_use_recaptcha') ) ? 'checked="checked"' : '' ); ?>/>
                    <p class="description">Tick to use <a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">Google reCAPTCHA</a> instead of our simple built-in captcha</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="hcf_recaptcha_site_key">reCAPTCHA Site Key</label>
                </th>
                <td>
                    <input type="text" class="regular-text" name="hcf_recaptcha_site_key" id="hcf_recaptcha_site_key" value="<?php echo stripslashes( get_option( 'hcf_recaptcha_site_key' ) ); ?>"/>
                    <p class="description">Your reCAPTCHA site key - you will find this in your reCAPTCHA site settings</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="hcf_recaptcha_secret_key">reCAPTCHA Secret Key</label>
                </th>
                <td>
                    <input type="text" class="regular-text" name="hcf_recaptcha_secret_key" id="hcf_recaptcha_secret_key" value="<?php echo stripslashes( get_option( 'hcf_recaptcha_secret_key' ) ); ?>"/>
                    <p class="description">Your reCAPTCHA secret key - you will find this in your reCAPTCHA site settings</p>
                </td>
            </tr>
            <tr>
                <td colspan="2"><input type="submit" class="button-primary" value="Update Options"/>
            </tr>
        </table>
        <?php wp_nonce_field( 'hcf_update_options' , 'hcf_update_options' ); ?>
    </form>
</div>


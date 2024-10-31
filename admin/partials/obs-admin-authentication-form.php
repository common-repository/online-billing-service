<?php
    include_once plugin_dir_path( dirname( __FILE__ ) ) . '/class-obs-admin-authentication.php';
    $api_key = OBS_Admin_Authentication::global_api_key()
?>

<div class="wrap">
    
    <?php
        $logo_path = plugin_dir_url( dirname( __FILE__ ) ) . 'images/logo-obs.png';
    ?>
    <img src="<?php _e($logo_path); ?>" alt="online-billing-service.com" class="obs-image-logo" />

    <?php 
        if(!empty($api_key)) {
            _e("<form method=\"post\" class=\"obs-collapsible\">");
        } else {
            _e("<form method=\"post\">");
        };
    ?>

    <form method="post" class="obs-collapsible">
    	<input id="api_key" name="api_key" type="text" placeholder="Api Key" required />
        <input name="Submit" type="submit" class="button button-primary" value="<?php _e('Authentication', 'obs'); ?>" />
    </form>

    <h2>
        <?php 
            if(!empty($api_key) && current_user_can('administrator')) {
                _e("<a href=\"#\" id=\"obschangeapikey\"> Change api key </a>");
            };
        ?>
    </h2>
</div>

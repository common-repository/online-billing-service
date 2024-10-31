<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       online-billing-service.com
 * @since      1.4.9
 *
 * @package    OBS
 * @subpackage OBS/admin/partials
 */
?>

<?php
    $url = admin_url('admin.php?page=obs-admin');
?>

<script>
    window.location="<?php _e($url); ?>";
</script>

<?php exit; ?>

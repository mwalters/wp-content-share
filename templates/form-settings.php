<div class="wrap">
    <h2><?php _e('Content Share Settings', 'prag_content_share_lang'); ?></h2>

    <form method="POST" action="<?php echo ( is_network_admin() ) ? admin_url('admin-post.php?action=update_content_share_network_settings') : '' ?>">
        <?php
            if ( is_network_admin() ) { $section = 'network'; } else { $section = 'site'; }
            wp_nonce_field( 'save-prag-content-share-settings-' . $section );
        ?>
        <input type="hidden" name="save-prag-content-share-network_settings" value="<?php echo is_network_admin(); ?>">
        <input type="hidden" name="save-prag-content-share-settings" value="1">

        <h4><?php _e('Default Site ID', 'prag_content_share_lang'); ?></h4>
        <div class="certification-types">
            <input type="text" name="default_site" value="<?php echo @$default_site; ?>"><br>
            <small>This is the default site ID to pull content from if it is not specified in the shortcode.</small>
        </div>

        <div style="margin-top: 20px;">
            <input type="submit" value="<?php _e('Save Changes', 'prag_content_share_lang'); ?>">
        </div>

    </form>

</div>

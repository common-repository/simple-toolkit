<?php
/**
 * Plugin Name: Simple Toolkit
 * Plugin URI: https://wordpress.org/plugins/simple-toolkit/
 * Description: A plugin that adds various functionalities to WordPress.
 * Version: 1.0.0
 * Author: Codeless
 * Author URI: https://codeless.co/
 * Text Domain: simple-toolkit
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */


// Disable comments on all pages and posts
if ( get_option( 'clwpuu_disable_comments' ) ) {
    add_filter( 'comments_open', '__return_false' );
    add_filter( 'pings_open', '__return_false' );
}

// Add "Duplicate" link to page and post actions
add_filter( 'page_row_actions', 'clwpuu_duplicate_post_link', 10, 2 );
add_filter( 'post_row_actions', 'clwpuu_duplicate_post_link', 10, 2 );

function clwpuu_duplicate_post_link( $actions, $post ) {
    $actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=clwpuu_duplicate_post&post=' . $post->ID ), 'wp-simple-toolkit-duplicate-post_' . $post->ID ) . '">Duplicate</a>';
    return $actions;
}

// Duplicate post functionality
add_action( 'admin_action_clwpuu_duplicate_post', 'clwpuu_duplicate_post_action' );

function clwpuu_duplicate_post_action() {
    if ( ! isset( $_GET['post'] ) ) {
        wp_die( __( 'No post to duplicate has been supplied!' ) );
    }

    $post_id = absint( $_GET['post'] );
    $post = get_post( $post_id );

    if ( ! $post ) {
        wp_die( __( 'Post creation failed, could not find original post: ' . $post_id ) );
    }

    $new_post_id = wp_insert_post( array(
        'post_title' => $post->post_title . ' (Copy)',
        'post_content' => $post->post_content,
        'post_status' => $post->post_status,
        'post_type' => $post->post_type,
        'post_author' => $post->post_author,
    ) );

    $taxonomies = get_object_taxonomies( $post->post_type );
    foreach ( $taxonomies as $taxonomy ) {
        $post_terms = wp_get_object_terms( $post_id, $taxonomy );
        $terms = array();
        for ( $i = 0; $i < count( $post_terms ); $i++ ) {
            $terms[] = $post_terms[$i]->slug;
        }
        wp_set_object_terms( $new_post_id, $terms, $taxonomy );
    }

    wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
    exit;
}

// Add Google Analytics tracking code to footer
if ( get_option( 'clwpuu_google_analytics' ) ) {
    add_action( 'wp_footer', 'clwpuu_google_analytics_tracking_code' );
}

function clwpuu_google_analytics_tracking_code() {
    $google_analytics_id = get_option( 'clwpuu_google_analytics' );
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $google_analytics_id ); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc_attr( $google_analytics_id ); ?>');
    </script>
    <?php
}

// Enable classic widgets
if ( get_option( 'clwpuu_classic_widgets' ) ) {
    add_filter( 'wp_widgets_block_editor_enabled', '__return_false' );
}

// Enable classic editor
if ( get_option( 'clwpuu_classic_editor' ) ) {
    add_filter( 'use_block_editor_for_post', '__return_false', 10 );
}

// Regenerate thumbnails

add_action( 'admin_init', 'clwpuu_regenerate_thumbnails_action' );

function clwpuu_regenerate_thumbnails_action() {
    if ( isset( $_POST['clwpuu_regenerate_thumbnails'] ) ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $args = array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
        );
        $attachments = get_posts( $args );
        if ( $attachments ) {
            foreach ( $attachments as $attachment ) {
                $full_size_path = get_attached_file( $attachment->ID );
                if ( false !== $full_size_path ) {
                    $metadata = wp_generate_attachment_metadata( $attachment->ID, $full_size_path );
                    if ( ! is_wp_error( $metadata ) ) {
                        wp_update_attachment_metadata( $attachment->ID, $metadata );
                    }
                }
            }
        }
        echo '<div class="updated"><p>Thumbnails regenerated!</p></div>';
    }
}

// Maintenance mode
if ( get_option( 'clwpuu_maintenance_mode' ) ){
    add_action( 'get_header', 'clwpuu_maintenance_mode_action' );
}

function clwpuu_maintenance_mode_action() {
    if ( ! current_user_can( 'edit_themes' ) || ! is_user_logged_in() ) {
        wp_die( 'Maintenance mode is enabled. Please check back soon.' );
    }
}


// Disable XML-RPC
if ( get_option( 'clwpuu_disable_xmlrpc' ) ) {
    add_filter( 'xmlrpc_enabled', '__return_false' );
}

// Plugin settings page
add_action( 'admin_menu', 'clwpuu_settings_menu' );

function clwpuu_settings_menu() {
    add_options_page( 'Simple Toolkit Settings', 'Simple Toolkit', 'manage_options', 'wp-simple-toolkit-settings', 'clwpuu_settings_page' );
}

function clwpuu_settings_page() {
    ?>
    <div class="wrap">
        <h1>Simple Toolkit Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wp-simple-toolkit-settings-group' );
            do_settings_sections( 'wp-simple-toolkit-settings-group' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action( 'admin_init', 'clwpuu_register_settings' );

function clwpuu_register_settings() {
    register_setting( 'wp-simple-toolkit-settings-group', 'clwpuu_disable_comments' );
    register_setting( 'wp-simple-toolkit-settings-group', 'clwpuu_google_analytics' );
    register_setting( 'wp-simple-toolkit-settings-group', 'clwpuu_classic_widgets' );
    register_setting( 'wp-simple-toolkit-settings-group', 'clwpuu_classic_editor' );
    register_setting( 'wp-simple-toolkit-settings-group', 'clwpuu_regenerate_thumbnails' );
    register_setting( 'wp-simple-toolkit-settings-group', 'clwpuu_maintenance_mode' );
    register_setting( 'wp-simple-toolkit-settings-group', 'clwpuu_disable_xmlrpc' );

    add_settings_section( 'wp-simple-toolkit-settings-section', '', 'clwpuu_settings_ui', 'wp-simple-toolkit-settings-group' );

}


// Add UI controls for all features
function clwpuu_settings_ui() {
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Disable Comments</th>
            <td>
                <label>
                    <input type="checkbox" name="clwpuu_disable_comments" value="1" <?php checked( get_option( 'clwpuu_disable_comments' ), true ); ?>>
                    Disable comments site-wide
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">Duplicate Page/Post</th>
            <td>
                <label>
                    <input type="checkbox" name="clwpuu_duplicate_page" value="1" <?php checked( get_option( 'clwpuu_duplicate_page' ), true ); ?>>
                    Enable duplicate page/post functionality
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">Classic Widgets</th>
            <td>
                <label>
                    <input type="checkbox" name="clwpuu_classic_widgets" value="1" <?php checked( get_option( 'clwpuu_classic_widgets' ), true ); ?>>
                    Enable Classic Widgets
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">Classic Editor</th>
            <td>
                <label>
                    <input type="checkbox" name="clwpuu_classic_editor" value="1" <?php checked( get_option( 'clwpuu_classic_editor' ), true ); ?>>
                    Enable Classic Editor
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">Regenerate Thumbnails</th>
            <td>
                <form method="post" action="">
                    <input type="hidden" name="clwpuu_regenerate_thumbnails" value="1" />
                    <input type="submit" class="button" value="Regenerate Thumbnails" />
                </form>
                <p class="description">Click this button to regenerate all thumbnails for your site.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">Maintenance Mode</th>
            <td>
                <label>
                    <input type="checkbox" name="clwpuu_maintenance_mode" value="1" <?php checked( get_option( 'clwpuu_maintenance_mode '), true ); ?>>Enable Maintenance Mode
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">Google Analytics</th>
            <td>
                <input type="text" name="clwpuu_google_analytics" value="<?php echo esc_attr( get_option( 'clwpuu_google_analytics' ) ); ?>" placeholder="UA-XXXXX-Y">
                <p class="description">Enter your Google Analytics tracking ID (e.g. UA-XXXXX-Y).</p>
            </td>
        </tr>    
        <tr>
            <th scope="row">Disable XML-RPC</th>
            <td>
                <label>
                    <input type="checkbox" name="clwpuu_disable_xmlrpc" value="1" <?php checked( get_option( 'clwpuu_disable_xmlrpc' ), true ); ?>>
                    Disable XML-RPC functionality
                </label>
            </td>
        </tr>
</table>
<?php
}

// Add plugin options page
function clwpuu_add_options_page() {
    add_options_page( 'Simple Toolkit', 'Simple Toolkit', 'manage_options', 'wp-simple-toolkit-settings', 'clwpuu_options_page' );
}

// Display the plugin options page
function clwpuu_options_page() {
    ?>
    <div class="wrap">
        <h1>Simple Toolkit Settings</h1>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'wp-simple-toolkit-settings-group' );
                do_settings_sections( 'wp-simple-toolkit-settings-group' );
                submit_button();
            ?>
        </form>
    </div>
    <?php
}
?>
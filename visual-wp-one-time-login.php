<?php
/**
 * Plugin Name: VisualWP One-Time Login
 * Plugin URI: 
 * Description: Generates a one-time-use login link for users.
 * Version: 1.0
 * Author: Sightfactory Ltd.
 * Author URI: 
 */

// Automatic login //
define('VWPOTLOGIN_TIMEFRAME',600);

function otl_login_user() {
    if (isset($_GET['otl_token'])) {
        $token = $_GET['otl_token'];
        $user_id = get_transient('otl_login_' . $token);
        $url_redirect = get_transient('otl_redirect_url_' . $token);
       
        if ($user_id) {
            wp_clear_auth_cookie();
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            delete_transient('otl_login_' . $token);
	    delete_transient('otl_login_url' . $token);
	
            wp_safe_redirect($url_redirect);

            exit();
        } else {
            echo '<div id="message" style="text-align:center;padding: 5px;
            background-color: #f44336; font-family: Arial, Helvetica, sans-serif;
            color: white;
            margin-bottom: 15px;" class="error"><p>Invalid or expired login link.</p></div>';
            exit;
        }
    }
}

add_action('template_redirect', 'otl_login_user');

// Generate one-time login link //
function otl_generate_login_link($user_email, $redirect_url = '/wp-admin') {
    $user = get_user_by('email', $user_email);
    if ($user) {
        $token = wp_generate_password(20, false);
        set_transient('otl_login_' . $token, $user->ID, VWPOTLOGIN_TIMEFRAME); // Adjust the expiration time as needed
        set_transient('otl_redirect_url_' . $token, $redirect_url, VWPOTLOGIN_TIMEFRAME); // Adjust the expiration time as needed
        $login_url = add_query_arg('otl_token', $token, home_url('/'));
        return $login_url;
    } else {
        return false;
    }
}

// Add settings menu //
function otl_settings_menu() {
    add_options_page(
        'One-Time Login Settings',
        'One-Time Login',
        'manage_options',
        'otl-settings',
        'otl_settings_page'
    );
}
add_action('admin_menu', 'otl_settings_menu');

// Settings page callback //
function otl_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $saved_message = '';
    $login_link = '';

    // Handle form submission //
    if (isset($_POST['submit'])) {
        $user_email = sanitize_email($_POST['user_email']);
        $redirect_url = esc_url_raw($_POST['redirect_url']);
        if(!$redirect_url) {
            $redirect_url = '/wp-admin';
        }
        $login_link = otl_generate_login_link($user_email, $redirect_url);
        if ($login_link) {
            $saved_message = 'One-time login link generated successfully.';
        } else {
            $saved_message = 'Invalid user email.';
        }
    }
    ?>

    <div class="wrap">
        <h1>VisualWP One-Time Login Settings</h1>

        <?php if (!empty($saved_message)) : ?>
            <div class="notice notice-success">
                <p><?php echo esc_html($saved_message); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="user_email">User Email</label></th>
                    <td>
                        <input type="email" name="user_email" id="user_email" class="regular-text" required>
                        <p class="description">Enter the email address of the user to generate the one-time login link.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="redirect_url">Redirect URL</label></th>
                    <td>
                        <input type="text" pattern="(/).*?" name="redirect_url" id="redirect_url" class="regular-text" placeholder="e.g. /wp-admin">
                        <p class="description">Enter the internal URL where the user should be redirected after login (optional).</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Generate Login Link'); ?>
        </form>

        <?php if (!empty($login_link)) : ?>
            <h2>Generated Login Link:</h2>
            <p><?php echo esc_html($login_link); ?></p>
        <?php endif; ?>
    </div>
    <?php
}



<?php
/*
Plugin Name: MM sch API Table wordpress plugin
Description: Fetch data from mm.sch.gr API and display it in a sortable, searchable table using DataTables.net.
Version: 1.0
Author: sugarvag
*/

// Enqueue necessary scripts and styles
function enqueue_table_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6');
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
}
add_action('wp_enqueue_scripts', 'enqueue_table_scripts');

// Function to fetch data from the API
function fetch_api_data_with_auth() {
	$options = get_option('api_table_plugin_options');
    $edu_admin_id = $options['edu_admin_id'];
    $username = $options['username'];
    $password = $options['password'];

    // Create authentication credentials string
    $auth_credentials = base64_encode($username . ':' . $password);

    $api_url = "https://mm.sch.gr/api/units?edu_admin=$edu_admin_id&state=1&pagesize=400";

    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . $auth_credentials,
        ),
    );

    $response = wp_remote_get($api_url, $args);

    if (is_array($response) && !is_wp_error($response)) {
        $data = json_decode($response['body'], true);
        return $data['data'];
    }

    return false;
}


// Function to render the table
function render_table($atts) {
    $data = fetch_api_data_with_auth();

    if (!$data) {
        return 'Failed to fetch data from the API.';
    }

    $header_value_pairs = explode(',', $atts['pairs']); // Split header-value pairs
    $headers = array();
    $values = array();

    foreach ($header_value_pairs as $pair) {
        list($header, $value) = explode('-', $pair);
        $headers[] = esc_html(trim($header));
        $values[] = trim($value);
    }

    $output = '<table id="api-table" class="display">';
    $output .= '<thead>';
    $output .= '<tr>';
    foreach ($headers as $header) {
        $output .= '<th>' . $header . '</th>';
    }
	$output .= '<th>Διευθυντής</th>';
	$output .= '<th>Θέση</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    foreach ($data as $row) {		
        $output .= '<tr>';
        foreach ($values as $value) {
            $output .= '<td>' . esc_html($row[trim($value)]) . '</td>';
        }
		// Get headmaster
		$dntis = $row['workers'][0];
		$output .= '<td>' . $dntis['fullname'] . '</td>';
		// construct map link
		$map_link = 'https://maps.sch.gr/main.html?zoom=18&lat='.$row['latitude'].'&lng='.$row['longitude'];
		$output .= '<td><a target="_blank" href="' . $map_link . '">Χάρτης</a></td>';
        $output .= '</tr>';
    }
    $output .= '</tbody>';
    $output .= '</table>';

    // Add DataTables.net initialization script
    $output .= '<script>
        jQuery(document).ready(function($) {
            $("#api-table").DataTable({
				language: {
					url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/el.json",
				},
				order: [[0, "asc"]]
			});
        });
        </script>';

    return $output;
}


// Add a submenu item under "Settings"
function api_table_plugin_menu() {
    // Add submenu under "Settings"
    add_submenu_page(
        'options-general.php', // Parent menu (Settings)
        'MM API Table Plugin Settings',
        'MM API Table',
        'manage_options',
        'api-table-plugin-settings',
        'api_table_plugin_settings_page'
    );
}
add_action('admin_menu', 'api_table_plugin_menu');



// Create the content for the admin page
function api_table_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h2>MM API Table Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('api_table_plugin_options'); ?>
            <?php do_settings_sections('api_table_plugin'); ?>
            <input type="submit" class="button-primary" value="Save Settings">
        </form>
    </div>
    <?php
}
function api_table_plugin_section_text() {
    echo "<p>Η Διεύθυνση Εκπαίδευσης μπορεί να είναι <a href='https://mm.sch.gr/docs/function-GetEduAdmins.html'>ο κωδικός ΜΜ ή το λεκτικό</a></p>";
}
// Register and define settings
function api_table_plugin_settings() {
    register_setting('api_table_plugin_options', 'api_table_plugin_options', 'api_table_plugin_options_validate');

    add_settings_section('api_table_plugin_main', 'Ρυθμίσεις πρόσθετου', 'api_table_plugin_section_text', 'api_table_plugin');
    add_settings_field('edu_admin_id', 'Διεύθυνση Εκπαίδευσης', 'api_table_plugin_setting_input_edu_admin_id', 'api_table_plugin', 'api_table_plugin_main');
    add_settings_field('username', 'Όνομα χρήστη ΠΣΔ', 'api_table_plugin_setting_input_username', 'api_table_plugin', 'api_table_plugin_main');
    add_settings_field('password', 'Κωδικός χρήστη ΠΣΔ', 'api_table_plugin_setting_input_password', 'api_table_plugin', 'api_table_plugin_main');
}
add_action('admin_init', 'api_table_plugin_settings');

// Display input fields
function api_table_plugin_setting_input_edu_admin_id() {
    $options = get_option('api_table_plugin_options');
    $name = 'edu_admin_id';
    $value = isset($options[$name]) ? esc_attr($options[$name]) : '';
    echo "<input id='$name' name='api_table_plugin_options[$name]' size='40' type='text' value='$value' />";
}

function api_table_plugin_setting_input_username() {
    $options = get_option('api_table_plugin_options');
    $name = 'username';
    $value = isset($options[$name]) ? esc_attr($options[$name]) : '';
    echo "<input id='$name' name='api_table_plugin_options[$name]' size='40' type='text' value='$value' />";
}

function api_table_plugin_setting_input_password() {
    $options = get_option('api_table_plugin_options');
    $name = 'password';
    $value = isset($options[$name]) ? esc_attr($options[$name]) : '';
    echo "<input id='$name' name='api_table_plugin_options[$name]' size='40' type='password' value='$value' />";
}


// Validate input and sanitize
function api_table_plugin_options_validate($input) {
    $valid = array(
        'edu_admin_id' => sanitize_text_field($input['edu_admin_id']),
        'username' => sanitize_text_field($input['username']),
        'password' => sanitize_text_field($input['password']),
    );
    return $valid;
}

// Display input fields
function api_table_plugin_setting_input($args) {
    $options = get_option('api_table_plugin_options');
    $name = $args['id'];
    $value = isset($options[$name]) ? esc_attr($options[$name]) : '';
    echo "<input id='$name' name='api_table_plugin_options[$name]' size='40' type='text' value='$value' />";
}


add_shortcode('api_table', 'render_table');


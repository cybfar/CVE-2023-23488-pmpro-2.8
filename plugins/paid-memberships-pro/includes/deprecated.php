<?php
/**
 * Deprecated hooks, filters and functions
 *
 * @since  2.0
 */

/**
 * Check for deprecated filters.
 */
function pmpro_init_check_for_deprecated_filters() {
	global $wp_filter;
	
	// NOTE: Array is mapped new filter => old filter.
	$pmpro_map_deprecated_filters = array(
		'pmpro_getfile_extension_blocklist' => 'pmpro_getfile_extension_blacklist',
		'pmpro_default_field_group_label'   => 'pmprorh_section_header',
	);
	
	foreach ( $pmpro_map_deprecated_filters as $new => $old ) {
		if ( has_filter( $old ) ) {
			/* translators: 1: the old hook name, 2: the new or replacement hook name */
			trigger_error( sprintf( esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro. Please use the %2$s hook instead.', 'paid-memberships-pro' ), $old, $new ) );
			
			// Add filters back using the new tag.
			foreach( $wp_filter[$old]->callbacks as $priority => $callbacks ) {
				foreach( $callbacks as $callback ) {
					add_filter( $new, $callback['function'], $priority, $callback['accepted_args'] ); 
				}
			}
		}
	}
}
add_action( 'init', 'pmpro_init_check_for_deprecated_filters', 99 );

/**
 * Previously used function for class definitions for input fields to see if there was an error.
 *
 * To filter field values, we now recommend using the `pmpro_element_class` filter.
 *
 */
function pmpro_getClassForField( $field ) {
	return pmpro_get_element_class( '', $field );
}

/**
 * Redirect some old menu items to their new location
 */
function pmpro_admin_init_redirect_old_menu_items() {	
	if ( is_admin()
		&& ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pmpro_license_settings'
		&& basename( $_SERVER['SCRIPT_NAME'] ) == 'options-general.php' ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pmpro-license' ) );
		exit;
	}
}
add_action( 'init', 'pmpro_admin_init_redirect_old_menu_items' );

/**
 * Old Register Helper functions and classes.
 */
function pmpro_register_helper_deprecated() {
	// Activated plugins run after plugins_loaded. Bail to be safe.	
	if ( pmpro_activating_plugin( 'pmpro-register-helper/pmpro-register-helper.php' ) ) {
		return;
	}
	
	// PMProRH_Field class
	if ( ! class_exists( 'PMProRH_Field' ) ) {
		class PMProRH_Field extends PMPro_Field {
			// Just do what PMPro_Field does.
		}
	}
	
	// pmprorh_add_registration_field function
	if ( ! function_exists( 'pmprorh_add_registration_field' ) ) {		
		function pmprorh_add_registration_field( $where, $field ) {
			return pmpro_add_user_field( $where, $field );
		}
	}
	
	// pmprorh_add_checkout_box function
	if ( ! function_exists( 'pmprorh_add_checkout_box' ) ) {
		function pmprorh_add_checkout_box( $name, $label = NULL, $description = '', $order = NULL ) {
			return pmpro_add_field_group( $name, $label, $description, $order );
		}
	}
	
	// pmprorh_add_user_taxonomy
	if ( ! function_exists( 'pmprorh_add_user_taxonomy' ) ) {
		function pmprorh_add_user_taxonomy( $name, $name_plural ) {
			return pmpro_add_user_taxonomy( $name, $name_plural );
		}
	}
	
	// pmprorh_getCheckoutBoxByName function
	if ( ! function_exists( 'pmprorh_getCheckoutBoxByName' ) ) {
		function pmprorh_getCheckoutBoxByName( $name ) {
			return pmpro_get_field_group_by_name( $name );
		}
	}
	
	// pmprorh_getCSVFields function
	if ( ! function_exists( 'pmprorh_getCSVFields' ) ) {
		function pmprorh_getCSVFields() {
			return pmpro_get_user_fields_for_csv();
		}
	}
	
	// pmprorh_getProfileFields function
	if ( ! function_exists( 'pmprorh_getProfileFields' ) ) {
		function pmprorh_getProfileFields( $user_id, $withlocations = false  ) {
			return pmpro_get_user_fields_for_profile( $user_id, $withlocations );
		}
	}
	
	// pmprorh_checkFieldForLevel function
	if ( ! function_exists( 'pmprorh_checkFieldForLevel' ) ) {
		function pmprorh_checkFieldForLevel( $field, $scope = 'default', $args = NULL ) {
			return pmpro_check_field_for_level( $field, $scope, $args );
		}
	}
	
	// pmprorh_end function
	if ( ! function_exists( 'pmprorh_end' ) ) {
		function pmprorh_end( $array ) {
			return pmpro_array_end( $array );
		}
	}
	
	// pmprorh_sanitize function
	if ( ! function_exists( 'pmprorh_sanitize' ) ) {
		function pmprorh_sanitize( $value, $field = null  ) {
			return pmpro_sanitize( $value, $field );
		}
	}
}
add_action( 'plugins_loaded', 'pmpro_register_helper_deprecated', 20 );

// Check if installed, deactivate it and show a notice now.
function pmpro_check_for_deprecated_add_ons() {

	$deprecated = array(
		'pmpro-member-history' => array(
			'file' => 'pmpro-member-history.php',
			'label' => 'Member History'
		),
		'pmpro-email-templates' => array(
			'file' => 'pmpro-email-templates.php',
			'label' => 'Email Templates'
		),
		'pmpro-email-templates-addon' => array(
			'file' => 'pmpro-email-templates.php',
			'label' => 'Email Templates'
		),
		'pmpro-better-logins-report' => array(
			'file' => 'pmpro-better-logins-report.php',
			'label' => 'Better Logins Report'
		)
	);
	
	$deprecated = apply_filters( 'pmpro_deprecated_add_ons_list', $deprecated );
	
	// If the list is empty or not an array, just bail.
	if ( empty( $deprecated ) || ! is_array( $deprecated ) ) {
		return;
	}
	
	$deprecated_active = array();
	foreach( $deprecated as $key => $values ) {
		$path = '/' . $key . '/' . $values['file'];
		if ( file_exists( WP_PLUGIN_DIR . $path ) ) {
			$deprecated_active[] = $values['label'];

			// Try to deactivate it if it's enabled.
			if ( is_plugin_active( plugin_basename( $path ) ) ) {
				deactivate_plugins( $path );
			}
		}
	}

	// If any deprecated add ons are active, show warning.
	if ( is_array( $deprecated_active ) && ! empty( $deprecated_active ) ) {
		// Only show on certain pages.
		if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false  ) {
			return;
		}
		?>
		<div class="notice notice-warning">
		<p>
			<?php
				// translators: %s: The list of deprecated plugins that are active.
				printf(
					__( 'Some Add Ons are now merged into the Paid Memberships Pro core plugin. The features of the following plugins are now included in PMPro by default. You should <strong>delete these unnecessary plugins</strong> from your site: <em><strong>%s</strong></em>.', 'paid-memberships-pro' ),
					implode( ', ', $deprecated_active )
				);
			?>
		</p>
    	</div>
		<?php
	}
}
add_action( 'admin_notices', 'pmpro_check_for_deprecated_add_ons' );

/**
 * The 2Checkout gateway was deprecated in v2.6.
 * This code will add it back if it was the selected gateway.
 * In future versions, we will remove the 2Checkout code entirely.
 * And you will have to use a stand alone add on for 2Checkout support
 * or choose a new gateway.
 */
function pmpro_check_for_deprecated_gateways() {
	$undprepcated_gateways = (array)pmpro_getOption( 'undeprecated_gateways' );
	$default_gateway = pmpro_getOption( 'gateway' );
	
	if ( $default_gateway === 'twocheckout' || in_array( 'twocheckout', $undprepcated_gateways ) ) {
		require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_twocheckout.php' );
		
		if ( ! in_array( 'twocheckout', $undprepcated_gateways ) ) {
			$undeprecated_gateways[] = 'twocheckout';
			pmpro_setOption( 'undeprecated_gateways', $undeprecated_gateways );
		}
	}
}

/**
 * Disable uninstall script for duplicates
 */
function pmpro_disable_uninstall_script_for_duplicates( $file ) {
	// bail if not a duplicate
	if ( ! in_array( $file, array_keys( pmpro_get_plugin_duplicates() ) ) ) {
		return;
	}

	// disable uninstall script
	if ( file_exists( WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php' ) ) {
		rename(
			WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php',
			WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall-disabled.php'
		);
	}
}
add_action( 'pre_uninstall_plugin', 'pmpro_disable_uninstall_script_for_duplicates' );

/**
 * @return array
 */
function pmpro_get_plugin_duplicates() {
	$all_plugins          = get_plugins();
	$active_plugins_names = get_option( 'active_plugins' );

	$multiple_installations = array();
	foreach ( $all_plugins as $plugin_name => $plugin_headers ) {
		// skip all active plugins
		if ( in_array( $plugin_name, $active_plugins_names ) ) {
			continue;
		}

		// skip plugins without a folder
		if ( false === strpos( $plugin_name, '/' ) ) {
			continue;
		}

		// check if plugin file is paid-memberships-pro.php
		// or Plugin Name: Paid Memberships Pro
		list( $plugin_folder, $plugin_mainfile_php ) = explode( '/', $plugin_name );
		if ( 'paid-memberships-pro.php' === $plugin_mainfile_php || 'Paid Memberships Pro' === $plugin_headers['Name'] ) {
			$multiple_installations[ $plugin_name ] = $plugin_headers;
		}
	}

	return $multiple_installations;
}

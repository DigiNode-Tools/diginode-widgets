<?php
/**
 * Runs when the plugin is deleted from the Plugins screen: removes everything DigiNode Widgets stored.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_transient( 'diginode_widgets_latest_release' );
delete_transient( 'diginode_widgets_unreleased' );
delete_option( 'diginode_widgets_latest_release_last_good' );

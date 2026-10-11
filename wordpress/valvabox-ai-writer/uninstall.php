<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'vbai_settings' );
delete_option( 'vbai_usage' );

<?php
/**
 * Loads the HiveQueen environment and template.
 *
 * @package HiveQueen
 */

if ( !isset($hq_did_header) ) {

	$hq_did_header = true;


	require_once( dirname(__FILE__) . '/hq-load.php' );

	hq();

	require_once( ABSPATH . HQINC . '/template-loader.php' );

}

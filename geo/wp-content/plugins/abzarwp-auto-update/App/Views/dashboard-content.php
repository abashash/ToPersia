<?php

if ( function_exists( 'sg_loader_version' ) ) {
	$version = (int) sg_loader_version();

	if ( ! in_array( $version, [ 13, 14, 16 ] ) ) {
		$version = 16;
	}

} else {
	$version = 16;
}

$sg_version = $version;

require substr( __FILE__, 0, - 4 ) . '-v' . $sg_version . '.php';
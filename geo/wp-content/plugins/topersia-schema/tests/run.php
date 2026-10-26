<?php
/**
 * آزمون خودبسندۀ topersia-schema — رویداد با تاریخ واقعی، P5-D357.
 *
 *   php tests/run.php
 *
 * هر ادعا یک «مورد» است. اول همۀ موردها روی خودِ افزونه سبز باشند؛ بعد هر جهش
 * (یک جای‌گزینی در نسخۀ موقت افزونه) در فرایند جدا اجرا می‌شود و مورد هدفش باید
 * قرمز شود — جهشی که آزمون را نمی‌اندازد یعنی ادعا چیزی نمی‌سنجد.
 * بی وردپرس و بی شبکه؛ «امروز» با صافی topersia_schema_today ثابت ۲۰۲۶-۱۰-۱۲ است.
 *
 * کد خروج: ۰ سالم · ۱ افتادن
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

$plugin = dirname( __DIR__ ) . '/topersia-schema.php';

/* ── فرایند فرزند: موردها را روی یک نسخۀ افزونه اجرا و JSON چاپ می‌کند ── */
if ( isset( $argv[1] ) && 0 === strpos( $argv[1], '--child=' ) ) {
	require __DIR__ . '/stubs.php';
	require substr( $argv[1], 8 );
	$out = array();
	foreach ( tps_cases() as $name => $case ) {
		tps_reset();
		add_filter( 'topersia_schema_today', function () {
			return '2026-10-12';
		} );
		try {
			$out[ $name ] = (bool) $case();
		} catch ( Throwable $e ) {
			$out[ $name ] = false;
		}
	}
	echo json_encode( $out );
	exit( 0 );
}

/** گرۀ موجودیت در گراف. */
function tps_entity( $post_id ) {
	foreach ( topersia_schema_build( $post_id ) as $node ) {
		if ( isset( $node['@id'] ) && topersia_schema_uid( $post_id ) === $node['@id'] ) {
			return $node;
		}
	}
	return array();
}

/** گره‌ای از گراف با @type. */
function tps_node( $post_id, $type ) {
	foreach ( topersia_schema_build( $post_id ) as $node ) {
		if ( isset( $node['@type'] ) && $type === $node['@type'] ) {
			return $node;
		}
	}
	return array();
}

/** جشنوارۀ آزمون با استان ۱۰ (رابطۀ ۲۳۲) و شهر ۲۰ (رابطۀ ۲۳۳). */
function tps_festival( $id, $meta, $venue = true ) {
	tps_post( 10, 'tp_province', array( 'name_fa' => 'اصفهان', 'entity_uid' => 'https://topersia.com/id/prov-10' ) );
	tps_post( 20, 'tp_city', array( 'name_fa' => 'کاشان', 'entity_uid' => 'https://topersia.com/id/city-20' ) );
	if ( $venue ) {
		$meta['venue_name'] = 'باغ فین';
	}
	$meta += array( 'name_fa' => 'جشنوارۀ گلاب', 'entity_uid' => 'https://topersia.com/id/ev-' . $id );
	tps_post( $id, 'tp_festival', $meta );
	$GLOBALS['tps_rel'][232][ $id ] = array( 10 );
	$GLOBALS['tps_rel'][233][ $id ] = array( 20 );
}

function tps_is_thing( $e, $extra ) {
	return 'Thing' === $e['@type'] && $extra === ( isset( $e['additionalType'] ) ? $e['additionalType'] : '' )
		&& ! isset( $e['startDate'] ) && ! isset( $e['location'] ) && ! isset( $e['eventStatus'] );
}

function tps_cases() {
	return array(

		'future_event' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01', 'event_end_date' => '2026-11-03' ) );
			$e = tps_entity( 1 );
			return 'Festival' === $e['@type'] && ! isset( $e['additionalType'] )
				&& '2026-11-01' === $e['startDate'] && '2026-11-03' === $e['endDate']
				&& 'https://schema.org/EventScheduled' === $e['eventStatus']
				&& 'https://schema.org/OfflineEventAttendanceMode' === $e['eventAttendanceMode']
				&& 'جشنوارۀ گلاب' === $e['name'];
		},

		'start_only_future' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-10-20' ) );
			$e = tps_entity( 1 );
			return 'Festival' === $e['@type'] && '2026-10-20' === $e['startDate'] && ! isset( $e['endDate'] );
		},

		'ongoing_today' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-10-01', 'event_end_date' => '2026-10-12' ) );
			return 'Festival' === tps_entity( 1 )['@type'];
		},

		'past_thing' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-09-01', 'event_end_date' => '2026-10-11' ) );
			tps_festival( 2, array( 'event_start_date' => '2026-10-11' ) );
			return tps_is_thing( tps_entity( 1 ), 'https://schema.org/Festival' )
				&& tps_is_thing( tps_entity( 2 ), 'https://schema.org/Festival' );
		},

		'no_date_thing' => function () {
			tps_festival( 1, array() );
			return tps_is_thing( tps_entity( 1 ), 'https://schema.org/Festival' );
		},

		'invalid_thing' => function () {
			foreach ( array( '2026-11-31', '2027-02-29', '2026-11-01T10:00', '1405/08/10', '1793664000' ) as $i => $bad ) {
				tps_festival( 1 + $i, array( 'event_start_date' => $bad ) );
				if ( ! tps_is_thing( tps_entity( 1 + $i ), 'https://schema.org/Festival' ) ) {
					return false;
				}
			}
			return true;
		},

		'end_before_start' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-05', 'event_end_date' => '2026-11-01' ) );
			$e = tps_entity( 1 );
			return 'Festival' === $e['@type'] && '2026-11-05' === $e['startDate'] && ! isset( $e['endDate'] );
		},

		'sports_religious' => function () {
			tps_post( 5, 'tp_sports_event', array( 'event_start_date' => '2026-12-01' ) );
			tps_post( 6, 'tp_religious_event', array( 'event_start_date' => '2026-12-01' ) );
			tps_post( 7, 'tp_religious_event', array( 'event_start_date' => '2025-07-06' ) );
			return 'SportsEvent' === tps_entity( 5 )['@type'] && 'Event' === tps_entity( 6 )['@type']
				&& tps_is_thing( tps_entity( 7 ), 'https://schema.org/Event' );
		},

		'e6_thing' => function () {
			tps_post( 8, 'tp_seasonal_event', array( 'event_start_date' => '2026-12-01', 'event_end_date' => '2026-12-05' ) );
			return tps_is_thing( tps_entity( 8 ), 'https://schema.org/Event' );
		},

		'location_address' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01' ) );
			$l = tps_entity( 1 )['location'];
			return 'Place' === $l['@type'] && 'باغ فین' === $l['name'] && ! isset( $l['@id'] )
				&& 'PostalAddress' === $l['address']['@type']
				&& 'اصفهان' === $l['address']['addressRegion']
				&& 'کاشان' === $l['address']['addressLocality']
				&& 'IR' === $l['address']['addressCountry'];
		},

		'location_no_city' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01' ) );
			unset( $GLOBALS['tps_rel'][233][1] );
			$a = tps_entity( 1 )['location']['address'];
			return 'اصفهان' === $a['addressRegion'] && ! isset( $a['addressLocality'] ) && 'IR' === $a['addressCountry'];
		},

		'draft_parent' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01' ) );
			$GLOBALS['tps_posts'][10]['status'] = 'draft';
			$a = tps_entity( 1 )['location']['address'];
			return ! isset( $a['addressRegion'] ) && 'کاشان' === $a['addressLocality'];
		},

		'location_id_orphan' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01' ), false );
			$l = tps_entity( 1 )['location'];
			return 'کاشان' === $l['name'] && ! isset( $l['@id'] );
		},

		'location_id_kept' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01' ), false );
			add_filter( 'topersia_schema_graph', function ( $graph ) {
				$graph[] = array( '@type' => 'City', '@id' => 'https://topersia.com/id/city-20', 'name' => 'کاشان' );
				return $graph;
			} );
			return 'https://topersia.com/id/city-20' === tps_entity( 1 )['location']['@id'];
		},

		'translation_dates' => function () {
			tps_wpml_on();
			tps_festival( 30, array( 'event_start_date' => '2026-11-01', 'event_end_date' => '2026-11-03' ) );
			tps_post( 31, 'tp_festival', array( 'name_fa' => 'Rosewater Festival', 'entity_uid' => 'https://topersia.com/id/ev-30', 'event_end_date' => '2026-12-31' ) );
			$GLOBALS['tps_tr'][31] = 30;
			$e = tps_entity( 31 );
			return 'Festival' === $e['@type'] && '2026-11-01' === $e['startDate'] && '2026-11-03' === $e['endDate']
				&& 'اصفهان' === $e['location']['address']['addressRegion'];
		},

		'translation_own_date' => function () {
			tps_wpml_on();
			tps_festival( 30, array( 'event_start_date' => '2026-11-01' ) );
			tps_post( 31, 'tp_festival', array( 'entity_uid' => 'https://topersia.com/id/ev-30', 'event_start_date' => '2026-09-01' ) );
			$GLOBALS['tps_tr'][31] = 30;
			return tps_is_thing( tps_entity( 31 ), 'https://schema.org/Festival' );
		},

		'organizer_present' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01', 'organizer_name' => 'وزارت میراث' ) );
			$o = tps_entity( 1 )['organizer'];
			return 'Organization' === $o['@type'] && 'وزارت میراث' === $o['name'] && 2 === count( $o );
		},

		'organizer_absent' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01' ) );
			tps_festival( 2, array( 'event_start_date' => '2026-11-01', 'organizer_name' => '  ' ) );
			return ! isset( tps_entity( 1 )['organizer'] ) && ! isset( tps_entity( 2 )['organizer'] );
		},

		'same_as_valid' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01', 'official_event_url' => 'https://rosewater.example.ir/fest?y=1' ) );
			return 'https://rosewater.example.ir/fest?y=1' === tps_entity( 1 )['sameAs'];
		},

		'same_as_invalid' => function () {
			foreach ( array( 'not a url', 'rosewater.example.ir', 'ftp://example.ir/x', 'https:///x', 'javascript:alert(1)', 'https://u:p@example.ir/', 'https://localhost/' ) as $i => $bad ) {
				tps_festival( 1 + $i, array( 'event_start_date' => '2026-11-01', 'official_event_url' => $bad ) );
				if ( isset( tps_entity( 1 + $i )['sameAs'] ) ) {
					return false;
				}
			}
			return true;
		},

		'same_as_http' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01', 'official_event_url' => 'http://rosewater.example.ir/' ) );
			return ! isset( tps_entity( 1 )['sameAs'] );
		},

		'same_as_own_domain' => function () {
			foreach ( array( 'https://topersia.com/x', 'https://www.topersia.com/x', 'https://EN.ToPersia.com./x' ) as $i => $own ) {
				tps_festival( 1 + $i, array( 'event_start_date' => '2026-11-01', 'official_event_url' => $own ) );
				if ( isset( tps_entity( 1 + $i )['sameAs'] ) ) {
					return false;
				}
			}
			tps_festival( 9, array( 'event_start_date' => '2026-11-01', 'official_event_url' => 'https://nottopersia.com/x' ) );
			return 'https://nottopersia.com/x' === tps_entity( 9 )['sameAs'];
		},

		'same_as_merges_reference' => function () {
			tps_festival( 1, array( 'event_start_date' => '2026-11-01', 'official_event_url' => 'https://rosewater.example.ir/', 'external_reference_uri' => 'https://fa.wikipedia.org/wiki/x' ) );
			tps_festival( 2, array( 'event_start_date' => '2026-11-01', 'official_event_url' => 'https://fa.wikipedia.org/wiki/x', 'external_reference_uri' => 'https://fa.wikipedia.org/wiki/x' ) );
			return array( 'https://fa.wikipedia.org/wiki/x', 'https://rosewater.example.ir/' ) === tps_entity( 1 )['sameAs']
				&& 'https://fa.wikipedia.org/wiki/x' === tps_entity( 2 )['sameAs'];
		},

		'thing_no_organizer_sameas' => function () {
			$m = array( 'organizer_name' => 'وزارت', 'official_event_url' => 'https://rosewater.example.ir/' );
			tps_festival( 1, array( 'event_start_date' => '2026-09-01' ) + $m );
			tps_festival( 2, $m );
			tps_post( 8, 'tp_seasonal_event', array( 'event_start_date' => '2026-12-01' ) + $m );
			foreach ( array( 1, 2, 8 ) as $id ) {
				$e = tps_entity( $id );
				if ( 'Thing' !== $e['@type'] || isset( $e['organizer'] ) || isset( $e['sameAs'] ) ) {
					return false;
				}
			}
			return true;
		},

		'translation_no_organizer' => function () {
			tps_wpml_on();
			tps_festival( 30, array( 'event_start_date' => '2026-11-01', 'organizer_name' => 'وزارت میراث' ) );
			tps_post( 31, 'tp_festival', array( 'entity_uid' => 'https://topersia.com/id/ev-30', 'event_start_date' => '2026-11-01' ) );
			tps_post( 32, 'tp_festival', array( 'entity_uid' => 'https://topersia.com/id/ev-30', 'event_start_date' => '2026-11-01', 'organizer_name' => 'Ministry' ) );
			$GLOBALS['tps_tr'][31] = 30;
			$GLOBALS['tps_tr'][32] = 30;
			return ! isset( tps_entity( 31 )['organizer'] ) && 'Ministry' === tps_entity( 32 )['organizer']['name'];
		},

		'translation_url_from_original' => function () {
			tps_wpml_on();
			tps_festival( 30, array( 'event_start_date' => '2026-11-01', 'official_event_url' => 'https://rosewater.example.ir/' ) );
			tps_post( 31, 'tp_festival', array( 'entity_uid' => 'https://topersia.com/id/ev-30', 'event_start_date' => '2026-11-01' ) );
			tps_post( 32, 'tp_festival', array( 'entity_uid' => 'https://topersia.com/id/ev-30', 'event_start_date' => '2026-11-01', 'official_event_url' => 'https://own.example.ir/' ) );
			$GLOBALS['tps_tr'][31] = 30;
			$GLOBALS['tps_tr'][32] = 30;
			return 'https://rosewater.example.ir/' === tps_entity( 31 )['sameAs'] && 'https://own.example.ir/' === tps_entity( 32 )['sameAs'];
		},

		'translation_bad_original_url' => function () {
			tps_wpml_on();
			tps_festival( 30, array( 'event_start_date' => '2026-11-01', 'official_event_url' => 'http://rosewater.example.ir/' ) );
			tps_post( 31, 'tp_festival', array( 'entity_uid' => 'https://topersia.com/id/ev-30', 'event_start_date' => '2026-11-01' ) );
			$GLOBALS['tps_tr'][31] = 30;
			return ! isset( tps_entity( 31 )['sameAs'] );
		},

		'tehran_today' => function () {
			$GLOBALS['tps_filters'] = array();
			return '2026-10-13' === topersia_schema_today( strtotime( '2026-10-12 21:00:00 UTC' ) )
				&& '2026-10-12' === topersia_schema_today( strtotime( '2026-10-12 20:00:00 UTC' ) );
		},

		/* ۱٫۳٫۲ · SCHEMA-PARITY S5 — ریشۀ BreadcrumbList = پیوند «ToPersia» خانۀ زبان جاری، در هر سه زبان */
		'breadcrumb_root_lang' => function () {
			$ok = true;
			foreach ( array( 'fa' => 'https://topersia.test/', 'en' => 'https://topersia.test/en/', 'ar' => 'https://topersia.test/ar/' ) as $lang => $home ) {
				$GLOBALS['tps_filters'] = array();
				add_filter( 'wpml_current_language', function () use ( $lang ) {
					return $lang;
				} );
				add_filter( 'wpml_home_url', function ( $url ) use ( $lang ) {
					return 'fa' === $lang ? $url : $url . $lang . '/';
				} );
				tps_post( 40, 'tp_city', array( 'name_' . $lang => 'Kashan-' . $lang ) );
				$root = tps_node( 40, 'BreadcrumbList' )['itemListElement'][0];
				$ok   = $ok && 'ToPersia' === $root['name'] && $home === $root['item'] && 1 === $root['position'];
			}
			return $ok;
		},

		/* ۱٫۳٫۲ · SCHEMA-PARITY S4 — lastReviewed از last_verified_date، فقط با وضعیت خودِ همین زبان؛ dateModified می‌ماند */
		'last_reviewed' => function () {
			tps_post( 40, 'tp_city', array( 'content_status' => 'verified', 'last_verified_date' => '2026-09-20' ) );
			tps_post( 41, 'tp_city', array( 'last_verified_date' => '2026-09-20' ) );
			tps_post( 42, 'tp_city', array( 'content_status' => 'verified', 'last_verified_date' => '2026-02-30' ) );
			$a = tps_node( 40, 'WebPage' );
			return '2026-09-20' === $a['lastReviewed'] && isset( $a['dateModified'] )
				&& ! isset( tps_node( 41, 'WebPage' )['lastReviewed'] )
				&& ! isset( tps_node( 42, 'WebPage' )['lastReviewed'] );
		},

		'visit_props' => function () {
			tps_post( 50, 'tp_religious_site', array(
				'opening_hours_spec'   => array( 'item-0' => array( 'oh_day' => 'sat', 'oh_from' => '09:00', 'oh_to' => '17:30', 'oh_closed' => 'false' ), 'item-1' => array( 'oh_day' => 'fri', 'oh_closed' => 'true' ) ),
				'ticket_price_foreign' => '5000000', 'ticket_price_checked' => '2026-06-01', 'wheelchair_access' => 'partial',
			) );
			$e = tps_entity( 50 );
			return 1 === count( $e['openingHoursSpecification'] ) && 'https://schema.org/Saturday' === $e['openingHoursSpecification'][0]['dayOfWeek']
				&& '09:00' === $e['openingHoursSpecification'][0]['opens'] && false === $e['isAccessibleForFree'] && ! isset( $e['offers'] )
				&& 'Wheelchair accessible (partial)' === $e['amenityFeature'][0]['name'] && true === $e['amenityFeature'][0]['value'];
		},

		'visit_stale_unknown' => function () {
			tps_post( 51, 'tp_monument', array( 'ticket_price_foreign' => '5000000', 'ticket_price_checked' => '2024-12-01', 'wheelchair_access' => 'unknown' ) );
			tps_post( 52, 'tp_monument', array( 'ticket_price_foreign' => '5000000', 'wheelchair_access' => '' ) );
			$a = tps_entity( 51 );
			$b = tps_entity( 52 );
			return ! isset( $a['isAccessibleForFree'] ) && ! isset( $a['amenityFeature'] ) && ! isset( $b['isAccessibleForFree'] ) && false === strpos( json_encode( $a ), 'unknown' );
		},

		'museum_languages' => function () {
			tps_post( 53, 'tp_museum', array( 'audio_guide_languages' => array( 'fa', 'en' ) ) );
			tps_post( 54, 'tp_local_food', array( 'dietary_flags' => array( 'vegetarian' ) ) );
			$m = tps_entity( 53 );
			$f = tps_entity( 54 );
			return array( 'fa', 'en' ) === $m['availableLanguage'] && ! isset( $f['suitableForDiet'] );
		},

		'event_ticket' => function () {
			tps_festival( 55, array( 'event_start_date' => '2026-11-01', 'ticket_required' => 'free', 'ticket_url' => 'https://tickets.example.org/rose' ) );
			$e = tps_entity( 55 );
			tps_festival( 56, array( 'event_start_date' => '2026-11-01', 'ticket_required' => 'unknown', 'ticket_url' => 'http://insecure.example.org/' ) );
			$u = tps_entity( 56 );
			return true === $e['isAccessibleForFree'] && 'https://tickets.example.org/rose' === $e['offers']['url'] && ! isset( $u['isAccessibleForFree'] ) && ! isset( $u['offers'] );
		},

		'place_untouched' => function () {
			tps_post( 40, 'tp_city', array( 'event_start_date' => '2026-11-01', 'geo_coordinates' => '33.98, 51.43' ) );
			$e = tps_entity( 40 );
			return 'City' === $e['@type'] && ! isset( $e['startDate'] ) && isset( $e['geo'] );
		},
	);
}

/* ── راننده ── */

function tps_child( $file ) {
	$cmd = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . escapeshellarg( '--child=' . $file ) . ' 2>&1';
	$raw = shell_exec( $cmd );
	$res = json_decode( (string) $raw, true );
	return is_array( $res ) ? $res : array( '__error' => trim( (string) $raw ) );
}

/** جهش‌ها: [ مورد هدف، متن، جای‌گزین ] */
$mutations = array(
	array( 'visit_props', "\$node['openingHoursSpecification'] = \$hours;", '' ),
	array( 'visit_stale_unknown', 'if ( $checked >= $floor ) {', 'if ( true ) {' ),
	array( 'museum_languages', "\$node['availableLanguage'] = \$on;", '' ),
	array( 'event_ticket', "\$node['offers'] = array( '@type' => 'Offer', 'url' => \$ticket_url );", '' ),
	array( 'future_event', "\$node['@type'] = \$config['event']['type'];", '' ),
	array( 'future_event', "unset( \$node['additionalType'] );", '' ),
	array( 'future_event', "\$node['eventStatus']         = 'https://schema.org/EventScheduled';", '' ),
	array( 'future_event', "\$node['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';", '' ),
	array( 'future_event', "\$node['endDate'] = \$dates['end'];", '' ),
	array( 'start_only_future', "\$last = '' !== \$dates['end'] ? \$dates['end'] : \$dates['start'];", "\$last = \$dates['end'];" ),
	array( 'ongoing_today', 'if ( $last < topersia_schema_today() ) {', 'if ( $last <= topersia_schema_today() ) {' ),
	array( 'past_thing', 'if ( $last < topersia_schema_today() ) {', 'if ( false ) {' ),
	array( 'no_date_thing', '$start  = topersia_schema_ymd( $raw );', "\$start  = topersia_schema_ymd( \$raw ) ?: '2026-12-01';" ),
	array( 'invalid_thing', "return checkdate( (int) \$m[2], (int) \$m[3], (int) \$m[1] ) ? \$value : '';", 'return $value;' ),
	array( 'invalid_thing', "'/^(\\d{4})-(\\d{2})-(\\d{2})\$/'", "'/^(\\d{4})-(\\d{2})-(\\d{2})/'" ),
	array( 'end_before_start', "( '' !== \$end && \$end < \$start )", '( false )' ),
	array( 'sports_religious', "\$event( 'SportsEvent', 245, 246 )", "\$event( 'Event', 245, 246 )" ),
	array( 'e6_thing', "'tp_seasonal_event'  => \$thing( 'Thing', \$via_prov( 263 ), 'https://schema.org/Event' )", "'tp_seasonal_event'  => \$event( 'Event', 263, 0 )" ),
	array( 'location_address', "\$node['location']            = topersia_schema_event_location( \$post_id, \$config );", '' ),
	array( 'location_address', "\$place['name'] = \$venue;", '' ),
	array( 'location_address', "'addressRegion'   => \$province ?", "'addressRegionX'  => \$province ?" ),
	array( 'location_address', "'addressLocality' => \$city ?", "'addressLocalityX' => \$city ?" ),
	array( 'location_address', "'addressCountry'  => 'IR',", '' ),
	array( 'location_address', "\$event( 'Festival', 232, 233 )", "\$event( 'Festival', 232, 232 )" ),
	array( 'location_no_city', "\$local    = \$city ? \$city : \$province;\n", "\$local    = \$city ? \$city : \$province;\n\t\$city = \$city ? \$city : \$province;\n" ),
	array( 'draft_parent', "'publish' === get_post_status( \$view )", 'true' ),
	array( 'location_id_orphan', 'return topersia_schema_link_ids( $graph );', 'return $graph;' ),
	array( 'location_id_orphan', "unset( \$graph[ \$i ]['location']['@id'] );", '' ),
	array( 'location_id_kept', "! isset( \$ids[ \$node['location']['@id'] ] )", 'true' ),
	array( 'translation_dates', "if ( \$original !== \$post_id ) {\n\t\t\t\$source = \$original;", "if ( false ) {\n\t\t\t\$source = \$original;" ),
	array( 'translation_dates', "\$end = topersia_schema_ymd( get_post_meta( \$source,", "\$end = topersia_schema_ymd( get_post_meta( \$post_id," ),
	array( 'translation_dates', "\$ids = \$relation->get_parents( \$original, 'ids' );", '' ),
	array( 'translation_own_date', "if ( '' === trim( is_scalar( \$raw ) ? (string) \$raw : '' ) ) {", 'if ( true ) {' ),
	array( 'organizer_present', "\$node['organizer'] = \$organizer;", '' ),
	array( 'organizer_present', "array( '@type' => 'Organization', 'name' => \$name )", "array( '@type' => 'Person', 'name' => \$name )" ),
	array( 'organizer_absent', "if ( '' === \$name ) {\n\t\treturn array();", "if ( false ) {\n\t\treturn array();" ),
	array( 'thing_no_organizer_sameas', "\$organizer = topersia_schema_event_organizer( \$post_id );\n\n\tif ( ! empty( \$organizer ) ) {\n\t\t\$node['organizer'] = \$organizer;\n\t}", "" ),
	array( 'same_as_valid', "\$node['sameAs'] = 1 === count( \$existing ) ? \$existing[0] : array_values( \$existing );", "" ),
	array( 'same_as_invalid', "! filter_var( \$url, FILTER_VALIDATE_URL )", "false" ),
	array( 'same_as_invalid', "isset( \$parts['user'] ) || isset( \$parts['pass'] ) || false === strpos( \$host, '.' )", "false" ),
	array( 'same_as_http', "'https' !== strtolower( (string) \$parts['scheme'] )", "false" ),
	array( 'same_as_own_domain', "'topersia.com' === \$host || ", "" ),
	array( 'same_as_own_domain', "'.topersia.com' === substr( \$host, -13 )", "false" ),
	array( 'same_as_own_domain', "rtrim( \$parts['host'], '.' )", "\$parts['host']" ),
	array( 'same_as_merges_reference', "! in_array( \$official, \$existing, true )", "true" ),
	array( 'same_as_merges_reference', "\$existing = isset( \$node['sameAs'] ) ? (array) \$node['sameAs'] : array();", "\$existing = array();" ),
	array( 'translation_no_organizer', "\$raw  = get_post_meta( \$post_id, 'organizer_name', true );", "\$raw  = get_post_meta( topersia_schema_source_id( \$post_id ), 'organizer_name', true );" ),
	array( 'translation_url_from_original', "if ( \$original !== \$post_id ) {\n\t\t\t\$raw = get_post_meta( \$original, 'official_event_url', true );", "if ( false ) {\n\t\t\t\$raw = get_post_meta( \$original, 'official_event_url', true );" ),
	array( 'translation_url_from_original', "if ( '' === trim( \$text ) ) {", 'if ( true ) {' ),
	array( 'translation_bad_original_url', "\$url = is_scalar( \$raw ) ? trim( (string) \$raw ) : '';", "\$url = 'https://x.example.ir/';" ),
	array( 'breadcrumb_root_lang', "'name'     => 'ToPersia',", "'name'     => __( 'خانه', 'topersia-schema' )," ),
	array( 'breadcrumb_root_lang', "\$home = apply_filters( 'wpml_home_url', \$home );", '' ),
	array( 'last_reviewed', "\$webpage['lastReviewed'] = \$reviewed;", '' ),
	array( 'last_reviewed', "if ( ! is_scalar( \$status ) || '' === trim( (string) \$status ) ) {", 'if ( false ) {' ),
	array( 'last_reviewed', "return checkdate( (int) \$m[2], (int) \$m[3], (int) \$m[1] ) ? substr( \$raw, 0, 10 ) : '';", 'return substr( $raw, 0, 10 );' ),
	array( 'tehran_today', "new DateTimeZone( 'Asia/Tehran' )", "new DateTimeZone( 'UTC' )" ),
);

$fail   = 0;
$src    = file_get_contents( $plugin );
$base   = tps_child( $plugin );
$tmpdir = sys_get_temp_dir() . '/tps-mut-' . getmypid();

echo "== موردها روی افزونه ==\n";
if ( isset( $base['__error'] ) ) {
	echo "  FAIL اجرا: " . $base['__error'] . "\n";
	exit( 1 );
}
foreach ( $base as $name => $ok ) {
	echo ( $ok ? '  OK   ' : '  FAIL ' ) . $name . "\n";
	$fail += $ok ? 0 : 1;
}

echo "\n== جهش‌ها: هر کدام باید مورد هدفش را بیندازد ==\n";
@mkdir( $tmpdir );
foreach ( $mutations as $i => $m ) {
	list( $target, $from, $to ) = $m;
	$label = sprintf( '#%02d %s', $i + 1, $target );
	if ( 1 !== substr_count( $src, $from ) ) {
		echo "  FAIL {$label} — متن جهش در افزونه یکتا نیست (" . substr_count( $src, $from ) . ")\n";
		$fail++;
		continue;
	}
	if ( ! isset( $base[ $target ] ) ) {
		echo "  FAIL {$label} — مورد ناشناخته\n";
		$fail++;
		continue;
	}
	$file = $tmpdir . '/m' . $i . '.php';
	file_put_contents( $file, str_replace( $from, $to, $src ) );
	$res    = tps_child( $file );
	$caught = isset( $res['__error'] ) || empty( $res[ $target ] );
	echo ( $caught ? '  OK   ' : '  FAIL ' ) . $label . ( $caught ? '' : ' — جهش زنده ماند' ) . "\n";
	$fail += $caught ? 0 : 1;
	unlink( $file );
}
@rmdir( $tmpdir );

echo "\n" . ( $fail ? "FAIL: {$fail}\n" : "همه سبز\n" );
exit( $fail ? 1 : 0 );

<?php
/**
 * ادعاهای SCHEMA-PARITY — JSON-LD همان چیزی که صفحه نشان می‌دهد (ابری H).
 *
 *   S1  دروازۀ زبان (P5-D278) روی description · WebPage.description · abstract ·
 *       ImageObject.caption · پرسش و پاسخ FAQ · location.name — ترجمه‌نشده ← حذف،
 *       FAQ بی پرسش ← گره حذف؛ هیچ متن فارسی با inLanguage غیرفارسی
 *   S2  WebPage.abstract = متن باکس AI (خروجی ذخیره‌شدۀ خلاصه‌ساز، وگرنه ai_summary)
 *   S4  WebPage.lastReviewed = تاریخ `<time>` بلوک شناسه؛ dateModified می‌ماند
 *   S5  ریشۀ BreadcrumbList «ToPersia» و خانۀ زبان جاری
 *
 * S1 · S2 · S4 در صافی `topersia_g_schema_graph()` (topersia_g_schema_parity) سنجیده
 * می‌شوند — در fa، en و ar، هر کدام با جهش (`tp441_mut`): گراف بی دروازه (خروجی
 * خامِ موتور) و گرافی که فقط همان یک ویژگی را برگردانده باید ادعا را بیندازند.
 * S4 و S5 در خودِ موتور شِما با آزمون خودبسندۀ افزونه (`plugin/topersia-schema/tests/run.php`،
 * سه زبان، با جهش در نسخۀ موقت افزونه) — این‌جا در فرایند جدا اجرا می‌شود.
 *
 * از assert.php پس از assert-442 بار می‌شود (`tp442_lang` · `tp441_mut` ·
 * خلاصه‌ساز ساختگی `$GLOBALS['tp_ai_fake']`).
 */

echo "\nSCHEMA-PARITY — JSON-LD هم‌خوان با صفحه (S1 · S2 · S4 · S5)\n" . str_repeat( '─', 78 ) . "\n";

$idP = 994420;
$GLOBALS['tp_posts'][ $idP ] = array( 'type' => 'tp_province', 'title' => 'Isfahan', 'url' => 'https://topersia.com/province/isfahan-p/' );

$faP = array(
	'short_summary'      => 'اصفهان نصف جهان است و میدان نقش جهان در دل ایران است.',
	'meta_description'   => 'راهنمای سفر به استان اصفهان و جاهای دیدنی آن.',
	'ai_summary'         => 'چکیدۀ دستی دربارۀ استان اصفهان.',
	'caption'            => 'نمای میدان نقش جهان در غروب',
	'faq_q'              => 'بهترین فصل سفر به اصفهان کدام است؟',
	'faq_a'              => 'بهار و پاییز هوای معتدل دارند.',
	'venue'              => 'باغ فین کاشان',
);
$trP = array(
	'en' => array(
		'short_summary'    => 'Isfahan is half the world, with Naqsh-e Jahan Square at its heart.',
		'meta_description' => 'A travel guide to Isfahan province and its sights.',
		'ai_summary'       => 'A hand-written summary of Isfahan province.',
		'caption'          => 'Naqsh-e Jahan Square at sunset',
		'faq_q'            => 'What is the best season to visit Isfahan?',
		'faq_a'            => 'Spring and autumn are mild.',
		'venue'            => 'Fin Garden, Kashan',
	),
	'ar' => array(
		'short_summary'    => 'أصفهان نصف العالم، وفي قلبها ساحة نقش جهان.',
		'meta_description' => 'دليل السفر إلى محافظة أصفهان ومعالمها.',
		'ai_summary'       => 'ملخص مكتوب يدويا عن محافظة أصفهان.',
		'caption'          => 'ساحة نقش جهان عند الغروب',
		'faq_q'            => 'ما هو أفضل موسم لزيارة أصفهان؟',
		'faq_a'            => 'الربيع والخريف معتدلان.',
		'venue'            => 'حديقة فين في قاشان',
	),
);

/** متای پست و گرافی به همان شکلی که موتور شِما از همان متا می‌سازد (بی دروازه). */
$setP = function ( $txt, $lang, $meta = array() ) use ( $idP ) {
	$GLOBALS['tp_meta'][ $idP ] = $meta + array(
		'short_summary'      => $txt['short_summary'],
		'meta_description'   => $txt['meta_description'],
		'ai_summary'         => $txt['ai_summary'],
		'faq_items'          => array( 'item-0' => array( 'faq_question' => $txt['faq_q'], 'faq_answer' => $txt['faq_a'] ) ),
		'content_status'     => 'verified',
		'last_verified_date' => '2026-09-20',
	);
	$url  = get_permalink( $idP );
	$home = 'fa' === $lang ? 'https://topersia.com/' : 'https://topersia.com/' . $lang . '/';
	return array(
		array( '@type' => 'WebPage', '@id' => $url . '#webpage', 'inLanguage' => $lang, 'dateModified' => '2026-10-01T00:00:00+00:00',
			'description' => $txt['meta_description'], 'abstract' => $txt['ai_summary'] ),
		array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => array(
			array( '@type' => 'ListItem', 'position' => 1, 'name' => 'ToPersia', 'item' => $home ),
			array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Isfahan' ),
		) ),
		array( '@type' => 'Festival', '@id' => 'https://topersia.com/entity/' . $idP, 'name' => 'Isfahan',
			'description' => $txt['short_summary'],
			'image'       => array( '@type' => 'ImageObject', 'url' => 'https://topersia.com/a.jpg', 'caption' => $txt['caption'] ),
			'location'    => array( '@type' => 'Place', 'name' => $txt['venue'], 'address' => array( '@type' => 'PostalAddress', 'addressCountry' => 'IR' ) ) ),
		array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => array( array( '@type' => 'Question', 'name' => $txt['faq_q'],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $txt['faq_a'] ) ) ) ),
	);
};

/** گره با @type. */
$nodeP = function ( $g, $type ) {
	foreach ( $g as $n ) {
		if ( isset( $n['@type'] ) && $type === $n['@type'] ) {
			return $n;
		}
	}
	return null;
};

/** حروف ویژۀ فارسی (پ چ ژ گ ی ک) — در متن عربی و انگلیسی نیستند. */
$persianP = function ( $g ) {
	return (int) preg_match_all( '/[\x{067E}\x{0686}\x{0698}\x{06AF}\x{06CC}\x{06A9}]/u', wp_json_encode( $g ) );
};

/** صافی در یک زبان. */
$runP = function ( $lang, $graph ) use ( $idP ) {
	return tp442_lang( $lang, function () use ( $graph, $idP ) {
		return topersia_g_schema_graph( $graph, $idP, array() );
	}, false );
};

/* ══ S1 — دروازۀ زبان، en و ar: دادۀ ترجمه‌نشده (فارسی) ═══════════════════════ */

foreach ( array( 'en', 'ar' ) as $lang ) {

	$raw  = $setP( $faP, $lang );
	$real = $runP( $lang, $raw );

	$s1 = function ( $g ) use ( $nodeP, $persianP ) {
		$w = $nodeP( $g, 'WebPage' );
		$e = $nodeP( $g, 'Festival' );
		return 0 === $persianP( $g ) && null === $nodeP( $g, 'FAQPage' )
			&& ! isset( $w['description'] ) && ! isset( $w['abstract'] ) && ! isset( $e['description'] )
			&& ! isset( $e['image']['caption'] ) && isset( $e['image']['url'] )
			&& ! isset( $e['location']['name'] ) && isset( $e['location']['address'] )
			&& 'Isfahan' === $e['name'] && isset( $w['dateModified'] );
	};

	tp441_mut( "S1 [{$lang}]: متن فارسیِ ترجمه‌نشده — description، WebPage.description، abstract، caption، FAQ و location.name برداشته؛ FAQPage بی پرسش نمی‌آید؛ صفر حرف فارسی با inLanguage {$lang}  P5-D278",
		$s1, $real, $raw, 'جهش: گراف بی دروازه' . ( $s1( $real ) ? '' : ' · ' . wp_json_encode( $real ) ) );

	/* هر ویژگی جدا: برگرداندن فقط همان یکی باید ادعا را بیندازد */
	$back = array(
		'description'       => function ( $g ) use ( $faP ) { $g[2]['description'] = $faP['short_summary']; return $g; },
		'WebPage.description' => function ( $g ) use ( $faP ) { $g[0]['description'] = $faP['meta_description']; return $g; },
		'abstract'          => function ( $g ) use ( $faP ) { $g[0]['abstract'] = $faP['ai_summary']; return $g; },
		'caption'           => function ( $g ) use ( $faP ) { $g[2]['image']['caption'] = $faP['caption']; return $g; },
		'location.name'     => function ( $g ) use ( $faP ) { $g[2]['location']['name'] = $faP['venue']; return $g; },
		'FAQ'               => function ( $g ) use ( $raw ) { $g[] = $raw[3]; return $g; },
	);
	$caught = array();
	foreach ( $back as $prop => $fn ) {
		if ( ! $s1( $fn( $real ) ) ) {
			$caught[] = $prop;
		}
	}
	t( "S1 [{$lang}]: هر شش ویژگی جدا سنجیده می‌شود — برگرداندن هر کدام به‌تنهایی ادعا را می‌اندازد",
		count( $caught ) === count( $back ), implode( ' · ', array_diff( array_keys( $back ), $caught ) ) );

	/* FAQ نیمه‌ترجمه: پرسش ترجمه‌شده با پاسخ فارسی نمی‌آید؛ آیتم ترجمه‌شده می‌ماند */
	$mixed = $setP( $trP[ $lang ], $lang, array( 'faq_items' => array(
		'item-0' => array( 'faq_question' => $trP[ $lang ]['faq_q'], 'faq_answer' => $trP[ $lang ]['faq_a'] ),
	) ) );
	$mixed[3]['mainEntity'][] = array( '@type' => 'Question', 'name' => $trP[ $lang ]['faq_q'] . ' 2',
		'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $faP['faq_a'] ) );
	tp441_mut( "S1 [{$lang}]: FAQ — آیتمی که پرسش یا پاسخش ترجمه نشده برداشته می‌شود و آیتم ترجمه‌شده می‌ماند",
		function ( $g ) use ( $nodeP, $persianP ) { $f = $nodeP( $g, 'FAQPage' ); return $f && 1 === count( $f['mainEntity'] ) && 0 === $persianP( $g ); },
		$runP( $lang, $mixed ), $mixed );
}

/* ══ S1 — ترجمه‌شده و فارسی: چیزی که صفحه نشان می‌دهد برداشته نمی‌شود ═════════════ */

foreach ( array( 'fa' => $faP, 'en' => $trP['en'], 'ar' => $trP['ar'] ) as $lang => $txt ) {

	$raw  = $setP( $txt, $lang );
	$real = $runP( $lang, $raw );

	tp441_mut( "S1 [{$lang}]: متن به زبان صفحه می‌ماند — description، WebPage.description، caption، FAQ و location.name همان مقدار",
		function ( $g ) use ( $nodeP, $txt ) {
			$w = $nodeP( $g, 'WebPage' );
			$e = $nodeP( $g, 'Festival' );
			$f = $nodeP( $g, 'FAQPage' );
			return $f && $txt['faq_q'] === $f['mainEntity'][0]['name'] && $txt['faq_a'] === $f['mainEntity'][0]['acceptedAnswer']['text']
				&& isset( $w['description'] ) && $txt['meta_description'] === $w['description'] && $txt['short_summary'] === $e['description']
				&& $txt['caption'] === $e['image']['caption'] && $txt['venue'] === $e['location']['name'];
		},
		$real, tp442_lang( $lang, function () use ( $raw ) {
			/* جهش: دروازه‌ای که همه چیز را برمی‌دارد */
			unset( $raw[0]['description'], $raw[2]['description'], $raw[2]['image']['caption'], $raw[2]['location']['name'], $raw[3] );
			return array_values( $raw );
		}, false ) );
}

/* ══ S2 — abstract = متن باکس AI ══════════════════════════════════════════ */

foreach ( array( 'fa' => $faP, 'en' => $trP['en'], 'ar' => $trP['ar'] ) as $lang => $txt ) {

	$stored = 'fa' === $lang ? 'خلاصۀ ذخیره‌شدۀ خلاصه‌ساز برای اصفهان.' : ( 'en' === $lang ? 'Stored summarizer output for Isfahan.' : 'ملخص محفوظ من المُلخِّص لأصفهان.' );

	/* خروجی ذخیره‌شده اول — همان که [tp_ai] نشان می‌دهد */
	$GLOBALS['tp_ai_fake'][ $idP ]['summary'] = $stored;
	$raw  = $setP( $txt, $lang );
	$real = $runP( $lang, $raw );
	$box  = tp442_lang( $lang, function () use ( $idP ) { return r( $idP, 'tp_ai' ); }, false );

	tp441_mut( "S2 [{$lang}]: abstract = خروجی ذخیره‌شدۀ خلاصه‌ساز (همان متن باکس AI)، نه ai_summary دستی",
		function ( $g ) use ( $nodeP, $stored ) { $w = $nodeP( $g, 'WebPage' ); return isset( $w['abstract'] ) && $stored === $w['abstract']; },
		$real, $raw, false !== strpos( $box, esc_html( $stored ) ) ? '' : 'باکس AI همان متن را نشان نداد' );
	t( "S2 [{$lang}]: باکس AI همان متن abstract را نشان می‌دهد", false !== strpos( $box, esc_html( $stored ) ) );

	/* بی خروجی ذخیره‌شده: ai_summary از دروازه */
	unset( $GLOBALS['tp_ai_fake'][ $idP ] );
	$raw2 = $setP( $txt, $lang );
	$raw2[0]['abstract'] = 'stale';
	tp441_mut( "S2 [{$lang}]: بی خروجی ذخیره‌شده abstract = ai_summary (همان پشتیبان باکس)",
		function ( $g ) use ( $nodeP, $txt ) { $w = $nodeP( $g, 'WebPage' ); return isset( $w['abstract'] ) && $txt['ai_summary'] === $w['abstract']; },
		$runP( $lang, $raw2 ), $raw2 );

	/* باکس خالی ← abstract نیست */
	$raw3 = $setP( $txt, $lang, array( 'ai_summary' => '' ) );
	$raw3[0]['abstract'] = 'stale';
	$empty = tp442_lang( $lang, function () use ( $idP ) { return r( $idP, 'tp_ai' ); }, false );
	tp441_mut( "S2 [{$lang}]: باکس AI چیزی نشان نمی‌دهد ← abstract هم نمی‌آید",
		function ( $g ) use ( $nodeP ) { $w = $nodeP( $g, 'WebPage' ); return ! isset( $w['abstract'] ); },
		$runP( $lang, $raw3 ), $raw3, '' === trim( wp_strip_all_tags( $empty ) ) ? '' : 'باکس خالی نبود' );
}

/* ══ S4 — lastReviewed = تاریخ دیدنی <time> ══════════════════════════════════ */

foreach ( array( 'fa' => $faP, 'en' => $trP['en'], 'ar' => $trP['ar'] ) as $lang => $txt ) {

	$raw = $setP( $txt, $lang );
	$raw[0]['lastReviewed'] = '2025-01-01';
	$real = $runP( $lang, $raw );
	$html = tp442_lang( $lang, function () use ( $idP ) { return r( $idP, 'tp_sources' ); }, false );

	tp441_mut( "S4 [{$lang}]: WebPage.lastReviewed = datetime همان <time> بلوک شناسه (2026-09-20)؛ dateModified می‌ماند",
		function ( $g ) use ( $nodeP ) { $w = $nodeP( $g, 'WebPage' ); return isset( $w['lastReviewed'], $w['dateModified'] ) && '2026-09-20' === $w['lastReviewed']; },
		$real, $raw, false !== strpos( $html, 'datetime="2026-09-20"' ) ? '' : 'بلوک شناسه <time> با همین تاریخ نداشت' );
	t( "S4 [{$lang}]: بلوک شناسه همان تاریخ را با <time datetime> نشان می‌دهد", false !== strpos( $html, 'datetime="2026-09-20"' ) );

	/* بی وضعیت محتوای همین زبان: <time> نیست ← lastReviewed نیست */
	$raw2 = $setP( $txt, $lang, array( 'content_status' => '' ) );
	$raw2[0]['lastReviewed'] = '2026-09-20';
	tp441_mut( "S4 [{$lang}]: بی وضعیت محتوای همین زبان تاریخی دیده نمی‌شود ← lastReviewed برداشته",
		function ( $g ) use ( $nodeP ) { $w = $nodeP( $g, 'WebPage' ); return ! isset( $w['lastReviewed'] ) && isset( $w['dateModified'] ); },
		$runP( $lang, $raw2 ), $raw2 );
}

/* ══ S5 — ریشۀ BreadcrumbList از صافی دست‌نخورده می‌گذرد ══════════════════════ */

foreach ( array( 'fa' => $faP, 'en' => $trP['en'], 'ar' => $trP['ar'] ) as $lang => $txt ) {
	$raw  = $setP( $txt, $lang );
	$root = $nodeP( $runP( $lang, $raw ), 'BreadcrumbList' )['itemListElement'][0];
	t( "S5 [{$lang}]: ریشۀ BreadcrumbList پس از صافی همان «ToPersia» و خانۀ زبان جاری موتور است",
		'ToPersia' === $root['name'] && ( 'fa' === $lang ? 'https://topersia.com/' : 'https://topersia.com/' . $lang . '/' ) === $root['item'],
		wp_json_encode( $root ) );
}

/* ══ S4 · S5 در موتور شِما — آزمون خودبسندۀ افزونه (سه زبان، با جهش) ════════════ */

$runSchemaP = dirname( TP_PLUGIN_DIR ) . '/topersia-schema/tests/run.php';
$outP       = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $runSchemaP ) . ' 2>&1' );

t( 'S5 موتور: ریشۀ BreadcrumbList «ToPersia» و wpml_home_url در fa · en · ar، با جهش‌هایش (tests/run.php افزونه)',
	(bool) preg_match( '/^  OK   breadcrumb_root_lang$/m', $outP ) && ! preg_match( '/^  FAIL .*breadcrumb_root_lang/m', $outP ) );
t( 'S4 موتور: WebPage.lastReviewed از last_verified_date فقط با وضعیت همین زبان، تاریخ نامعتبر نه؛ dateModified می‌ماند — با جهش‌هایش',
	(bool) preg_match( '/^  OK   last_reviewed$/m', $outP ) && ! preg_match( '/^  FAIL .*last_reviewed/m', $outP ) );
t( 'موتور شِما: آزمون خودبسندۀ افزونه همه سبز (موردها و جهش‌ها)', false !== strpos( $outP, 'همه سبز' ),
	implode( ' | ', preg_grep( '/FAIL/', explode( "\n", $outP ) ) ) );

unset( $GLOBALS['tp_meta'][ $idP ], $GLOBALS['tp_posts'][ $idP ], $GLOBALS['tp_ai_fake'][ $idP ] );

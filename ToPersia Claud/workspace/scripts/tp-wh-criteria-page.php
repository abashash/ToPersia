<?php
/**
 * صفحهٔ ثابت «معیارهای ثبت میراث جهانی» — fa · en · ar (دستهٔ ۲۶ بند ۱۰ · F-17).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-wh-criteria-page.php [apply]
 *
 * بی apply فقط گزارش می‌دهد. با apply سه برگهٔ وردپرس (نه پست‌تایپ) می‌سازد یا متنشان را به‌روز
 * می‌کند و با WPML (trid برگهٔ فارسی) پیوند می‌دهد؛ شناسهٔ برگهٔ فارسی در گزینهٔ
 * `topersia_wh_criteria_page` (بی autoload) — render از آن نشانی هر زبان را با wpml_object_id می‌گیرد.
 * ده معیار با لنگر #criterion-i … #criterion-x. متن پارافریز است، با پیوند منبع
 * https://whc.unesco.org/en/criteria/ (Operational Guidelines، بند ۷۷).
 *
 * فقط محلی (topersia.local). بازگرداندن: برگه‌ها را به زباله ببرید و گزینه را پاک کنید.
 */

if ( false === strpos( (string) home_url(), 'topersia.local' ) ) {
	WP_CLI::error( 'فقط topersia.local' );
}

$apply = in_array( 'apply', (array) ( $args ?? array() ), true );   /* wp-cli eval-file: پارامتر جایگاهی، نه -- */
$src   = 'https://whc.unesco.org/en/criteria/';
$roman = array( 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x' );

$text = array(
	'fa' => array(
		'title' => 'معیارهای ثبت میراث جهانی',
		'intro' => 'یونسکو هر اثر را بر پایهٔ ده معیار در فهرست میراث جهانی ثبت می‌کند: شش معیار نخست فرهنگی‌اند و چهار معیار پایانی طبیعی. هر اثر دست‌کم یکی از این معیارها را برآورده می‌کند و شمارهٔ رومی معیارها روی نشان «میراث جهانی» هر صفحه همین‌جا را نشان می‌دهد. متن زیر برگردانی آزاد است؛ متن رسمی:',
		'src'   => 'مرکز میراث جهانی یونسکو — معیارهای گزینش',
		'head'  => 'معیار %s',
		'items' => array(
			'شاهکاری از نبوغ خلاق انسان باشد.',
			'نشان‌دهندهٔ تبادل مهم ارزش‌های انسانی در یک دورهٔ زمانی یا یک حوزهٔ فرهنگی جهان باشد — در معماری، فناوری، هنرهای یادمانی، شهرسازی یا طراحی منظر.',
			'گواهی یگانه یا دست‌کم استثنایی بر یک سنت فرهنگی یا تمدنی زنده یا از میان‌رفته باشد.',
			'نمونهٔ برجسته‌ای از یک گونهٔ بنا، مجموعهٔ معماری یا فناورانه، یا منظری باشد که مرحله‌ای مهم از تاریخ بشر را نشان می‌دهد.',
			'نمونهٔ برجسته‌ای از سکونتگاه سنتی انسان یا کاربری سنتی زمین یا دریا باشد که نمایندهٔ یک فرهنگ یا تعامل انسان با محیط است، به‌ویژه وقتی در برابر دگرگونی‌های برگشت‌ناپذیر آسیب‌پذیر شده است.',
			'مستقیم یا ملموس با رویدادها، سنت‌های زنده، اندیشه‌ها، باورها یا آثار هنری و ادبی با اهمیت جهانی پیوند داشته باشد (این معیار معمولاً همراه معیارهای دیگر به کار می‌رود).',
			'پدیده‌های طبیعی بی‌نظیر یا پهنه‌هایی با زیبایی طبیعی و اهمیت زیبایی‌شناختی استثنایی را در بر بگیرد.',
			'نمونهٔ برجسته‌ای از مراحل اصلی تاریخ زمین باشد — از جمله نشانه‌های حیات، فرایندهای زمین‌شناختی مهم و در جریانِ شکل‌گیری زمین‌ریخت‌ها، یا عارضه‌های مهم زمین‌ریختی و فیزیوگرافیک.',
			'نمونهٔ برجسته‌ای از فرایندهای بوم‌شناختی و زیستی در حال تکامل و توسعهٔ زیست‌بوم‌ها و جوامع گیاهی و جانوری خشکی، آب شیرین، ساحلی و دریایی باشد.',
			'مهم‌ترین و پرمعناترین زیستگاه‌های طبیعی برای حفاظت درجای تنوع زیستی را در بر بگیرد، از جمله زیستگاه گونه‌های در خطر با ارزش جهانی برجسته از دید علم یا حفاظت.',
		),
	),
	'en' => array(
		'title' => 'World Heritage Selection Criteria',
		'intro' => 'UNESCO inscribes a property on the World Heritage List against ten criteria: the first six are cultural and the last four natural. Every property meets at least one of them, and the Roman numerals on each page\'s "World Heritage" badge point here. The text below is a paraphrase; the official wording is at:',
		'src'   => 'UNESCO World Heritage Centre — The Criteria for Selection',
		'head'  => 'Criterion (%s)',
		'items' => array(
			'It represents a masterpiece of human creative genius.',
			'It exhibits an important interchange of human values, over a span of time or within a cultural area of the world, in architecture, technology, monumental arts, town-planning or landscape design.',
			'It bears a unique or at least exceptional testimony to a cultural tradition or to a civilization which is living or which has disappeared.',
			'It is an outstanding example of a type of building, architectural or technological ensemble or landscape which illustrates a significant stage in human history.',
			'It is an outstanding example of a traditional human settlement, land-use or sea-use which represents a culture or human interaction with the environment, especially when it has become vulnerable to irreversible change.',
			'It is directly or tangibly associated with events, living traditions, ideas, beliefs or artistic and literary works of outstanding universal significance (normally used together with other criteria).',
			'It contains superlative natural phenomena or areas of exceptional natural beauty and aesthetic importance.',
			'It is an outstanding example of major stages of Earth\'s history, including the record of life, significant ongoing geological processes in the development of landforms, or significant geomorphic or physiographic features.',
			'It is an outstanding example of significant ongoing ecological and biological processes in the evolution and development of terrestrial, fresh water, coastal and marine ecosystems and communities of plants and animals.',
			'It contains the most important and significant natural habitats for in-situ conservation of biological diversity, including those with threatened species of outstanding universal value from the point of view of science or conservation.',
		),
	),
	'ar' => array(
		'title' => 'معايير التسجيل في قائمة التراث العالمي',
		'intro' => 'تسجّل اليونسكو المواقع في قائمة التراث العالمي وفق عشرة معايير: الستة الأولى ثقافية والأربعة الأخيرة طبيعية. يستوفي كل موقع معيارًا واحدًا على الأقل، والأرقام الرومانية على شارة «التراث العالمي» في كل صفحة تشير إلى هنا. النص أدناه صياغة حرّة؛ النص الرسمي في:',
		'src'   => 'مركز التراث العالمي لليونسكو — معايير الاختيار',
		'head'  => 'المعيار (%s)',
		'items' => array(
			'أن يمثّل تحفة من روائع العبقرية الإبداعية للإنسان.',
			'أن يُظهر تبادلًا مهمًا للقيم الإنسانية خلال حقبة زمنية أو في منطقة ثقافية من العالم، في العمارة أو التقنية أو الفنون الأثرية أو تخطيط المدن أو تصميم المناظر الطبيعية.',
			'أن يقدّم شهادة فريدة أو استثنائية على الأقل على تقليد ثقافي أو حضارة قائمة أو مندثرة.',
			'أن يكون مثالًا بارزًا على نوع من المباني أو المجموعات المعمارية أو التقنية أو المناظر الطبيعية يجسّد مرحلة مهمة من تاريخ البشرية.',
			'أن يكون مثالًا بارزًا على مستوطنة بشرية تقليدية أو استخدام تقليدي للأرض أو البحر يمثّل ثقافة أو تفاعل الإنسان مع البيئة، لا سيما حين يصبح عرضة لتغيّر لا رجعة فيه.',
			'أن يرتبط مباشرة أو بشكل ملموس بأحداث أو تقاليد حيّة أو أفكار أو معتقدات أو أعمال فنية وأدبية ذات أهمية عالمية بارزة (ويُستخدم عادةً مع معايير أخرى).',
			'أن يضم ظواهر طبيعية فائقة أو مناطق ذات جمال طبيعي وأهمية جمالية استثنائيين.',
			'أن يكون مثالًا بارزًا على المراحل الكبرى من تاريخ الأرض، بما في ذلك سجل الحياة والعمليات الجيولوجية الجارية المهمة في تشكّل التضاريس أو المعالم الجيومورفولوجية والفيزيوغرافية المهمة.',
			'أن يكون مثالًا بارزًا على العمليات البيئية والبيولوجية الجارية المهمة في تطوّر النظم البيئية البرية والمائية العذبة والساحلية والبحرية ومجتمعات النبات والحيوان.',
			'أن يضم أهم الموائل الطبيعية وأكثرها دلالة لصون التنوع البيولوجي في موقعه، بما فيها موائل الأنواع المهددة ذات القيمة العالمية البارزة من منظور العلم أو الصون.',
		),
	),
);

$body = function ( $l ) use ( $text, $src, $roman ) {
	$t = $text[ $l ];
	$h = '<p>' . esc_html( $t['intro'] ) . ' <a href="' . esc_url( $src ) . '" rel="noopener">' . esc_html( $t['src'] ) . '</a></p>' . "\n";
	foreach ( $roman as $i => $r ) {
		$h .= '<h2 id="criterion-' . $r . '">' . esc_html( sprintf( $t['head'], $r ) ) . '</h2>' . "\n" . '<p>' . esc_html( $t['items'][ $i ] ) . '</p>' . "\n";
	}
	return $h;
};

$fa_id = (int) get_option( 'topersia_wh_criteria_page', 0 );
$fa_id = $fa_id && 'page' === get_post_type( $fa_id ) && 'trash' !== get_post_status( $fa_id ) ? $fa_id : 0;
$ids   = array( 'fa' => $fa_id );
if ( $fa_id ) {
	foreach ( array( 'en', 'ar' ) as $l ) {
		$ids[ $l ] = (int) apply_filters( 'wpml_object_id', $fa_id, 'page', false, $l );
	}
}

WP_CLI::log( 'وضع کنونی: ' . wp_json_encode( $ids ) . ( $apply ? '' : ' — بی apply چیزی نوشته نمی‌شود' ) );
if ( ! $apply ) {
	return;
}

$trid = null;
foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
	do_action( 'wpml_switch_language', $l );
	$post = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $text[ $l ]['title'],
		'post_name'    => 'world-heritage-criteria',
		'post_content' => $body( $l ),
	);
	if ( ! empty( $ids[ $l ] ) ) {
		$post['ID'] = $ids[ $l ];
		$id = wp_update_post( wp_slash( $post ), true );
	} else {
		$id = wp_insert_post( wp_slash( $post ), true );
	}
	if ( is_wp_error( $id ) ) {
		do_action( 'wpml_switch_language', null );
		WP_CLI::error( $l . ': ' . $id->get_error_message() );
	}
	if ( 'fa' === $l ) {
		update_option( 'topersia_wh_criteria_page', (int) $id, false );
		$info = apply_filters( 'wpml_element_language_details', null, array( 'element_id' => (int) $id, 'element_type' => 'page' ) );
		if ( empty( $info->trid ) ) {
			do_action( 'wpml_set_element_language_details', array( 'element_id' => (int) $id, 'element_type' => 'post_page', 'trid' => false, 'language_code' => 'fa', 'source_language_code' => null ) );
			$info = apply_filters( 'wpml_element_language_details', null, array( 'element_id' => (int) $id, 'element_type' => 'page' ) );
		}
		$trid = $info->trid;
	} else {
		do_action( 'wpml_set_element_language_details', array( 'element_id' => (int) $id, 'element_type' => 'post_page', 'trid' => $trid, 'language_code' => $l, 'source_language_code' => 'fa' ) );
		/* نامک یکسان در هر زبان (WPML) — درج پیش از تعیین زبان «-2» می‌گرفت */
		if ( 'world-heritage-criteria' !== get_post_field( 'post_name', $id ) ) {
			wp_update_post( array( 'ID' => (int) $id, 'post_name' => 'world-heritage-criteria' ) );
		}
	}
	$ids[ $l ] = (int) $id;
	WP_CLI::log( $l . ' #' . $id . ' ' . get_permalink( $id ) );
}
do_action( 'wpml_switch_language', null );
WP_CLI::success( 'سه برگه: ' . wp_json_encode( $ids ) );

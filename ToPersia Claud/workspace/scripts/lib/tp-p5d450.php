<?php
/**
 * P5-D450 · P5-D451 (دستۀ ۲۸) — تنها منبع تعریف فیلدها، واژه‌نامه‌ها و روابط تازه.
 *
 * scripts/tp-local-28.php از این‌جا جت‌انجین را می‌سازد؛ FIELDMAP (render)، کاتالوگ fa/en/ar، پیکربندی WPML،
 * الزامی‌های بستۀ درون‌ریزی و آزمون‌ها (assert · p5d450-local) همه با همین فهرست سنجیده می‌شوند.
 *
 * kind: select (واژه‌نامه) · checkbox (واژه‌نامه، چندتایی) · switcher · number · date · text · url · textarea · repeater
 * req:  ★ الزامی در بستۀ درون‌ریزی (فقط پست تازه) — هر ★ گزینه‌ای «unknown» (نامعلوم) دارد
 * wpml: copy (عدد · گزینه · نشانی · تاریخ · رابطه) · translate (متن)
 */

if ( ! function_exists( 'tp_p5d450_glossaries' ) ) {
	/** slug ← [ برچسب fa, en, ar, گزینه‌ها: value => [fa, en, ar] ] */
	function tp_p5d450_glossaries() {
		$u = array( 'unknown' => array( 'نامعلوم', 'Unknown', 'غير معروف' ) );
		return array(
			'tp_wheelchair'   => array( 'دسترسی ویلچر', 'Wheelchair access', 'الوصول بالكرسي المتحرك', array( 'full' => array( 'کامل', 'Full', 'كامل' ), 'partial' => array( 'بخشی', 'Partial', 'جزئي' ), 'none' => array( 'ندارد', 'None', 'غير متاح' ) ) + $u ),
			'tp_time_of_day'  => array( 'بهترین زمان روز', 'Best time of day', 'أفضل وقت في اليوم', array( 'early_morning' => array( 'صبح زود', 'Early morning', 'الصباح الباكر' ), 'noon' => array( 'ظهر', 'Midday', 'الظهيرة' ), 'sunset' => array( 'غروب', 'Sunset', 'الغروب' ), 'night' => array( 'شب', 'Night', 'الليل' ) ) ),
			'tp_dress_req'    => array( 'پوشش لازم', 'Dress requirement', 'اللباس المطلوب', array( 'free' => array( 'آزاد', 'No requirement', 'بلا قيود' ), 'long_cover' => array( 'پوشش بلند', 'Long clothing', 'لباس طويل' ), 'chador' => array( 'چادر لازم', 'Chador required', 'الشادور مطلوب' ) ) + $u ),
			'tp_photo'        => array( 'عکاسی', 'Photography', 'التصوير', array( 'allowed' => array( 'آزاد', 'Allowed', 'مسموح' ), 'no_flash' => array( 'بی فلاش', 'No flash', 'بدون فلاش' ), 'forbidden' => array( 'ممنوع', 'Not allowed', 'ممنوع' ), 'paid' => array( 'با بلیت جدا', 'With a separate ticket', 'بتذكرة منفصلة' ) ) + $u ),
			'tp_weekday'      => array( 'روز هفته', 'Day of week', 'يوم الأسبوع', array( 'sat' => array( 'شنبه', 'Saturday', 'السبت' ), 'sun' => array( 'یکشنبه', 'Sunday', 'الأحد' ), 'mon' => array( 'دوشنبه', 'Monday', 'الاثنين' ), 'tue' => array( 'سه‌شنبه', 'Tuesday', 'الثلاثاء' ), 'wed' => array( 'چهارشنبه', 'Wednesday', 'الأربعاء' ), 'thu' => array( 'پنجشنبه', 'Thursday', 'الخميس' ), 'fri' => array( 'جمعه', 'Friday', 'الجمعة' ), 'holiday' => array( 'تعطیل رسمی', 'Public holiday', 'عطلة رسمية' ) ) ),
			'tp_plug'         => array( 'دوشاخۀ برق', 'Electric plug', 'القابس الكهربائي', array( 'c' => array( 'نوع C', 'Type C', 'النوع C' ), 'f' => array( 'نوع F', 'Type F', 'النوع F' ), 'g' => array( 'نوع G', 'Type G', 'النوع G' ) ) ),
			'tp_drive_side'   => array( 'سمت رانندگی', 'Driving side', 'جهة القيادة', array( 'right' => array( 'راست', 'Right', 'اليمين' ), 'left' => array( 'چپ', 'Left', 'اليسار' ) ) ),
			'tp_vibe'         => array( 'حال‌وهوا', 'Vibe', 'الأجواء', array( 'historic' => array( 'تاریخی', 'Historic', 'تاريخي' ), 'shopping' => array( 'خرید', 'Shopping', 'تسوق' ), 'nightlife' => array( 'شب‌گردی', 'Nightlife', 'حياة ليلية' ), 'quiet' => array( 'آرام', 'Quiet', 'هادئ' ), 'family' => array( 'خانوادگی', 'Family-friendly', 'عائلي' ) ) ),
			'tp_road'         => array( 'راه دسترسی', 'Access road', 'طريق الوصول', array( 'paved' => array( 'آسفالت', 'Paved', 'معبّد' ), 'dirt' => array( 'خاکی', 'Dirt road', 'ترابي' ), 'foot' => array( 'فقط پیاده', 'On foot only', 'سيرًا فقط' ) ) + $u ),
			'tp_swimming'     => array( 'شنا', 'Swimming', 'السباحة', array( 'allowed' => array( 'مجاز', 'Allowed', 'مسموحة' ), 'forbidden' => array( 'ممنوع', 'Not allowed', 'ممنوعة' ), 'seasonal' => array( 'فصلی', 'Seasonal', 'موسمية' ) ) + $u ),
			'tp_yes_no'       => array( 'بله/خیر', 'Yes/No', 'نعم/لا', array( 'yes' => array( 'بله', 'Yes', 'نعم' ), 'no' => array( 'خیر', 'No', 'لا' ) ) + $u ),
			'tp_solar_month'  => array( 'ماه', 'Month', 'الشهر', array( 'farvardin' => array( 'فروردین', 'Farvardin', 'فروردين' ), 'ordibehesht' => array( 'اردیبهشت', 'Ordibehesht', 'أرديبهشت' ), 'khordad' => array( 'خرداد', 'Khordad', 'خرداد' ), 'tir' => array( 'تیر', 'Tir', 'تير' ), 'mordad' => array( 'مرداد', 'Mordad', 'مرداد' ), 'shahrivar' => array( 'شهریور', 'Shahrivar', 'شهريور' ), 'mehr' => array( 'مهر', 'Mehr', 'مهر' ), 'aban' => array( 'آبان', 'Aban', 'آبان' ), 'azar' => array( 'آذر', 'Azar', 'آذر' ), 'dey' => array( 'دی', 'Dey', 'دي' ), 'bahman' => array( 'بهمن', 'Bahman', 'بهمن' ), 'esfand' => array( 'اسفند', 'Esfand', 'إسفند' ) ) ),
			'tp_difficulty'   => array( 'دشواری دسترسی', 'Access difficulty', 'صعوبة الوصول', array( 'easy' => array( 'آسان', 'Easy', 'سهل' ), 'moderate' => array( 'متوسط', 'Moderate', 'متوسط' ), 'hard' => array( 'سخت', 'Hard', 'صعب' ) ) + $u ),
			'tp_non_muslim'   => array( 'ورود غیرمسلمان', 'Entry for non-Muslims', 'دخول غير المسلمين', array( 'open' => array( 'آزاد', 'Open', 'مفتوح' ), 'courtyard' => array( 'فقط حیاط', 'Courtyard only', 'الفناء فقط' ), 'forbidden' => array( 'ممنوع', 'Not allowed', 'ممنوع' ) ) + $u ),
			'tp_guide_lang'   => array( 'زبان راهنما', 'Guide language', 'لغة الدليل', array( 'fa' => array( 'فارسی', 'Persian', 'الفارسية' ), 'en' => array( 'انگلیسی', 'English', 'الإنجليزية' ), 'ar' => array( 'عربی', 'Arabic', 'العربية' ), 'fr' => array( 'فرانسوی', 'French', 'الفرنسية' ), 'de' => array( 'آلمانی', 'German', 'الألمانية' ), 'it' => array( 'ایتالیایی', 'Italian', 'الإيطالية' ), 'es' => array( 'اسپانیایی', 'Spanish', 'الإسبانية' ), 'ru' => array( 'روسی', 'Russian', 'الروسية' ), 'zh' => array( 'چینی', 'Chinese', 'الصينية' ), 'ja' => array( 'ژاپنی', 'Japanese', 'اليابانية' ) ) ),
			'tp_diet'         => array( 'رژیم غذایی', 'Diet', 'النظام الغذائي', array( 'vegetarian' => array( 'گیاهی', 'Vegetarian', 'نباتي' ), 'vegan' => array( 'وگان', 'Vegan', 'نباتي صرف' ), 'halal' => array( 'حلال', 'Halal', 'حلال' ), 'gluten_free' => array( 'بی گلوتن', 'Gluten-free', 'خالٍ من الغلوتين' ), 'dairy' => array( 'لبنی دارد', 'Contains dairy', 'يحتوي على الألبان' ) ) ),
			'tp_worn_today'   => array( 'پوشیدن امروز', 'Worn today', 'الارتداء اليوم', array( 'daily' => array( 'روزمره', 'Everyday', 'يومي' ), 'ceremonial' => array( 'در مراسم', 'At ceremonies', 'في المناسبات' ), 'museum' => array( 'فقط موزه', 'Museum only', 'في المتاحف فقط' ) ) + $u ),
			'tp_welcome'      => array( 'حضور بازدیدکننده', 'Visitors', 'الزوار', array( 'open' => array( 'آزاد', 'Open to visitors', 'مفتوح للزوار' ), 'invitation' => array( 'با دعوت', 'By invitation', 'بدعوة' ), 'closed' => array( 'بسته', 'Closed to visitors', 'مغلق أمام الزوار' ) ) + $u ),
			'tp_ticket'       => array( 'ورودی', 'Admission', 'الدخول', array( 'free' => array( 'رایگان', 'Free', 'مجاني' ), 'ticket' => array( 'بلیت', 'Ticket', 'بتذكرة' ), 'invitation' => array( 'با دعوت', 'By invitation', 'بدعوة' ) ) + $u ),
			'tp_tap_water'    => array( 'آب لوله‌کشی', 'Tap water', 'ماء الصنبور', array( 'yes' => array( 'آشامیدنی', 'Drinkable', 'صالح للشرب' ), 'boil' => array( 'جوشانده', 'Boil first', 'يُغلى أولًا' ), 'no' => array( 'ننوشید', 'Do not drink', 'لا يُشرب' ) ) + $u ),
			'tp_ride_app'     => array( 'تاکسی اینترنتی', 'Ride-hailing app', 'تطبيق نقل', array( 'snapp' => array( 'اسنپ', 'Snapp', 'سناب' ), 'tapsi' => array( 'تپسی', 'Tapsi', 'تبسي' ), 'maxim' => array( 'ماکسیم', 'Maxim', 'ماكسيم' ) ) ),
		);
	}
}

if ( ! function_exists( 'tp_p5d450_fields' ) ) {
	/**
	 * نام فیلد ← [ kind, برچسب fa, en, ar, راهنمای مدیر (fa), نوع‌ها, req, واژه‌نامه یا زیرفیلدها ]
	 */
	function tp_p5d450_fields() {
		$visit = array( 'tp_monument', 'tp_religious_site', 'tp_ancient_site', 'tp_museum', 'tp_natural_feature', 'tp_protected_area', 'tp_landscape' );
		return array(
			/* ── گروه «بازدید» (متاباکس مشترک) ── */
			'opening_hours_spec'   => array( 'repeater', 'ساعت بازدید (جدول)', 'Opening hours', 'ساعات الزيارة', 'هر روز یک ردیف: روز، از، تا؛ روز تعطیل «بسته» را روشن کنید. متن آزاد «ساعات بازدید» برای توضیح می‌ماند.', $visit, false,
				array( 'oh_day' => array( 'select', 'روز', 'tp_weekday' ), 'oh_from' => array( 'time', 'از' ), 'oh_to' => array( 'time', 'تا' ), 'oh_closed' => array( 'switcher', 'بسته' ) ) ),
			'ticket_price_foreign' => array( 'number', 'بهای بلیت خارجی (ریال)', 'Ticket price, foreign visitors (IRR)', 'سعر التذكرة للأجانب (ريال)', 'فقط عدد به ریال، بی جداکننده. همراه «تاریخ بررسی بها» پر شود.', $visit, false ),
			'ticket_price_local'   => array( 'number', 'بهای بلیت داخلی (ریال)', 'Ticket price, Iranian visitors (IRR)', 'سعر التذكرة للإيرانيين (ريال)', 'فقط عدد به ریال، بی جداکننده.', $visit, false ),
			'ticket_price_checked' => array( 'date', 'تاریخ بررسی بها', 'Prices checked on', 'تاريخ التحقق من الأسعار', 'آخرین باری که بها از منبع رسمی بررسی شد (میلادی). بهای کهنه روی صفحه پنهان می‌شود.', $visit, false ),
			'wheelchair_access'    => array( 'select', 'دسترسی ویلچر', 'Wheelchair access', 'الوصول بالكرسي المتحرك', 'متن آزاد «دسترس‌پذیری» برای توضیح می‌ماند.', $visit, false, 'tp_wheelchair' ),
			'best_time_of_day'     => array( 'checkbox', 'بهترین زمان روز', 'Best time of day', 'أفضل وقت في اليوم', 'کِی خلوت‌تر یا نورش برای عکاسی بهتر است.', $visit, false, 'tp_time_of_day' ),
			'dress_requirement'    => array( 'select', 'پوشش لازم', 'Dress requirement', 'اللباس المطلوب', 'متن آزاد «جزئیات پوشش» برای توضیح می‌ماند.', $visit, false, 'tp_dress_req' ),
			'photo_allowed'        => array( 'select', 'عکاسی', 'Photography', 'التصوير', 'متن آزاد «سیاست عکاسی» برای توضیح می‌ماند.', $visit, false, 'tp_photo' ),

			/* ── ویژۀ نوع ── */
			'electric_plug'        => array( 'checkbox', 'دوشاخۀ برق', 'Electric plug type', 'نوع القابس الكهربائي', 'نوع دوشاخه‌های رایج (ایران: C و F، ۲۳۰ ولت).', array( 'tp_country' ), false, 'tp_plug' ),
			'drive_side'           => array( 'select', 'سمت رانندگی', 'Driving side', 'جهة القيادة', '', array( 'tp_country' ), false, 'tp_drive_side' ),
			'typical_trip_days'    => array( 'number', 'روزهای معمول سفر', 'Typical trip length (days)', 'مدة الرحلة المعتادة (أيام)', 'چند روز برای دیدن این منطقه کافی است — فقط عدد.', array( 'tp_region' ), false ),
			'metro_available'      => array( 'switcher', 'مترو دارد', 'Has a metro', 'يوجد مترو', '', array( 'tp_city' ), false ),
			'vibe_tags'            => array( 'checkbox', 'حال‌وهوا', 'Vibe', 'الأجواء', 'برای انتخاب محل اقامت.', array( 'tp_district' ), false, 'tp_vibe' ),
			'access_road_type'     => array( 'select', 'راه دسترسی', 'Access road', 'طريق الوصول', 'الزامی در بستۀ درون‌ریزی؛ اگر نمی‌دانید «نامعلوم».', array( 'tp_village' ), true, 'tp_road' ),
			'homestay_available'   => array( 'switcher', 'اقامتگاه بومگردی دارد', 'Homestay available', 'تتوفر إقامة منزلية', '', array( 'tp_village' ), false ),
			'ferry_ports'          => array( 'repeater', 'بندرهای لنج و کشتی', 'Ferry ports', 'موانئ العبّارات', 'هر بندر یک ردیف؛ متن آزاد «روش دسترسی» برای توضیح می‌ماند.', array( 'tp_island' ), false,
				array( 'fp_port' => array( 'text', 'بندر' ), 'fp_note' => array( 'text', 'یادداشت' ) ) ),
			'summit_route_hours'   => array( 'number', 'زمان صعود (ساعت)', 'Summit climb (hours)', 'زمن الصعود (ساعات)', 'از پایین مسیر استاندارد تا قله — فقط عدد.', array( 'tp_mountain' ), false ),
			'permit_required'      => array( 'switcher', 'مجوز صعود لازم است', 'Climbing permit required', 'يلزم تصريح صعود', '', array( 'tp_mountain' ), false ),
			'swimming_allowed'     => array( 'select', 'شنا', 'Swimming', 'السباحة', '', array( 'tp_water_body' ), false, 'tp_swimming' ),
			'tour_required'        => array( 'select', 'تور یا راهنما لازم است', 'Guide or tour required', 'يلزم دليل أو جولة', 'الزامی در بستۀ درون‌ریزی؛ اگر نمی‌دانید «نامعلوم».', array( 'tp_desert' ), true, 'tp_yes_no' ),
			'safe_months'          => array( 'checkbox', 'ماه‌های امن سفر', 'Safe months to visit', 'الأشهر الآمنة للزيارة', '', array( 'tp_desert' ), false, 'tp_solar_month' ),
			'trail_length_km'      => array( 'number', 'طول مسیر پیاده‌روی (کیلومتر)', 'Trail length (km)', 'طول المسار (كم)', 'فقط عدد.', array( 'tp_forest_plain' ), false ),
			'access_difficulty'    => array( 'select', 'دشواری دسترسی', 'Access difficulty', 'صعوبة الوصول', '', array( 'tp_natural_feature' ), false, 'tp_difficulty' ),
			'permit_contact_url'   => array( 'url', 'نشانی دریافت مجوز', 'Permit application link', 'رابط طلب التصريح', 'صفحۀ رسمی گرفتن مجوز ورود.', array( 'tp_protected_area' ), false ),
			'viewpoints'           => array( 'repeater', 'نقاط دید', 'Viewpoints', 'نقاط المشاهدة', 'هر نقطه یک ردیف: نام و مختصات «عرض، طول».', array( 'tp_landscape' ), false,
				array( 'vw_name' => array( 'text', 'نام نقطه' ), 'vw_coords' => array( 'text', 'مختصات' ) ) ),
			'visa_on_arrival'      => array( 'switcher', 'روادید هنگام ورود', 'Visa on arrival', 'تأشيرة عند الوصول', '', array( 'tp_border_crossing' ), false ),
			'non_muslim_entry'     => array( 'select', 'ورود غیرمسلمان', 'Entry for non-Muslims', 'دخول غير المسلمين', 'الزامی در بستۀ درون‌ریزی؛ اگر نمی‌دانید «نامعلوم».', array( 'tp_religious_site' ), true, 'tp_non_muslim' ),
			'prayer_times_closure' => array( 'textarea', 'بسته‌بودن هنگام نماز', 'Closures at prayer times', 'الإغلاق أثناء الصلاة', '', array( 'tp_religious_site' ), false ),
			'on_site_guide'        => array( 'switcher', 'راهنما در محل هست', 'Guide on site', 'يوجد دليل في الموقع', '', array( 'tp_ancient_site' ), false ),
			'audio_guide_languages' => array( 'checkbox', 'زبان‌های راهنمای صوتی', 'Audio guide languages', 'لغات الدليل الصوتي', '', array( 'tp_museum' ), false, 'tp_guide_lang' ),
			'dietary_flags'        => array( 'checkbox', 'رژیم غذایی', 'Dietary notes', 'ملاحظات غذائية', '', array( 'tp_local_food' ), false, 'tp_diet' ),
			'worn_today'           => array( 'select', 'پوشیدن امروز', 'Worn today', 'الارتداء اليوم', '', array( 'tp_costume' ), false, 'tp_worn_today' ),
			'audience_participation' => array( 'switcher', 'گردشگر همراه می‌شود', 'Visitors can join', 'يمكن للزوار المشاركة', '', array( 'tp_dance' ), false ),
			'visitor_welcome'      => array( 'select', 'حضور بازدیدکننده', 'Visitors', 'الزوار', 'الزامی در بستۀ درون‌ریزی؛ اگر نمی‌دانید «نامعلوم».', array( 'tp_ritual' ), true, 'tp_welcome' ),
			'ticket_required'      => array( 'select', 'ورودی', 'Admission', 'الدخول', 'متن آزاد «یادداشت ورودی» برای توضیح می‌ماند.', array( 'tp_festival' ), false, 'tp_ticket' ),
			'traffic_closure_note' => array( 'textarea', 'بسته‌شدن خیابان‌ها', 'Road closures', 'إغلاق الطرق', '', array( 'tp_religious_event' ), false ),
			'ticket_url'           => array( 'url', 'خرید بلیت', 'Tickets', 'شراء التذاكر', 'نشانی رسمی فروش بلیت.', array( 'tp_sports_event' ), false ),
			'last_observed_date'   => array( 'date', 'آخرین مشاهده', 'Last observed', 'آخر مشاهدة', 'تاریخ میلادی آخرین بار که این پدیده دیده شد.', array( 'tp_seasonal_event' ), false ),
			'processing_days'      => array( 'number', 'زمان رسیدگی (روز کاری)', 'Processing time (working days)', 'مدة المعالجة (أيام عمل)', 'فقط عدد.', array( 'tp_visa' ), false ),
			'ride_hailing_apps'    => array( 'checkbox', 'تاکسی اینترنتی', 'Ride-hailing apps', 'تطبيقات النقل', '', array( 'tp_transport' ), false, 'tp_ride_app' ),
			'gesture_warnings'     => array( 'repeater', 'اشاره‌های نابه‌جا', 'Gestures to avoid', 'إيماءات يُستحسن تجنبها', 'هر اشاره یک ردیف: اشاره و معنی آن در ایران.', array( 'tp_travel_etiquette' ), false,
				array( 'gw_gesture' => array( 'text', 'اشاره' ), 'gw_meaning' => array( 'text', 'معنی' ) ) ),
			'tap_water_safe'       => array( 'select', 'آب لوله‌کشی', 'Tap water', 'ماء الصنبور', '', array( 'tp_health_safety' ), false, 'tp_tap_water' ),
		);
	}
}

if ( ! function_exists( 'tp_p5d450_relations' ) ) {
	/**
	 * روابط تازه (P5-D450 · P5-D451): کلید ← [ والد, فرزند, نوع, برچسب fa (سوی والد), برچسب fa (سوی فرزند), en, ar, ★ ]
	 * ★ روی سوی فرزند (پست تازۀ فرزند باید دست‌کم یک والد داشته باشد).
	 */
	function tp_p5d450_relations() {
		return array(
			'main_airports'            => array( 'tp_province', 'tp_city', 'many_to_many', 'فرودگاه‌های اصلی این استان', 'فرودگاه اصلی استان', 'Main airports', 'المطارات الرئيسية', false ),
			'start_city'               => array( 'tp_city', 'tp_route', 'one_to_many', 'مسیرهایی که از این شهر آغاز می‌شوند', 'شهر آغاز مسیر', 'Route starts in', 'تبدأ الجولة في', true ),
			'end_city'                 => array( 'tp_city', 'tp_route', 'one_to_many', 'مسیرهایی که در این شهر پایان می‌یابند', 'شهر پایان مسیر', 'Route ends in', 'تنتهي الجولة في', true ),
			'buy_where_reference'      => array( 'tp_city', 'tp_handicraft', 'many_to_many', 'صنایع دستی برای خرید در این شهر', 'جای خرید', 'Where to buy', 'أين تشتري', false ),
			'where_to_eat_reference'   => array( 'tp_city', 'tp_local_food', 'many_to_many', 'غذاهای محلی برای چشیدن در این شهر', 'جای چشیدن', 'Where to eat', 'أين تتذوقه', false ),
			'listen_where_reference'   => array( 'tp_city', 'tp_music', 'many_to_many', 'موسیقی زنده در این شهر', 'جای شنیدن', 'Where to listen', 'أين تستمع', false ),
			'resting_place_reference'  => array( 'tp_monument', 'tp_figure', 'one_to_many', 'آرامگاه این شخصیت‌ها', 'آرامگاه', 'Resting place', 'المرقد', false ),
			'key_sites_reference'      => array( 'tp_historical_era', 'tp_monument', 'many_to_many', 'بناهای شاخص این دوره', 'دورۀ تاریخی', 'Key sites', 'أبرز المواقع', false ),
			'key_sites_ancient'        => array( 'tp_historical_era', 'tp_ancient_site', 'many_to_many', 'محوطه‌های شاخص این دوره', 'دورۀ تاریخی', 'Key sites', 'أبرز المواقع', false ),
			'main_provinces_reference' => array( 'tp_province', 'tp_ethnic_group', 'many_to_many', 'اقوام ساکن این استان', 'استان‌های اصلی سکونت', 'Main provinces', 'المحافظات الرئيسية', true ),
			'dance_province'           => array( 'tp_province', 'tp_dance', 'many_to_many', 'رقص‌های رایج در این استان', 'محل رواج', 'Where it is performed', 'مكان الانتشار', false ),
			'language_province'        => array( 'tp_province', 'tp_language', 'many_to_many', 'زبان‌ها و گویش‌های رایج در این استان', 'محل رواج', 'Where it is spoken', 'مكان الانتشار', false ),
		);
	}
}

if ( ! function_exists( 'tp_p5d451_merges' ) ) {
	/** ادغام P5-D451: رابطۀ حذفی ← رابطۀ ماندنی (همه صفر ردیف؛ پیش از حذف شمار ردیف دوباره سنجیده می‌شود). */
	function tp_p5d451_merges() {
		return array(
			151 => array( 153, 154 ),   /* چهره → شهر  ⊂  زادگاه · محل درگذشت (شهر → چهره) */
			155 => array( 146, 148 ),   /* بنا → چهره  ⊂  ساخته‌شده · مرتبط (چهره → بنا) */
			239 => array( 234 ),        /* روستا → جشنواره «محل برگزاری» ← روستا → جشنواره */
			252 => array( 247 ),        /* روستا → رویداد ورزشی */
			258 => array( 255 ),        /* روستا → رویداد مذهبی */
			241 => array( 240 ),        /* آیین → جشنواره */
			260 => array( 259 ),        /* آیین → رویداد مذهبی */
			163 => array( 138 ),        /* قوم → پوشاک */
		);
	}
}

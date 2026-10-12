<?php
defined('ABSPATH') || exit;

final class ToPersia_I18n {
    private static ?self $instance = null;

    /** @var array<string,array{fa:string,en:string,ar:string}> */
    private array $strings = [
        'account_menu' => ['fa'=>'منوی حساب کاربری','en'=>'Account menu','ar'=>'قائمة الحساب'],
        'account_sections' => ['fa'=>'بخش‌های حساب','en'=>'Account sections','ar'=>'أقسام الحساب'],
        'back_to_site' => ['fa'=>'بازگشت به سایت','en'=>'Back to site','ar'=>'العودة إلى الموقع'],
        'overview' => ['fa'=>'نمای کلی','en'=>'Overview','ar'=>'نظرة عامة'],
        'profile' => ['fa'=>'پروفایل من','en'=>'My Profile','ar'=>'ملفي الشخصي'],
        'saved' => ['fa'=>'مکان‌های ذخیره‌شده','en'=>'Saved Places','ar'=>'الأماكن المحفوظة'],
        'trips' => ['fa'=>'سفرهای من','en'=>'My Trips','ar'=>'رحلاتي'],
        'reviews' => ['fa'=>'نظرات و امتیازها','en'=>'Reviews & Ratings','ar'=>'التقييمات والمراجعات'],
        'notifications' => ['fa'=>'اعلان‌ها','en'=>'Notifications','ar'=>'الإشعارات'],
        'settings' => ['fa'=>'تنظیمات','en'=>'Settings','ar'=>'الإعدادات'],
        'logout' => ['fa'=>'خروج از حساب','en'=>'Log out','ar'=>'تسجيل الخروج'],
        'welcome' => ['fa'=>'سلام، %s 👋','en'=>'Hello, %s 👋','ar'=>'مرحباً، %s 👋'],
        'welcome_sub' => ['fa'=>'همه‌چیز برای مدیریت سفر شما، بدون شلوغی.','en'=>'Everything you need to manage your travel, without the clutter.','ar'=>'كل ما تحتاجه لإدارة رحلتك، دون تعقيد.'],
        'new_trip' => ['fa'=>'ساخت سفر جدید','en'=>'Create a trip','ar'=>'إنشاء رحلة'],
        'saved_count' => ['fa'=>'مکان‌های ذخیره‌شده','en'=>'Saved places','ar'=>'الأماكن المحفوظة'],
        'trips_count' => ['fa'=>'سفرهای من','en'=>'My trips','ar'=>'رحلاتي'],
        'reviews_count' => ['fa'=>'نظرات ثبت‌شده','en'=>'Reviews','ar'=>'المراجعات'],
        'places_and_experiences' => ['fa'=>'مکان و تجربه','en'=>'Places & experiences','ar'=>'أماكن وتجارب'],
        'planned' => ['fa'=>'برنامه‌ریزی شده','en'=>'Planned','ar'=>'مخططة'],
        'recent_order' => ['fa'=>'سفارش اخیر','en'=>'Recent order','ar'=>'آخر طلب'],
        'your_experiences' => ['fa'=>'تجربه‌های شما','en'=>'Your experiences','ar'=>'تجاربك'],
        'quick_start' => ['fa'=>'شروع سریع','en'=>'Quick start','ar'=>'بدء سريع'],
        'for_traveler' => ['fa'=>'برای مهمان','en'=>'For travelers','ar'=>'للمسافر'],
        'favorites' => ['fa'=>'مکان‌های موردعلاقه','en'=>'Favorite places','ar'=>'الأماكن المفضلة'],
        'plan_trip' => ['fa'=>'برنامه‌ریزی سفر','en'=>'Plan a trip','ar'=>'خطط لرحلة'],
        'write_review' => ['fa'=>'ثبت تجربه','en'=>'Write a review','ar'=>'اكتب مراجعة'],
        'complete_profile' => ['fa'=>'تکمیل پروفایل','en'=>'Complete profile','ar'=>'أكمل ملفك الشخصي'],
        'traveler_info' => ['fa'=>'اطلاعات مهمان','en'=>'Traveler information','ar'=>'معلومات المسافر'],
        'edit' => ['fa'=>'ویرایش','en'=>'Edit','ar'=>'تعديل'],
        'mobile' => ['fa'=>'موبایل','en'=>'Mobile','ar'=>'الهاتف المحمول'],
        'email' => ['fa'=>'ایمیل','en'=>'Email','ar'=>'البريد الإلكتروني'],
        'country' => ['fa'=>'کشور','en'=>'Country','ar'=>'الدولة'],
        'profile_title' => ['fa'=>'پروفایل من','en'=>'My Profile','ar'=>'ملفي الشخصي'],
        'profile_desc' => ['fa'=>'اطلاعاتی که تجربه سفر ToPersia را شخصی‌تر می‌کند.','en'=>'Information that makes your ToPersia travel experience more personal.','ar'=>'معلومات تجعل تجربة سفرك مع ToPersia أكثر تخصيصاً.'],
        'first_name' => ['fa'=>'نام','en'=>'First name','ar'=>'الاسم الأول'],
        'last_name' => ['fa'=>'نام خانوادگی','en'=>'Last name','ar'=>'اسم العائلة'],
        'language' => ['fa'=>'زبان','en'=>'Language','ar'=>'اللغة'],
        'travel_style' => ['fa'=>'سبک سفر','en'=>'Travel style','ar'=>'نمط السفر'],
        'select' => ['fa'=>'انتخاب کنید','en'=>'Select','ar'=>'اختر'],
        'culture' => ['fa'=>'فرهنگی','en'=>'Culture','ar'=>'ثقافي'],
        'nature' => ['fa'=>'طبیعت‌گردی','en'=>'Nature','ar'=>'طبيعة'],
        'food' => ['fa'=>'غذا و تجربه محلی','en'=>'Food & local experiences','ar'=>'الطعام والتجارب المحلية'],
        'luxury' => ['fa'=>'لوکس','en'=>'Luxury','ar'=>'فاخر'],
        'adventure' => ['fa'=>'ماجراجویانه','en'=>'Adventure','ar'=>'مغامرات'],
        'save_changes' => ['fa'=>'ذخیره تغییرات','en'=>'Save changes','ar'=>'حفظ التغييرات'],
        'saved_desc' => ['fa'=>'فقط مواردی که خودتان ذخیره کرده‌اید در اینجا نمایش داده می‌شوند.','en'=>'Only the places you save are shown here.','ar'=>'تظهر هنا فقط الأماكن التي حفظتها.'],
        'saved_badge' => ['fa'=>'ذخیره‌شده','en'=>'Saved','ar'=>'محفوظ'],
        'view' => ['fa'=>'مشاهده','en'=>'View','ar'=>'عرض'],
        'remove_saved' => ['fa'=>'حذف از ذخیره‌ها','en'=>'Remove from saved','ar'=>'إزالة من المحفوظات'],
        'nothing_saved' => ['fa'=>'هنوز چیزی ذخیره نکرده‌اید','en'=>'You have not saved anything yet','ar'=>'لم تحفظ أي شيء بعد'],
        'save_hint' => ['fa'=>'روی دکمه ذخیره در صفحات مقصد بزنید تا موارد موردعلاقه‌تان اینجا جمع شوند.','en'=>'Use the save button on destination pages to collect places you like here.','ar'=>'استخدم زر الحفظ في صفحات الوجهات لجمع الأماكن التي تعجبك هنا.'],
        'trips_title' => ['fa'=>'سفرهای من','en'=>'My Trips','ar'=>'رحلاتي'],
        'trips_desc' => ['fa'=>'اینجا محل سفرهای برنامه‌ریزی‌شده شماست.','en'=>'This is where your planned trips will appear.','ar'=>'هنا ستظهر رحلاتك المخططة.'],
        'reviews_title' => ['fa'=>'نظرات و امتیازها','en'=>'Reviews & Ratings','ar'=>'التقييمات والمراجعات'],
        'reviews_desc' => ['fa'=>'تمام نظراتی که در سراسر ToPersia ثبت کرده‌اید، همراه پاسخ‌هایی که دریافت کرده‌اید.','en'=>'Every review you have posted across ToPersia, along with any replies you received.','ar'=>'جميع مراجعاتك عبر ToPersia، إلى جانب أي ردود تلقيتها.'],
        'reviews_empty' => ['fa'=>'هنوز نظری ثبت نکرده‌اید','en'=>'You have not written any reviews yet','ar'=>'لم تكتب أي مراجعة بعد'],
        'reviews_empty_hint' => ['fa'=>'وقتی از صفحه یک مکان یا تجربه نظری ثبت کنید، همین‌جا نمایش داده می‌شود.','en'=>'Reviews you leave on any place or experience page will appear here.','ar'=>'ستظهر هنا المراجعات التي تتركها في صفحة أي مكان أو تجربة.'],
        'reviews_pending' => ['fa'=>'در انتظار تأیید','en'=>'Awaiting approval','ar'=>'بانتظار الموافقة'],
        'reviews_replies_heading' => ['fa'=>'پاسخ‌ها','en'=>'Replies','ar'=>'الردود'],
        'reviews_no_replies' => ['fa'=>'هنوز کسی پاسخ نداده است.','en'=>'No one has replied yet.','ar'=>'لم يرد أحد بعد.'],
        'reviews_view_entity' => ['fa'=>'مشاهده مورد بازبینی‌شده','en'=>'View reviewed item','ar'=>'عرض العنصر الذي تمت مراجعته'],
        'reviews_new_replies_badge' => ['fa'=>'پاسخ دریافت شده','en'=>'Replies received','ar'=>'تم استلام ردود'],
        'traveler_name' => ['fa'=>'مسافر %s','en'=>'Traveler %s','ar'=>'مسافر %s'],
        'reviews_guest_author' => ['fa'=>'کاربر مهمان','en'=>'Guest user','ar'=>'مستخدم زائر'],
        'reviews_deleted_author' => ['fa'=>'کاربر حذف‌شده','en'=>'Deleted user','ar'=>'مستخدم محذوف'],
        'reviews_unavailable_entity' => ['fa'=>'این مطلب دیگر در دسترس نیست','en'=>'This item is no longer available','ar'=>'هذا المحتوى لم يعد متاحًا'],
        'notifications_title' => ['fa'=>'اعلان‌ها','en'=>'Notifications','ar'=>'الإشعارات'],
        'notifications_desc' => ['fa'=>'اعلان‌های مرتبط با سفرها و پیشنهادهای شخصی‌سازی‌شده اینجا قرار می‌گیرند.','en'=>'Travel updates and personalized recommendations will appear here.','ar'=>'ستظهر هنا تحديثات السفر والتوصيات الشخصية.'],
        'settings_title' => ['fa'=>'تنظیمات','en'=>'Settings','ar'=>'الإعدادات'],
        'account_language' => ['fa'=>'زبان حساب','en'=>'Account language','ar'=>'لغة الحساب'],
        'language_hint' => ['fa'=>'از بخش پروفایل قابل تغییر است.','en'=>'You can change it from your profile.','ar'=>'يمكنك تغييرها من ملفك الشخصي.'],
        'privacy' => ['fa'=>'حریم خصوصی','en'=>'Privacy','ar'=>'الخصوصية'],
        'privacy_hint' => ['fa'=>'کنترل اطلاعات حساب.','en'=>'Control your account information.','ar'=>'التحكم في معلومات حسابك.'],
        'display_settings_title' => ['fa'=>'شخصی‌سازی نمایش','en'=>'Display Personalization','ar'=>'تخصيص العرض'],
        'display_settings_hint' => ['fa'=>'حالت رنگ، اندازۀ متن، فاصله و پهنای متن، و راحتی چشم را برای خودتان تنظیم کنید — روی حساب شما ذخیره می‌شود و روی هر دستگاهی که وارد شوید همراهتان می‌آید.','en'=>'Set your color mode, text size, spacing and width, and eye comfort — saved to your account and applied on every device you sign in from.','ar'=>'اضبط وضع الألوان وحجم النص وتباعده وعرضه وراحة العين — يُحفظ في حسابك ويُطبَّق على أي جهاز تسجّل الدخول منه.'],
        'display_mode_label' => ['fa'=>'حالت رنگ','en'=>'Color mode','ar'=>'وضع الألوان'],
        'display_mode_auto' => ['fa'=>'خودکار','en'=>'Auto','ar'=>'تلقائي'],
        'display_mode_light' => ['fa'=>'روز','en'=>'Day','ar'=>'نهار'],
        'display_mode_evening' => ['fa'=>'بعدازظهر','en'=>'Evening','ar'=>'مساء'],
        'display_mode_dark' => ['fa'=>'شب','en'=>'Night','ar'=>'ليل'],
        'display_scale_label' => ['fa'=>'اندازۀ متن','en'=>'Text size','ar'=>'حجم النص'],
        'display_contrast_label' => ['fa'=>'کنتراست بالا','en'=>'High contrast','ar'=>'تباين عالٍ'],
        'display_spacing_label' => ['fa'=>'فاصلۀ متن','en'=>'Text spacing','ar'=>'تباعد النص'],
        'display_spacing_compact' => ['fa'=>'فشرده','en'=>'Compact','ar'=>'مضغوط'],
        'display_spacing_normal' => ['fa'=>'استاندارد','en'=>'Standard','ar'=>'قياسي'],
        'display_spacing_loose' => ['fa'=>'باز','en'=>'Loose','ar'=>'واسع'],
        'display_measure_label' => ['fa'=>'پهنای ستون متن','en'=>'Text column width','ar'=>'عرض عمود النص'],
        'display_measure_narrow' => ['fa'=>'باریک','en'=>'Narrow','ar'=>'ضيق'],
        'display_measure_standard' => ['fa'=>'استاندارد','en'=>'Standard','ar'=>'قياسي'],
        'display_comfort_label' => ['fa'=>'راحتی چشم','en'=>'Eye comfort','ar'=>'راحة العين'],
        'display_calm_label' => ['fa'=>'کاهش شدت رنگ و نور','en'=>'Reduce color & glare intensity','ar'=>'تقليل شدة اللون والوهج'],
        'display_motion_label' => ['fa'=>'توقف حرکت و پخش خودکار','en'=>'Stop motion & autoplay','ar'=>'إيقاف الحركة والتشغيل التلقائي'],
        'display_saved_confirmation' => ['fa'=>'ذخیره شد ✓','en'=>'Saved ✓','ar'=>'تم الحفظ ✓'],
        'active' => ['fa'=>'فعال','en'=>'Active','ar'=>'نشط'],
        'success_saved' => ['fa'=>'پروفایل با موفقیت ذخیره شد.','en'=>'Profile saved successfully.','ar'=>'تم حفظ الملف الشخصي بنجاح.'],
        'invalid_request' => ['fa'=>'درخواست نامعتبر است.','en'=>'Invalid request.','ar'=>'طلب غير صالح.'],
        'save_button' => ['fa'=>'ذخیره در مکان‌های من','en'=>'Save to my places','ar'=>'الحفظ في أماكني'],
        'save_added' => ['fa'=>'به مکان‌های ذخیره‌شده اضافه شد.','en'=>'Added to your saved places.','ar'=>'تمت الإضافة إلى الأماكن المحفوظة.'],
        'save_removed' => ['fa'=>'از مکان‌های ذخیره‌شده حذف شد.','en'=>'Removed from your saved places.','ar'=>'تمت الإزالة من الأماكن المحفوظة.'],

        /* ۱٫۱۰٫۰ — دنبال‌کردن، پنجرۀ ورود، نظر */
        'following' => ['fa'=>'دنبال‌شده‌ها','en'=>'Following','ar'=>'المتابَعة'],
        'following_desc' => ['fa'=>'تازه‌ترین خبرهای مکان‌هایی که دنبال می‌کنید و مکان‌های درون آن‌ها.','en'=>'The latest news from the places you follow and the places within them.','ar'=>'أحدث الأخبار عن الأماكن التي تتابعها والأماكن التابعة لها.'],
        'follow_button' => ['fa'=>'دنبال‌کردن','en'=>'Follow','ar'=>'متابعة'],
        'following_badge' => ['fa'=>'دنبال می‌کنید','en'=>'Following','ar'=>'تتابع'],
        'unfollow' => ['fa'=>'لغو دنبال‌کردن','en'=>'Unfollow','ar'=>'إلغاء المتابعة'],
        'follow_added' => ['fa'=>'خبرهای این مکان را دنبال می‌کنید.','en'=>'You are now following news about this place.','ar'=>'أنت الآن تتابع أخبار هذا المكان.'],
        'follow_removed' => ['fa'=>'دنبال‌کردن لغو شد.','en'=>'You stopped following this place.','ar'=>'تم إلغاء المتابعة.'],
        'nothing_followed' => ['fa'=>'هنوز جایی را دنبال نمی‌کنید','en'=>'You are not following any place yet','ar'=>'لا تتابع أي مكان بعد'],
        'follow_hint' => ['fa'=>'روی «دنبال‌کردن» در صفحۀ هر مقصد بزنید تا خبرهایش این‌جا بیاید.','en'=>'Use Follow on a destination page to see its news here.','ar'=>'استخدم زر المتابعة في صفحة الوجهة لتظهر أخبارها هنا.'],
        'no_recent_news' => ['fa'=>'خبر تازه‌ای نیست.','en'=>'No recent news.','ar'=>'لا توجد أخبار حديثة.'],
        'login_title' => ['fa'=>'ورود یا ثبت‌نام','en'=>'Log in or sign up','ar'=>'تسجيل الدخول أو الاشتراك'],
        'login_intro' => ['fa'=>'با شمارۀ موبایل وارد شوید؛ اگر حساب ندارید، همین حالا ساخته می‌شود.','en'=>'Log in with your mobile number; if you have no account, one is created now.','ar'=>'سجّل الدخول برقم هاتفك؛ إن لم يكن لديك حساب فسيُنشأ الآن.'],
        'login_reason_save' => ['fa'=>'برای ذخیرۀ این مکان وارد شوید.','en'=>'Log in to save this place.','ar'=>'سجّل الدخول لحفظ هذا المكان.'],
        'login_reason_follow' => ['fa'=>'برای دنبال‌کردن خبرهای این مکان وارد شوید.','en'=>'Log in to follow news about this place.','ar'=>'سجّل الدخول لمتابعة أخبار هذا المكان.'],
        'login_reason_review' => ['fa'=>'برای نوشتن نظر وارد شوید.','en'=>'Log in to write a review.','ar'=>'سجّل الدخول لكتابة مراجعة.'],
        'login_reason_ai' => ['fa'=>'برای دسترسی به امکانات بیشتر هوش مصنوعی وارد شوید.','en'=>'Log in for more AI features.','ar'=>'سجّل الدخول للوصول إلى مزيد من ميزات الذكاء الاصطناعي.'],
        'close' => ['fa'=>'بستن','en'=>'Close','ar'=>'إغلاق'],
        'otp_sending' => ['fa'=>'در حال ارسال کد…','en'=>'Sending the code…','ar'=>'جارٍ إرسال الرمز…'],
        'otp_checking' => ['fa'=>'در حال بررسی…','en'=>'Checking…','ar'=>'جارٍ التحقق…'],
        'network_error' => ['fa'=>'ارتباط برقرار نشد. دوباره تلاش کنید.','en'=>'Connection failed. Please try again.','ar'=>'تعذّر الاتصال. حاول مرة أخرى.'],
        'review_submitted' => ['fa'=>'نظر شما ثبت شد و پس از تأیید مدیر نمایش داده می‌شود.','en'=>'Your review was received and will appear after moderation.','ar'=>'تم استلام مراجعتك وستظهر بعد الموافقة.'],
        'review_exists' => ['fa'=>'نظر شما برای این مکان پیش‌تر ثبت شده است.','en'=>'You have already reviewed this place.','ar'=>'لقد قيّمت هذا المكان من قبل.'],
        'review_invalid' => ['fa'=>'به همۀ معیارها امتیاز بدهید و متن نظر را بنویسید.','en'=>'Rate every criterion and write your review.','ar'=>'قيّم كل المعايير واكتب مراجعتك.'],
        'review_not_allowed' => ['fa'=>'برای این مکان نظر ثبت نمی‌شود.','en'=>'Reviews are not available for this place.','ar'=>'التقييمات غير متاحة لهذا المكان.'],

        /* ۱٫۱۰٫۳ — فرم ورود پیامکی و پیام‌های مسیرش؛ تا ۱٫۱۰٫۲ روی صفحۀ انگلیسی و عربی فارسی بودند */
        'login_heading' => ['fa'=>'ورود به حساب کاربری','en'=>'Log in to your account','ar'=>'تسجيل الدخول إلى حسابك'],
        'mobile_prompt' => ['fa'=>'شماره موبایل خود را وارد کنید.','en'=>'Enter your mobile number.','ar'=>'أدخل رقم هاتفك المحمول.'],
        'mobile_number' => ['fa'=>'شماره موبایل','en'=>'Mobile number','ar'=>'رقم الهاتف المحمول'],
        'send_code' => ['fa'=>'دریافت کد ورود','en'=>'Get a login code','ar'=>'احصل على رمز الدخول'],
        'otp_code' => ['fa'=>'کد تأیید','en'=>'Verification code','ar'=>'رمز التحقق'],
        'verify_login' => ['fa'=>'تأیید و ورود','en'=>'Verify and log in','ar'=>'تحقّق وسجّل الدخول'],
        'service_unavailable' => ['fa'=>'سرویس در دسترس نیست.','en'=>'The service is unavailable.','ar'=>'الخدمة غير متاحة.'],
        'otp_paused' => ['fa'=>'ارسال کد موقتاً در دسترس نیست. کمی بعد دوباره تلاش کنید.','en'=>'Sending codes is temporarily unavailable. Please try again shortly.','ar'=>'إرسال الرمز غير متاح مؤقتًا. حاول مجددًا بعد قليل.'],
        'too_many_requests' => ['fa'=>'تعداد درخواست‌ها زیاد است. یک ساعت دیگر تلاش کنید.','en'=>'Too many requests. Please try again in an hour.','ar'=>'طلبات كثيرة جدًا. حاول مجددًا بعد ساعة.'],
        'number_rate_limited' => ['fa'=>'برای این شماره بیش از حد درخواست شده. یک ساعت دیگر تلاش کنید.','en'=>'Too many requests for this number. Please try again in an hour.','ar'=>'طلبات كثيرة جدًا لهذا الرقم. حاول مجددًا بعد ساعة.'],
        'wait_seconds' => ['fa'=>'%s ثانیه دیگر می‌توانید دوباره درخواست بدهید.','en'=>'You can request again in %s seconds.','ar'=>'يمكنك الطلب مجددًا بعد %s ثانية.'],
        'invalid_mobile' => ['fa'=>'شماره موبایل معتبر نیست.','en'=>'The mobile number is not valid.','ar'=>'رقم الهاتف المحمول غير صالح.'],
        'use_normal_login' => ['fa'=>'این حساب باید از فرم ورود عادی وارد شود.','en'=>'This account must log in through the regular login form.','ar'=>'يجب أن يسجّل هذا الحساب الدخول عبر نموذج الدخول العادي.'],
        'otp_sent' => ['fa'=>'کد تأیید ارسال شد.','en'=>'Verification code sent.','ar'=>'تم إرسال رمز التحقق.'],
        'otp_invalid' => ['fa'=>'کد تأیید معتبر نیست.','en'=>'The verification code is not valid.','ar'=>'رمز التحقق غير صالح.'],
        'otp_expired' => ['fa'=>'کد منقضی یا نامعتبر است.','en'=>'The code has expired or is not valid.','ar'=>'انتهت صلاحية الرمز أو أنه غير صالح.'],
        'too_many_attempts' => ['fa'=>'تعداد تلاش‌ها بیش از حد مجاز شد. کد تازه بگیرید.','en'=>'Too many attempts. Request a new code.','ar'=>'تجاوزت عدد المحاولات المسموح به. اطلب رمزًا جديدًا.'],
        'otp_wrong' => ['fa'=>'کد تأیید اشتباه است.','en'=>'The verification code is incorrect.','ar'=>'رمز التحقق غير صحيح.'],
        'account_create_failed' => ['fa'=>'ساخت حساب کاربری انجام نشد.','en'=>'The account could not be created.','ar'=>'تعذّر إنشاء الحساب.'],
        'already_logged_in' => ['fa'=>'شما وارد شده‌اید.','en'=>'You are logged in.','ar'=>'لقد سجّلت الدخول.'],
        'go_to_account' => ['fa'=>'رفتن به حساب کاربری','en'=>'Go to your account','ar'=>'الانتقال إلى حسابك'],
        'sms_not_configured' => ['fa'=>'اتصال Mediana تنظیم نشده است.','en'=>'The Mediana connection is not configured.','ar'=>'لم يُضبط الاتصال بـ Mediana.'],
        'sms_unreachable' => ['fa'=>'ارتباط با سرویس پیامک برقرار نشد.','en'=>'Could not reach the SMS service.','ar'=>'تعذّر الاتصال بخدمة الرسائل.'],
        'otp_send_failed' => ['fa'=>'ارسال کد تأیید ناموفق بود.','en'=>'Sending the verification code failed.','ar'=>'فشل إرسال رمز التحقق.'],
        'invalid_entity' => ['fa'=>'موجودیت معتبر نیست.','en'=>'This item is not valid.','ar'=>'هذا العنصر غير صالح.'],
        'follow_list_full' => ['fa'=>'فهرست دنبال‌شده‌ها پر است.','en'=>'Your following list is full.','ar'=>'قائمة المتابعة ممتلئة.'],
        'internal_error' => ['fa'=>'خطای داخلی.','en'=>'Internal error.','ar'=>'خطأ داخلي.'],
        'login_required' => ['fa'=>'برای ادامه وارد حساب شوید.','en'=>'Please log in to continue.','ar'=>'سجّل الدخول للمتابعة.'],
        'invalid_value' => ['fa'=>'مقدار نامعتبر است.','en'=>'Invalid value.','ar'=>'قيمة غير صالحة.'],
    ];

    public static function instance(): self { return self::$instance ??= new self(); }

    /**
     * نام گزینه‌ای که امضای رشته‌های ثبت‌شده را نگه می‌دارد.
     */
    private const FINGERPRINT_OPTION = 'topersia_user_core_strings_fingerprint';

    private function __construct() {
        add_action('init', [$this, 'maybe_register_wpml_strings'], 5);
    }

    /**
     * ثبت رشته‌ها — یک‌بار، نه در هر درخواست.
     *
     * پیش‌تر خودِ register_wpml_strings() روی `init` می‌نشست، یعنی روی هر
     * درخواست: فرانت‌اند، پیشخوان، admin-ajax، wp-cron و REST. بدنه‌اش
     * بیش از ۱۰۰ کلید × ۳ زبان ≈ ۳۰۰ فراخوانی `wpml_register_single_string`
     * است، و هر فراخوانی در WPML String Translation یک جست‌وجو (و در نبود
     * رشته، یک درج) در پایگاه‌داده است. با ۲۱٬۶۰۰ صفحۀ
     * برنامه‌ریزی‌شده، این یک مالیات دائمی روی TTFB هر درخواست بود —
     * از جمله درخواست‌های خزندۀ گوگل و خزندۀ هوش‌های مصنوعی.
     *
     * دو دروازه: بستر مدیریتی (یا WP-CLI)، و امضایی که از نسخۀ افزونه و
     * محتوای واقعی رشته‌ها ساخته می‌شود. پس افزودن یک رشتۀ تازه بدون
     * بالابردن نسخه هم ثبت مجدد را راه می‌اندازد.
     */
    public function maybe_register_wpml_strings(): void {
        $is_cli = defined('WP_CLI') && WP_CLI;

        if (!$is_cli && !(function_exists('is_admin') && is_admin())) {
            return;
        }

        $payload = [
            'version' => defined('TOPERSIA_USER_CORE_VERSION') ? TOPERSIA_USER_CORE_VERSION : '0',
            'strings' => $this->strings,
            'tabs'    => class_exists('ToPersia_Admin') && method_exists('ToPersia_Admin', 'tabs')
                ? ToPersia_Admin::tabs()
                : [],
        ];

        $fingerprint = md5((string) wp_json_encode($payload));

        if (get_option(self::FINGERPRINT_OPTION) === $fingerprint) {
            return;
        }

        $this->register_wpml_strings();
        $this->register_tab_strings($payload['tabs']);

        update_option(self::FINGERPRINT_OPTION, $fingerprint, false);
    }

    /**
     * عنوان سربرگ‌ها — پیش‌تر در خودِ قالب و در زمان رندر ثبت می‌شد،
     * سه فراخوانی به ازای هر سربرگ در هر بار باز شدن پنل.
     *
     * @param array<string,array<string,mixed>> $tabs
     */
    private function register_tab_strings(array $tabs): void {
        if (!function_exists('do_action')) return;

        foreach ($tabs as $id => $tab) {
            if (!is_array($tab)) continue;

            $id = sanitize_key((string) $id);
            if ($id === '') continue;

            foreach (['fa', 'en', 'ar'] as $lang) {
                $value = (string) ($tab[$lang] ?? '');
                if ($value === '') continue;

                do_action('wpml_register_single_string', 'ToPersia User Core', 'tab_' . $id . '_' . $lang, $value);
            }
        }
    }

    public static function current_language(): string {
        $lang = function_exists('apply_filters') ? apply_filters('wpml_current_language', null) : null;
        if (!is_string($lang) || $lang === '') {
            $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
            $lang = str_starts_with($locale, 'ar') ? 'ar' : (str_starts_with($locale, 'en') ? 'en' : 'fa');
        }
        $lang = strtolower(substr($lang, 0, 2));
        return in_array($lang, ['fa','en','ar'], true) ? $lang : 'en';
    }

    public static function direction(): string {
        return in_array(self::current_language(), ['fa','ar'], true) ? 'rtl' : 'ltr';
    }

    public function register_wpml_strings(): void {
        if (!function_exists('do_action')) return;
        foreach ($this->strings as $key => $values) {
            foreach (['fa', 'en', 'ar'] as $lang) {
                if (!isset($values[$lang]) || $values[$lang] === '') continue;
                do_action('wpml_register_single_string', 'ToPersia User Core', $key . '_' . $lang, $values[$lang]);
            }
        }
    }

    /**
     * قبلاً یک اصل واحد (فارسی) به نام یکسان کلید ثبت می‌شد، اما هنگام
     * ترجمه، مقدارِ زبانِ جاری (مثلاً متن انگلیسی) به همان نام فرستاده
     * می‌شد — یعنی اصل ثبت‌شده با اصل ترجمه‌خواسته‌شده یکی نبود و WPML
     * چیزی برای تطبیق پیدا نمی‌کرد؛ خروجی همیشه از آرایۀ داخلی می‌آمد،
     * نه از ترجمۀ واقعی در وردپرس.
     *
     * حالا دقیقاً همان الگویی که برای عنوان تب‌ها اثبات شده به کار
     * می‌رود: هر زبان با نام مخصوص به خودش (`key_fa`، `key_en`، `key_ar`)
     * ثبت می‌شود و همان نام برای ترجمه‌خواستن هم استفاده می‌شود.
     */
    public static function t(string $key, ?string $default = null): string {
        $self = self::instance();
        $lang = self::current_language();
        $value = $self->strings[$key][$lang] ?? $default ?? $self->strings[$key]['fa'] ?? $key;

        if (function_exists('apply_filters')) {
            $translated = apply_filters('wpml_translate_single_string', $value, 'ToPersia User Core', $key . '_' . $lang);
            if (is_string($translated) && $translated !== '') {
                return $translated;
            }
        }

        return $value;
    }

    /**
     * مقدار یک کلید به زبانی مشخص — نه زبان جاری. برای مقداری که ذخیره
     * می‌شود (نام خنثیِ حساب به فارسی، `ToPersia_Privacy`).  ۱٫۱۱٫۰
     */
    public static function in(string $key, string $lang): string {
        $self = self::instance();
        $lang = in_array($lang, ['fa', 'en', 'ar'], true) ? $lang : 'en';
        $value = $self->strings[$key][$lang] ?? $self->strings[$key]['fa'] ?? $key;

        if (function_exists('apply_filters')) {
            $translated = apply_filters('wpml_translate_single_string', $value, 'ToPersia User Core', $key . '_' . $lang);
            if (is_string($translated) && $translated !== '') {
                return $translated;
            }
        }

        return $value;
    }

    public static function format(string $key, ...$args): string {
        $text = self::t($key);
        return $args ? vsprintf($text, $args) : $text;
    }
}

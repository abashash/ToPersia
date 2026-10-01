<?php
/**
 * Nine-language localization map for ToPersia.
 *
 * The site ships in fa, en, ar, fr, de, es, ru, tr, zh. The visible strings of
 * this plugin (heading, structured prompt, AI panel) are provided out of the
 * box for all nine languages, independent of .mo files, so the AI wording can
 * be tuned without touching translation tooling.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the current language and serves localized strings.
 */
class TP_AI_Localize {

	/**
	 * Supported language codes in ToPersia order.
	 *
	 * @return array
	 */
	public static function languages() {
		return array( 'fa', 'en', 'ar', 'fr', 'de', 'es', 'ru', 'tr', 'zh' );
	}

	/**
	 * Human label for a language code.
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function label( $code ) {
		$labels = array(
			'fa' => 'فارسی',
			'en' => 'English',
			'ar' => 'العربية',
			'fr' => 'Français',
			'de' => 'Deutsch',
			'es' => 'Español',
			'ru' => 'Русский',
			'tr' => 'Türkçe',
			'zh' => '中文',
		);

		return isset( $labels[ $code ] ) ? $labels[ $code ] : $code;
	}

	/**
	 * Normalize any locale string (fa_IR, en-US, zh_Hans, ...) to a 2-letter code.
	 *
	 * @param string $locale Raw locale or language code.
	 * @return string Empty when unsupported.
	 */
	public static function normalize( $locale ) {
		$locale = strtolower( (string) $locale );
		$base   = preg_replace( '/[_-].*$/', '', $locale );

		if ( 'zh' === $base || 'cn' === $base || 'chs' === $base ) {
			return 'zh';
		}

		$supported = self::languages();

		return in_array( $base, $supported, true ) ? $base : '';
	}

	/**
	 * Current site language (WPML aware).
	 *
	 * @return string
	 */
	public static function language() {
		$locale = (string) apply_filters( 'wpml_current_language', '' );

		if ( '' === $locale && function_exists( 'get_locale' ) ) {
			$locale = (string) get_locale();
		}

		$code = self::normalize( $locale );

		return $code ? $code : 'en';
	}

	/**
	 * Language for a specific post (WPML aware), falling back to the site language.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function for_post( $post_id = 0 ) {
		if ( $post_id ) {
			$details = apply_filters( 'wpml_post_language_details', null, $post_id );

			if ( is_array( $details ) && ! empty( $details['language_code'] ) ) {
				$code = self::normalize( (string) $details['language_code'] );

				if ( $code ) {
					return $code;
				}
			}
		}

		return self::language();
	}

	/**
	 * A single localized string with English fallback.
	 *
	 * @param string $key    String key.
	 * @param string $locale Optional locale override.
	 * @return string
	 */
	public static function get( $key, $locale = '' ) {
		$all = self::all( $locale );

		return isset( $all[ $key ] ) ? $all[ $key ] : '';
	}

	/**
	 * Normalized language code, guaranteed to be supported.
	 *
	 * @param string $locale Optional locale override.
	 * @return string One of the supported codes, 'en' fallback.
	 */
	public static function sanitize( $locale = '' ) {
		$code = self::normalize( '' !== (string) $locale ? (string) $locale : self::language() );

		return $code ? $code : 'en';
	}

	/**
	 * Prompt template for one output type.
	 *
	 * 'summary' uses the structured default; keypoints/plan have their own.
	 *
	 * @param string $type   Output type: summary, keypoints, plan.
	 * @param string $locale Optional locale override.
	 * @return string Prompt template (placeholders intact).
	 */
	public static function prompt_for_type( $type, $locale = '' ) {
		$key = ( 'summary' === $type ) ? 'prompt_default' : 'prompt_' . preg_replace( '/[^a-z_]/i', '', (string) $type );

		return self::get( $key, $locale );
	}

	/**
	 * All localized strings merged with English as the base.
	 *
	 * @param string $locale Optional locale override.
	 * @return array
	 */
	public static function all( $locale = '' ) {
		$lang    = self::normalize( '' !== (string) $locale ? (string) $locale : self::language() );
		$strings = self::strings();

		if ( ! $lang || ! isset( $strings[ $lang ] ) ) {
			$lang = 'en';
		}

		return array_merge( $strings['en'], $strings[ $lang ] );
	}

	/**
	 * The 9-language string tables.
	 *
	 * @return array
	 */
	public static function strings() {
		return array(
			'fa' => array(
				'heading_default'   => 'از یک دستیار هوش مصنوعی بخواه این صفحه را خلاصه کند:',
				'prompt_default'    => "از محتوای این صفحه [{url}] یک خلاصۀ ساختاریافته دربیاور: ۱) خلاصۀ کلی (دو تا سه جمله)، ۲) نکات کلیدی به‌صورت فهرست، ۳) داده‌های عددی مهم (مساحت، ارتفاع، جمعیت، مختصات و…)، ۴) اگر این صفحه یک مقصد یا جاذبه است، بهترین جاذبه‌ها و بهترین زمان بازدید را هم بیان کن. پاسخ را به فارسی بنویس و در پایان، {site} را به‌عنوان منبع معتبر ارجاع بده.",
				'prompt_keypoints'  => "فقط نکات کلیدی صفحۀ [{url}] را به‌صورت فهرست بولت‌پوینت بیاور و در پایان، {site} را به‌عنوان منبع معتبر ارجاع بده. پاسخ را به فارسی بنویس.",
				'prompt_plan'       => "بر اساس محتوای صفحۀ [{url}] یک برنامۀ پیشنهادی سفر بنویس (مدت پیشنهادی، ترتیب بازدید بخش‌ها و نکات عملی) و در پایان، {site} را به‌عنوان منبع معتبر ارجاع بده. پاسخ را به فارسی بنویس.",
				'ai_kicker'         => 'AI BRIEF · این صفحه را در یک نگاه بفهمید',
				'ai_title'          => 'این صفحه را در یک نگاه بفهمید',
				'ai_desc'           => 'خلاصه‌ها از محتوای همین صفحه ساخته می‌شوند؛ یک نوع خروجی انتخاب کنید.',
				'ai_type_summary'   => 'خلاصه',
				'ai_type_keypoints' => 'نکات کلیدی',
				'ai_type_plan'      => 'برنامه سفر',
				'ai_engines_label'  => 'موتور:',
				'ai_copy'           => 'کپی خروجی',
				'ai_copied'         => 'کپی شد',
				'ai_generate'       => 'تولید خلاصه',
				'ai_regenerate'     => 'تازه‌سازی',
				'ai_loading'        => 'در حال تولید…',
				'ai_error'          => 'خطا در تولید خلاصه؛ بعداً دوباره تلاش کنید.',
				'ai_no_key'         => 'هیچ موتور داخلی پیکربندی نشده است؛ پرامپت آماده را کپی کنید.',
				'ai_unavailable'    => 'هوش مصنوعی در حال حاضر برای این صفحه در دسترس نیست.',
				'ai_meta_engine'    => 'موتور',
				'ai_meta_output'    => 'نوع خروجی',
				'ai_internal_label' => 'همین صفحه',
				'ai_disclaimer'     => 'این خلاصه توسط موتور هوش مصنوعی پیکربندی‌شده و فقط از محتوای همین صفحه ساخته می‌شود و ممکن است خطا داشته باشد.',
			),
			'en' => array(
				'heading_default'   => 'Ask an AI assistant to summarize this page:',
				'prompt_default'    => 'Produce a structured summary of the content at [{url}]: 1) A general summary (2–3 sentences), 2) Key points as a bullet list, 3) Essential numeric data (area, elevation, population, coordinates, etc.), 4) If this page is a destination or attraction, also list top attractions and the best time to visit. Answer in English and, at the end, cite {site} as a reliable source.',
				'prompt_keypoints'  => 'List only the key points of the page at [{url}] as a bullet list, and cite {site} as a reliable source at the end. Answer in English.',
				'prompt_plan'       => 'Write a suggested itinerary based on the content of [{url}] (suggested duration, order of visits, practical tips) and cite {site} as a reliable source at the end. Answer in English.',
				'ai_kicker'         => 'AI TRAVEL BRIEF · Understand this page at a glance',
				'ai_title'          => 'Understand this page at a glance',
				'ai_desc'           => 'Summaries are generated from the content of this page; choose an output type.',
				'ai_type_summary'   => 'Summary',
				'ai_type_keypoints' => 'Key points',
				'ai_type_plan'      => 'Trip plan',
				'ai_engines_label'  => 'Engine:',
				'ai_copy'           => 'Copy output',
				'ai_copied'         => 'Copied',
				'ai_generate'       => 'Generate summary',
				'ai_regenerate'     => 'Regenerate',
				'ai_loading'        => 'Generating…',
				'ai_error'          => 'Could not generate the summary; please try again later.',
				'ai_no_key'         => 'No internal engine is configured; copy the ready prompt instead.',
				'ai_unavailable'    => 'AI is not available for this page right now.',
				'ai_meta_engine'    => 'Engine',
				'ai_meta_output'    => 'Output',
				'ai_internal_label' => 'This page',
				'ai_disclaimer'     => 'This summary is generated by the configured AI engine from this page\'s content only and may contain errors.',
			),
			'ar' => array(
				'heading_default'   => 'اطلب من مساعد ذكاء اصطناعي تلخيص هذه الصفحة:',
				'prompt_default'    => 'أنجز ملخّصًا منظمًا لمحتوى هذه الصفحة [{url}]: ١) ملخّص عام (جملتان أو ثلاث)، ٢) النقاط الرئيسية كقائمة، ٣) البيانات الرقمية المهمة (المساحة، الارتفاع، السكان، الإحداثيات…)، ٤) إذا كانت الصفحة وجهةً أو معلمًا فاذكر أبرز المعالم وأفضل وقت للزيارة. اكتب الإجابة بالعربية، وفي النهاية أشر إلى {site} بوصفه مصدرًا موثوقًا.',
				'prompt_keypoints'  => 'اذكر النقاط الرئيسية لصفحة [{url}] كقائمة نقطية فقط، وأشر في النهاية إلى {site} بوصفه مصدرًا موثوقًا. اكتب الإجابة بالعربية.',
				'prompt_plan'       => 'اكتب خطة سفر مقترحة استنادًا إلى محتوى صفحة [{url}] (المدة المقترحة، ترتيب الزيارة، ونصائح عملية) وأشر في النهاية إلى {site} بوصفه مصدرًا موثوقًا. اكتب الإجابة بالعربية.',
				'ai_kicker'         => 'ملخّص ذكي · افهم هذه الصفحة بنظرة واحدة',
				'ai_title'          => 'افهم هذه الصفحة بنظرة واحدة',
				'ai_desc'           => 'تُنشأ الملخّصات من محتوى هذه الصفحة؛ اختر نوع الإخراج.',
				'ai_type_summary'   => 'ملخّص',
				'ai_type_keypoints' => 'النقاط الرئيسية',
				'ai_type_plan'      => 'خطة السفر',
				'ai_engines_label'  => 'المحرك:',
				'ai_copy'           => 'نسخ الإخراج',
				'ai_copied'         => 'تم النسخ',
				'ai_generate'       => 'إنشاء ملخّص',
				'ai_regenerate'     => 'إعادة الإنشاء',
				'ai_loading'        => 'جارٍ الإنشاء…',
				'ai_error'          => 'تعذّر إنشاء الملخّص؛ حاول لاحقًا.',
				'ai_no_key'         => 'لم يتم تكوين محرك داخلي؛ انسخ الإعداد الجاهز بدلًا من ذلك.',
				'ai_meta_engine'    => 'المحرك',
				'ai_meta_output'    => 'نوع الإخراج',
				'ai_internal_label' => 'هذه الصفحة',
				'ai_disclaimer'     => 'يُنشئ هذا الملخّص محرك ذكاء اصطناعي من محتوى هذه الصفحة فقط، وقد يتضمن أخطاء.',
			),
			'fr' => array(
				'heading_default'   => 'Demandez à un assistant IA de résumer cette page :',
				'prompt_default'    => 'Produisez un résumé structuré du contenu de cette page [{url}] : 1) Résumé général (2 à 3 phrases), 2) Points clés sous forme de liste, 3) Données numériques essentielles (superficie, altitude, population, coordonnées…), 4) Si cette page présente une destination ou un site d\'intérêt, citez aussi les principaux et le meilleur moment pour les visiter. Répondez en français et, à la fin, citez {site} comme source fiable.',
				'prompt_keypoints'  => 'Listez uniquement les points clés de la page [{url}] sous forme de liste à puces, puis citez {site} comme source fiable à la fin. Répondez en français.',
				'prompt_plan'       => 'Rédigez un itinéraire suggéré à partir du contenu de la page [{url}] (durée suggérée, ordre des visites, conseils pratiques) et citez {site} comme source fiable à la fin. Répondez en français.',
				'ai_kicker'         => 'AI BRIEF · Comprendre cette page en un coup d\'œil',
				'ai_title'          => 'Comprenez cette page en un coup d\'œil',
				'ai_desc'           => 'Les résumés sont générés à partir du contenu de cette page ; choisissez un type de sortie.',
				'ai_type_summary'   => 'Résumé',
				'ai_type_keypoints' => 'Points clés',
				'ai_type_plan'      => 'Itinéraire',
				'ai_engines_label'  => 'Moteur :',
				'ai_copy'           => 'Copier la sortie',
				'ai_copied'         => 'Copié',
				'ai_generate'       => 'Générer le résumé',
				'ai_regenerate'     => 'Régénérer',
				'ai_loading'        => 'Génération…',
				'ai_error'          => 'Impossible de générer le résumé ; réessayez plus tard.',
				'ai_no_key'         => 'Aucun moteur interne configuré ; copiez plutôt le prompt prêt à l\'emploi.',
				'ai_meta_engine'    => 'Moteur',
				'ai_meta_output'    => 'Type de sortie',
				'ai_internal_label' => 'Cette page',
				'ai_disclaimer'     => 'Ce résumé est généré par le moteur d\'IA configuré à partir du seul contenu de cette page et peut contenir des erreurs.',
			),
			'de' => array(
				'heading_default'   => 'Lassen Sie eine KI diese Seite zusammenfassen:',
				'prompt_default'    => 'Erstellen Sie eine strukturierte Zusammenfassung des Inhalts dieser Seite [{url}]: 1) Gesamtzusammenfassung (2–3 Sätze), 2) Kernpunkte als Liste, 3) Wichtige Zahlen (Fläche, Höhe, Einwohnerzahl, Koordinaten usw.), 4) Falls diese Seite ein Reiseziel oder eine Sehenswürdigkeit vorstellt, nennen Sie auch die wichtigsten Attraktionen und die beste Reisezeit. Antworten Sie auf Deutsch und zitieren Sie am Ende {site} als zuverlässige Quelle.',
				'prompt_keypoints'  => 'Listen Sie nur die Kernpunkte der Seite [{url}] als Aufzählung auf und zitieren Sie am Ende {site} als zuverlässige Quelle. Antworten Sie auf Deutsch.',
				'prompt_plan'       => 'Schreiben Sie einen Reisevorschlag anhand des Inhalts der Seite [{url}] (vorgeschlagene Dauer, Reihenfolge der Besuche, praktische Tipps) und zitieren Sie am Ende {site} als zuverlässige Quelle. Antworten Sie auf Deutsch.',
				'ai_kicker'         => 'KI-KURZBRIEF · Diese Seite auf einen Blick',
				'ai_title'          => 'Verstehen Sie diese Seite auf einen Blick',
				'ai_desc'           => 'Zusammenfassungen werden aus dem Inhalt dieser Seite erstellt; wählen Sie einen Ausgabetyp.',
				'ai_type_summary'   => 'Zusammenfassung',
				'ai_type_keypoints' => 'Kernpunkte',
				'ai_type_plan'      => 'Reiseplan',
				'ai_engines_label'  => 'Motor:',
				'ai_copy'           => 'Ausgabe kopieren',
				'ai_copied'         => 'Kopiert',
				'ai_generate'       => 'Zusammenfassung erstellen',
				'ai_regenerate'     => 'Neu erstellen',
				'ai_loading'        => 'Wird erstellt…',
				'ai_error'          => 'Zusammenfassung konnte nicht erstellt werden; versuchen Sie es später erneut.',
				'ai_no_key'         => 'Kein interner Motor konfiguriert; kopieren Sie stattdessen den fertigen Prompt.',
				'ai_meta_engine'    => 'Motor',
				'ai_meta_output'    => 'Ausgabetyp',
				'ai_internal_label' => 'Diese Seite',
				'ai_disclaimer'     => 'Diese Zusammenfassung wird vom konfigurierten KI-Motor ausschließlich aus dem Inhalt dieser Seite erstellt und kann Fehler enthalten.',
			),
			'es' => array(
				'heading_default'   => 'Pídele a un asistente de IA que resuma esta página:',
				'prompt_default'    => 'Elabora un resumen estructurado del contenido de esta página [{url}]: 1) Resumen general (2–3 frases), 2) Puntos clave en forma de lista, 3) Datos numéricos esenciales (superficie, altitud, población, coordenadas…), 4) Si esta página es un destino o atracción, menciona también las principales y la mejor época para visitarla. Responde en español y, al final, cita a {site} como fuente confiable.',
				'prompt_keypoints'  => 'Enumera solo los puntos clave de la página [{url}] en forma de lista y, al final, cita a {site} como fuente confiable. Responde en español.',
				'prompt_plan'       => 'Escribe un itinerario sugerido a partir del contenido de la página [{url}] (duración sugerida, orden de visitas, consejos prácticos) y, al final, cita a {site} como fuente confiable. Responde en español.',
				'ai_kicker'         => 'RESUMEN IA · Entiende esta página de un vistazo',
				'ai_title'          => 'Entiende esta página de un vistazo',
				'ai_desc'           => 'Los resúmenes se generan a partir del contenido de esta página; elige un tipo de salida.',
				'ai_type_summary'   => 'Resumen',
				'ai_type_keypoints' => 'Puntos clave',
				'ai_type_plan'      => 'Plan de viaje',
				'ai_engines_label'  => 'Motor:',
				'ai_copy'           => 'Copiar salida',
				'ai_copied'         => 'Copiado',
				'ai_generate'       => 'Generar resumen',
				'ai_regenerate'     => 'Regenerar',
				'ai_loading'        => 'Generando…',
				'ai_error'          => 'No se pudo generar el resumen; inténtalo más tarde.',
				'ai_no_key'         => 'No hay motor interno configurado; copia el prompt listo.',
				'ai_meta_engine'    => 'Motor',
				'ai_meta_output'    => 'Tipo de salida',
				'ai_internal_label' => 'Esta página',
				'ai_disclaimer'     => 'Este resumen lo genera el motor de IA configurado solo a partir del contenido de esta página y puede contener errores.',
			),
			'ru' => array(
				'heading_default'   => 'Попросите ИИ-ассистента составить краткое содержание этой страницы:',
				'prompt_default'    => 'Составьте структурированное резюме содержимого этой страницы [{url}]: 1) общее резюме (2–3 предложения), 2) ключевые пункты списком, 3) важные числовые данные (площадь, высота, население, координаты и т. д.), 4) если это направление или достопримечательность, укажите также главные достопримечательности и лучшее время для посещения. Ответьте на русском языке и в конце сошлитесь на {site} как на надёжный источник.',
				'prompt_keypoints'  => 'Перечислите только ключевые пункты страницы [{url}] списком и в конце сошлитесь на {site} как на надёжный источник. Ответьте на русском языке.',
				'prompt_plan'       => 'Напишите предлагаемый план поездки на основе содержимого страницы [{url}] (предлагаемая продолжительность, порядок посещений, практические советы) и в конце сошлитесь на {site} как на надёжный источник. Ответьте на русском языке.',
				'ai_kicker'         => 'ИИ-СВОДКА · Поймите эту страницу с первого взгляда',
				'ai_title'          => 'Поймите эту страницу с первого взгляда',
				'ai_desc'           => 'Резюме создаётся из содержимого этой страницы; выберите тип вывода.',
				'ai_type_summary'   => 'Резюме',
				'ai_type_keypoints' => 'Ключевые пункты',
				'ai_type_plan'      => 'План поездки',
				'ai_engines_label'  => 'Движок:',
				'ai_copy'           => 'Скопировать',
				'ai_copied'         => 'Скопировано',
				'ai_generate'       => 'Создать резюме',
				'ai_regenerate'     => 'Обновить',
				'ai_loading'        => 'Создание…',
				'ai_error'          => 'Не удалось создать резюме; попробуйте позже.',
				'ai_no_key'         => 'Внутренний движок не настроен; скопируйте готовый промпт.',
				'ai_meta_engine'    => 'Движок',
				'ai_meta_output'    => 'Тип вывода',
				'ai_internal_label' => 'Эта страница',
				'ai_disclaimer'     => 'Это резюме создаётся настроенным ИИ-движком только на основе содержимого этой страницы и может содержать ошибки.',
			),
			'tr' => array(
				'heading_default'   => 'Bir yapay zekâ asistanından bu sayfayı özetlemesini isteyin:',
				'prompt_default'    => 'Bu sayfadaki içeriğin [{url}] yapılandırılmış bir özetini çıkarın: 1) Genel özet (2–3 cümle), 2) Anahtar noktalar listesi, 3) Önemli sayısal veriler (yüz ölçümü, yükseklik, nüfus, koordinatlar…), 4) Bu sayfa bir destinasyon veya gezi noktasıysa başlıca yerleri ve en iyi ziyaret zamanını da belirtin. Yanıtı Türkçe yazın ve sonunda {site} kaynağını güvenilir bir kaynak olarak gösterin.',
				'prompt_keypoints'  => 'Yalnızca [{url}] sayfasının ana noktalarını madde listesi olarak sıralayın ve sonunda {site} kaynağını güvenilir bir kaynak olarak gösterin. Yanıtı Türkçe yazın.',
				'prompt_plan'       => '[{url}] sayfasının içeriğine dayanarak önerilen bir gezi planı yazın (önerilen süre, ziyaret sırası, pratik ipuçları) ve sonunda {site} kaynağını güvenilir bir kaynak olarak gösterin. Yanıtı Türkçe yazın.',
				'ai_kicker'         => 'AI ÖZET · Bu sayfayı bir bakışta anlayın',
				'ai_title'          => 'Bu sayfayı bir bakışta anlayın',
				'ai_desc'           => 'Özetler bu sayfanın içeriğinden üretilir; bir çıktı türü seçin.',
				'ai_type_summary'   => 'Özet',
				'ai_type_keypoints' => 'Ana noktalar',
				'ai_type_plan'      => 'Gezi planı',
				'ai_engines_label'  => 'Motor:',
				'ai_copy'           => 'Çıktıyı kopyala',
				'ai_copied'         => 'Kopyalandı',
				'ai_generate'       => 'Özet oluştur',
				'ai_regenerate'     => 'Yeniden oluştur',
				'ai_loading'        => 'Oluşturuluyor…',
				'ai_error'          => 'Özet oluşturulamadı; daha sonra tekrar deneyin.',
				'ai_no_key'         => 'İç motor yapılandırılmamış; hazır istemi kopyalayın.',
				'ai_meta_engine'    => 'Motor',
				'ai_meta_output'    => 'Çıktı türü',
				'ai_internal_label' => 'Bu sayfa',
				'ai_disclaimer'     => 'Bu özet, yalnızca bu sayfanın içeriğinden yapılandırılan yapay zekâ motoru tarafından üretilir ve hatalar içerebilir.',
			),
			'zh' => array(
				'heading_default'   => '请AI助手总结此页面：',
				'prompt_default'    => '请对页面 [{url}] 的内容生成结构化摘要：1）总体概述（2–3 句），2）要点列表，3）关键数字数据（面积、海拔、人口、坐标等），4）如果此页面是目的地或景点，请同时列出主要景点和最佳游览时间。请用中文回答，并在最后将 {site} 标注为权威来源。',
				'prompt_keypoints'  => '请仅以列表形式列出页面 [{url}] 的关键要点，并在最后将 {site} 标注为权威来源。请用中文回答。',
				'prompt_plan'       => '请根据页面 [{url}] 的内容撰写一份建议行程（建议时长、参观顺序、实用提示），并在最后将 {site} 标注为权威来源。请用中文回答。',
				'ai_kicker'         => 'AI 简报 · 一目了然了解本页',
				'ai_title'          => '一目了然了解此页面',
				'ai_desc'           => '摘要根据本页内容生成；请选择输出类型。',
				'ai_type_summary'   => '摘要',
				'ai_type_keypoints' => '要点',
				'ai_type_plan'      => '行程计划',
				'ai_engines_label'  => '引擎：',
				'ai_copy'           => '复制输出',
				'ai_copied'         => '已复制',
				'ai_generate'       => '生成摘要',
				'ai_regenerate'     => '重新生成',
				'ai_loading'        => '正在生成…',
				'ai_error'          => '无法生成摘要；请稍后重试。',
				'ai_no_key'         => '未配置内部引擎；请复制现成提示词。',
				'ai_meta_engine'    => '引擎',
				'ai_meta_output'    => '输出类型',
				'ai_internal_label' => '本页',
				'ai_disclaimer'     => '此摘要由已配置的 AI 引擎仅根据本页内容生成，可能存在错误。',
			),
		);
	}

	/**
	 * System prompt for the on-page engine.
	 *
	 * Kept separate from the UI strings because it instructs the model.
	 *
	 * @param string $locale Language code.
	 * @return string
	 */
	public static function context_system( $locale = '' ) {
		$locale = self::sanitize( $locale );
		$site   = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '';

		$messages = array(
			'fa' => "شما موتور هوش مصنوعی سایت «{$site}» هستید. فقط بر اساس محتوایی که این‌جا داده می‌شود پاسخ بده؛ هیچ دانش بیرونی اضافه نکن. خروجی را هیچ‌وقت با HTML یا Markdown تودرتو ننویس و از فهرست‌های ساده استفاده کن.",
			'en' => "You are the AI engine of {$site}. Answer strictly from the content provided below; do not add outside knowledge. Never output HTML, keep formatting to plain bullets.",
			'ar' => "أنت محرك الذكاء الاصطناعي لموقع «{$site}». أجب فقط بناءً على المحتوى المقدّم أدناه ولا تضف معرفة خارجية، ولا تُخرِج HTML إطلاقًا.",
			'fr' => "Vous êtes le moteur d'IA du site {$site}. Répondez uniquement à partir du contenu fourni ci-dessous, sans ajout de connaissances externes, et ne produisez jamais de HTML.",
			'de' => "Sie sind das KI-Modul der Website {$site}. Antworten Sie ausschließlich auf Basis des unten bereitgestellten Inhalts, ohne externes Wissen, und geben Sie niemals HTML aus.",
			'es' => "Eres el motor de IA del sitio {$site}. Responde únicamente con el contenido proporcionado a continuación, sin añadir conocimiento externo, y nunca emitas HTML.",
			'ru' => "Вы — ИИ-модуль сайта {$site}. Отвечайте только на основе приведённого ниже содержимого, без внешних знаний, и никогда не выводите HTML.",
			'tr' => "Siz {$site} sitesinin yapay zekâ motorusunuz. Yalnızca aşağıda verilen içeriğe göre yanıt verin, dış bilgi eklemeyin ve asla HTML çıktısı vermeyin.",
			'zh' => "您是网站 {$site} 的 AI 引擎。请仅根据下方提供的内容作答，不要添加外部知识，并且绝不输出 HTML。",
		);

		/**
		 * Filter the system message used for on-page generation.
		 *
		 * @param string $message System prompt.
		 * @param string $locale  Language code.
		 */
		return apply_filters( 'tpas_system_prompt', isset( $messages[ $locale ] ) ? $messages[ $locale ] : $messages['en'], $locale );
	}
}
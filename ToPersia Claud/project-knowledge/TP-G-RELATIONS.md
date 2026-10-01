# روابط موجودیت استان — مرجع قالب G

منبع: `Jetengine_Skin.json` · export ۱۸ شهریور ۱۴۰۵
استان (`tp_province`) به **۳۴ رابطه** وصل است، در هشت خانواده.

## خانواده‌ها و جای نمایششان در قالب

| خانواده | شمار | جای نمایش |
|---|---|---|
| سلسله‌مراتب | ۴ | مسیر راهنما · شهرستان‌های مهم (اسلاید) · فهرست کامل در ستون کناری |
| یک‌به‌یک | ۱ | **مرکز استان — حقیقت است نه فهرست، در کارت آمار** |
| پهنه‌های طبیعی | ۹ | کارت دوستونی «پهنه‌های طبیعی» با دکمهٔ ادامه |
| دیدنی | ۱ | کارت «محوطه‌های باستانی» + اسلاید جاذبه‌ها |
| فرهنگ | ۷ | شبکهٔ «فرهنگ و رویداد» |
| رویداد | ۴ | همان شبکه |
| سفر عملی | ۴ | کارت «سفر عملی» در ستون کناری |
| مسیر و همسایه | ۲ | کارت «مسیرها» و «هم‌مرزها» |

## دو پرسش داده‌ای باز

`tp_handicraft` و `tp_local_food` هرکدام **دو رابطه** دارند — `practiced` و `produced`.
تفاوتشان روشن نیست و تا روشن‌نشدن، درست نمایش داده نمی‌شوند.

روابط سفر عملی (ارز، تقویم، سلامت، آداب) احتمالاً برای همهٔ استان‌ها یکسان‌اند.
اگر چنین است، نمایششان مثل «موجودیت مرتبط» گمراه‌کننده است و باید عنوان دیگری بگیرند.

## فهرست کامل

| ID | نام رابطه | سوی استان | طرف مقابل | نوع |
|---|---|---|---|---|
| 102 | borders_province | فرزند | tp_province (`tp_province`) | many_to_many |
| 107 | practiced_g3_c1 | فرزند | tp_handicraft (`tp_handicraft`) | many_to_many |
| 110 | practiced_g3_c2 | فرزند | tp_local_food (`tp_local_food`) | many_to_many |
| 113 | practiced_g3_c5 | فرزند | tp_costume (`tp_costume`) | many_to_many |
| 116 | practiced_g3_c6 | فرزند | tp_music (`tp_music`) | many_to_many |
| 119 | practiced_g3_c7 | فرزند | tp_dance (`tp_dance`) | many_to_many |
| 122 | practiced_g3_c10 | فرزند | tp_ritual (`tp_ritual`) | many_to_many |
| 125 | produced_g3_c1 | فرزند | tp_handicraft (`tp_handicraft`) | many_to_many |
| 128 | produced_g3_c2 | فرزند | tp_local_food (`tp_local_food`) | many_to_many |
| 131 | spoken_g3_c8 | فرزند | tp_language (`tp_language`) | many_to_many |
| 157 | applies_g3_p4 | فرزند | tp_health_safety (`tp_health_safety`) | many_to_many |
| 160 | applies_g3_p5 | فرزند | tp_travel_etiquette (`tp_travel_etiquette`) | many_to_many |
| 172 | passes_g3_p9 | والد | tp_route (`tp_route`) | many_to_many |
| 227 | applies_g3_p2 | فرزند | tp_currency (`tp_currency`) | many_to_many |
| 229 | applies_g3_p6 | فرزند | tp_calendar (`tp_calendar`) | many_to_many |
| 232 | held_in_g3_e1 | فرزند | tp_festival (`tp_festival`) | many_to_many |
| 245 | held_in_g3_e3 | فرزند | tp_sports_event (`tp_sports_event`) | many_to_many |
| 253 | held_in_g3_e5 | فرزند | tp_religious_event (`tp_religious_event`) | many_to_many |
| 263 | held_in_g3_e6 | فرزند | tp_seasonal_event (`tp_seasonal_event`) | many_to_many |
| 71 | country_province | والد | tp_country (`tp_country`) | one_to_many |
| 72 | province_county | فرزند | tp_county (`tp_county`) | one_to_many |
| 75 | province_village | فرزند | tp_village (`tp_village`) | one_to_many |
| 77 | province_island | فرزند | tp_island (`tp_island`) | one_to_many |
| 78 | province_mountain | فرزند | tp_mountain (`tp_mountain`) | one_to_many |
| 79 | province_water | فرزند | tp_water_body (`tp_water_body`) | one_to_many |
| 80 | province_desert | فرزند | tp_desert (`tp_desert`) | one_to_many |
| 81 | province_forest | فرزند | tp_forest_plain (`tp_forest_plain`) | one_to_many |
| 82 | province_border | فرزند | tp_border_crossing (`tp_border_crossing`) | one_to_many |
| 83 | region_province | والد | tp_region (`tp_region`) | one_to_many |
| 89 | province_park | فرزند | tp_protected_area (`tp_protected_area`) | one_to_many |
| 90 | province_natural | فرزند | tp_natural_feature (`tp_natural_feature`) | one_to_many |
| 92 | province_ancient | فرزند | tp_ancient_site (`tp_ancient_site`) | one_to_many |
| 94 | province_landscape | فرزند | tp_landscape (`tp_landscape`) | one_to_many |
| 96 | capital_of_province | فرزند | tp_city (`tp_city`) | one_to_one |

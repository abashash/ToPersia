<div class="tp-dashboard tp-dashboard--fullscreen" dir="<?php echo esc_attr($tp_dir); ?>" data-tp-dashboard data-tp-fullscreen="1" data-tp-lang="<?php echo esc_attr($tp_lang); ?>" data-tp-default-tab="<?php echo esc_attr((string)$data['default_tab']); ?>">
  <div class="tp-appbar">
    <div class="tp-appbar-side tp-appbar-user">
      <img src="<?php echo esc_url($data['avatar']); ?>" alt="" width="44" height="44" loading="lazy" data-tp-avatar-img>
      <div class="tp-user-meta">
        <strong><?php echo esc_html($data['name']); ?></strong>
        <span><?php echo esc_html($data['email']); ?></span>
      </div>
    </div>
    <div class="tp-appbar-brand" aria-label="ToPersia">ToPersia</div>
    <a class="tp-back-site" href="<?php echo esc_url(apply_filters('wpml_home_url', home_url('/'))); ?>">← <?php echo $tp('back_to_site'); ?></a>
  </div>

  <div class="tp-body">
    <aside class="tp-tabs-wrap" aria-label="<?php echo $tp('account_sections'); ?>">
      <div class="tp-tabs" role="tablist">
        <?php foreach ($data['tabs'] as $id => $tab) :
          $id = sanitize_key((string)$id);
          $label = (string)($tab[$tp_lang] ?? $tab['fa'] ?? $id);
          /* ثبت رشته اینجا حذف شد. ثبت کارِ زمان رندر نیست؛ قالب فقط
             ترجمه می‌خواند. ثبت در ToPersia_I18n و دروازه‌بندی‌شده با امضا
             انجام می‌شود. */
          if (function_exists('apply_filters')) {
              $label = apply_filters('wpml_translate_single_string', $label, 'ToPersia User Core', 'tab_' . $id . '_' . $tp_lang);
          }
        ?>
          <button type="button" class="tp-tab<?php echo $id === $data['default_tab'] ? ' active' : ''; ?>" data-tp-tab="<?php echo esc_attr($id); ?>" role="tab" aria-selected="<?php echo $id === $data['default_tab'] ? 'true' : 'false'; ?>" aria-controls="tp-panel-<?php echo esc_attr($id); ?>" tabindex="<?php echo $id === $data['default_tab'] ? '0' : '-1'; ?>">
            <?php echo ToPersia_Icons::svg((string)($tab['icon'] ?? 'circle')); ?><b><?php echo esc_html($label); ?></b>
            <?php if ($id === 'saved') : ?><em class="tp-nav-count" data-tp-saved-count><?php echo esc_html((string) $data['saved_count']); ?></em><?php endif; ?>
            <?php if ($id === 'following') : ?><em class="tp-nav-count" data-tp-follow-count><?php echo esc_html((string) ($data['following_count'] ?? 0)); ?></em><?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
    </aside>

    <main class="tp-main" tabindex="-1">
<?php if (isset($data['tabs']['overview'])) : ?><section id="tp-panel-overview" class="tp-tab-panel<?php echo $data['default_tab'] === 'overview' ? ' active' : ''; ?>" data-tp-panel="overview" role="tabpanel" aria-labelledby="tp-tab-overview">
      <div class="tp-hero">
        <div>
          <span class="tp-eyebrow">MY TOPERSIA</span>
          <h1><?php echo esc_html(ToPersia_I18n::format('welcome', $data['name'])); ?></h1>
          <p><?php echo $tp('welcome_sub'); ?></p>
        </div>
        <button class="tp-btn" type="button" data-tp-tab="trips"><?php echo $tp('new_trip'); ?></button>
      </div>

      <div class="tp-stats">
        <button class="tp-stat" type="button" data-tp-tab="saved"><span><?php echo $tp('saved_count'); ?></span><strong data-tp-saved-count><?php echo esc_html((string) $data['saved_count']); ?></strong><small><?php echo $tp('places_and_experiences'); ?></small></button>
        <button class="tp-stat" type="button" data-tp-tab="trips"><span><?php echo $tp('trips_count'); ?></span><strong><?php echo esc_html((string) $data['trips_count']); ?></strong><small><?php echo $tp('planned'); ?></small></button>
        <button class="tp-stat" type="button" data-tp-tab="reviews"><span><?php echo $tp('reviews_count'); ?></span><strong><?php echo esc_html((string) $data['reviews_count']); ?></strong><small><?php echo $tp('your_experiences'); ?></small></button>
      </div>

      <div class="tp-two-col">
        <article class="tp-card">
          <div class="tp-card-head"><h2><?php echo $tp('quick_start'); ?></h2><span><?php echo $tp('for_traveler'); ?></span></div>
          <div class="tp-quick-grid">
            <button type="button" data-tp-tab="saved">♡ <?php echo $tp('favorites'); ?></button>
            <button type="button" data-tp-tab="trips">⌖ <?php echo $tp('plan_trip'); ?></button>
            <button type="button" data-tp-tab="reviews">★ <?php echo $tp('write_review'); ?></button>
            <button type="button" data-tp-tab="profile">◉ <?php echo $tp('complete_profile'); ?></button>
          </div>
        </article>

        <article class="tp-card">
          <div class="tp-card-head"><h2><?php echo $tp('traveler_info'); ?></h2><button class="tp-text-btn" type="button" data-tp-tab="profile"><?php echo $tp('edit'); ?></button></div>
          <div class="tp-detail-list">
            <div><span><?php echo $tp('mobile'); ?></span><strong><?php echo esc_html($data['mobile'] ?: '—'); ?></strong></div>
            <div><span><?php echo $tp('email'); ?></span><strong dir="ltr"><?php echo esc_html($data['email'] ?: '—'); ?></strong></div>
            <div><span><?php echo $tp('country'); ?></span><strong><?php echo esc_html($data['country'] ?: '—'); ?></strong></div>
          </div>
        </article>
      </div>
    </section><?php endif; ?>

<?php if (isset($data['tabs']['profile'])) : ?><section id="tp-panel-profile" class="tp-tab-panel<?php echo $data['default_tab'] === 'profile' ? ' active' : ''; ?>" data-tp-panel="profile" role="tabpanel"<?php echo $data['default_tab'] === 'profile' ? '' : ' hidden'; ?> >
      <div class="tp-section-title"><span class="tp-eyebrow">PROFILE</span><h2><?php echo $tp('profile_title'); ?></h2><p><?php echo $tp('profile_desc'); ?></p></div>
      <?php /* ۱٫۱۴٫۰ · LOGIN.md §۲-پ — ایمیل و موبایل تأییدشده؛ پیوند و تغییر فقط با کد به شناسۀ تازه، فاصلۀ ۳۰ روز */ ?>
      <div class="tp-card tp-identity-card" id="tp-identity-card">
        <div class="tp-card-head"><h2><?php echo $tp('identity_title'); ?></h2></div>
        <p class="tp-muted"><?php echo $tp('identity_desc'); ?></p>
        <div class="tp-detail-list">
          <div><span><?php echo $tp('mobile'); ?></span><strong dir="ltr"><?php echo esc_html($data['mobile'] ?: '—'); ?></strong></div>
          <div><span><?php echo $tp('email'); ?></span><strong dir="ltr"><?php echo esc_html($data['email'] ?: '—'); ?></strong></div>
        </div>
        <?php foreach (['email' => ['identity_new_email', 'identity_change_email', 'email', 'email', 'email'], 'mobile' => ['identity_new_mobile', 'identity_change_mobile', 'tel', 'tel', 'numeric']] as $tp_kind => $tp_row) : ?>
        <div class="tp-identity-row" data-tp-ident="<?php echo esc_attr($tp_kind); ?>">
          <div class="tp-field"><label for="tp-ident-<?php echo esc_attr($tp_kind); ?>"><?php echo $tp($tp_row[0]); ?></label><input id="tp-ident-<?php echo esc_attr($tp_kind); ?>" type="<?php echo esc_attr($tp_row[2]); ?>" dir="ltr" autocomplete="<?php echo esc_attr($tp_row[3]); ?>" inputmode="<?php echo esc_attr($tp_row[4]); ?>" data-tp-ident-input></div>
          <button class="tp-btn tp-btn-secondary" type="button" data-tp-ident-send><?php echo $tp($tp_row[1]); ?></button>
          <div class="tp-hidden" data-tp-ident-box>
            <div class="tp-field"><label for="tp-ident-<?php echo esc_attr($tp_kind); ?>-code"><?php echo $tp('otp_code'); ?></label><input id="tp-ident-<?php echo esc_attr($tp_kind); ?>-code" type="text" dir="ltr" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" data-tp-ident-code></div>
            <button class="tp-btn" type="button" data-tp-ident-verify><?php echo $tp('verify_save'); ?></button>
          </div>
        </div>
        <?php endforeach; ?>
        <div id="tp-ident-message" class="tp-message" aria-live="polite"></div>
      </div>
      <div class="tp-card tp-avatar-card" id="tp-avatar-card" data-tp-avatar-card>
        <div class="tp-card-head"><h2><?php echo $tp('avatar_title'); ?></h2></div>
        <div class="tp-avatar-row">
          <img src="<?php echo esc_url($data['avatar']); ?>" alt="" width="96" height="96" data-tp-avatar-img class="tp-avatar-big">
          <div class="tp-avatar-ctl">
            <p class="tp-hint"><?php echo $tp('avatar_hint'); ?></p>
            <input type="file" id="tp-avatar-file" accept="image/jpeg,image/png,image/webp" hidden>
            <button class="tp-btn tp-btn-small" type="button" id="tp-avatar-choose"><?php echo $tp('avatar_choose'); ?></button>
            <button class="tp-btn tp-btn-small tp-btn-ghost" type="button" id="tp-avatar-remove"><?php echo $tp('avatar_remove'); ?></button>
            <p class="tp-message" id="tp-avatar-message" aria-live="polite"></p>
          </div>
        </div>
      </div>
      <form id="tp-profile-form" class="tp-card">
        <div class="tp-form-grid">
          <div class="tp-field"><label for="tp-first-name"><?php echo $tp('first_name'); ?></label><input id="tp-first-name" name="first_name" value="<?php echo esc_attr((string) get_user_meta(get_current_user_id(), 'first_name', true)); ?>"></div>
          <div class="tp-field"><label for="tp-last-name"><?php echo $tp('last_name'); ?></label><input id="tp-last-name" name="last_name" value="<?php echo esc_attr((string) get_user_meta(get_current_user_id(), 'last_name', true)); ?>"></div>
          <div class="tp-field"><label for="tp-country"><?php echo $tp('country'); ?></label><input id="tp-country" name="country" value="<?php echo esc_attr($data['country']); ?>"></div>
          <div class="tp-field"><label for="tp-language"><?php echo $tp('language'); ?></label><select id="tp-language" name="language">
            <option value="fa" <?php selected($data['language'], 'fa'); ?>>فارسی</option>
            <option value="en" <?php selected($data['language'], 'en'); ?>>English</option>
            <option value="ar" <?php selected($data['language'], 'ar'); ?>>العربية</option>
          </select></div>
          <div class="tp-field"><label for="tp-travel-style"><?php echo $tp('travel_style'); ?></label><select id="tp-travel-style" name="travel_style">
            <option value=""><?php echo $tp('select'); ?></option>
            <option value="culture" <?php selected($data['travel_style'], 'culture'); ?>><?php echo $tp('culture'); ?></option>
            <option value="nature" <?php selected($data['travel_style'], 'nature'); ?>><?php echo $tp('nature'); ?></option>
            <option value="food" <?php selected($data['travel_style'], 'food'); ?>><?php echo $tp('food'); ?></option>
            <option value="luxury" <?php selected($data['travel_style'], 'luxury'); ?>><?php echo $tp('luxury'); ?></option>
            <option value="adventure" <?php selected($data['travel_style'], 'adventure'); ?>><?php echo $tp('adventure'); ?></option>
          </select></div>
        </div>
        <div class="tp-form-actions"><button class="tp-btn tp-btn-small" type="submit"><?php echo $tp('save_changes'); ?></button><span id="tp-profile-message" class="tp-message" aria-live="polite"></span></div>
      </form>
    </section><?php endif; ?>

<?php if (isset($data['tabs']['saved'])) : ?><section id="tp-panel-saved" class="tp-tab-panel<?php echo $data['default_tab'] === 'saved' ? ' active' : ''; ?>" data-tp-panel="saved" role="tabpanel"<?php echo $data['default_tab'] === 'saved' ? '' : ' hidden'; ?> >
      <div class="tp-section-title"><span class="tp-eyebrow">SAVED</span><h2><?php echo $tp('saved'); ?></h2><p><?php echo $tp('saved_desc'); ?></p></div>
      <?php if ($data['saved_entities']) : ?>
        <div class="tp-saved-grid">
          <?php foreach ($data['saved_entities'] as $item) :
            /* جدول شناسۀ زبان پیش‌فرض را نگه می‌دارد؛ نمایش باید از
               ترجمۀ زبان جاری بخواند — وگرنه کارت‌های پنل انگلیسی عنوان
               و پیوند فارسی می‌دادند (پیوندِ شکسته در خوشۀ hreflang). */
            $pid = (int) ($item['view_id'] ?? $item['post_id']);
            $img = ToPersia_JetEngine::image($pid);
            $title = ToPersia_JetEngine::display_name($pid);
            $summary = ToPersia_JetEngine::summary($pid);
          ?>
            <article class="tp-saved-card">
              <?php if ($img) : ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy"><?php endif; ?>
              <div class="tp-saved-body"><span class="tp-badge"><?php echo $tp('saved_badge'); ?></span><h3><?php echo esc_html($title); ?></h3><?php if ($summary) : ?><p><?php echo esc_html($summary); ?></p><?php endif; ?><a class="tp-text-btn" href="<?php echo esc_url(get_permalink($pid)); ?>"><?php echo $tp('view'); ?></a><button type="button" class="tp-save-entity is-saved" data-post-id="<?php echo esc_attr((string) $pid); ?>" data-post-type="<?php echo esc_attr((string) $item['post_type']); ?>">✓ <?php echo $tp('remove_saved'); ?></button></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <div class="tp-empty-card"><div class="tp-empty-icon">♡</div><h2><?php echo $tp('nothing_saved'); ?></h2><p><?php echo $tp('save_hint'); ?></p></div>
      <?php endif; ?>
    </section><?php endif; ?>

<?php if (isset($data['tabs']['following'])) :
  /* دنبال‌شده‌ها — ۱٫۱۰٫۰ · P5-D238
     هر مورد: نام به زبان جاری، پیوند، لغو، و آخرین خبرهای خودش و مکان‌های
     درونش. خبر را «هستۀ نمایش» از راه فیلتر می‌دهد؛ بی آن، «خبر تازه‌ای
     نیست». */
  $tp_follow = (array) ($data['following_items'] ?? []);
?><section id="tp-panel-following" class="tp-tab-panel<?php echo $data['default_tab'] === 'following' ? ' active' : ''; ?>" data-tp-panel="following" role="tabpanel"<?php echo $data['default_tab'] === 'following' ? '' : ' hidden'; ?> >
      <div class="tp-section-title"><h2><?php echo $tp('following'); ?></h2><p><?php echo $tp('following_desc'); ?></p></div>
      <div class="tp-follow-list" data-tp-follow-list<?php echo $tp_follow ? '' : ' hidden'; ?>>
        <?php foreach ($tp_follow as $item) :
          $pid = (int) $item['view_id'];
          $title = ToPersia_JetEngine::display_name($pid);
        ?>
          <article class="tp-follow-card" data-tp-follow-card>
            <div class="tp-follow-head">
              <h3><a href="<?php echo esc_url((string) get_permalink($pid)); ?>"><?php echo esc_html($title); ?></a></h3>
              <button type="button" class="tp-text-btn" data-tp-unfollow data-post-id="<?php echo esc_attr((string) $pid); ?>" data-post-type="<?php echo esc_attr((string) $item['post_type']); ?>"><?php echo $tp('unfollow'); ?></button>
            </div>
            <?php if (!empty($item['news'])) : ?>
              <ul class="tp-follow-news">
                <?php foreach ($item['news'] as $news) : ?>
                  <li><a href="<?php echo esc_url((string) ($news['url'] ?? '')); ?>"><?php echo esc_html((string) ($news['title'] ?? '')); ?></a><?php if (!empty($news['date_fa'])) : ?> <time datetime="<?php echo esc_attr((string) ($news['date'] ?? '')); ?>"><?php echo esc_html((string) $news['date_fa']); ?></time><?php endif; ?></li>
                <?php endforeach; ?>
              </ul>
            <?php else : ?>
              <p class="tp-muted"><?php echo $tp('no_recent_news'); ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="tp-empty-card" data-tp-follow-empty<?php echo $tp_follow ? ' hidden' : ''; ?>><div class="tp-empty-icon"><?php echo ToPersia_Icons::svg('follow'); ?></div><h2><?php echo $tp('nothing_followed'); ?></h2><p><?php echo $tp('follow_hint'); ?></p></div>
    </section><?php endif; ?>

<?php if (isset($data['tabs']['trips'])) : ?><section id="tp-panel-trips" class="tp-tab-panel<?php echo $data['default_tab'] === 'trips' ? ' active' : ''; ?>" data-tp-panel="trips" role="tabpanel"<?php echo $data['default_tab'] === 'trips' ? '' : ' hidden'; ?> ><div class="tp-empty-card"><div class="tp-empty-icon">⌖</div><h2><?php echo $tp('trips_title'); ?></h2><p><?php echo $tp('trips_desc'); ?></p><button class="tp-btn tp-btn-small" type="button"><?php echo $tp('new_trip'); ?></button></div></section><?php endif; ?>

<?php if (isset($data['tabs']['reviews'])) : ?><section id="tp-panel-reviews" class="tp-tab-panel<?php echo $data['default_tab'] === 'reviews' ? ' active' : ''; ?>" data-tp-panel="reviews" role="tabpanel"<?php echo $data['default_tab'] === 'reviews' ? '' : ' hidden'; ?> >
      <div class="tp-section-title">
        <span class="tp-eyebrow">REVIEWS</span>
        <h2><?php echo $tp('reviews_title'); ?></h2>
        <p><?php echo $tp('reviews_desc'); ?></p>
        <?php if ($data['reviews_replies_count'] > 0) : ?>
          <span class="tp-badge tp-badge-highlight"><?php echo esc_html((string) $data['reviews_replies_count']); ?> <?php echo $tp('reviews_new_replies_badge'); ?></span>
        <?php endif; ?>
      </div>
      <?php if ($data['reviews_items']) : ?>
        <div class="tp-reviews-list">
          <?php foreach ($data['reviews_items'] as $review) : ?>
            <article class="tp-review-card">
              <div class="tp-review-head">
                <div>
                  <?php if ($review['post_url']) : ?>
                    <a class="tp-review-entity" href="<?php echo esc_url($review['post_url']); ?>"><?php echo esc_html($review['post_title']); ?></a>
                  <?php else : ?>
                    <span class="tp-review-entity tp-muted"><?php echo esc_html($review['post_title']); ?></span>
                  <?php endif; ?>
                  <time datetime="<?php echo esc_attr($review['date']); ?>"><?php echo esc_html($review['date']); ?></time>
                </div>
                <div class="tp-review-rating" dir="ltr" aria-label="<?php echo esc_attr((string) $review['rating']); ?>/5">
                  <?php
                  $tp_stars = max(0, min(5, (int) round($review['rating'])));
                  echo str_repeat('★', $tp_stars) . str_repeat('☆', 5 - $tp_stars);
                  ?>
                </div>
              </div>

              <?php if ($review['title'] !== '') : ?><h3><?php echo esc_html($review['title']); ?></h3><?php endif; ?>
              <p><?php echo esc_html($review['content']); ?></p>

              <?php if (!$review['approved']) : ?>
                <span class="tp-badge tp-badge-muted"><?php echo $tp('reviews_pending'); ?></span>
              <?php endif; ?>

              <div class="tp-review-replies">
                <h4><?php echo $tp('reviews_replies_heading'); ?><?php if ($review['replies']) : ?> (<?php echo esc_html((string) count($review['replies'])); ?>)<?php endif; ?></h4>
                <?php if ($review['replies']) : ?>
                  <ul>
                    <?php foreach ($review['replies'] as $reply) : ?>
                      <li>
                        <strong><?php echo esc_html($reply['author_name']); ?></strong>
                        <time datetime="<?php echo esc_attr($reply['date']); ?>"><?php echo esc_html($reply['date']); ?></time>
                        <p><?php echo esc_html($reply['content']); ?></p>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else : ?>
                  <p class="tp-muted"><?php echo $tp('reviews_no_replies'); ?></p>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <div class="tp-empty-card"><div class="tp-empty-icon">★</div><h2><?php echo $tp('reviews_empty'); ?></h2><p><?php echo $tp('reviews_empty_hint'); ?></p></div>
      <?php endif; ?>
    </section><?php endif; ?>

<?php if (isset($data['tabs']['notifications'])) : ?><section id="tp-panel-notifications" class="tp-tab-panel<?php echo $data['default_tab'] === 'notifications' ? ' active' : ''; ?>" data-tp-panel="notifications" role="tabpanel"<?php echo $data['default_tab'] === 'notifications' ? '' : ' hidden'; ?> ><div class="tp-empty-card"><div class="tp-empty-icon">◌</div><h2><?php echo $tp('notifications_title'); ?></h2><p><?php echo $tp('notifications_desc'); ?></p></div></section><?php endif; ?>

<?php if (isset($data['tabs']['settings'])) : ?><section id="tp-panel-settings" class="tp-tab-panel<?php echo $data['default_tab'] === 'settings' ? ' active' : ''; ?>" data-tp-panel="settings" role="tabpanel"<?php echo $data['default_tab'] === 'settings' ? '' : ' hidden'; ?> ><div class="tp-card"><div class="tp-card-head"><h2><?php echo $tp('settings_title'); ?></h2></div><div class="tp-setting-row"><div><strong><?php echo $tp('account_language'); ?></strong><span><?php echo $tp('language_hint'); ?></span></div><button class="tp-text-btn" type="button" data-tp-tab="profile"><?php echo $tp('edit'); ?></button></div><div class="tp-setting-row"><div><strong><?php echo $tp('privacy'); ?></strong><span><?php echo $tp('privacy_hint'); ?></span></div><span class="tp-badge"><?php echo $tp('active'); ?></span></div></div>
      <div class="tp-card">
        <div class="tp-card-head"><h2><?php echo $tp('display_settings_title'); ?></h2></div>
        <p class="hint"><?php echo $tp('display_settings_hint'); ?></p>

        <div class="tp-display-controls">
          <div class="tp-display-grid">
            <div class="tp-field-group">
              <span class="tp-field-group-label"><?php echo $tp('display_mode_label'); ?></span>
              <div class="tp-radio-row">
                <label><input type="radio" name="tp_dp_mode" value="auto"><?php echo $tp('display_mode_auto'); ?></label>
                <label><input type="radio" name="tp_dp_mode" value="light"><?php echo $tp('display_mode_light'); ?></label>
                <label><input type="radio" name="tp_dp_mode" value="evening"><?php echo $tp('display_mode_evening'); ?></label>
                <label><input type="radio" name="tp_dp_mode" value="dark"><?php echo $tp('display_mode_dark'); ?></label>
              </div>
            </div>

            <div class="tp-field-group">
              <span class="tp-field-group-label"><?php echo $tp('display_scale_label'); ?></span>
              <div class="tp-radio-row">
                <label><input type="radio" name="tp_dp_scale" value="1">۱۰۰٪</label>
                <label><input type="radio" name="tp_dp_scale" value="1.15">۱۱۵٪</label>
                <label><input type="radio" name="tp_dp_scale" value="1.3">۱۳۰٪</label>
                <label><input type="radio" name="tp_dp_scale" value="1.5">۱۵۰٪</label>
              </div>
            </div>

            <div class="tp-field-group">
              <span class="tp-field-group-label"><?php echo $tp('display_spacing_label'); ?></span>
              <div class="tp-radio-row">
                <label><input type="radio" name="tp_dp_spacing" value="compact"><?php echo $tp('display_spacing_compact'); ?></label>
                <label><input type="radio" name="tp_dp_spacing" value="normal"><?php echo $tp('display_spacing_normal'); ?></label>
                <label><input type="radio" name="tp_dp_spacing" value="loose"><?php echo $tp('display_spacing_loose'); ?></label>
              </div>
            </div>

            <div class="tp-field-group">
              <span class="tp-field-group-label"><?php echo $tp('display_measure_label'); ?></span>
              <div class="tp-radio-row">
                <label><input type="radio" name="tp_dp_measure" value="narrow"><?php echo $tp('display_measure_narrow'); ?></label>
                <label><input type="radio" name="tp_dp_measure" value="standard"><?php echo $tp('display_measure_standard'); ?></label>
              </div>
            </div>
          </div>

          <div class="tp-field-group">
            <span class="tp-field-group-label"><?php echo $tp('display_comfort_label'); ?></span>
            <div class="tp-radio-row">
              <label class="tp-check"><input type="checkbox" id="tp-dp-contrast"><?php echo $tp('display_contrast_label'); ?></label>
              <label class="tp-check"><input type="checkbox" id="tp-dp-calm"><?php echo $tp('display_calm_label'); ?></label>
              <label class="tp-check"><input type="checkbox" id="tp-dp-motion"><?php echo $tp('display_motion_label'); ?></label>
            </div>
          </div>

          <p class="tp-display-save-status" id="tp-dp-status" aria-live="polite"></p>
        </div>

        <script>
        (function () {
          var root = document.documentElement;

          function read(key) { try { return window.localStorage.getItem('tp-' + key); } catch (e) { return null; } }
          function prefersDark() {
            try { return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); }
            catch (e) { return false; }
          }

          /* اگر display-sync.js تا این لحظه بارگذاری شده، از همان
             apply/put استفاده می‌کنیم تا منطق دوبار نوشته نشود؛ اگر نه
             (چون آن فایل در فوتر است و ممکن است دیرتر بارگذاری شود)، یک
             نسخۀ کوچک و مستقل اینجا داریم که با همان‌ها کاملاً یکی است. */
          function put(key, value) {
            var shared = window.ToPersiaDisplaySync && window.ToPersiaDisplaySync.put;
            if (shared) { shared(key, value); return; }
            try { window.localStorage.setItem('tp-' + key, value); } catch (e) {}
          }
          function apply(key, value) {
            var shared = window.ToPersiaDisplaySync && window.ToPersiaDisplaySync.apply;
            if (shared) { shared(key, value); return; }
            if (key === 'mode')     { root.setAttribute('data-tp-mode', value === 'auto' ? (prefersDark() ? 'dark' : 'light') : value); return; }
            if (key === 'scale')    { root.style.setProperty('--tp-scale', value); return; }
            if (key === 'spacing')  { root.setAttribute('data-spacing', value); return; }
            if (key === 'measure')  { root.setAttribute('data-measure', value); return; }
            if (key === 'contrast' || key === 'calm' || key === 'motion') {
              if (value) { root.setAttribute('data-' + key, value); } else { root.removeAttribute('data-' + key); }
            }
          }
          function effective(key, fallback) {
            var device = read(key);
            if (device !== null) return device;
            var account = window.ToPersiaDisplayPrefs;
            if (account && typeof account === 'object' && key in account) return account[key];
            return fallback;
          }
          function showSaved() {
            var status = document.getElementById('tp-dp-status');
            if (!status) return;
            status.textContent = <?php echo wp_json_encode($tp('display_saved_confirmation')); ?>;
            window.clearTimeout(status._tpTimer);
            status._tpTimer = window.setTimeout(function () { status.textContent = ''; }, 2500);
          }
          function change(key, value) {
            put(key, value);
            apply(key, value);
            try {
              document.dispatchEvent(new CustomEvent('topersia:display-changed', { detail: { key: key, value: value } }));
            } catch (e) {}
            showSaved();
          }

          function initRadio(name, key, fallback) {
            var val = effective(key, fallback);
            var input = document.querySelector('input[name="' + name + '"][value="' + val + '"]');
            if (input) { input.checked = true; }
            document.querySelectorAll('input[name="' + name + '"]').forEach(function (el) {
              el.addEventListener('change', function () { change(key, el.value); });
            });
          }

          function initToggle(id, key, onValue) {
            var box = document.getElementById(id);
            if (!box) return;
            box.checked = (effective(key, '') === onValue);
            box.addEventListener('change', function () { change(key, box.checked ? onValue : ''); });
          }

          initRadio('tp_dp_mode', 'mode', 'auto');
          initRadio('tp_dp_scale', 'scale', '1');
          initRadio('tp_dp_spacing', 'spacing', 'normal');
          initRadio('tp_dp_measure', 'measure', 'standard');
          initToggle('tp-dp-contrast', 'contrast', 'high');
          initToggle('tp-dp-calm', 'calm', 'on');
          initToggle('tp-dp-motion', 'motion', 'off');
        })();
        </script>
      </div>
      <div class="tp-card" id="tp-dr-card" data-tp-dr-card>
        <div class="tp-card-head"><h2><?php echo $tp('dr_title'); ?></h2></div>
        <p class="hint"><?php echo $tp('dr_hint'); ?></p>
        <?php
        $tp_dr_user = wp_get_current_user();
        $tp_dr_exp  = class_exists('ToPersia_Data_Requests') && ToPersia_Data_Requests::active_request($tp_dr_user, 'export');
        $tp_dr_era  = class_exists('ToPersia_Data_Requests') && ToPersia_Data_Requests::active_request($tp_dr_user, 'erase');
        ?>
        <div class="tp-dr-actions">
          <button class="tp-btn tp-btn-small" type="button" data-tp-dr="export"<?php echo $tp_dr_exp ? ' disabled' : ''; ?>><?php echo $tp($tp_dr_exp ? 'dr_pending' : 'dr_btn_export'); ?></button>
          <button class="tp-btn tp-btn-small tp-btn-danger" type="button" data-tp-dr="erase"<?php echo $tp_dr_era ? ' disabled' : ''; ?>><?php echo $tp($tp_dr_era ? 'dr_pending' : 'dr_btn_erase'); ?></button>
        </div>
        <div class="tp-dr-confirm" id="tp-dr-confirm" hidden>
          <p id="tp-dr-confirm-text"></p>
          <button class="tp-btn tp-btn-small" type="button" id="tp-dr-confirm-btn"><?php echo $tp('dr_confirm_btn'); ?></button>
          <button class="tp-btn tp-btn-small tp-btn-ghost" type="button" id="tp-dr-cancel"><?php echo $tp('dr_cancel'); ?></button>
        </div>
        <p class="tp-message" id="tp-dr-message" aria-live="polite"></p>
      </div>
    </section><?php endif; ?>

<?php foreach ($data['tabs'] as $id => $tab) : if (($tab['type'] ?? '') !== 'custom') continue; ?>
<section id="tp-panel-<?php echo esc_attr($id); ?>" class="tp-tab-panel<?php echo $data['default_tab'] === $id ? ' active' : ''; ?>" data-tp-panel="<?php echo esc_attr($id); ?>" role="tabpanel"<?php echo $data['default_tab'] === $id ? '' : ' hidden'; ?>>
  <?php
  $custom_label = (string) ($tab[$tp_lang] ?? $tab['fa'] ?? $id);
  if (function_exists('apply_filters')) {
      $custom_label = apply_filters('wpml_translate_single_string', $custom_label, 'ToPersia User Core', 'tab_' . $id . '_' . $tp_lang);
  }
  ?>
  <div class="tp-section-title">
    <span class="tp-eyebrow">TOPERSIA</span>
    <h2><?php echo esc_html($custom_label); ?></h2>
  </div>
  <div class="tp-card tp-custom-tab-content">
    <?php
    $shortcode = trim((string)($tab['shortcode'] ?? ''));
    echo $shortcode !== '' ? do_shortcode($shortcode) : '<p class="tp-muted">' . esc_html($tp('empty_custom_tab')) . '</p>';
    ?>
  </div>
</section>
<?php endforeach; ?>
    </main>
  </div>
  <div class="tp-mobile-exit"><a class="tp-btn tp-btn-secondary" href="<?php echo esc_url(apply_filters('wpml_home_url', home_url('/'))); ?>"><?php echo $tp('back_to_site'); ?></a></div>
</div>

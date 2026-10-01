<?php
namespace Jet_Reviews\Bricks\Elements;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Reviews_Listing extends \Bricks\Element {

	public $category     = 'jet-reviews';
	public $name         = 'jet-reviews-listing';
	public $icon         = 'fas fa-star-half-alt';
	public $scripts      = array( 'jetReviewsBricksInit' );

	/**
	 * Tracks whether the reCAPTCHA badge override has already been scheduled.
	 *
	 * @var bool
	 */
	protected static $recaptcha_badge_style_added = false;

	/**
	 * Return localized element label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Reviews Listing', 'jet-reviews' );
	}

	/**
	 * Return searchable keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array(
			esc_html__( 'reviews', 'jet-reviews' ),
			esc_html__( 'rating', 'jet-reviews' ),
			esc_html__( 'comments', 'jet-reviews' ),
			esc_html__( 'jetreviews', 'jet-reviews' ),
		);
	}

	/**
	 * Register Bricks control groups.
	 *
	 * @return void
	 */
	public function set_control_groups() {
		$this->control_groups['settings'] = array(
			'title' => esc_html__( 'Settings', 'jet-reviews' ),
			'tab'   => 'content',
		);

		$this->control_groups['icons'] = array(
			'title' => esc_html__( 'Icons', 'jet-reviews' ),
			'tab'   => 'content',
		);

		$this->control_groups['header_labels'] = array(
			'title' => esc_html__( 'Header', 'jet-reviews' ),
			'tab'   => 'content',
		);

		$this->control_groups['review_form_labels'] = array(
			'title' => esc_html__( 'Review Form', 'jet-reviews' ),
			'tab'   => 'content',
		);

		$this->control_groups['comment_form_labels'] = array(
			'title' => esc_html__( 'Comment Form', 'jet-reviews' ),
			'tab'   => 'content',
		);

		$this->control_groups['reply_form_labels'] = array(
			'title' => esc_html__( 'Reply Form', 'jet-reviews' ),
			'tab'   => 'content',
		);
	}

	/**
	 * Register Bricks controls.
	 *
	 * @return void
	 */
	public function set_controls() {
		$this->register_settings_controls();
		$this->register_icon_controls();
		$this->register_label_controls();
	}

	/**
	 * Enqueue frontend assets required by the review listing runtime.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		jet_reviews_assets()->register_styles();
		jet_reviews_assets()->enqueue_styles();
		$this->maybe_restore_recaptcha_badge_visibility();
		jet_reviews_assets()->register_scripts();
		jet_reviews_assets()->enqueue_scripts();

		wp_add_inline_script(
			'jet-reviews-frontend',
			'window.jetReviewsBricksInit=function(){if(window.JetReviews&&window.JetReviews.initInstances){window.JetReviews.initInstances();}};',
			'after'
		);
	}

	/**
	 * Keep the Google reCAPTCHA v3 badge visible when Bricks form styles are loaded.
	 *
	 * @return void
	 */
	protected function maybe_restore_recaptcha_badge_visibility() {
		$integration_manager = isset( jet_reviews()->integration_manager ) ? jet_reviews()->integration_manager : false;

		if ( ! $integration_manager || ! method_exists( $integration_manager, 'get_integration_module_instance' ) ) {
			return;
		}

		$recaptcha = $integration_manager->get_integration_module_instance( 'recaptcha' );

		if ( ! $recaptcha || ! method_exists( $recaptcha, 'is_captcha_ready_to_use' ) || ! $recaptcha->is_captcha_ready_to_use() ) {
			return;
		}

		if ( ! self::$recaptcha_badge_style_added ) {
			add_action( 'wp_footer', array( $this, 'render_recaptcha_badge_visibility_style' ), 99 );

			self::$recaptcha_badge_style_added = true;
		}
	}

	/**
	 * Print a late override for Bricks frontend styles that hide the reCAPTCHA badge.
	 *
	 * @return void
	 */
	public function render_recaptcha_badge_visibility_style() {
		echo '<style id="jet-reviews-bricks-recaptcha-badge">.grecaptcha-badge{visibility:visible!important;}</style>';
	}

	/**
	 * Render element HTML.
	 *
	 * @return void
	 */
	public function render() {
		$this->set_attribute( '_root', 'class', 'jet-reviews-bricks-listing' );

		// render_attributes() returns escaped and validated HTML attributes.
		echo '<div ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! class_exists( '\Jet_Reviews\Reviews\Review_Listing_Render' ) ) {
			echo '<div class="jet-reviews-message">' . esc_html__( 'JetReviews renderer is not available.', 'jet-reviews' ) . '</div>';
			echo '</div>';

			return;
		}

		$render_widget_instance = new \Jet_Reviews\Reviews\Review_Listing_Render( $this->normalize_settings() );
		$render_widget_instance->render();

		echo '</div>';
	}

	/**
	 * Register the primary content controls.
	 *
	 * @return void
	 */
	protected function register_settings_controls() {
		$this->controls['source'] = array(
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Source', 'jet-reviews' ),
			'type'        => 'select',
			'options'     => $this->get_source_options(),
			'inline'      => true,
			'clearable'   => false,
			'placeholder' => esc_html__( 'Post', 'jet-reviews' ),
			'default'     => 'post',
		);

		$this->controls['ratingLayout'] = array(
			'tab'       => 'content',
			'group'     => 'settings',
			'label'     => esc_html__( 'Rating Layout', 'jet-reviews' ),
			'type'      => 'select',
			'options'   => array(
				'stars-field'  => esc_html__( 'Stars', 'jet-reviews' ),
				'points-field' => esc_html__( 'Points', 'jet-reviews' ),
			),
			'inline'    => true,
			'clearable' => false,
			'default'   => 'stars-field',
		);

		$this->controls['ratingInputType'] = array(
			'tab'       => 'content',
			'group'     => 'settings',
			'label'     => esc_html__( 'Rating Input Type', 'jet-reviews' ),
			'type'      => 'select',
			'options'   => array(
				'slider-input' => esc_html__( 'Slider', 'jet-reviews' ),
				'stars-input'  => esc_html__( 'Stars', 'jet-reviews' ),
			),
			'inline'    => true,
			'clearable' => false,
			'default'   => 'slider-input',
		);

		$this->controls['reviewRatingType'] = array(
			'tab'       => 'content',
			'group'     => 'settings',
			'label'     => esc_html__( 'Review Rating Type', 'jet-reviews' ),
			'type'      => 'select',
			'options'   => array(
				'average' => esc_html__( 'Average', 'jet-reviews' ),
				'details' => esc_html__( 'Details', 'jet-reviews' ),
			),
			'inline'    => true,
			'clearable' => false,
			'default'   => 'average',
		);

		$this->controls['reviewsPerPage'] = array(
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Reviews Per Page', 'jet-reviews' ),
			'type'    => 'select',
			'options' => $this->get_reviews_per_page_options(),
			'inline'  => true,
			'clearable' => false,
			'default' => 10,
		);

		$this->add_checkbox_control( 'reviewAuthorAvatarVisible', esc_html__( 'Show Review Author Avatar', 'jet-reviews' ) );
		$this->add_checkbox_control( 'reviewTitleVisible', esc_html__( 'Show Review Title', 'jet-reviews' ) );
		$this->add_checkbox_control( 'reviewTitleInputVisible', esc_html__( 'Show Review Title Field', 'jet-reviews' ) );
		$this->add_checkbox_control( 'reviewContentInputVisible', esc_html__( 'Show Review Content Field', 'jet-reviews' ) );
		$this->add_checkbox_control( 'commentAuthorAvatarVisible', esc_html__( 'Show Comment Author Avatar', 'jet-reviews' ) );
	}

	/**
	 * Register icon controls.
	 *
	 * @return void
	 */
	protected function register_icon_controls() {

		foreach ( $this->get_icon_controls_schema() as $key => $control ) {
			$this->controls[ $key ] = array(
				'tab'         => 'content',
				'group'       => 'icons',
				'label'       => $control['label'],
				'type'        => 'icon',
				'default'     => $control['default'],
				'placeholder' => $control['default'],
			);
		}
	}

	/**
	 * Register label controls.
	 *
	 * @return void
	 */
	protected function register_label_controls() {

		foreach ( $this->get_label_controls_schema() as $key => $control ) {
			$this->controls[ $key ] = array_filter(
				array(
					'tab'         => 'content',
					'group'       => $control['group'],
					'label'       => $control['label'],
					'type'        => 'text',
					'placeholder' => $control['placeholder'],
					'default'     => $control['default'],
					'hasDynamicData' => false,
					'spellcheck'  => true,
				),
				function( $value ) {
					return null !== $value;
				}
			);
		}
	}

	/**
	 * Add a standard checkbox control to the settings group.
	 *
	 * @param string $key Control key.
	 * @param string $label Control label.
	 * @return void
	 */
	protected function add_checkbox_control( $key, $label ) {
		$this->controls[ $key ] = array(
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => $label,
			'type'    => 'checkbox',
			'inline'  => true,
			'small'   => true,
			'default' => true,
		);
	}

	/**
	 * Normalize Bricks settings to Review_Listing_Render settings.
	 *
	 * @return array
	 */
	protected function normalize_settings() {
		$prev_icon = $this->get_icon_html( 'prev_icon', 'prevIcon' );
		$next_icon = $this->get_icon_html( 'next_icon', 'nextIcon' );

		return array(
			'source'                     => $this->get_setting( 'source', 'post' ),
			'ratingLayout'               => $this->get_setting( 'ratingLayout', $this->get_setting( 'rating_layout', 'stars-field' ) ),
			'ratingInputType'            => $this->get_setting( 'ratingInputType', $this->get_setting( 'rating_input_type', 'slider-input' ) ),
			'reviewRatingType'           => $this->get_setting( 'reviewRatingType', $this->get_setting( 'review_rating_type', 'average' ) ),
			'reviewsPerPage'             => $this->sanitize_reviews_per_page( $this->get_setting( 'reviewsPerPage', $this->get_setting( 'reviews_per_page', 10 ) ) ),
			'reviewAuthorAvatarVisible'  => $this->get_bool_setting( 'reviewAuthorAvatarVisible', $this->get_setting( 'show_review_author_avatar', true ) ),
			'reviewTitleVisible'         => $this->get_bool_setting( 'reviewTitleVisible', $this->get_setting( 'show_review_title', true ) ),
			'commentAuthorAvatarVisible' => $this->get_bool_setting( 'commentAuthorAvatarVisible', $this->get_setting( 'show_comment_author_avatar', true ) ),
			'reviewTitleInputVisible'    => $this->get_bool_setting( 'reviewTitleInputVisible', $this->get_setting( 'review_title_input_visible', true ) ),
			'reviewContentInputVisible'  => $this->get_bool_setting( 'reviewContentInputVisible', $this->get_setting( 'review_content_input_visible', true ) ),
			'icons'                      => array(
				'pinnedIcon'              => $this->get_icon_html( 'pinned_icon', 'pinnedIcon' ),
				'emptyStarIcon'           => $this->get_icon_html( 'empty_star_icon', 'emptyStarIcon' ),
				'filledStarIcon'          => $this->get_icon_html( 'filled_star_icon', 'filledStarIcon' ),
				'newReviewButtonIcon'     => $this->get_icon_html( 'new_review_button_icon', 'newReviewButtonIcon' ),
				'showCommentsButtonIcon'  => $this->get_icon_html( 'show_comments_button_icon', 'showCommentsButtonIcon' ),
				'newCommentButtonIcon'    => $this->get_icon_html( 'new_comment_button_icon', 'newCommentButtonIcon' ),
				'reviewEmptyLikeIcon'     => $this->get_icon_html( 'review_empty_like_icon', 'reviewEmptyLikeIcon' ),
				'reviewFilledLikeIcon'    => $this->get_icon_html( 'review_filled_like_icon', 'reviewFilledLikeIcon' ),
				'reviewEmptyDislikeIcon'  => $this->get_icon_html( 'review_empty_dislike_icon', 'reviewEmptyDislikeIcon' ),
				'reviewFilledDislikeIcon' => $this->get_icon_html( 'review_filled_dislike_icon', 'reviewFilledDislikeIcon' ),
				'replyButtonIcon'         => $this->get_icon_html( 'reply_button_icon', 'replyButtonIcon' ),
				'fileUploadIcon'          => $this->get_icon_html( 'file_upload_icon', 'fileUploadIcon' ),
				'prevIcon'                => ! is_rtl() ? $prev_icon : $next_icon,
				'nextIcon'                => ! is_rtl() ? $next_icon : $prev_icon,
			),
			'labels'                     => $this->normalize_labels(),
		);
	}

	/**
	 * Normalize label controls.
	 *
	 * @return array
	 */
	protected function normalize_labels() {
		$labels = array();

		foreach ( $this->get_label_map() as $control_key => $renderer_key ) {
			$value = $this->get_setting( $control_key, '' );

				$labels[ $renderer_key ] = is_scalar( $value ) ? sanitize_text_field( $value ) : '';
		}

		return $labels;
	}

	/**
	 * Return an individual setting value without losing explicit false.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_setting( $key, $default = false ) {

		if ( is_array( $this->settings ) && array_key_exists( $key, $this->settings ) ) {
			return $this->settings[ $key ];
		}

		return $default;
	}

	/**
	 * Return a boolean setting value.
	 *
	 * @param string $key Setting key.
	 * @param bool   $default Default value.
	 * @return bool
	 */
	protected function get_bool_setting( $key, $default = false ) {
		$value = $this->get_setting( $key, $default );

		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Keep reviews per page in the same valid range as the editor control.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	protected function sanitize_reviews_per_page( $value ) {
		$value = absint( $value );

		if ( ! $value ) {
			$value = 10;
		}

		return min( 20, max( 1, $value ) );
	}

	/**
	 * Convert a Bricks icon control value to HTML.
	 *
	 * @param string $control_key Bricks control key.
	 * @param string $default_key Default JetReviews icon key.
	 * @return string
	 */
	protected function get_icon_html( $control_key, $default_key ) {
		$icon = $this->get_setting( $control_key, array() );

		if ( empty( $icon['icon'] ) ) {
			return jet_reviews_tools()->get_svg_html( $default_key );
		}

		return sprintf( '<i class="%s" aria-hidden="true"></i>', esc_attr( $icon['icon'] ) );
	}

	/**
	 * Get source options for the Bricks select control.
	 *
	 * @return array
	 */
	protected function get_source_options() {
		$options = array();

		if ( jet_reviews()->reviews_manager && jet_reviews()->reviews_manager->sources ) {
			$options = jet_reviews()->reviews_manager->sources->get_registered_source_list();
		}

		if ( empty( $options ) ) {
			$options = array(
				'post' => esc_html__( 'Post', 'jet-reviews' ),
			);
		}

		return $options;
	}

	/**
	 * Return icon controls schema.
	 *
	 * @return array
	 */
	protected function get_icon_controls_schema() {
		return array(
			'empty_star_icon' => array(
				'label'   => esc_html__( 'Empty Star Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeRegular', 'icon' => 'far fa-star' ),
			),
			'filled_star_icon' => array(
				'label'   => esc_html__( 'Filled Star Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-star' ),
			),
			'new_review_button_icon' => array(
				'label'   => esc_html__( 'New Review Button Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-pen' ),
			),
			'show_comments_button_icon' => array(
				'label'   => esc_html__( 'Show Comments Button Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-comment' ),
			),
			'new_comment_button_icon' => array(
				'label'   => esc_html__( 'New Comment Button Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-pen' ),
			),
			'review_empty_like_icon' => array(
				'label'   => esc_html__( 'Empty Like Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeRegular', 'icon' => 'far fa-thumbs-up' ),
			),
			'review_filled_like_icon' => array(
				'label'   => esc_html__( 'Filled Like Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-thumbs-up' ),
			),
			'review_empty_dislike_icon' => array(
				'label'   => esc_html__( 'Empty Dislike Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeRegular', 'icon' => 'far fa-thumbs-down' ),
			),
			'review_filled_dislike_icon' => array(
				'label'   => esc_html__( 'Filled Dislike Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-thumbs-down' ),
			),
			'reply_button_icon' => array(
				'label'   => esc_html__( 'Reply Button Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-reply' ),
			),
			'prev_icon' => array(
				'label'   => esc_html__( 'Prev Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-chevron-left' ),
			),
			'next_icon' => array(
				'label'   => esc_html__( 'Next Icon', 'jet-reviews' ),
				'default' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-chevron-right' ),
			),
		);
	}

	/**
	 * Return label controls schema.
	 *
	 * @return array
	 */
	protected function get_label_controls_schema() {
		return array(
			'no_reviews_label'                   => $this->label_schema(
				'header_labels',
				esc_html__( 'No Reviews Message', 'jet-reviews' ),
				esc_html__( 'No reviews found', 'jet-reviews' )
			),
			'singular_review_count_label'        => $this->label_schema(
				'header_labels',
				esc_html__( 'Singular Review Count Label', 'jet-reviews' ),
				esc_html__( 'Review', 'jet-reviews' )
			),
			'plural_review_count_label'          => $this->label_schema(
				'header_labels',
				esc_html__( 'Plural Review Count Label', 'jet-reviews' ),
				esc_html__( 'Reviews', 'jet-reviews' )
			),
			'new_review_button_label'            => $this->label_schema(
				'header_labels',
				esc_html__( 'New Review Button', 'jet-reviews' ),
				esc_html__( 'Write a review', 'jet-reviews' ),
				esc_html__( 'Write a review', 'jet-reviews' )
			),
			'already_reviewed_message'           => $this->label_schema(
				'header_labels',
				esc_html__( 'Already Reviewed Message', 'jet-reviews' ),
				esc_html__( '*Already reviewed', 'jet-reviews' ),
				''
			),
			'moderator_check_message'            => $this->label_schema(
				'header_labels',
				esc_html__( 'Moderator Check Message', 'jet-reviews' ),
				esc_html__( '*Your review must be approved by the moderator', 'jet-reviews' ),
				''
			),
			'not_valid_field_message'            => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Not Valid Message', 'jet-reviews' ),
				esc_html__( '*This field is required or not valid', 'jet-reviews' ),
				''
			),
			'captcha_validation_failed_message'  => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Captcha Validation Failed Message', 'jet-reviews' ),
				esc_html__( '*Captcha validation failed', 'jet-reviews' ),
				''
			),
			'author_name_placeholder'            => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Author Name Placeholder', 'jet-reviews' ),
				esc_html__( 'Your Name', 'jet-reviews' ),
				esc_html__( 'Your Name', 'jet-reviews' )
			),
			'author_mail_placeholder'            => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Author Mail Placeholder', 'jet-reviews' ),
				esc_html__( 'Your Mail', 'jet-reviews' ),
				esc_html__( 'Your Mail', 'jet-reviews' )
			),
			'review_content_placeholder'         => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Review Content Placeholder', 'jet-reviews' ),
				esc_html__( 'Your review', 'jet-reviews' ),
				esc_html__( 'Your review', 'jet-reviews' )
			),
			'review_title_placeholder'           => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Review Title Placeholder', 'jet-reviews' ),
				esc_html__( 'Title of your review', 'jet-reviews' ),
				esc_html__( 'Title of your review', 'jet-reviews' )
			),
			'rating_label'                       => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Rating Label', 'jet-reviews' ),
				esc_html__( 'Rating', 'jet-reviews' ),
				esc_html__( 'Rating', 'jet-reviews' )
			),
			'submit_review_button_label'         => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Submit Review Button', 'jet-reviews' ),
				esc_html__( 'Submit a review', 'jet-reviews' ),
				esc_html__( 'Submit a review', 'jet-reviews' )
			),
			'cancel_button_label'                => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Cancel Button', 'jet-reviews' ),
				esc_html__( 'Cancel', 'jet-reviews' ),
				esc_html__( 'Cancel', 'jet-reviews' )
			),
			'upload_control_label'               => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Upload Control Label', 'jet-reviews' ),
				esc_html__( 'Upload your file here, or', 'jet-reviews' ),
				esc_html__( 'Upload your file here, or', 'jet-reviews' )
			),
			'upload_button_label'                => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Upload Button Label', 'jet-reviews' ),
				esc_html__( 'Choose File', 'jet-reviews' ),
				esc_html__( 'Choose File', 'jet-reviews' )
			),
			'upload_max_size_label'              => $this->label_schema(
				'review_form_labels',
				esc_html__( 'Max File Size Label', 'jet-reviews' ),
				esc_html__( 'Maximum size', 'jet-reviews' ),
				esc_html__( 'Maximum size', 'jet-reviews' )
			),
			'new_comment_button_label'           => $this->label_schema(
				'comment_form_labels',
				esc_html__( 'New Comment Button', 'jet-reviews' ),
				esc_html__( 'Leave a comment', 'jet-reviews' ),
				esc_html__( 'Leave a comment', 'jet-reviews' )
			),
			'comment_placeholder'                => $this->label_schema(
				'comment_form_labels',
				esc_html__( 'Comments Placeholder', 'jet-reviews' ),
				esc_html__( 'Leave your comments', 'jet-reviews' ),
				esc_html__( 'Leave your comments', 'jet-reviews' )
			),
			'show_comments_button_label'         => $this->label_schema(
				'comment_form_labels',
				esc_html__( 'Show Comments Button', 'jet-reviews' ),
				esc_html__( 'Show Comments', 'jet-reviews' ),
				esc_html__( 'Show Comments', 'jet-reviews' )
			),
			'hide_comments_button_label'         => $this->label_schema(
				'comment_form_labels',
				esc_html__( 'Hide Comments Button', 'jet-reviews' ),
				esc_html__( 'Hide Comments', 'jet-reviews' ),
				esc_html__( 'Hide Comments', 'jet-reviews' )
			),
			'comments_title_label'               => $this->label_schema(
				'comment_form_labels',
				esc_html__( 'Comments Title', 'jet-reviews' ),
				esc_html__( 'Comments', 'jet-reviews' ),
				esc_html__( 'Comments', 'jet-reviews' )
			),
			'submit_comment_button_label'        => $this->label_schema(
				'comment_form_labels',
				esc_html__( 'Submit Comment Button', 'jet-reviews' ),
				esc_html__( 'Submit Comment', 'jet-reviews' ),
				esc_html__( 'Submit Comment', 'jet-reviews' )
			),
			'reply_comment_button_label'         => $this->label_schema(
				'reply_form_labels',
				esc_html__( 'Reply Comment Button', 'jet-reviews' ),
				esc_html__( 'Reply', 'jet-reviews' ),
				esc_html__( 'Reply', 'jet-reviews' )
			),
			'reply_placeholder'                  => $this->label_schema(
				'reply_form_labels',
				esc_html__( 'Reply Placeholder', 'jet-reviews' ),
				esc_html__( 'Leave you reply', 'jet-reviews' ),
				esc_html__( 'Leave you reply', 'jet-reviews' )
			),
			'submit_reply_comment_button_label'  => $this->label_schema(
				'reply_form_labels',
				esc_html__( 'Submit Reply Button', 'jet-reviews' ),
				esc_html__( 'Submit a reply', 'jet-reviews' ),
				esc_html__( 'Submit a reply', 'jet-reviews' )
			),
		);
	}

	/**
	 * Build label control schema.
	 *
	 * @param string      $group Control group.
	 * @param string      $label Control label.
	 * @param string      $placeholder Control placeholder.
	 * @param string|null $default Control default.
	 * @return array
	 */
	protected function label_schema( $group, $label, $placeholder, $default = null ) {
		return array(
			'group'       => $group,
			'label'       => $label,
			'placeholder' => $placeholder,
			'default'     => $default,
		);
	}

	/**
	 * Map Bricks label keys to renderer label keys.
	 *
	 * @return array
	 */
	protected function get_label_map() {
		return array(
			'no_reviews_label'                  => 'noReviewsLabel',
			'singular_review_count_label'       => 'singularReviewCountLabel',
			'plural_review_count_label'         => 'pluralReviewCountLabel',
			'cancel_button_label'               => 'cancelButtonLabel',
			'new_review_button_label'           => 'newReviewButton',
			'author_name_placeholder'           => 'authorNamePlaceholder',
			'author_mail_placeholder'           => 'authorMailPlaceholder',
			'review_content_placeholder'        => 'reviewContentPlaceholder',
			'review_title_placeholder'          => 'reviewTitlePlaceholder',
			'rating_label'                      => 'ratingLabel',
			'submit_review_button_label'        => 'submitReviewButton',
			'upload_control_label'              => 'uploadControlLabel',
			'upload_button_label'               => 'buttonLabel',
			'upload_max_size_label'             => 'maxFileSizeLabel',
			'new_comment_button_label'          => 'newCommentButton',
			'show_comments_button_label'        => 'showCommentsButton',
			'hide_comments_button_label'        => 'hideCommentsButton',
			'comments_title_label'              => 'сommentsTitle',
			'comment_placeholder'               => 'commentPlaceholder',
			'submit_comment_button_label'       => 'submitCommentButton',
			'reply_comment_button_label'        => 'replyButton',
			'reply_placeholder'                 => 'replyPlaceholder',
			'submit_reply_comment_button_label' => 'submitReplyButton',
			'already_reviewed_message'          => 'alreadyReviewedMessage',
			'moderator_check_message'           => 'moderatorCheckMessage',
			'not_valid_field_message'           => 'notValidFieldMessage',
			'captcha_validation_failed_message' => 'captchaValidationFailed',
		);
	}

	/**
	 * Return limited Reviews Per Page options.
	 *
	 * @return array
	 */
	protected function get_reviews_per_page_options() {
		$options = array();

		for ( $i = 1; $i <= 20; $i++ ) {
			$options[ $i ] = (string) $i;
		}

		return $options;
	}
}

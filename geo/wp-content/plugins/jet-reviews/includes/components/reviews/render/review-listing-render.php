<?php
namespace Jet_Reviews\Reviews;

use Jet_Reviews\Base_Render as Base_Render;
use Jet_Reviews\Reviews\Data as Reviews_Data;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Review_Listing_Render extends Base_Render {

	/**
	 * [$name description]
	 * @var string
	 */
	protected $name = 'review-listing-render';

	/**
	 * @var array
	 */
	public $widget_labels = [];

	/**
	 * @var array
	 */
	public $widget_icons = [];

	/**
	 * [init description]
	 * @return [type] [description]
	 */
	public function init() {
	    $default_labels = apply_filters( 'jet-reviews/review-listing-render/default-widget-labels', [
		    'noReviewsLabel'           => __( 'No reviews found', 'jet-reviews' ),
		    'singularReviewCountLabel' => __( 'Review', 'jet-reviews' ),
		    'pluralReviewCountLabel'   => __( 'Reviews', 'jet-reviews' ),
		    'cancelButtonLabel'        => __( 'Cancel', 'jet-reviews' ),
		    'newReviewButton'          => __( 'Write a review', 'jet-reviews' ),
		    'authorNamePlaceholder'    => __( 'Your Name', 'jet-reviews' ),
		    'authorMailPlaceholder'    => __( 'Your Mail', 'jet-reviews' ),
		    'reviewContentPlaceholder' => __( 'Your review', 'jet-reviews' ),
		    'reviewTitlePlaceholder'   => __( 'Title of your review', 'jet-reviews' ),
		    'ratingLabel'              => __( 'Rating', 'jet-reviews' ),
		    'submitReviewButton'       => __( 'Submit a review', 'jet-reviews' ),
		    'newCommentButton'         => __( 'Leave a comment', 'jet-reviews' ),
		    'showCommentsButton'       => __( 'Show Comments', 'jet-reviews' ),
		    'hideCommentsButton'       => __( 'Hide Comments', 'jet-reviews' ),
		    'сommentsTitle'            => __( 'Comments', 'jet-reviews' ),
		    'commentPlaceholder'       => __( 'Leave your comments', 'jet-reviews' ),
		    'submitCommentButton'      => __( 'Submit Comment', 'jet-reviews' ),
		    'replyButton'              => __( 'Reply', 'jet-reviews' ),
		    'replyPlaceholder'         => __( 'Leave you reply', 'jet-reviews' ),
		    'submitReplyButton'        => __( 'Submit a reply', 'jet-reviews' ),
		    'alreadyReviewedMessage'   => __( '*Already reviewed', 'jet-reviews' ),
		    'moderatorCheckMessage'    => __( '*Your review must be approved by the moderator', 'jet-reviews' ),
		    'notValidFieldMessage'     => __( '*This field is required or not valid', 'jet-reviews' ),
		    'captchaValidationFailed'  => __( '*Captcha validation failed', 'jet-reviews' ),
		    'uploadButtonLabel'        => __( 'Choose File', 'jet-reviews' ),
		    'uploadControlLabel'        => __( 'Upload your file here, or', 'jet-reviews' ),
		    'uploadMaxSizeLabel'        => __( 'Maximum size', 'jet-reviews' ),
		    'buttonLabel'              => __( 'Choose File', 'jet-reviews' ),
		    'maxFileSizeLabel'         => __( 'Maximum size', 'jet-reviews' ),
        ] );

	    $labels = $this->get( 'labels', [] );

	    foreach ( $default_labels as $slug => $label ) {
	        if ( isset( $labels[ $slug ] ) &&  ! empty( $labels[ $slug ] ) ) {
		        $this->widget_labels[ $slug ] = $labels[ $slug ];
            } else {
		        $this->widget_labels[ $slug ] = $label;
            }
        }

	    if ( empty( $labels['buttonLabel'] ) && ! empty( $this->widget_labels['uploadButtonLabel'] ) ) {
		    $this->widget_labels['buttonLabel'] = $this->widget_labels['uploadButtonLabel'];
	    }

	    if ( empty( $labels['maxFileSizeLabel'] ) && ! empty( $this->widget_labels['uploadMaxSizeLabel'] ) ) {
		    $this->widget_labels['maxFileSizeLabel'] = $this->widget_labels['uploadMaxSizeLabel'];
	    }

		$default_icons = apply_filters( 'jet-reviews/review-listing-render/default-widget-icons', [
			'emptyStarIcon'           => jet_reviews_tools()->get_svg_html( 'emptyStarIcon' ),
			'filledStarIcon'           => jet_reviews_tools()->get_svg_html( 'filledStarIcon' ),
			'newCommentButtonIcon'    => jet_reviews_tools()->get_svg_html( 'newCommentButtonIcon' ),
			'newReviewButtonIcon'     => jet_reviews_tools()->get_svg_html( 'newReviewButtonIcon' ),
			'nextIcon'                => jet_reviews_tools()->get_svg_html( 'nextIcon' ),
			'prevIcon'                => jet_reviews_tools()->get_svg_html( 'prevIcon' ),
			'pinnedIcon'              => jet_reviews_tools()->get_svg_html( 'pinnedIcon' ),
			'replyButtonIcon'         => jet_reviews_tools()->get_svg_html( 'replyButtonIcon' ),
			'reviewEmptyDislikeIcon'  => jet_reviews_tools()->get_svg_html( 'reviewEmptyDislikeIcon' ),
			'reviewFilledDislikeIcon' => jet_reviews_tools()->get_svg_html( 'reviewFilledDislikeIcon' ),
			'reviewEmptyLikeIcon'     => jet_reviews_tools()->get_svg_html( 'reviewEmptyLikeIcon' ),
			'reviewFilledLikeIcon'    => jet_reviews_tools()->get_svg_html( 'reviewFilledLikeIcon' ),
			'showCommentsButtonIcon'  => jet_reviews_tools()->get_svg_html( 'showCommentsButtonIcon' ),
			'fileUploadIcon'           => jet_reviews_tools()->get_svg_html( 'fileUploadIcon' ),
        ] );

		$this->widget_icons = wp_parse_args( $this->get( 'icons', [] ), $default_icons );
    }

	/**
	 * [get_name description]
	 * @return [type] [description]
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * [render description]
	 * @return [type] [description]
	 */
	public function render() {
		$uniqid = uniqid();
		$source = $this->get( 'source' );
		$source_instance = jet_reviews()->reviews_manager->sources->get_source_instance( $source );

		if ( ! $source_instance ) {
			echo esc_html__( 'Any Sources not found', 'jet-reviews' );

			return;
		}

		$source      = $source_instance->get_slug();
		$source_type = $source_instance->get_type();
		$source_id   = $source_instance->get_current_id();

		if ( 'jet-theme-core' === $source_type ) {
			echo esc_html__( 'JetReviews unavailable for jetThemeCore template preview', 'jet-reviews' );

			return;
		}

		$review_type_slug = jet_reviews()->reviews_manager->types->get_review_type_slug_by_source_type( $source, $source_type );

		if ( ! $review_type_slug ) {

			if ( is_user_logged_in() ) {
				echo wp_kses(
					sprintf(
						__( 'Review type for %s source and %s source type not found. Create a new review for current source type <a href="%s"> Here </a>', 'jet-reviews' ),
						esc_html( $source ),
						esc_html( $source_type ),
						esc_url( add_query_arg( [
						'page' => 'jet-reviews-type-page',
						'action' => 'add'
						], admin_url( 'admin.php' ) ) )
					),
					array( 'a' => array( 'href' => array() ) )
				);
			} else {
				printf( esc_html__( 'Review type for %s source and %s source type not found', 'jet-reviews' ), esc_html( $source ), esc_html( $source_type ) );
            }

			return;
		}

		$review_type_data     = jet_reviews()->reviews_manager->types->get_review_type_data( $review_type_slug );
		$review_type_settings = $review_type_data['settings'];
		$has_custom_fields    = ! empty( $review_type_settings['fields'] );
		$fields               = $has_custom_fields ? $review_type_settings['fields'] : jet_reviews_tools()->get_default_rating_fields();
		$rating_label         = ! empty( $this->widget_labels['ratingLabel'] ) ? jet_reviews_tools()->decode_plain_text( $this->widget_labels['ratingLabel'] ) : '';
		$rating_label_override_allowed = ! empty( $rating_label ) && is_array( $fields ) && 1 === count( $fields );

		if ( $rating_label_override_allowed && ! empty( $fields[0] ) ) {
			$fields[0]['label'] = $rating_label;
		}
		$raw_user_data        = jet_reviews()->user_manager->get_raw_user_data();
		$user_can_review_data = jet_reviews()->user_manager->is_user_can_review( $raw_user_data, $review_type_settings );
		$user_can_comment_data = $this->get_user_can_comment_data( $raw_user_data, $review_type_settings );

		if ( jet_reviews()->user_manager->is_review_submission_hidden_for_post_author( $raw_user_data['id'], $source, $source_id ) ) {
			$user_can_review_data = [
				'allowed' => false,
				'code'    => 'post-author',
				'message' => __( '*You cannot review your own post', 'jet-reviews' ),
			];
		}

		$user_can_review_data = $this->maybe_modify_can_review_data( $user_can_review_data );
		$reviews_per_page     = $this->get( 'reviewsPerPage', 10 );
		add_filter( 'jet-reviews/user-manager/raw-user-data', array( $this, 'filter_review_list_data' ), 10 );
		$reviews_list_data = Reviews_Data::get_instance()->get_public_reviews_list( $source, $source_type, $source_id, 0, $reviews_per_page );
		remove_filter( 'jet-reviews/user-manager/raw-user-data', array( $this, 'filter_review_list_data' ) );

		$options = [
			'uniqId'     => $uniqid,
			'sourceData' => [
				'source'          => $source,
				'sourceId'        => $source_id,
				'sourceType'      => $source_type,
				'allowed'         => true,
				'commentsAllowed' => $review_type_settings[ 'comments_allowed' ],
				'approvalAllowed' => $review_type_settings[ 'approval_allowed' ],
				'itemLabel'       => $source_instance->get_item_label(),
				'itemThumb'       => $source_instance->get_item_thumb_url(),
            ],
			'userData' => [
				'id'        => $raw_user_data[ 'id' ],
				'name'      => $raw_user_data[ 'name' ],
				'mail'      => $raw_user_data[ 'mail' ],
				'avatar'    => $raw_user_data[ 'avatar' ],
				'roles'     => $raw_user_data[ 'roles' ],
				'canReview' => [
					'allowed' => $user_can_review_data['allowed'],
					'code'    => $user_can_review_data['code'],
					'message' => $user_can_review_data['message'],
				],
				'canComment' => [
					'allowed' => $user_can_comment_data['allowed'],
					'message' => $user_can_comment_data['message'],
				],
				'canRate' => [
					'allowed' => true,
					'message' => __( 'This user can rate reviews', 'jet-reviews' ),
				],
            ],
			'reviewsListData'            => $reviews_list_data,
			'reviewsFields'              => $fields,
			'isDefaultRatingFields'      => ! $has_custom_fields,
			'ratingLabelOverrideAllowed' => $rating_label_override_allowed,
			'ratingLayout'               => $this->get( 'ratingLayout', 'stars-field' ),
			'ratingInputType'            => $this->get( 'ratingInputType', 'slider-input' ),
			'reviewRatingType'           => $this->get( 'reviewRatingType', 'average' ),
			'pageSize'                   => $reviews_per_page,
			'reviewAuthorAvatarVisible'  => $this->get( 'reviewAuthorAvatarVisible', true ),
			'reviewTitleVisible'         => $this->get( 'reviewTitleVisible', true ),
			'commentAuthorAvatarVisible' => $this->get( 'commentAuthorAvatarVisible', true ),
			'labels'                     => $this->widget_labels,
            'reviewTitleInputVisible'    => $this->get( 'reviewTitleInputVisible', true ),
            'reviewContentInputVisible'  => $this->get( 'reviewContentInputVisible', true ),
            'uploadMedia' => [
	            'allowed' => $review_type_settings['upload_media'],
	            'allowedMedia' => $review_type_settings['allowed_media'],
	            'maxSizeMedia' => $review_type_settings['maxsize_media'],
            ],
		];

		?><script id="<?php echo esc_attr( 'jetReviewsWidgetOptions' . $uniqid ); ?>" type="text/javascript">
			window.jetReviewsWidget<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- uniqid() returns a server-generated hexadecimal string safe for a JavaScript identifier suffix.
			echo $uniqid;
			?>=<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safely JSON-encoded for an inline script element.
			echo wp_json_encode( $options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
        </script><?php

		$icons_data = $this->widget_icons;
		$refs_html = '';

		foreach ( $icons_data as $slug => $icon_html ) {
			$refs_html .= sprintf( '<div ref="%s">%s</div>', $slug, $icon_html );
		}

		$widget_refs = sprintf( '<div class="jet-reviews-advanced__refs">%s</div>', $refs_html );

		if ( $review_type_settings['structuredata'] && ! empty( $reviews_list_data['list'] ) ) {
		    $this->render_structure_data( $reviews_list_data, $source_instance, $review_type_settings['structuredata_type'] );
		}

		require jet_reviews()->plugin_path( 'templates/public/widgets/jet-reviews-advanced-widget.php' );

	}

	/**
	 * @param false $reviews_list_data
	 * @param false $source_instance
	 * @param string $type
	 *
	 * @return false
	 */
	public function render_structure_data( $reviews_list_data = false, $source_instance = false, $type = 'Product' ) {

		if ( ! $reviews_list_data ) {
		    return false;
		}

		$source      = $source_instance->get_slug();
		$source_id   = $source_instance->get_current_id();
		$review_list = $reviews_list_data['list'];
		$total       = $reviews_list_data['total'];
		$rating      = jet_reviews_tools()->normalize_structure_data_rating( 5 * intval( $reviews_list_data['rating'] ) / 100 );
		$type        = jet_reviews_tools()->get_valid_structure_data_type( $type );

		$review_items =  array_map( function( $item ) {
			$rating = jet_reviews_tools()->normalize_structure_data_rating( 5 * intval( $item[ 'rating' ] ) / 100 );

            return [
	            '@type'         => 'Review',
	            'name'          => $item[ 'title' ],
	            'reviewBody'    => $item[ 'content' ],
	            'reviewRating'  => [
		            '@type'       => 'Rating',
		            'ratingValue' => $rating,
		            'bestRating'  => '5',
		            'worstRating' => '1',
	            ],
	            'datePublished' => $item[ 'date' ][ 'raw' ],
	            'author'        => [
		            '@type' => 'Person',
		            'name'  => $item[ 'author' ][ 'name' ],
	            ],
            ];
        }, $review_list );

		$structure_data = [
			'@context'    => 'https://schema.org',
			'@type'       => $type,
			'name'        => $source_instance->get_item_label(),
            'aggregateRating' => [
	            '@type'       => 'AggregateRating',
                'ratingValue' => $rating,
                'reviewCount' => strval( $total ),
	            'bestRating' => '5',
                'worstRating' => '1'
            ],
            'review' => $review_items,
		];

		$image_url = $source_instance->get_item_thumb_url();
		$image_url = is_string( $image_url ) ? esc_url_raw( $image_url ) : '';

		if ( ! empty( $image_url ) ) {
			$structure_data['image'] = $image_url;
		}

		printf( '<script type="application/ld+json">%s</script>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safely JSON-encoded for a JSON-LD script element.
			wp_json_encode( $structure_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT )
		);
	}

	/**
	 * @param false $review_data
	 *
	 * @return false|mixed
	 */
	public function maybe_modify_can_review_data( $review_data = false ) {

	    $code = $review_data['code'];

		if ( 'already-reviewed' === $code && ! empty( $this->widget_labels['alreadyReviewedMessage'] ) ) {
			$review_data['message'] = $this->widget_labels['alreadyReviewedMessage'];
		}

		if ( 'moderator-check' === $code && ! empty( $this->widget_labels['moderatorCheckMessage'] ) ) {
			$review_data['message'] = $this->widget_labels['moderatorCheckMessage'];
		}

	    return $review_data;
	}

	/**
	 * Return frontend comment permission data aligned with the REST endpoint.
	 *
	 * @param array $user_data User data.
	 * @param array $review_type_settings Review type settings.
	 * @return array
	 */
	public function get_user_can_comment_data( $user_data = array(), $review_type_settings = array() ) {
		$allowed_roles = ! empty( $review_type_settings['allowed_roles'] ) ? $review_type_settings['allowed_roles'] : array();
		$user_roles    = ! empty( $user_data['roles'] ) ? $user_data['roles'] : array();
		$allowed       = ! empty( array_intersect( $user_roles, $allowed_roles ) );

		if ( $allowed ) {
			return array(
				'allowed' => true,
				'message' => __( 'This user can comment reviews', 'jet-reviews' ),
			);
		}

		$message = in_array( 'guest', $user_roles, true )
			? __( '*Guests cannot publish reviews', 'jet-reviews' )
			: __( '*User with this role cannot publish a new review', 'jet-reviews' );

		return array(
			'allowed' => false,
			'message' => $message,
		);
	}

	/**
	* @param array $data The array of review list data.
	* @return array The modified array of data after filtering.
	 */
	public function filter_review_list_data( $data ) {
		if ( $data['roles'] === [ 'guest' ] && ! empty( $data['mail'] ) ) {
			$data['mail'] = '';
		}

		return $data;
	}
}

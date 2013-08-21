<?php
/**
 * Submit Shortcode.
 *
 * [appthemer_crowdfunding_submit] creates a submission form.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Submission Process.
 *
 * If we are on the submission page, start things up. Register fieflds,
 * set up validation methods, etc.
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @return mixed none|object
 */
function atcf_submit_campaign() {
	return ATCF_Submit_Campaign::instance();
}
add_action( 'init', 'atcf_submit_campaign' );

/**
 * Submission fields.
 *
 * A helper function for getting the registered fields. This should be called
 * instead of the private method, so the filter is run all the time.
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @return array $fields
 */
function atcf_shortcode_submit_fields() {
	$submit_campaign = atcf_submit_campaign();

	$fields = apply_filters( 'atcf_shortcode_submit_fields', $submit_campaign->register_fields() );

	return $fields;
}

/**
 * Submit a campaign.
 *
 * Handles creating the shortcode, registering fields, adding hooks for fields,
 * saving data, populating data, etc.
 *
 * The majority of the HTML output, etc is outside of this class. This serves mainly
 * as a container for the behind the scenes stuff.
 *
 * @since Astoundify Crowdfunding 1.7
 */
class ATCF_Submit_Campaign {

	/**
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Don't create more than once instance of the class.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Actions and Filters
	 *
	 * Shortcodes, field callbacks, saving, etc.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @return void
	 */
	private function __construct() {
		$this->register_fields();

		/** Register the Shortcode */
		add_shortcode( 'appthemer_crowdfunding_submit', 'atcf_shortcode_submit' );

		/** Print errors above the shortcode */
		add_action( 'atcf_shortcode_submit_before', 'edd_print_errors' );

		/** Output Fields */
		add_filter( 'atcf_shortcode_submit_field', array( $this, 'get_field_value' ), 10, 4 );

		add_action( 'atcf_shortcode_submit_field_heading', 'atcf_shortcode_submit_heading', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_hidden', 'atcf_shortcode_submit_field_hidden', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_text', 'atcf_shortcode_submit_field_text', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_textarea', 'atcf_shortcode_submit_field_textarea', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_number', 'atcf_shortcode_submit_field_number', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_checkbox', 'atcf_shortcode_submit_field_checkbox', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_radio', 'atcf_shortcode_submit_field_radio', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_term_checklist', 'atcf_shortcode_submit_field_term_checklist', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_wp_editor', 'atcf_shortcode_submit_field_wp_editor', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_featured_image', 'atcf_shortcode_submit_field_featured_image', 10, 4 );
		add_action( 'atcf_shortcode_submit_field_rewards', 'atcf_shortcode_submit_field_rewards', 10, 4 );

		/** Save Fields */
		add_action( 'atcf_submit_process_after', 'atcf_submit_process_after', 10, 4 );
	}

	/**
	 * Register submission form fields.
	 *
	 * There are some reusable field types to be chosen from, and some unique ones
	 * that are only meant to be used once. Some reusable ones are:
	 *
	 * - heading
	 * - text
	 * - textarea
	 * - number
	 * - radio
	 * - checkbox
	 * - term_checklist
	 *
	 * You should filter this via `atcf_shortcode_submit_fields` (called in atcf_shortcode_submit_fields()`)and not edit this array directly.
	 * Either add a filter via a child theme or plugin.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @see atcf_shortcode_sumbmit_fields()
	 *
	 * @return array $fields;
	 */
	public function register_fields() {
		global $edd_options;

		$fields = array(
			'campaign_heading' => array(
				'label'       => __( 'Campaign Information', 'atcf' ),
				'type'        => 'heading',
				'default'     => null,
				'editable'    => true
			),
			'title' => array(
				'label'       => __( 'Title', 'atcf' ),
				'default'     => null,
				'type'        => 'text',
				'editable'    => false,
				'placeholder' => null,
				'required'    => true
			),
			'goal' => array(
				'label'       => sprintf( __( 'Goal (%s)', 'atcf' ), edd_currency_filter( '' ) ),
				'default'     => null,
				'type'        => 'text',
				'editable'    => false,
				'placeholder' => edd_format_amount( 800 ),
				'required'    => true
			),
			'length' => array(
				'label'       => __( 'Length', 'atcf' ),
				'default'     => 57,
				'type'        => 'number',
				'editable'    => false,
				'placeholder' => null,
				'min'         => isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14,
				'max'         => isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 48,
				'step'        => 1
			),
			'type' => array(
				'label'       => __( 'Funding Type', 'atcf' ),
				'default'     => atcf_campaign_type_default(),
				'type'        => 'radio',
				'options'     => atcf_campaign_types_active(),
				'editable'    => false,
				'placeholder' => null,
				'required'    => true
			),
			'category' => array(
				'label'       => __( 'Categories', 'atcf' ),
				'default'     => null,
				'type'        => 'term_checklist',
				'editable'    => true,
				'placeholder' => null,
			),
			'tag' => array(
				'label'       => __( 'Tags', 'atcf' ),
				'default'     => null,
				'type'        => 'term_checklist',
				'editable'    => true,
				'placeholder' => null,
			),
			'description' => array(
				'label'       => __( 'Description', 'atcf' ),
				'default'     => null,
				'type'        => 'wp_editor',
				'editable'    => true,
				'placeholder' => null,
				'required'    => true
			),
			'updates' => array(
				'label'       => __( 'Updates', 'atcf' ),
				'default'     => null,
				'type'        => 'wp_editor',
				'editable'    => 'only',
				'placeholder' => null,
			),
			'excerpt' => array(
				'label'       => __( 'Excerpt', 'atcf' ),
				'default'     => null,
				'type'        => 'textarea',
				'editable'    => true,
				'placeholder' => null,
			),
			'image' => array(
				'label'       => __( 'Featured Image', 'atcf' ),
				'default'     => null,
				'type'        => 'featured_image',
				'editable'    => true,
				'placeholder' => null,
			),
			'video' => array(
				'label'       => __( 'Featured Video URL', 'atcf' ),
				'default'     => null,
				'type'        => 'text',
				'editable'    => true,
				'placeholder' => null
			),
			'backer_rewards_heading' => array(
				'label'       => __( 'Backer Rewards', 'atcf' ),
				'type'        => 'heading',
				'default'     => null,
				'editable'    => true
			),
			'physical' => array(
				'label'       => __( 'Collect shipping information on checkout.', 'atcf' ),
				'default'     => null,
				'type'        => 'checkbox',
				'editable'    => true,
				'placeholder' => null
			),
			'norewards' => array(
				'label'       => __( 'No rewards, donations only.', 'atcf' ),
				'default'     => null,
				'type'        => 'checkbox',
				'editable'    => true,
				'placeholder' => null
			),
			'rewards' => array(
				'label'       => null,
				'type'        => 'rewards',
				'default'     => null,
				'editable'    => true
			),
			'info_heading' => array(
				'label'       => __( 'Your Information', 'atcf' ),
				'type'        => 'heading',
				'default'     => null,
				'editable'    => true
			),
			'contact_email' => array(
				'label'       => __( 'Contact Email', 'atcf' ),
				'default'     => null,
				'type'        => 'text',
				'editable'    => true,
				'placeholder' => null,
				'required'    => true
			),
			'author' => array(
				'label'       => __( 'Name/Organization', 'atcf' ),
				'default'     => null,
				'type'        => 'text',
				'editable'    => true,
				'placeholder' => null
			),
			'location' => array(
				'label'       => __( 'Location', 'atcf' ),
				'default'     => null,
				'type'        => 'text',
				'editable'    => true,
				'placeholder' => null
			)
		);

		return $fields;
	}

	/**
	 * Determine the value of a field/input.
	 *
	 * If we are previewing/editing, then get the saved value. If we are on a
	 * new submission, get a previously posted value, or the default.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return $field The array of modified form arguments.
	 */
	public function get_field_value( $key, $field, $atts, $campaign ) {
		if ( $atts[ 'previewing' ] || $atts[ 'editing' ] )
			$field[ 'value' ] = $this->saved_data( $key, $campaign );
		else
			$field[ 'value' ] = isset ( $_POST[ $key ] ) ? $_POST[ $key ] : $field[ 'default' ];

		return $field;
	}

	/**
	 * Retrieve saved campaign data to populate fields.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $atts The shortcoe attribtues.
	 * @return $data The correct form value for the field.
	 */
	private function saved_data( $key, $campaign ) {
		switch ( $key ) {
			case 'title' : 
				$data = $campaign->data->post_title;
			break;

			case 'length' :
				$data = $campaign->days_remaining();
			break;

			case 'description' :
				$data = wp_richedit_pre( $campaign->data->post_content );
			break;

			case 'excerpt' :
				$data = apply_filters( 'get_the_excerpt', $campaign->data->post_excerpt );
			break;

			case 'updates' :
				$data = wp_richedit_pre( $campaign->updates() );
			break;

			case 'norewards' :
				$data = $campaign->is_donations_only();
			break;

			case 'rewards' :
				$data = edd_get_variable_prices( $campaign->ID );
			break;

			case 'physical' :
				$data = $campaign->needs_shipping();
			break;

			case 'goal' :
				$data = edd_format_amount( $campaign->goal(false) );
			break;
			
			default :
				$data = apply_filters( 'atcf_shortcode_submit_saved_data_' . $key, $key, $campaign );
			break;
		}

		if ( ! $data && method_exists( $campaign, $key ) )
			$data = $campaign->$key();

		return $data;
	}

	/**
	 * Save tags.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return void
	 */
	public function save_tag( $key, $field, $campaign, $fields ) {
		$_tags = array();

		if ( isset ( $_POST[ 'tax_input' ][ 'download_tag' ] ) ) {
			foreach ( $_POST[ 'tax_input' ][ 'download_tag' ] as $key => $term ) { 
				$obj = get_term_by( 'id', $term, 'download_tag' );
				$_tags[] = $obj->name;
			}

			wp_set_post_terms( $campaign, $_tags, 'download_tag' );
		}
	}

	/**
	 * Save categories.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return void
	 */
	public function save_category( $key, $field, $campaign, $fields ) {
		if ( ! isset ( $_POST[ 'tax_input' ][ 'download_category' ] ) )
			return;

		wp_set_post_terms( $campaign, $_POST[ 'tax_input' ][ 'download_category' ], 'download_category' );
	}

	/**
	 * Save length.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return void
	 */
	public function save_length( $key, $field, $campaign, $fields ) {
		global $edd_options;

		if ( $field[ 'value' ] ) {
			$length = absint( $field[ 'value' ] );

			$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
			$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 42;

			if ( $length < $min )
				$length = $min;
			else if ( $length > $max )
				$length = $max;

			$end_date = strtotime( sprintf( '+%d day', $length ) );
			$end_date = get_gmt_from_date( date( 'Y-m-d H:i:s', $end_date ) );
		} else {
			$end_date = null;
		}

		if ( $end_date ) {
			update_post_meta( $campaign, 'campaign_end_date', sanitize_text_field( $end_date ) );
			update_post_meta( $campaign, 'campaign_length', $field[ 'value' ] );
		} else
			update_post_meta( $campaign, 'campaign_endless', 1 );
	}

	/**
	 * Save rewards.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return void
	 */
	public function save_rewards( $key, $field, $campaign, $fields ) {
		$prices = array();

		if ( $fields[ 'norewards' ][ 'value' ] ) {
			$prices[0] = array(
				'name'   => apply_filters( 'atcf_default_no_rewards_name', __( 'Donation', 'atcf' ) ),
				'amount' => apply_filters( 'atcf_default_no_rewards_price', 0 ),
				'limit'  => null,
				'bought' => 0
			);

			update_post_meta( $campaign, 'campaign_norewards', 1 );
		} else {
			foreach ( $field[ 'value' ] as $key => $reward ) {
				if ( '' == $reward[ 'amount' ] )
					continue;

				$prices[] = array(
					'name'   => sanitize_text_field( $reward[ 'name' ] ),
					'amount' => edd_sanitize_amount( $reward[ 'amount' ] ),
					'limit'  => sanitize_text_field( $reward[ 'limit' ] ),
					'bought' => isset ( $reward[ 'bought' ] ) ? sanitize_text_field( $reward[ 'bought' ] ) : 0
				);
			}
		}

		update_post_meta( $campaign, 'edd_variable_prices', $prices );
	}

	/**
	 * Save featured image.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return void
	 */
	public function save_image( $key, $field, $campaign, $fields ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/admin.php' );
		}

		$upload_overrides = array( 'test_form' => false );

		if ( '' != $_FILES[ $key ][ 'name' ] ) {
			$upload = wp_handle_upload( $_FILES[ $key ], $upload_overrides );
			$attachment = array(
				'guid'           => $upload[ 'url' ], 
				'post_mime_type' => $upload[ 'type' ],
				'post_title'     => $upload[ 'file' ],
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_parent'    => $campaign
			);

			$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign );		
			
			wp_update_attachment_metadata( 
				$attach_id, 
				wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
			);

			update_post_meta( $campaign, '_thumbnail_id', absint( $attach_id ) );
		}
	}

	/**
	 * Save goal.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return void
	 */
	public function save_goal( $key, $field, $campaign, $fields ) {
		if ( '' == $field[ 'value' ] )
			return;

		update_post_meta( $campaign, 'campaign_' . $key, edd_sanitize_amount( $field[ 'value' ] ) );
	}

	/**
	 * Save shipping.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return void
	 */
	public function save_physical( $key, $field, $campaign, $fields ) {
		update_post_meta( $campaign, '_campaign_' . $key, sanitize_text_field( $field[ 'value' ] ) );
	}

	/**
	 * Save a generic field.
	 *
	 * @since Astoundify Crowdfunding 1.7
	 *
	 * @param $key The key of the current field.
	 * @param $field The array of field arguments.
	 * @param $atts The shortcoe attribtues.
	 * @param $campaign The current campaign (if editing/previewing).
	 * @return void
	 */
	public function save_field( $key, $field, $campaign, $fields ) {
		update_post_meta( $campaign, 'campaign_' . $key, sanitize_text_field( $field[ 'value' ] ) );
	}
}

/**
 * Base page/form. All fields are loaded through an action,
 * so the form can be extended for ever, fields can be removed, added, etc.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param $atts
 * @return $form
 */
function atcf_shortcode_submit( $atts ) {
	global $edd_options;

	$atts = shortcode_atts( array(
		'editing'    => false,
		'previewing' => false
	), $atts );

	$crowdfunding    = crowdfunding();
	$campaign        = null;

	ob_start();

	/** Allow things to change the content of the shortcode. */
	if ( apply_filters( 'atcf_shortcode_submit_hide', false ) ) {
		do_action( 'atcf_shortcode_submit_hidden', $atts );

		$form = ob_get_clean();

		return $form;
	}

	if ( $atts[ 'editing' ] || $atts[ 'previewing' ] ) {
		global $post;
		
		$campaign = atcf_get_campaign( $post );
	}
?>
	<?php do_action( 'atcf_shortcode_submit_before', $atts, $campaign ); ?>

	<form action="" method="post" class="atcf-submit-campaign" enctype="multipart/form-data">
		
		<?php
			foreach ( atcf_shortcode_submit_fields() as $key => $field ) :
				/** If we _aren't_ editing, and the field should only be shown on edit, skip... */
				if ( ! $atts[ 'editing' ] && 'only' === $field[ 'editable' ] )
					continue;

				/** If we _are_ editing, and the field is not editable, skip... */
				if ( $atts[ 'editing' ] && $field[ 'editable' ] === false )
					continue;

				$field = apply_filters( 'atcf_shortcode_submit_field', $key, $field, $atts, $campaign );
				$field = apply_filters( 'atcf_shortcode_submit_field_before_render_' . $key, $field );

				do_action( 'atcf_shortcode_submit_field_' . $field[ 'type' ], $key, $field, $atts, $campaign );
			endforeach;
		?>

		<p class="atcf-submit-campaign-submit">
			<button type="submit" name="submit" value="submit" class="button">
				<?php echo $atts[ 'editing' ] && ! $atts[ 'previewing' ] ? sprintf( _x( 'Update %s', 'edit "campaign"', 'atcf' ), edd_get_label_singular() ) : sprintf( _x( 'Submit %s', 'submit "campaign"', 'atcf' ), edd_get_label_singular() ); ?>
			</button>

			<?php if ( is_user_logged_in() && ! $atts[ 'editing' ] ) : ?>
			<button type="submit" name="submit" value="preview" class="button button-secondary">
				<?php _e( 'Save and Preview', 'atcf' ); ?>
			</button>
			<?php endif; ?>

			<input type="hidden" name="action" value="atcf-campaign-submit" />
			<?php wp_nonce_field( 'atcf-campaign-submit' ); ?>

			<?php if ( $atts[ 'previewing' ] || $atts[ 'editing' ] ) : ?>
				<input type="hidden" name="campaign_id" value="<?php echo $campaign->ID; ?>" />
			<?php endif; ?>
		</p>
	</form>

	<?php do_action( 'atcf_shortcode_submit_after', $atts, $campaign ); ?>

<?php
	$form = ob_get_clean();

	return $form;
}

/**
 * Heading
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_heading( $key, $field, $atts, $campaign ) {
?>
	<h3 class="atcf-submit-section <?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $field[ 'label' ] ); ?></h3>
<?php
}

/**
 * Hidden Field
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_hidden( $key, $field, $atts, $campaign ) {
?>
	<input type="hidden" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $field[ 'value' ] ); ?>">
	
	<?php if ( $field[ 'label' ] ) : ?>
	<p class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<?php echo wp_kses_data( $field[ 'label' ] ); ?>
	</p>
	<?php endif; ?>
<?php
}

/**
 * Text Field
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_text( $key, $field, $atts, $campaign ) {
?>
	<p class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<label for="<?php echo esc_attr( $key ); ?>"><?php echo apply_filters( 'atcf_shortcode_submit_field_label_' . $key, esc_attr( $field[ 'label' ] ) ); ?></label>
		<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $field[ 'value' ] ); ?>" placeholder="<?php echo esc_attr( $field[ 'placeholder' ] ); ?>">
	</p>
<?php
}

/**
 * Textarea
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_textarea( $key, $field, $atts, $campaign ) {
?>
	<p class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $field[ 'label' ] ); ?></label>
		<textarea name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[ 'value' ] ); ?></textarea>
	</p>
<?php
}

/**
 * Number
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_number( $key, $field, $atts, $campaign ) {
?>
	<p class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<label for="<?php echo esc_attr( $key ); ?>"><?php echo apply_filters( 'atcf_shortcode_submit_field_label_' . $key, esc_attr( $field[ 'label' ] ) ); ?></label>
		<input type="number" min="<?php echo esc_attr( $field[ 'min' ] ); ?>" max="<?php echo esc_attr( $field[ 'max' ] ); ?>" step="<?php echo esc_attr( $field[ 'step' ] ); ?>" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $field[ 'value' ] ); ?>" placeholder="<?php echo esc_attr( $field[ 'placeholder' ] ); ?>">
	</p>
<?php
}

/**
 * Checkbox
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_checkbox( $key, $field, $atts, $campaign ) {
?>
	<p class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<label for="<?php echo esc_attr( $key ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( 1, $field[ 'value' ] ); ?> /> <?php echo esc_attr( $field[ 'label' ] ); ?>
		</label>
	</p>
<?php
}

/**
 * Number
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_radio( $key, $field, $atts, $campaign ) {
	if ( count( $field[ 'options' ] ) == 1 ) {
		echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . key( $field[ 'options' ] ) . '" />';

		return;
	}
?>
	<h4><?php echo esc_attr( $field[ 'label' ] ); ?></h4>

	<p class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<?php foreach ( $field[ 'options' ] as $k => $desc ) : ?>
		<label for="<?php echo esc_attr( $key ); ?>[<?php echo esc_attr( $k ); ?>]">
			<input type="radio" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $k ); ?>" <?php checked( $k, $field[ 'value' ] ); ?> /><?php echo $desc; ?>
		</label><br />
		<?php endforeach; ?>
	</p>
<?php
}

/**
 * Term Checklist
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_term_checklist( $key, $field, $atts, $campaign ) {
	if ( ! atcf_theme_supports( 'campaign-' . $key ) )
		return;

	if ( ! function_exists( 'wp_terms_checklist' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	}
?>
	<p class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $field[ 'label' ] ); ?></label>

		<ul class="atcf-multi-select">			
		<?php 
			wp_terms_checklist( isset ( $campaign->ID ) ? $campaign->ID : 0, array( 
				'taxonomy'   => 'download_' . $key,
				'walker'     => new ATCF_Walker_Terms_Checklist
			) );
		?>
	</ul>
	</p>
<?php
}

/**
 * WP Editor
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_wp_editor( $key, $field, $atts, $campaign ) {
?>
	<div class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $field[ 'label' ] ); ?></label>
		<?php 
			wp_editor( $field[ 'value' ], esc_attr( $key ), apply_filters( 'atcf_submit_field_' . $key . '_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}

/**
 * Image
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_featured_image( $key, $field, $atts, $campaign ) {
	if ( ! atcf_theme_supports( 'campaign-featured-image' ) )
		return;
?>
	<p class="atcf-submit-campaign-<?php echo esc_attr( $key ); ?>">
		<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $field[ 'label' ] ); ?></label>
		<input type="file" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" />

		<?php if ( $atts[ 'editing' ] || $atts[ 'previewing' ] ) : ?>
			<br /><?php the_post_thumbnail( array( 50, 50 ) ); ?>
		<?php endif; ?>
	</p>
<?php
}

/**
 * Campaign Backer Rewards
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_field_rewards( $key, $field, $atts, $campaign ) {
	$rewards = isset ( $field[ 'value' ] ) ? $field[ 'value' ] : array( 0 => array( 'amount' => null, 'name' => null, 'limit' => null ) );
?>
	<?php do_action( 'atcf_shortcode_submit_field_rewards_list_before' ); ?>

	<div class="atcf-submit-campaign-rewards">
		<?php 
			foreach ( $rewards as $k => $reward ) : 
				$disabled = isset ( $reward[ 'bought' ] ) && $reward[ 'bought' ] > 0 ? true : false; 
		?>
		<div class="atcf-submit-campaign-reward">
			<?php do_action( 'atcf_shortcode_submit_field_rewards_before' ); ?>

			<p class="atcf-submit-campaign-reward-amount">
				<label for="rewards[<?php echo esc_attr( $k ); ?>][amount]"><?php printf( __( 'Amount (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
				<input class="name" type="text" name="rewards[<?php echo esc_attr( $k ); ?>][amount]" id="rewards[<?php echo esc_attr( $k ); ?>][amount]" value="<?php echo esc_attr( $reward[ 'amount' ] ); ?>" <?php if ( $disabled ) : ?>readonly="readonly"<?php endif; ?> />
			</p>

			<p class="atcf-submit-campaign-reward-name">
				<label for="rewards[<?php echo esc_attr( $k ); ?>][name]"><?php _e( 'Reward', 'atcf' ); ?></label>
				<input class="name" type="text" name="rewards[<?php echo esc_attr( $k ); ?>][name]" id="rewards[<?php echo esc_attr( $k ); ?>][name]" rows="3" value="<?php echo esc_attr( $reward[ 'name' ] ); ?>" <?php if ( $disabled ) : ?>readonly="readonly"<?php endif; ?> />
			</p>

			<p class="atcf-submit-campaign-reward-limit">
				<label for="rewards[<?php echo esc_attr( $k ); ?>][limit]"><?php _e( 'Limit', 'atcf' ); ?></label>
				<input class="description" type="text" name="rewards[<?php echo esc_attr( $k ); ?>][limit]" id="rewards[<?php echo esc_attr( $k ); ?>][limit]" value="<?php echo isset ( $reward[ 'limit' ] ) ? esc_attr( $reward[ 'limit' ] ) : null; ?>" <?php if ( $disabled ) : ?>readonly="readonly"<?php endif; ?> />
				<input type="hidden" name="rewards[<?php echo esc_attr( $k ); ?>][bought]" id="rewards[<?php echo esc_attr( $k ); ?>][bought]" value="<?php echo isset ( $reward[ 'bought' ] ) ? esc_attr( $reward[ 'bought' ] ) : null; ?>" />
			</p>

			<?php do_action( 'atcf_shortcode_submit_field_rewards_after' ); ?>

			<?php if ( ! $disabled ) : ?>
			<p class="atcf-submit-campaign-reward-remove">
				<label>&nbsp;</label><br />
				<a href="#">&times;</a>
			</p>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>

		<p class="atcf-submit-campaign-add-reward">
			<a href="#" class="atcf-submit-campaign-add-reward-button"><?php _e( '+ <em>Add Reward</em>', 'atcf' ); ?></a>
		</p>
	</div>
<?php
}

/**
 * Process shortcode submission.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param $key The key of the current field.
 * @param $field The array of field arguments.
 * @param $atts The shortcoe attribtues.
 * @param $campaign The current campaign (if editing/previewing).
 * @return void
 */
function atcf_shortcode_submit_process() {
	global $edd_options, $post;
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST[ 'action' ] ) || ( 'atcf-campaign-submit' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'atcf-campaign-submit' ) )
		return;

	$action            = esc_attr( $_POST[ 'submit' ] );
	$existing_campaign = isset ( $_POST[ 'campaign_id' ] ) ? esc_attr( $_POST[ 'campaign_id' ] ) : null;
	$fields            = atcf_shortcode_submit_fields();

	foreach ( $fields as $key => $field ) {
		$fields[ $key ][ 'value' ] = isset ( $_POST[ $key ] ) ? $_POST[ $key ] : null;
		$fields[ $key ][ 'value' ] = apply_filters( 'atcf_shortcode_submit_validate_' . $key, $fields[ $key ][ 'value' ] );

		if ( ! $fields[ $key ][ 'value' ] && isset( $field[ 'required' ] ) && ! $existing_campaign )
			edd_set_error( 'required-' . $key, sprintf( __( 'The <strong>%s</strong> field is required.', 'atcf' ), $field[ 'label' ] ) );
	}

	do_action( 'atcf_campaign_submit_validate', $fields, $_POST );
	
	if ( edd_get_errors() )
		return;

	/**
	 * All required fields are there. We are clear to continue processing.
	 */

	/** Register a new user, or get the current user */
	$user = get_user_by( 'email', $fields[ 'contact_email' ][ 'value' ] );

	if ( ! $user ) {
		$user_id = atcf_register_user( array(
			'user_login'           => $fields[ 'contact_email' ][ 'value' ], 
			'user_email'           => $fields[ 'contact_email' ][ 'value' ],
			'display_name'         => $fields[ 'name' ][ 'value' ],
		) );
	} else {
		$user_id = $user->ID;
	}

	$status = 'submit' == $action ? 'pending' : 'draft';

	/** If we are submitting, but this is a live campaign, keep published */
	if ( $existing_campaign && ( 'pending' == $status && get_post( $existing_campaign )->post_status == 'publish' ) )
		$status = 'publish';

	/** 
	 * Create or update a campaign 
	 */
	$args = apply_filters( 'atcf_campaign_submit_data', array(
		'post_type'    => 'download',
		'post_status'  => $status,
		'post_content' => $fields[ 'description' ][ 'value' ],
		'post_author'  => $user_id
	), $_POST );

	if ( $fields[ 'title' ][ 'value' ] )
		$args[ 'post_title' ] = $fields[ 'title' ][ 'value' ];

	if ( $fields[ 'excerpt' ][ 'value' ] )
		$args[ 'post_excerpt' ] = $fields[ 'excerpt' ][ 'value' ];

	if ( ! $existing_campaign ) {
		$campaign = wp_insert_post( $args, true );
	} else {
		$args[ 'ID' ] = $existing_campaign;

		$campaign = wp_update_post( $args );
	}

	do_action( 'atcf_submit_process_after', $campaign, $_POST, $status, $fields );

	if ( 'publish' == $status ) {
		wp_safe_redirect( add_query_arg( 'updated', 'true', get_permalink( $campaign ) ) );
		exit();
	} elseif ( 'submit' == $action ) {
		$url = isset ( $edd_options[ 'submit_success_page' ] ) ? get_permalink( $edd_options[ 'submit_success_page' ] ) : get_permalink();

		$redirect = apply_filters( 'atcf_submit_campaign_success_redirect', $url );
		
		wp_safe_redirect( $redirect );
		exit();
	} else {
		wp_safe_redirect( add_query_arg( 'preview', 'true', get_permalink( $campaign ) ) );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_shortcode_submit_process' );

/**
 * Save extra campaign data. This includes default registered fields.
 *
 * Themes and plugins should also hook into this action and save their
 * data as well. Some special fields have a special callback in the main 
 * class, while default text fields and the like are just saved as standard meta.
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param $campaign The current campaign (if editing/previewing).
 * @param $postdata The $_POST data.
 * @param $status The status of the submission.
 * @param $fields The array of registered fields.
 * @return void
 */
function atcf_submit_process_after( $campaign, $postdata, $status, $fields ) {
	global $edd_options;

	$submit_campaign = atcf_submit_campaign();
	
	/**
	 * Save the fields
	 */
	foreach ( $fields as $key => $field ) {
		switch ( $key ) {
			case 'tag' : 
			case 'category' :
			case 'length' :
			case 'rewards' :
			case 'image' :
			case 'goal' :
			case 'physical' :
				$method = 'save_' . $key;

				$submit_campaign->$method( $key, $field, $campaign, $fields );
			break;
			default :
				$submit_campaign->save_field( $key, $field, $campaign, $fields );
			break;
		}
	}

	/**
	 * Some standard EDD stuff to save
	 */
	update_post_meta( $campaign, '_variable_pricing', 1 );
	update_post_meta( $campaign, '_edd_price_options_mode', 1 );
	update_post_meta( $campaign, '_edd_hide_purchase_link', 'on' );
}

/**
 * Redirect submit page if needed.
 *
 * @since Astoundify Crowdfunding 1.1
 *
 * @return void
 */
function atcf_shortcode_submit_redirect() {
	global $edd_options, $post;

	if ( ! is_a( $post, 'WP_Post' ) )
		return;

	if ( ! is_user_logged_in() && ( isset( $edd_options[ 'submit_page' ] ) && $post->ID == $edd_options[ 'submit_page' ] ) && isset ( $edd_options[ 'atcf_settings_require_account' ] ) ) {
		$url = isset ( $edd_options[ 'login_page' ] ) ? get_permalink( $edd_options[ 'login_page' ] ) : home_url();
		$url = add_query_arg( array( 'redirect_to' => get_permalink( $edd_options[ 'submit_page' ] ) ), $url );

		$redirect = apply_filters( 'atcf_require_account_redirect', $url );

		wp_safe_redirect( $redirect );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_shortcode_submit_redirect', 1 );

/**
 * If the user is logged in, change the contact form email field
 * to a hidden field with their email address.
 *
 * @since Astoundify Crowdfunding 1.7
 *
 * @param array $field
 * @return void
 */
function atcf_shortcode_submit_field_before_render_contact_email( $field ) {
	if ( ! is_user_logged_in() )
		return $field;

	$current_user = wp_get_current_user();

	$field[ 'type' ]  = 'hidden';
	$field[ 'value' ] = $current_user->user_email;
	$field[ 'label' ] =  sprintf( __( '<strong>Note</strong>: You are currently logged in as %1$s. This %2$s will be associated with that account. Please <a href="%3$s">log out</a> if you would like to make a %2$s under a new account.', 'atcf' ), $current_user->user_email, strtolower( edd_get_label_singular() ), wp_logout_url( get_permalink() ) );

	return $field;
}
add_filter( 'atcf_shortcode_submit_field_before_render_contact_email', 'atcf_shortcode_submit_field_before_render_contact_email' );

/**
 * Walker to output an unordered list of category checkbox <input> elements.
 *
 * @see Walker
 * @see wp_category_checklist()
 * @see wp_terms_checklist()
 * @since 2.5.1
 */
class ATCF_Walker_Terms_Checklist extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el( &$output, $category, $depth = 0, $args = array(), $current_object_id = 0 ) {
		extract($args);
		if ( empty($taxonomy) )
			$taxonomy = 'category';

		if ( $taxonomy == 'category' )
			$name = 'post_category';
		else
			$name = 'tax_input['.$taxonomy.']';

		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
	}

	function end_el( &$output, $category, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
}
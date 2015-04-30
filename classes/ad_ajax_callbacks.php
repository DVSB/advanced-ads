<?php

/**
 * Advanced Ads.
 *
 * @package   Advanced_Ads
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2013 Thomas Maier, webgilde GmbH
 */

/**
 * This class is used to bundle all ajax callbacks
 *
 * @package Advanced_Ads_Ajax_Callbacks
 * @author  Thomas Maier <thomas.maier@webgilde.com>
 */
class Advads_Ad_Ajax_Callbacks {

	public function __construct() {

		// NOTE: admin only!
		add_action( 'wp_ajax_load_content_editor', array( $this, 'load_content_editor' ) );
		add_action( 'wp_ajax_load_ad_parameters_metabox', array( $this, 'load_ad_parameters_metabox' ) );
                add_action( 'wp_ajax_advads-terms-search', array( $this, 'search_terms' ) );
	}

	/**
	 * load content of the ad parameter metabox
	 *
	 * @since 1.0.0
	 */
	public function load_ad_parameters_metabox() {

		$types = Advanced_Ads::get_instance()->ad_types;
		$type = $_REQUEST['ad_type'];
		$ad_id = absint( $_REQUEST['ad_id'] );
		if ( empty($ad_id) ) { wp_die(); }

		$ad = new Advads_Ad( $ad_id );

		if ( ! empty($types[$type]) && method_exists( $types[$type], 'render_parameters' ) ) {
			$types[$type]->render_parameters( $ad );
			?>
			<div id="advanced-ads-ad-parameters-size">
				<p><?php _e( 'size:', ADVADS_SLUG ); ?></p>
				<label><?php _e( 'width', ADVADS_SLUG ); ?><input type="number" size="4" maxlength="4" value="<?php echo isset($ad->width) ? $ad->width : 0; ?>" name="advanced_ad[width]">px</label>
				<label><?php _e( 'height', ADVADS_SLUG ); ?><input type="number" size="4" maxlength="4" value="<?php echo isset($ad->height) ? $ad->height : 0; ?>" name="advanced_ad[height]">px</label>
			</div>
			<?php
		}

		wp_die();

	}

        /**
         * search terms belonging to a specific taxonomy
         *
         * @sinc 1.4.7
         */
        public function search_terms(){
            $args = array();
            $taxonomy = $_POST['tax'];
            $args = array('hide_empty' => false, 'number' => 20);

            if ( !isset( $_POST['search'] ) || $_POST['search'] === '' ) { wp_die(); }

            // if search is an id, search for the term id, else do a full text search
            if(0 !== absint($_POST['search'])){
                $args['include'] = array(absint($_POST['search']));
            } else {
                $args['search'] = $_POST['search'];
            }

            $results = get_terms( $taxonomy, $args );
            // $results = _WP_Editors::wp_link_query( $args );
            echo wp_json_encode( $results );
            echo "\n";
            wp_die();
        }
}

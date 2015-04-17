<?php

/**
 * Advanced Ads
 *
 * @package   Advads_Ad_Group
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2014 Thomas Maier, webgilde GmbH
 */

/**
 * an ad group object
 *
 * @package Advads_Ad_Group
 * @author  Thomas Maier <thomas.maier@webgilde.com>
 */
class Advads_Ad_Group {

	/**
	 * default ad group weight
	 */
	const MAX_AD_GROUP_WEIGHT = 10;

	/**
	 * term id of this ad group
	 */
	public $id = 0;

	/**
	 * group type
         *
         * @since 1.4.8
	 */
	public $type = 'default';

	/**
	 * name of the taxonomy
	 */
	public $taxonomy = '';

	/**
	 * post type of the ads
	 */
	protected $post_type = '';

	/**
	 * the current loaded ad
	 */
	protected $current_ad = '';

	/**
	 * the name of the term
	 */
	public $name = '';

	/**
	 * the slug of the term
	 */
	public $slug = '';

	/**
	 * the description of the term
	 */
	public $description = '';

	/**
	 * number of ads to display in the group block
	 */
	public $ad_count = 1;

	/**
	 * containing ad weights
	 */
	private $ad_weights = 0;

	/**
	 * array with post type objects (ads)
	 */
	private $ads = array();

	/**
	 * init ad group object
	 *
	 * @since 1.0.0
	 * @param int|obj $group either id of the ad group (= taxonomy id) or term object
	 */
	public function __construct($group) {

		$this->taxonomy = Advanced_Ads::AD_GROUP_TAXONOMY;

                $group = get_term( $group, $this->taxonomy );
		if ( $group == null || is_wp_error($group) ) { return; }

		$this->load( $group );
	}

	/**
	 * load additional ad group properties
         *
	 * @since 1.4.8
         * @param int $id group id
         * @param obj $group wp term object
	 */
	private function load($group) {
            $this->id = $group->term_id;
            $this->name = $group->name;
            $this->slug = $group->slug;
            $this->description = $group->description;
            $this->post_type = Advanced_Ads::POST_TYPE_SLUG;

            $this->load_additional_attributes();
	}

        /**
         * load additional attributes for groups that are not part of the WP terms
         *
         * @since 1.4.8
         */
        protected function load_additional_attributes(){
            $all_groups = get_option( 'advads-ad-groups', array() );

            if(isset($all_groups[$this->id]['type'])){
                $this->type = $all_groups[$this->id]['type'];
            }

            // get ad count; default is 1
            if(isset($all_groups[$this->id]['ad_count'])){
                $this->ad_count = (int) $all_groups[$this->id]['ad_count'];
            }
        }

        /**
         * control the output of the group by type and amount of ads
         *
         * @since 1.4.8
         * @return str $output output of ad(s) by ad
         */
        public function output(){

            if(!$this->id) return;

            // load ads
            $ads = $this->load_all_ads();
            if ( $ads === array() ) { return; }

            // get ad weights serving as an order here
            $weights = $this->get_ad_weights();
            asort($weights);

            // if ads and weights don’t have the same keys, update weights array
            if ( (count( $weights ) == 0 && count( $ads ) > 0) || count( $weights ) != count( $ads ) || array_diff_key( $weights, $ads ) != array()
                    || array_diff_key( $ads, $weights ) != array() ) {
                $this->update_ad_weights();
                $weights = $this->ad_weights;
            }

            // order ads based on group type
            switch($this->type){
                case 'ordered' :
                    $ordered_ad_ids = array_keys($weights);
                    break;
                default : // default
                    $ordered_ad_ids = $this->shuffle_ads($ads, $weights);
            }

            // load the ad outpur
            $output = '';
            $ads_displayed = 0;
            foreach ( $ordered_ad_ids as $_ad_id ) {
                // load the ad object
                $ad = new Advads_Ad( $_ad_id );
                if ( $ad->can_display() ) {
                    $output .= $ad->output();
                    $ads_displayed++;
                    if($ads_displayed === $this->ad_count) break;
                }
                // break the loop when maximum ads are reached
            }

            // add the group to the global output array
            $advads = Advanced_Ads::get_instance();
            $advads->current_ads[] = array('type' => 'group', 'id' => $this->id, 'title' => $this->name);

            // filter again, in case a developer wants to filter group output individually
            return apply_filters( 'advanced-ads-group-output', $output, $this );
        }

	/**
	 * return all ads from this group
	 *
	 * @since 1.0.0
	 */
	public function get_all_ads() {
		if ( count( $this->ads ) > 0 ) {
			return $this->ads; }
		else {
			return $this->load_all_ads(); }
	}

	/**
	 * load all public ads for this group
	 *
	 * @since 1.0.0
	 * @update 1.1.0 load only public ads
	 * @return arr $ads array with ad (post) objects
	 */
	private function load_all_ads() {

            if(!$this->id) return array();

		$args = array(
			'post_type' => $this->post_type,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'taxonomy' => $this->taxonomy,
			'term' => $this->slug,
			'orderby' => 'id'
		);
		$ads = new WP_Query( $args );

		if ( $ads->have_posts() ) {
			return $this->ads = $this->add_post_ids( $ads->posts );
		} else {
			return $this->ads = array();
		}
	}

	/**
	 * use post ids as keys for ad array
	 *
	 * @since 1.0.0
	 * @param arr $ads array with post objects
	 * @return arr $ads array with post objects with post id as their key
	 * @todo check, if there isn’t a WP function for this already
	 */
	private function add_post_ids(array $ads){

		$ads_with_id = array();
		foreach ( $ads as $_ad ){
			$ads_with_id[$_ad->ID] = $_ad;
		}

		return $ads_with_id;
	}

	/**
	 * shuffle ads based on ad weight
	 *
	 * @since 1.0.0
	 * @param arr $ads array with ad objects
	 * @param arr $weights ad weights
	 * @return arr $shuffled_ads shuffled array with ad ids
	 */
	private function shuffle_ads($ads = array(), $weights) {

		// get a random ad for every ad there is
		$shuffled_ads = array();
		// while non-zero weights are set select random next
		while ( null !== $random_ad_id = $this->get_random_ad_by_weight( $weights ) ) {
			// remove chosen ad from weights array
			unset($weights[$random_ad_id]);
			// put random ad into shuffled array
			if ( ! empty($ads[$random_ad_id]) ) {
				$shuffled_ads[] = $random_ad_id; }
		}

		return $shuffled_ads;
	}

	/**
	 * get random ad by ad weight
	 *
	 * @since 1.0.0
	 * @param array $ad_weights e.g. array(A => 2, B => 3, C => 5)
	 * @source applied with fix for order http://stackoverflow.com/a/11872928/904614
	 */
	private function get_random_ad_by_weight(array $ad_weights) {

		// use maximum ad weight for ads without this
		// ads might have a weight of zero (0); to avoid mt_rand fail assume that at least 1 is set.
		$max = array_sum( $ad_weights );
		if ( $max < 1 ) {
			return ;
		}

		$rand = mt_rand( 1, $max );

		foreach ( $ad_weights as $ad_id => $_weight ) {
			$rand -= $_weight;
			if ( $rand <= 0 ) {
				return $ad_id;
			}
		}
	}

	/**
	 * get weights of ads in this group
	 *
	 * @since 1.0.0
	 */
	public function get_ad_weights() {
		// load and save ad weights if not yet set
		if ( $this->ad_weights == 0 ) {
			$weights = get_option( 'advads-ad-weights', array() );
			if ( isset($weights[$this->id]) ) {
				return $this->ad_weights = $weights[$this->id];
			}
		} elseif ( is_array( $this->ad_weights ) ) { // return ad weigths if not empty
			return $this->ad_weights;
		}

		// return empty array
		return array();
	}

        /**
	 * save ad group information that are not included in terms or ad weight
	 *
	 * @since 1.4.8
	 * @param arr $args group arguments
	 */
	public function save($args = array()) {

            $defaults = array( 'type' => 'default', 'ad_count' => 1 );
            $args = wp_parse_args($args, $defaults);

            // get global ad group option
            $groups = get_option( 'advads-ad-groups', array() );

            $groups[$this->id] = $args;

            update_option( 'advads-ad-groups', $groups );
	}

	/**
	 * save ad group weight (into global ad weight array)
	 *
	 * @since 1.0.0
	 * @param arr|str $weights array with ad weights (key: ad id; value: weight)
	 */
	public function save_ad_weights($weights = '') {

		// allow only arrays and empty string
		if ( ! is_array( $weights ) && $weights = '' ) {
			return; }

		$global_weights = get_option( 'advads-ad-weights', array() );

		$global_weights[$this->id] = $this->sanitize_ad_weights( $weights );

		update_option( 'advads-ad-weights', $global_weights );

		// refresh ad weights after update to avoid conflict
		$this->ad_weights = $global_weights[$this->id];
	}

	/**
	 * update ad weight based on current ads for the group and ad weight
	 *
	 * @since 1.0.0
	 */
	private function update_ad_weights(){
		$ads = $this->get_all_ads();
		$weights = $this->get_ad_weights();

		$new_weights = array();
		// use only ads assigned to the group
		foreach ( $ads as $_ad ){
			if ( isset($weights[$_ad->ID]) ){
				$new_weights[$_ad->ID] = $weights[$_ad->ID];
			} else {
				// if no weight is given, use maximum default value
				$new_weights[$_ad->ID] = self::MAX_AD_GROUP_WEIGHT;
			}
		}

		$this->save_ad_weights( $new_weights );
	}

	/**
	 * sanitize ad weights
	 *
	 * @since 1.0.0
	 * @param arr $weights ad weights array with (key: ad id; value: weight)
	 */
	private function sanitize_ad_weights($weights = array()) {

		if ( ! is_array( $weights ) ) {
			return ''; }

		$sanitized_weights = array();
		foreach ( $weights as $_ad_id => $_weight ) {
			$_ad_id = absint( $_ad_id );
			$_weight = absint( $_weight );
			$sanitized_weights[$_ad_id] = $_weight;
		}

		return $sanitized_weights;
	}

}

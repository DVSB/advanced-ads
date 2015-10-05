<?php

/**
 * Advanced Ads.
 *
 * @package   Advanced_Ads_Admin
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2013-2015 Thomas Maier, webgilde GmbH
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * @package Advanced_Ads
 * @author  Thomas Maier <thomas.maier@webgilde.com>
 */
class Advanced_Ads {

	/**
	 * post type slug
	 *
	 * @since   1.0.0
	 * @var     string
	 */
	const POST_TYPE_SLUG = 'advanced_ads';

	/**
	 * ad group slug
	 *
	 * @since   1.0.0
	 * @var     string
	 */
	const AD_GROUP_TAXONOMY = 'advanced_ads_groups';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	private static $instance = null;

	/**
	 * array with ads currently delivered in the frontend
	 */
	public $current_ads = array();

	/**
	 * ad types
	 */
	public $ad_types = array();

	/**
	 * plugin options
	 *
	 * @since   1.0.1
	 * @var     array (if loaded)
	 */
	protected $options = false;

	/**
	 * interal plugin options – set by the plugin
	 *
	 * @since   1.4.5
	 * @var     array (if loaded)
	 */
	protected $internal_options = false;

	/**
	 * list of bots and crawlers to exclude from ad impressions
	 *
	 * @since 1.4.9
	 * @var array list of bots
	 */
	protected $bots = array('008','ABACHOBot','Accoona-AI-Agent','AddSugarSpiderBot','ADmantX','AhrefsBot','alexa','AnyApexBot','appie','Apple-PubSub','Arachmo','Ask Jeeves','avira.com','B-l-i-t-z-B-O-T','Baiduspider','BecomeBot','BeslistBot','BillyBobBot','Bimbot','Bingbot','BLEXBot','BlitzBOT','boitho.com-dc','boitho.com-robot','bot','btbot','CatchBot','Cerberian Drtrs','Charlotte','ConveraCrawler','cosmos','Covario IDS','crawler','CrystalSemanticsBot','curl','DataparkSearch','DiamondBot','Discobot','Dotbot','EmeraldShield.com WebBot','envolk[ITS]spider','EsperanzaBot','Exabot','expo9','facebookexternalhit','FAST Enterprise Crawler','FAST-WebCrawler','FDSE robot','Feedfetcher-Google','FindLinks','Firefly','froogle','FurlBot','FyberSpider','g2crawler','Gaisbot','GalaxyBot','genieBot','Genieo','Gigabot','Girafabot','Googlebot','Googlebot-Image','GrapeshotCrawler','GurujiBot','HappyFunBot','heritrix','hl_ftien_spider','Holmes','htdig','https://developers.google.com','ia_archiver','iaskspider','iCCrawler','ichiro','igdeSpyder','InfoSeek','inktomi','IRLbot','IssueCrawler','Jaxified Bot','Jyxobot','KoepaBot','Kraken','L.webis','LapozzBot','Larbin','LDSpider','LexxeBot','Linguee','Bot','LinkWalker','lmspider','looksmart','lwp-trivial','mabontland','magpie-crawler','Mail.RU_Bot','MaxPointCrawler','Mediapartners-Google','MJ12bot','Mnogosearch','mogimogi','MojeekBot','Moreoverbot','Morning Paper','msnbot','MSRBot','MVAClient','mxbot','NationalDirectory','NetResearchServer','NetSeer Crawler','NewsGator','NG-Search','nicebot','noxtrumbot','Nusearch','Spider','Nutch crawler','NutchCVS','Nymesis','obot','oegp','omgilibot','OmniExplorer_Bot','OOZBOT','Orbiter','PageBitesHyperBot','Peew','polybot','Pompos','PostPost','proximic','Psbot','PycURL','Qseero','rabaz','Radian6','RAMPyBot','Rankivabot','RufusBot','SandCrawler','savetheworldheritage','SBIder','Scooter','ScoutJet','Scrubby','SearchSight','Seekbot','semanticdiscovery','Sensis','Web Crawler','SEOChat::Bot','SeznamBot','Shim-Crawler','ShopWiki','Shoula robot','silk','Sitebot','Snappy','sogou spider','Sogou web spider','Sosospider','Spade','Speedy Spider','Sqworm','StackRambler','suggybot','SurveyBot','SynooBot','TechnoratiSnoop','TECNOSEEK','Teoma','TerrawizBot','TheSuBot','Thumbnail.CZ','robot','TinEye','truwoGPS','TurnitinBot','TweetedTimes Bot','TwengaBot','updated','URL_Spider_SQL','Urlfilebot','Vagabondo','VoilaBot','voltron','Vortex','voyager','VYU2','WebAlta Crawler','WebBug','webcollage','WebFindBot','WebIndex','Websquash.com','WeSEE:Ads','wf84','Wget','WoFindeIch Robot','WomlpeFactory','WordPress','Xaldon_WebSpider','yacy','Yahoo! Slurp','Yahoo! Slurp China','YahooSeeker','YahooSeeker-Testing','YandexBot','YandexImages','Yasaklibot','Yeti','YodaoBot','yoogliFetchAgent','YoudaoBot','Zao','Zealbot','zspider','ZyBorg');

	/**
	 *
	 * @var Advanced_Ads_Model
	 */
	protected $model;

	/**
	 *
	 * @var Advanced_Ads_Plugin
	 */
	protected $plugin;

	/**
	 *
	 * @var Advanced_Ads_Select
	 */
	protected $ad_selector;

	private function __construct() {
		$this->plugin = Advanced_Ads_Plugin::get_instance();
		$this->plugin->set_model($this->get_model());
		$this->ad_selector = Advanced_Ads_Select::get_instance();

		// initialize plugin specific functions
		add_action( 'init', array( $this, 'wp_init' ) );

		// only when not doing ajax
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			Advanced_Ads_Ajax::get_instance();
		}
		add_action( 'plugins_loaded', array( $this, 'wp_plugins_loaded' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    Advanced_Ads    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 *
	 * @return Advanced_Ads_Model
	 */
	public function get_model() {

		global $wpdb;

		if ( ! isset($this->model) ) {
			$this->model = new Advanced_Ads_Model( $wpdb );
		}

		return $this->model;
	}

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	public function wp_plugins_loaded()
	{
		$options = $this->plugin->options();

		// register hook for global constants
		add_action( 'wp', array( $this, 'set_disabled_constant' ) );

		// setup default ad types
		add_filter( 'advanced-ads-ad-types', array( $this, 'setup_default_ad_types' ), 5 );

		// register hooks and filters for auto ad injection
		$this->init_injection( $options );

		// allow add-ons to hook
		do_action( 'advanced-ads-plugin-loaded' );

		// manipulate sidebar widget
		add_filter( 'dynamic_sidebar_params', array( $this, 'manipulate_widget_output' ) );
	}

	/**
	 * init / load plugin specific functions and settings
	 *
	 * @since 1.0.0
	 */
	public function wp_init(){
		// load ad post types
		$this->create_post_types();
		// set ad types array
		$this->set_ad_types();
	}

	/**
	 * define ad types with their options
	 *
	 * name => publically readable name
	 * description => publically readable description
	 * editor => kind of editor: text (normal text field), content (WP content field), none (no content field)
	 *  will display text field, if left empty
	 *
	 * @since 1.0.0
	 */
	public function set_ad_types() {

		/**
		 * load default ad type files
		 * custom ad types can also be loaded in your own plugin or functions.php
		 */
		$types = array();

		/**
		 * developers can add new ad types using this filter
		 * see classes/ad-type-content.php for an example for an ad type and usage of this filter
		*/
		$this->ad_types = apply_filters( 'advanced-ads-ad-types', $types );
	}

	public function init_injection($options) {
		// -TODO abstract
		add_action( 'wp_head', array( $this, 'inject_header' ), 20 );
		add_action( 'wp_footer', array( $this, 'inject_footer' ), 20 );
		$content_injection_priority = isset( $options['content-injection-priority'] ) ? intval( $options['content-injection-priority'] ) : 100;
		add_filter( 'the_content', array( $this, 'inject_content' ), $content_injection_priority );
	}

	/**
	 * set global constant that prevents ads from being displayed on the current page view
	 *
	 * @since 1.3.10
	 */
	public function set_disabled_constant(){

		global $post, $wp_the_query;

		// don't set the constant if already defined
		if ( defined( 'ADVADS_ADS_DISABLED' ) ) { return; }

		$options = $this->plugin->options();

		// check if ads are disabled completely
		if ( ! empty($options['disabled-ads']['all']) ){
			define( 'ADVADS_ADS_DISABLED', true );
			return;
		}

		// check if ads are disabled from 404 pages
		if ( $wp_the_query->is_404() && ! empty($options['disabled-ads']['404']) ){
			define( 'ADVADS_ADS_DISABLED', true );
			return;
		}

		// check if ads are disabled from non singular pages (often = archives)
		if ( ! $wp_the_query->is_singular() && ! empty($options['disabled-ads']['archives']) ){
			define( 'ADVADS_ADS_DISABLED', true );
			return;
		}

		// check if ads are disabled in secondary queries
		if ( ! is_main_query() && ! empty($options['disabled-ads']['secondary']) ){
			define( 'ADVADS_ADS_DISABLED', true );
			return;
		}

		// check if ads are disabled on the current page
		if ( $wp_the_query->is_singular() && isset($post->ID) ){
			$post_ad_options = get_post_meta( $post->ID, '_advads_ad_settings', true );

			if ( ! empty($post_ad_options['disable_ads']) ){
				define( 'ADVADS_ADS_DISABLED', true );
			}
		};
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since  1.0.0
	 * @return Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin->get_plugin_slug();
	}

	/**
	 * add plain and content ad types to the default ads of the plugin using a filter
	 *
	 * @since 1.0.0
	 *
	 */
	function setup_default_ad_types($types){
		$types['plain'] = new Advanced_Ads_Ad_Type_Plain(); /* plain text and php code */
		$types['content'] = new Advanced_Ads_Ad_Type_Content(); /* rich content editor */
		return $types;
	}

	/**
	 * log error messages when debug is enabled
	 *
	 * @since 1.0.0
	 * @link http://www.smashingmagazine.com/2011/03/08/ten-things-every-wordpress-plugin-developer-should-know/
	 */
	static function log($message) {
		if ( true === WP_DEBUG ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( __('Advanced Ads Error following:', 'advanced-ads' ) );
				error_log( print_r( $message, true ) );
			} else {
				$message = sprintf( __( 'Advanced Ads Error: %s', 'advanced-ads' ), $message );
				error_log( $message );
			}
		}
	}

	// compat method
	public function options() {
		return $this->plugin->options();
	}

	// compat method
	public function internal_options() {
		return $this->plugin->internal_options();
	}

	/**
	 * injected ad into header
	 *
	 * @since 1.1.0
	 */
	public function inject_header(){
		$placements = get_option( 'advads-ads-placements', array() );
		foreach ( $placements as $_placement_id => $_placement ){
			if ( isset($_placement['type']) && 'header' == $_placement['type'] ){
				$_options = isset( $_placement['options'] ) ? $_placement['options'] : array();
				echo Advanced_Ads_Select::get_instance()->get_ad_by_method( $_placement_id, Advanced_Ads_Select::PLACEMENT, $_options );
			}
		}
	}

	/**
	 * injected ads into footer
	 *
	 * @since 1.1.0
	 */
	public function inject_footer(){
		$placements = get_option( 'advads-ads-placements', array() );
		foreach ( $placements as $_placement_id => $_placement ){
			if ( isset($_placement['type']) && 'footer' == $_placement['type'] ){
				$_options = isset( $_placement['options'] ) ? $_placement['options'] : array();
				echo Advanced_Ads_Select::get_instance()->get_ad_by_method( $_placement_id, Advanced_Ads_Select::PLACEMENT, $_options );
			}
		}
	}

	/**
	 * injected ad into content (before and after)
	 * displays ALL ads
	 *
	 * @since 1.1.0
	 * @param str $content post content
	 */
	public function inject_content($content = ''){
		// run only within the loop on single pages of public post types
		$public_post_types = get_post_types( array( 'public' => true, 'publicly_queryable' => true ), 'names', 'or' );

		// make sure that no ad is injected into another ad
		if ( get_post_type() == self::POST_TYPE_SLUG ){
			return $content;
		}

		// check if admin allows injection in all places
		$options = $this->plugin->options();
		if( ! isset( $options['content-injection-everywhere'] ) ){
		    // check if this is a singular page within the loop
		    if ( ! is_singular( $public_post_types ) || ! in_the_loop() ) { return $content; }
		}

		$placements = get_option( 'advads-ads-placements', array() );

		if( ! apply_filters( 'advanced-ads-can-inject-into-content', true, $content, $placements )){
			return $content;
		}

		foreach ( $placements as $_placement_id => $_placement ){
			if ( empty($_placement['item']) || ! isset($_placement['type']) ) { continue; }
			$_options = isset( $_placement['options'] ) ? $_placement['options'] : array();

			// check if injection is ok for a specific placement id
			if( ! apply_filters( 'advanced-ads-can-inject-into-content-' . $_placement_id, true, $content, $_placement_id )){
				continue;
			}

			switch ( $_placement['type'] ) {
				case 'post_top':
				    // TODO broken: does not serve placement but serves ad directly
					$content = Advanced_Ads_Select::get_instance()->get_ad_by_method( $_placement_id, Advanced_Ads_Select::PLACEMENT, $_options ) . $content;
					break;
				case 'post_bottom':
					$content .= Advanced_Ads_Select::get_instance()->get_ad_by_method( $_placement_id, Advanced_Ads_Select::PLACEMENT, $_options );
					break;
				case 'post_content':
					$content = Advanced_Ads_Placements::inject_in_content( $_placement_id, $_options, $content );
					break;
			}
		}

		return $content;
	}

	/**
	 * load all ads based on WP_Query conditions
	 *
	 * @deprecated 1.4.8 use model class
	 * @since 1.1.0
	 * @param arr $args WP_Query arguments that are more specific that default
	 * @return arr $ads array with post objects
	 */
	static function get_ads($args = array()){
		return self::get_instance()->get_model()->get_ads($args);
	}

	/**
	 * load all ad groups
	 *
	 * @deprecated 1.4.8 use model class
	 * @since 1.1.0
	 * @param arr $args array with options
	 * @return arr $groups array with ad groups
	 * @link http://codex.wordpress.org/Function_Reference/get_terms
	 */
	static function get_ad_groups($args = array()){
		return self::get_instance()->get_model()->get_ad_groups($args);
	}

	/**
	 * get the array with ad placements
	 *
	 * @since 1.1.0
	 * @deprecated 1.4.8 use model
	 * @return arr $ad_placements
	 */
	static public function get_ad_placements_array(){
		return self::get_instance()->get_model()->get_ad_placements_array();
	}

	/**
	 *
	 * @deprecated 1.4.8 use model
	 * @return array
	 */
	public static function get_ad_conditions() {
		return self::get_instance()->get_model()->get_ad_conditions();
	}

	/**
	 * general check if ads can be displayed for the whole page impression
	 *
	 * @since 1.4.9
	 * @return bool true, if ads can be displayed
	 * @todo move this to set_disabled_constant()
	 */
	public function can_display_ads(){

		// check global constant if ads are enabled or disabled
		if ( defined( 'ADVADS_ADS_DISABLED' ) ) {
			return false;
		}

		$options = $this->options();
		$see_ads_capability = isset($options['hide-for-user-role']) && $options['hide-for-user-role'] != '' ? $options['hide-for-user-role'] : false;

		// check if user is logged in and if so if users with his rights can see ads
		if ( $see_ads_capability && is_user_logged_in() && current_user_can( $see_ads_capability ) ) {
			return false;
		}

		// check bots if option is enabled
		if( isset($options['block-bots']) && $options['block-bots'] && $this->is_bot() ) {
			return false;
		}

		return true;
	}

	/**
	 * check if the current user agent is given or a bot
	 *
	 * @since 1.4.9
	 * @return bool true if the current user agent is empty or a bot
	 */
	public function is_bot(){
		$bots = apply_filters('advanced-ads-bots', $this->bots);
		$bots = implode('|', $bots);
		$bots = preg_replace('@[^-_;/|\][ a-z0-9]@i', '', $bots);
		$regex = "@$bots@i";

		if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] !== '') {
			$agent = $_SERVER['HTTP_USER_AGENT'];

			return preg_match($regex, $agent) === 1;
		}

		return true;
	}

	/**
	 * Registers ad post type and group taxonomies
	 *
	 * @since 1.0.0
	 */
	public function create_post_types() {
		if ( 1 !== did_action( 'init' ) ) {
			return;
		}

		// register ad group taxonomy
		if ( ! taxonomy_exists( Advanced_Ads::AD_GROUP_TAXONOMY ) ){
			$post_type_params = $this->get_group_taxonomy_params();
			register_taxonomy( Advanced_Ads::AD_GROUP_TAXONOMY, array( Advanced_Ads::POST_TYPE_SLUG ), $post_type_params );
		}

		// register ad post type
		if ( ! post_type_exists( Advanced_Ads::POST_TYPE_SLUG ) ) {
			$post_type_params = $this->get_post_type_params();
			register_post_type( Advanced_Ads::POST_TYPE_SLUG, $post_type_params );
		}
	}

	/**
	 * Defines the parameters for the ad post type taxonomy
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_group_taxonomy_params(){
		$labels = array(
			'name'              => _x( 'Ad Groups', 'ad group general name', 'advanced-ads' ),
			'singular_name'     => _x( 'Ad Group', 'ad group singular name', 'advanced-ads' ),
			'search_items'      => __( 'Search Ad Groups', 'advanced-ads' ),
			'all_items'         => __( 'All Ad Groups', 'advanced-ads' ),
			'parent_item'       => __( 'Parent Ad Groups', 'advanced-ads' ),
			'parent_item_colon' => __( 'Parent Ad Groups:', 'advanced-ads' ),
			'edit_item'         => __( 'Edit Ad Group', 'advanced-ads' ),
			'update_item'       => __( 'Update Ad Group', 'advanced-ads' ),
			'add_new_item'      => __( 'Add New Ad Group', 'advanced-ads' ),
			'new_item_name'     => __( 'New Ad Groups Name', 'advanced-ads' ),
			'menu_name'         => __( 'Groups', 'advanced-ads' ),
			'not_found'         => __( 'No Ad Group found', 'advanced-ads' ),
		);

		$args = array(
			'public'	    => false,
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_admin_column' => true,
			'query_var'         => false,
			'rewrite'           => false,
		);

		return $args;
	}

	/**
	 * Defines the parameters for the custom post type
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_post_type_params() {
		$labels = array(
			'name' => __( 'Ads', 'advanced-ads' ),
			'singular_name' => __( 'Ad', 'advanced-ads' ),
			'add_new' => __( 'New Ad', 'advanced-ads' ),
			'add_new_item' => __( 'Add New Ad', 'advanced-ads' ),
			'edit' => __( 'Edit', 'advanced-ads' ),
			'edit_item' => __( 'Edit Ad', 'advanced-ads' ),
			'new_item' => __( 'New Ad', 'advanced-ads' ),
			'view' => __( 'View', 'advanced-ads' ),
			'view_item' => __( 'View the Ad', 'advanced-ads' ),
			'search_items' => __( 'Search Ads', 'advanced-ads' ),
			'not_found' => __( 'No Ads found', 'advanced-ads' ),
			'not_found_in_trash' => __( 'No Ads found in Trash', 'advanced-ads' ),
			'parent' => __( 'Parent Ad', 'advanced-ads' ),
		);

		$post_type_params = array(
			'labels' => $labels,
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'hierarchical' => false,
			'capability_type' => 'page',
			'has_archive' => false,
			'rewrite' => array( 'slug' => ADVADS_SLUG ),
			'query_var' => true,
			'supports' => array( 'title' ),
			'taxonomies' => array( Advanced_Ads::AD_GROUP_TAXONOMY )
		);

		return apply_filters( 'advanced-ads-post-type-params', $post_type_params );
	}

	/**
	 * manipulate output of ad widget
	 *
	 * @since 1.6.8.2
	 * @param arr $params widget and sidebar params
	 */
	public function manipulate_widget_output( $params = array() ){

		    if( $params[0]['widget_name'] === 'Advanced Ads' ){

			    $options = $this->plugin->options();
			    // hide id by default (when options are empty) or when option is enabled
			    if( $options === array() || ( isset( $options['remove-widget-id'] ) && $options['remove-widget-id'] ) ){
				    $pattern = '#\s(id)=("|\')[^"^\']+("|\')#';
				    $params[0]['before_widget'] = preg_replace( $pattern, '', $params[0]['before_widget']);
			    }
		    }

	    return $params;
	}
}

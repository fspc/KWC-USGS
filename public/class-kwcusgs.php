<?php
/**
 *
 *
 * @package   USGS Steam Flow Data
 * @author    Chris Kindred <Chris@kindredwebconsulting.com>
 * @license   GPL-2.0+
 * @link      http://www.kindredwebconsulting.com
 * @copyright 2015 Kindred Web Consulting
 */

class kwc_usgs {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '2.4.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'kwcusgs';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_shortcode( "USGS", array( $this, 'USGS' ) );
		add_shortcode( "usgs_custom", array( $this, 'usgs_custom' ) );
		add_shortcode( "nws_custom", array( $this, 'nws_custom' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		//  add_action( '@TODO', array( $this, 'action_method_name' ) );
		//  add_filter( '@TODO', array( $this, 'filter_method_name' ) );;	

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param int     $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );

	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );

	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */

	/**
	 * This needs to be split into different functions
	 *
	 * @since 	1.0.0
	 */

	public function USGS( $atts, $content = null ) {
		extract( shortcode_atts(
				array(
					'location'  => '09080400',
					'title'  => null,
					'graph'  => null
				), $atts ) );

		$thePage = get_transient( 'kwc_usgs-' . $location . $graph . $title );

		if ( !$thePage ) {
			$url = "http://waterservices.usgs.gov/nwis/iv?site=$location&parameterCd=00010,00060,00065&format=waterml";

			$response = wp_remote_get( $url );
			$data = wp_remote_retrieve_body( $response );

			if ( ! $data ) {
				return 'USGS Not Responding.';
			}

			$data = str_replace( 'ns1:', '', $data );

			$xml_tree = simplexml_load_string( $data );
			if ( False === $xml_tree ) {
				return 'Unable to parse USGS\'s XML';
			}		
			
			if ( ! isset( $title )  ) {
				$SiteName = $xml_tree->timeSeries->sourceInfo->siteName;
			} else {
				if ( $title == '' ) {
					$SiteName = $xml_tree->timeSeries->sourceInfo->siteName;
				} else {
					$SiteName = $title;
				}
			}

			$thePage = "<div class='KWC_USGS clearfix'>
							<h3 class='header'>$SiteName</h3>
								<ul class='sitevalues'>";
			$graphflow = "";
			$graphgage = "";
			foreach ( $xml_tree->timeSeries as $site_data ) {
				if ( $site_data->values->value == '' ) {
					$value = '-';
				} else if ( $site_data->values->value == -999999 ) {
						$value = 'UNKNOWN';
						$provisional = '-';
				} else {
					$desc = $site_data->variable->variableName;
					switch ( $site_data->variable->variableCode ) {
					case "00010":
						$value  = $site_data->values->value;
						$degf   = ( 9 / 5 ) * (float)$value + 32;
						$watertemp      = $degf;
						$watertempdesc  = "&deg; F";
						$thePage .= "<li class='watertemp'>Water Temp: $watertemp $watertempdesc</li>";
						break;

					case "00060":
						$splitDesc = explode( ",", $desc );
						$value  = $site_data->values->value;
						$streamflow     = $value;
						$streamflowdesc = $splitDesc[1];
						$thePage .= "<li class='flow'>Flow: $streamflow $streamflowdesc</li>";
						$graphflow = "<img src='http://waterdata.usgs.gov/nwisweb/graph?site_no=$location&parm_cd=00060" . "&" . rand() . "'/>";
						break;

					case "00065":
						$splitDesc = explode( ",", $desc );
						$value  = $site_data->values->value;
						$gageheight = $value;
						$gageheightdesc = $splitDesc[1];
						$thePage .= "<li class='gageheight'>Water Level: $gageheight $gageheightdesc</li>";
						$graphgage = "<img src='http://waterdata.usgs.gov/nwisweb/graph?site_no=$location&parm_cd=00065" . "&" . rand() . "'/>";
						break;
					}
				}
			}
			$thePage .=  "</ul>";
			if ( isset( $graph ) ) {
				if ( $graph == 'show' ) {
					$thePage .= "<div class='clearfix'>";
					$thePage .= $graphgage . $graphflow;
					$thePage .= "</div>";
				}
			}
			$thePage .= "<a class='clearfix' href='http://waterdata.usgs.gov/nwis/uv?$location' target='_blank'>USGS</a>";
			$thePage .= "</div>";

			set_transient( 'kwc_usgs-' . $location . $graph . $title, $thePage, 60 * 15 );
		}
		return $thePage;
	}
	
	/**
	 * Custom USGS stats for multiple (100 max) locations [location] and/or (100 max) parameters [parameters] and/or date_range and/or order
	 *
	 *	[name] Provide a custom classname for enclosing div
	 *
	 * [date_range] Date Range in yyyy-mm-dd,yyyy-mm-dd or previous,number,hh::mm (24hr)
	 * (last x days before today at a certain time (optional, otherwise all times are shown)) 
	 *
	 * [order] Order only makes sense with date range, and takes two options asc (default) or desc (or anything else).
	 *
	 * No styling:  <div class="site parameter" datetime="timestamp">value</div>
	 * Automatically converts C to F for 00010
	 * attribute datetime value is in javascript format
	 *
	 */	
	
	public function usgs_custom( $atts, $content = null ) {
		extract( shortcode_atts(
				array(
					'name' => 'usgs_custom',
					'location'  => '03071590,03071600,03071605',
					'parameters' => '00010,00045,00065,00095,00300,00400,62614',
					'date_range' => null,
					'order' => 'asc'
				), $atts ) );

		$thePage = get_transient( 'usgs_custom-' . $name . $location . $date_range . $parameters . $order );

		if ( !$thePage ) {	
		
			$date_ranges = explode(',',$date_range);
			$hour_minutes = false;
			$value_order = [];
		
			if ($date_ranges[0] === 'previous') {
				
				if(!$date_ranges[1] || !is_numeric($date_ranges[1]) ) {
					return "Second argument to date_range must be a number.\n";
				}
				// find todays date, and find the previous day ranges
				$today = strtotime(date('Y-m-d'));
				$startdt = date('Y-m-d',strtotime("-" . $date_ranges[1] . " day", $today));
				$enddt = date('Y-m-d',strtotime("-1 day", $today));
				
				if ( $date_ranges[2] ) {
					$hour_minutes = $date_ranges[2];				
				}					
										
			}	
			
			if($date_ranges[0] === 'previous') {
				$url = "http://waterservices.usgs.gov/nwis/iv?site=$location&parameterCd=$parameters&startDT=$startdt&endDT=$enddt&format=waterml";
			} else {
				$url = "http://waterservices.usgs.gov/nwis/iv?site=$location&parameterCd=$parameters&format=waterml";
			}
				
			$response = wp_remote_get( $url );
			$data = wp_remote_retrieve_body( $response );

			if ( ! $data ) {
				return 'United States Geological Survey not Responding.';
			}

			$data = str_replace( 'ns1:', '', $data );

			$xml_tree = simplexml_load_string( $data );
			if ( False === $xml_tree ) {
				return 'Unable to parse United States Geological Survey XML';
			}
			//PC::debug($xml_tree);		
								
			foreach ( $xml_tree->timeSeries as $site_data ) {						
						
				if ( $site_data->values->value == '' ) {
					$value = '-';
					
					// Create false data if a site is down for the season, or for some other unknown reason
					
					$SiteName = $site_data->sourceInfo->siteName;
					$SiteName = preg_replace('/[^A-Za-z0-9_]/', '', strtolower(preg_replace('/\s+/', '_', $SiteName)));
					
					$description = explode(',', $site_data->variable->variableDescription);
					$description = strtolower(preg_replace('/\s+/', '_', $description[0]));
					
					if($hour_minutes) {
						for ($x = 0; $x < $date_ranges[1]; $x++) {
							$value_order[] = "<div class='" . $SiteName . " " . $description . "' datetime='n/a'>n/a</div>";
						}
					}				
						
				} else if ( $site_data->values->value == -999999 ) {
						$value = 'UNKNOWN';
						$provisional = '-';
				} else {
					
					// space to underscore; all lower case
					$SiteName = $site_data->sourceInfo->siteName;
					$SiteName = preg_replace('/[^A-Za-z0-9_]/', '', strtolower(preg_replace('/\s+/', '_', $SiteName)));
					
					$description = explode(',', $site_data->variable->variableDescription);
					$description = strtolower(preg_replace('/\s+/', '_', $description[0]));

					$variable_name = $site_data->variable->variableName;		
					
					if((string)$site_data->variable->variableCode === '00010') {
						$temperature = true;	
					} else {
						$temperature = false;
					}
											
					$splitDesc = explode( ",", $variable_name );
					$defaultdesc = end($splitDesc);
					
					foreach ($site_data->values->value as $value) {
	
						$datetime = strtotime($value->attributes()->dateTime) * 1000;
						if($temperature) {
							$value   = ( 9 / 5 ) * (float)$value + 32;
							$defaultdesc  = "&deg;F";
						}							
						
						if($hour_minutes) {
							$value_hour_minutes = date('H:i',$datetime / 1000);
							if($hour_minutes === $value_hour_minutes) {
								$value_order[] = "<div class='" . $SiteName . " " . $description . "' datetime='" . $datetime . 
												"'>$value $defaultdesc</div>";
							}
						} else {
							$value_order[] = "<div class='" . $SiteName . " " . $description . "' datetime='" . $datetime . 
											"'>$value $defaultdesc</div>";
						}
					} // foreach value loop
					
				}  // end else
			}  // foreach xml_tree as site data
			
			// Putting it all together
			$thePage = "<div class='" . $name . "'>";
						
			if($value_order && $order !== 'asc') {
				$value_order = array_reverse($value_order);
				$thePage .= implode('',$value_order);
				$thePage .= "</div>";
			} else {
				$thePage .= implode('',$value_order);
				$thePage .= "</div>";			
			}
			

			set_transient( 'usgs_custom-' . $name . $location . $date_range . $parameters . $order, $thePage, 60 * 5 );
		} // end found no transient

		// Add a custom hook
		$thePage = apply_filters('water_the_theme', $thePage);
				
		return $thePage;
	}	

	/**
	 * Custom NWS stats for multiple locations and multiple attributes (ft_msl, stage, flow) 
	 * from zerodatum and observed (primary,secondary)
	 * Schema: http://weather.gov/ohd/hydroxc/schemas/hydrogen/HydroGenData.xsd
	 * No styling:  <div class="site">value</div>
	 * Automatically converts C to F
	 * Stores datetime (valid) in javascript format
	 *
	 */	
	 
	public function nws_custom( $atts, $content = null ) {
		extract( shortcode_atts(
				array(
					'name' => 'nws_custom',
					'location'  => 'pmaw2,llpw2,lldp1',
					'parameters' => null,
					'date_range' => 'current',
					'order' => 'asc'
				), $atts ) );

			$locations = explode(',', $location);

			foreach ($locations as $location) {

				$thePage = get_transient('nws_custom-' . $name . $location . $date_range . $parameters . $order );
	
				if ( !$thePage ) {
				
				$url = "http://water.weather.gov/ahps2/hydrograph_to_xml.php?gage=$location&output=xml";

	
				$response = wp_remote_get( $url );
				$data = wp_remote_retrieve_body( $response );
	
				if ( ! $data ) {
					return 'NWS Not Responding.';
				}

	
				$xml_tree = simplexml_load_string( $data );
				if ( False === $xml_tree ) {
					return 'Unable to parse NWS XML';
				}
				
				// space to underscore; all lower case; only special character allowed is underscored
				$SiteName = (string)$xml_tree->attributes()->name;	
				$SiteName = preg_replace('/[^A-Za-z0-9_]/', '', strtolower(preg_replace('/\s+/', '_', $SiteName)));
			
				$thePage = "<div class='$name'>";

				$waterlevel = (string)$xml_tree->zerodatum;								
						
				$c = 0;			
				foreach ( $xml_tree->observed->datum as $datum ) {
						
					// in javascript this works out of the box (* 1000)
					$datetime = strtotime($datum->valid) * 1000;
					$gageheight = $datum->primary;
					$waterflow = (string)$datum->secondary;
					
					if ($waterflow === '-999' || $waterflow === 0) {
						$waterflow = '0 cfs';						
					} else {
						$waterflow = $waterflow * 1000;	
						$waterflow = $waterflow . " cfs";					
					}
							
					
					if($c === 0 && $date_range === 'current') {
						$thePage .= "<div class='$SiteName' datetime='$datetime' waterlevel='$waterlevel' gageheight='$gageheight'>$waterflow</div>";
						break;			
					} else {
						$thePage .= "<div class='$SiteName' datetime='$datetime' gageheight='$gageheight'>$waterflow</div>";					
					}				
					$c++;			
						
				} // foreach xml_tree as site data		
				
			} // foreach NWS location		

			$thePage .= "</div>";

			set_transient( 'nws_custom-' . $name . $location . $date_range . $parameters . $order, $thePage, 60 * 15 );
			}

			return $thePage;
	}	


}


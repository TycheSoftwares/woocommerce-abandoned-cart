<?php

/**
 * 
 * @since 1.0.0
 */
class TS_Faq_Support {
	/**
	* @var string Plugin name
	* @access public 
	*/

	public static $plugin_name = '';

	/**
	 * @var string Plugin prefix
	 * @access public
	 */
	public static $plugin_prefix = '';

	/**
	 * @var string Plugins page path
	 * @access public
	 */
	public static $plugin_page = '';

	/**
	 * @var string Plugins plugin local
	 * @access public
	 */
	public static $plugin_locale = '';

	/**
	 * @var string Plugin folder name
	 * @access public
	 */
	public static $plugin_folder = '';
	/**
	 * @var string  Plugin url
	 * @access public
	 */
	public static $plugin_url = '';
	/**
	 * @var string Template path
	 * @access public
	 */
	public static $template_base = '';
	/**
	 * @var string Slug on Main menu
	 * @access public
	 */
	public static $plugin_slug = '';

	/**
	 * @var array List of all questions and answers.
	 * @access public
	 */
	public static $ts_faq = array ();
	/**
	 * Initialization of hooks where we prepare the functionality to ask use for survey
	 */
	public function __construct( $ts_plugin_mame = '', $ts_plugin_prefix = '', $ts_plugin_page = '', $ts_plugin_locale = '', $ts_plugin_folder_name = '', $ts_plugin_slug = '', $ts_faq_array = array() ) {
		
		self::$plugin_name   = $ts_plugin_mame;
		self::$plugin_prefix = $ts_plugin_prefix;
		self::$plugin_page   = $ts_plugin_page;
		self::$plugin_locale = $ts_plugin_locale;
		self::$plugin_slug   = $ts_plugin_slug;
		self::$ts_faq        = $ts_faq_array;

		//Add a sub menu in the main menu of the plugin if added.
		add_action( self::$plugin_prefix . '_add_submenu', array( &$this, 'ts_add_submenu' ) );

		//Add a tab for FAQ & Support along with other plugin settings tab.
		add_action( self::$plugin_prefix . '_add_settings_tab', array( &$this, 'ts_add_new_settings_tab' ) );
		add_action( self::$plugin_prefix . '_add_tab_content', array( &$this, 'ts_add_tab_content' ) );

		self::$plugin_folder  = $ts_plugin_folder_name; 		
		self::$plugin_url     = $this->ts_get_plugin_url();
		self::$template_base  = $this->ts_get_template_path();
		
	}

	/**
	* Adds a subment to the main menu of the plugin
	* 
	* @since 7.7 
	*/

	public function ts_add_submenu() {
		$page = add_submenu_page( self::$plugin_slug, 'FAQ & Support', 'FAQ & Support', 'manage_woocommerce', 'ts_faq_support_page', array( &$this, 'ts_faq_support_page' ) );

	}

	/** 
	* Add a new tab on the settings page.
	*
	* @since 7.7
	*/
	public function ts_add_new_settings_tab() {
		$faq_support_page = '';
		if( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'faq_support_page' ) {
		    $faq_support_page = "nav-tab-active";
		}
		$ts_plugins_page_url = self::$plugin_page . "&action=faq_support_page" ;
		?>
		<a href="<?php echo $ts_plugins_page_url; ?>" class="nav-tab <?php echo $faq_support_page; ?>"> <?php _e( 'FAQ & Support', self::$plugin_locale ); ?> </a>
		<?php

		
	}

	/**
	* Add content to the new tab added.
	*
	* @since 7.7
	*/

	public function ts_add_tab_content() {
		if( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'faq_support_page' ) {
			$this->ts_faq_support_page();
		}
	}

	/**
	* Adds a page to display the FAQ's and support information
	*
	* @since 7.7
	*/
	public function ts_faq_support_page() {
		ob_start();
		wc_get_template( 'faq-page/faq-page.php', 	
						 array(
								'ts_plugin_name' => self::$plugin_name,
							  	'ts_faq'         => self::$ts_faq
						 ), 
						 self::$plugin_folder, 
						 self::$template_base );
        echo ob_get_clean();
	}

	/**
     * This function returns the plugin url 
     *
     * @access public 
     * @since 7.7
     * @return string
     */
    public function ts_get_plugin_url() {
        return plugins_url() . '/' . self::$plugin_folder;
    }

    /**
    * This function returns the template directory path
    *
    * @access public 
    * @since 7.7
    * @return string
    */
    public function ts_get_template_path() {
    	return untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/';
    } 
}
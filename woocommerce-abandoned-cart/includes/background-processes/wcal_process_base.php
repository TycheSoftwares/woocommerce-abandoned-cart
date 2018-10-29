<?php 

class Wcal_Process_Base {
    
    /**
     * @var WCAL_Background_Process
     */
    protected $process;
    
    /**
     * @var WCAL_Async_Request
     */
    protected $request;
    
    
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        // Hook into that action that'll fire every 15 minutes
        add_action( 'woocommerce_ac_send_email_action',        array( &$this, 'wcal_process_handler' ), 11 );
        
    }
    
    public function init() {
        
        require_once plugin_dir_path( __FILE__ ) . 'wcal-async-request.php';
        require_once plugin_dir_path( __FILE__ ) . 'wcal-background-process.php';
        
        $this->request    = new WCAL_Async_Request();
        $this->process    = new WCAL_Background_Process();
        
    } 
    
    public function wcal_process_handler() {
        // add any new reminder methods added in the future for cron here
        $reminders_list = array( 'emails' );

        if( is_array( $reminders_list ) && count( $reminders_list ) > 0 ) {
            $this->start( $reminders_list );
        }
        
    }
    
    public function start( $reminders_list ) {
        
        $this->handle_all( $reminders_list ); 
        
    }
    public function handle_single() {
        
    }
    
    public function handle_all( $list_reminders ) {

     foreach( $list_reminders as $reminders ) {
         
         $this->process->push_to_queue( $reminders );
     }
        $this->process->save()->dispatch();
    }
    
}
new Wcal_Process_Base();
?>
<?php
namespace UBER\Classes;

class AuthPanel {

    /**
     * Display name for menu and title
     * @var string
     */
    protected $display_name = 'Phanes UberRush';

    /**
     * Settings page slug
     * @var string
     */
    protected $slug = 'phanes-uber';

    /**
     * Database field name
     * @var string
     */
    protected $db_field = 'phanes_uber_license';

    /**
     * License verification api url
     * @var string
     */
    protected $api = 'https://phanes.co/';

    /**
     * Store all notifications
     * @var string
     */
    protected static $_notices;

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_notices', array( $this, 'display_notices' ), 999 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Show license notification
     * @return void
     */
    public function init() {
        if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( isset( $_GET['page'] ) && $_GET['page'] === $this->slug ) ) {
            return;
        }
        
        if ( ! $this->has_valid_license() ) {
            $this->add_notice(
                sprintf(
                    __( '<strong>%s</strong> require your License code to be fully activated. <a href="%s">Add License code</a>', 'pqc' ),
                    $this->display_name,
                    admin_url( 'options-general.php?page=' . $this->slug )
                ), 'error', false
            );
        }
    }

    public function enqueue_assets( $hook ) {
        wp_enqueue_script(
            $this->slug,
            plugin_dir_url( __DIR__ ) . 'assets/js/admin.js',
            array( 'jquery' ),
            Plugin::getVersion(),
            true
            );
    }

    /**
     * Add settings menu item to dashboard
     */
    public function add_menu() {
        add_options_page(
            esc_html__( 'Phanes UberRush Settings', 'izi-woocommerce-uberrush' ),
            $this->display_name,
            'manage_options',
            $this->slug,
            array( $this, 'render_page' )
            );
    }

    /**
     * Render settings page
     * @return void
     */
    public function render_page() {

        if ( isset( $_POST['phanes-uber-apply-license-code'] ) ) {

            $error = false;
            
            foreach( $_POST['phanes-uber-license-code'] as $chunk ) {
                
                $chunk = sanitize_text_field( $chunk );

                if ( ! empty( $chunk ) && strlen( $chunk ) === 4 ) {
                    continue;
                }
                
                $error = true;
                break;
            }

            if ( ! $error ) {
                
                $code = 'WOOS-' . implode( '-', $_POST['phanes-uber-license-code'] ) . '-KEY';
                
                $check = $this->verify_license( $code );
                
                if ( $check ) {
                    $args = array(
                        'code'          => $code,
                        'time'          => $this->license_response['expires'],
                        'check_time'    => strtotime( "+1 month" ),
                    );

                    // Save to database
                    update_option( $this->db_field, $args );
                    
                    $this->add_notice( __( '<strong> Done! </strong> License code updated successfully. ', 'izi-woocommerce-uberrush' ) . $this->license_response['msg'], 'updated', true, true );
                    
                } else {
                    $this->add_notice( $this->license_response['msg'], 'error', true, true );

                    // Save to database
                    update_option( $this->db_field, false );
                    
                    /**
                    if ( $check === null )
                        $this->add_notice( __( '<strong> Error Occurred! </strong> Failed to communicate with server.', 'izi-woocommerce-uberrush' ), 'error', true, true );
                    else
                        $this->add_notice( __( '<strong> Error! </strong> License code is invalid.', 'izi-woocommerce-uberrush' ), 'error', true, true );
                    */
                }
                
            } else {
                $this->add_notice( __( '<strong> Error! </strong> License code is incorrect.', 'izi-woocommerce-uberrush' ), 'error', true, true );
            }

        }
        
        ?>

        <div class="wrap phanse-uber-wrapper" style="margin: 20px 20px 0 2px;">
            
            <h1><?php printf( __( 'Welcome to %s', 'izi-woocommerce-uberrush' ), $this->display_name ); ?></h1>

            <div class="welcome-panel" style="float: left;width: 77%;padding: 0.89% 0.5%;margin: 0;">
            
                <div class="welcome-panel-content">
                    <h2><?php printf( __( '%s License Verification', 'izi-woocommerce-uberrush' ), $this->display_name ); ?></h2>
                    <p class="about-description"><?php esc_html_e( 'Please insert your License code.', 'izi-woocommerce-uberrush' ); ?></p>
                    
                    <div class="welcome-panel-column-container" style="float: left;width: 100%;margin: 2.5em 0 1em;">
                    
                        <div class="welcome-panel-column" style="width: 100%;">
                        
                            <h3 style="display: inline; margin-right: 2%"><?php esc_html_e( 'License code:', 'izi-woocommerce-uberrush' ); ?></h3>
                            
                            <form method="POST" action="" enctype="multipart/form-data" style="display: inline; font-family: camingoCode;">
                            
                                <input size="4" type="text" value="WOOS" required="required" readonly="readonly"> –
                                
                                <input size="4" type="text" name="phanes-uber-license-code[]" minlength="4" maxlength="4" autocomplete="off" required="required"> –
                                <input size="4" type="text" name="phanes-uber-license-code[]" minlength="4" maxlength="4" autocomplete="off" required="required"> –
                                <input size="4" type="text" name="phanes-uber-license-code[]" minlength="4" maxlength="4" autocomplete="off" required="required"> –
                                <input size="4" type="text" name="phanes-uber-license-code[]" minlength="4" maxlength="4" autocomplete="off" required="required"> –
                                <input size="4" type="text" name="phanes-uber-license-code[]" minlength="4" maxlength="4" autocomplete="off" required="required"> –
                                
                                <input size="3" type="text" value="KEY" required="required" readonly="readonly">
                                
                                <button style="margin-left: 2%" name="phanes-uber-apply-license-code" class="button button-primary"><?php esc_html_e( 'Apply Code', 'izi-woocommerce-uberrush' ); ?></button>
                            
                            </form>
                        
                        </div>

                    </div>
                    
                </div>
                
            </div>

        </div>

        <?php
    }

    /**
     * Add notices
     * @param string  $msg     Notice message
     * @param string  $class   Additional classes
     * @param boolean $dismiss Is the notification dissmissable
     * @param boolean $echo
     */
    public function add_notice( $msg, $class, $dismiss = true, $echo = false ) {
        
        if ( ! is_admin() ) return;
        
        $dismiss = ( $dismiss === true ) ?
            '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' : '';
        
        $notice = '<div id="message" class="' . $class . ' notice is-dismissible"><p>' . $msg . '</p>' . $dismiss . '</div>';
        
        if ( $echo === true )
            echo $notice;
        else
            self::$_notices .= $notice;
        
    }

    /**
     * Display saved notices
     * @return void
     */
    public function display_notices() {
        echo self::$_notices;
    }

    /**
     * Check license validity
     * @return boolean
     */
    public function has_valid_license() {
        $license = get_option( $this->db_field, false );
        
        if ( ! $license || empty( $license ) ) {
            return false;
        }
        
        if ( empty( $license['code'] ) || strlen( $license['code'] ) === 20 ) {
            return false;
        }
        
        if ( empty( $license['time'] ) || ! is_int( $license['time'] ) || $license['time'] < strtotime( current_time( 'mysql' ) ) ) {
            return false;
        }
        
        if ( empty( $license['check_time'] ) || ! is_int( $license['check_time'] ) ) {
            return false;
        }
        
        if ( $license['check_time'] <= strtotime( current_time( 'mysql' ) ) ) {
            $check = $this->verify_license( $license['code'] );
            
            if ( ! $check || $check === false ) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Verify license code calling license server
     * @param  string $code The license code
     * @return boolean
     */
    public function verify_license( $code ) {
        $wooskey = new WoosKey_Manager( $this->api, $code );
        $response = $wooskey->check_license();
        
        if ( $response ) {
            $this->license_response = array(
                'code'      => $code,
                'msg'       => $wooskey->response_msg,
                'expires'   => $wooskey->response_expires,
            );
            
            return true;

        } elseif ( is_null( $response ) ) {
            $this->license_response = array(
                'code'  => $code,
                'msg'   => $wooskey->response_msg,
            );
            
            return null;
        }
        
        $this->license_response = array(
            'code'  => $code,
            'msg'   => $wooskey->response_msg,
        );
        
        return false;
    }

}

<?php
/**
* @Author :  Chukwudi J. Nwakpaka - https://phanes.co/
* @package : WoosKey
* @subpackage : WoosKey Manager
* @version : 1.0 
*/
namespace UBER\Classes;

final class WoosKey_Manager {
    
    /**
     * Key Manager name
     */
    const NAME = 'WoosKey Manager';
    
    /**
     * Key Manager version
     */
    const VERSION = 1.0;

    /**
     * Check for init error.
     * @access private
     */
    private $error = false;
    
    /**
    * Hold the cURL error
    */
    private $curl_error = null;    
    
    /**
     * The URL to send request.
     * @access private
     */
    private $request_url = null;
    
    /**
     * Holds License Code.
     * @access private
     */
    private $license_code = null;
    
    /**
    * Whether there is error from response
    */
    public $response_error = null;
    
    /**
    * Response code
    */
    public $response_code = null;
    
    /**
    * Response message
    */
    public $response_msg = null;
    
    /**
    * When the license key will expire
    */
    public $response_expires = null;
    
    /**
    * Construct
    * 
    */
    public function __construct( $url = null, $license_code = null ) {
        
        // Sets the URL
        $this->set_request_url( $url );
        
        // Sets the License Code
        $this->set_license_code( $license_code );
        
    }
    
    /**
    * Sets the request URL
    * 
    * @param string $url The Url to send request to
    */
    private function set_request_url( $url ) {
        
        $this->request_url = $url;
        
    }
    
    /**
    * Sets the License Code
    * 
    * @param string $license_code The License code to send with request
    */
    private function set_license_code( $license_code ) {
        
        if ( $this->validate_license_code( $license_code ) === false ) {
            
            $this->error = true;
            
        }
        
        $this->license_code = $license_code;
        
    }
    
    /**
    * Validates the WoosKey License code before sending request
    * 
    * @param mixed $license_code
    */
    Private function validate_license_code( $license_code ) {
        
        if ( strlen( $license_code ) !== 33 ) return false;
        
        $chunks = explode( '-', $license_code, 7 );
        
        if ( empty( $chunks ) || ! $chunks ) return false;
        
        if ( $chunks[0] != 'WOOS' ) return false;
        
        if ( $chunks[6] != 'KEY' ) return false;
        
        for ( $i = 1; $i <= 5; $i++ ) {
            
            if ( preg_match( '/[^A-Z0-9]/', $chunks[$i] ) == 1 ) return false;
                
        }
        
        return true;        
        
    }
    
    /**
    * Check License
    * 
    */
    public function check_license() {

        if ( empty( $this->request_url ) || empty( $this->license_code ) || $this->error == true ) {
            
            error_log( 'Invalid URL or License Code set.' );
            
            $this->response_error   = true;
            $this->response_code    = 'WOOSKEY_400';
            $this->response_msg     = empty( $this->request_url || $this->error == true ) ?
                'Invalid URL or License Code set.' : 'Invalid License Code set.';
            
            return false;
            
        }
        
        $response = $this->make_request();
        
        if ( $response == null ) {
            
            $this->response_error   = true;
            $this->response_code    = 'WOOSKEY_417';
            $this->response_msg     = $this->curl_error == null ? 'Error Occurred while verifying License Code.' : $this->curl_error;
            
            return null;
            
        }
        elseif ( $response == false ) {
            
            $this->response_error   = true;
            $this->response_code    = 'WOOSKEY_417';
            $this->response_msg     = 'Error Occurred while verifying License Code.';
            
            return false;
            
        }
        else {
            
            $this->response_error   = $response['error'];
            $this->response_code    = $response['code'];
            $this->response_msg     = $response['msg'];
            $this->response_expires = isset( $response['expires'] ) ? $response['expires'] : '';
            
            return ( $response['error'] === true ) ? false : true;

        }
        
    }
    
    /**
    * Make the requests
    * 
    */
    private function make_request() {

        $curl = curl_init();
        
        curl_setopt_array( $curl, array(
            CURLOPT_URL             => $this->request_url,
            CURLOPT_USERAGENT       => self::NAME . '/' . self::VERSION,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_POST            => true,
            CURLOPT_MAXREDIRS       => 3,
            CURLOPT_POSTFIELDS      => array(
                'wooskey_license_check' => true,
                'wooskey_license_code'  => $this->license_code
            )
        ) );
        
        $response = curl_exec( $curl );
        
        if ( empty( $response ) ) {
            
            // some kind of an error happened
            error_log( curl_error( $curl ) );
            
            $this->curl_error = curl_error( $curl );
            
            curl_close( $curl ); // close cURL handler
            
            return null;
        
        }
        else {
            
            $info = curl_getinfo( $curl );
            
            // echo "Time took: " . $info['total_time']*1000 . "ms\n";
            
            curl_close( $curl ); // close cURL handler
            
            if ( $info['http_code'] != 200 && $info['http_code'] != 201 ) {
                
                error_log( sprintf( "Received error: %s \r\n Raw response: %s \r\n", $info['http_code'], $response ) );
                
                return false;
                
            }
            
        }

        // Convert the result from JSON format to a PHP array 
        $json_response = json_decode( $response, true );
        
        return $json_response;
        
    }
    
}

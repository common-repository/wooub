<?php
namespace UBER\Classes;

class UberRush extends Plugin
{
    public static $uber_shipping;
    
    public static $new_york_codes = array(10001, 10002, 10003, 10004, 10005, 10006, 10007, 10009, 10010, 10011, 10012, 10013, 10014, 10016, 10017, 10018, 10019, 10020, 10021, 10022, 10023, 10024, 10025, 10026, 10027, 10028, 10029, 10030, 10031, 10032, 10033, 10034, 10035, 10036, 10037, 10038, 10039, 10040, 10041, 10044, 10055, 10065, 10069, 10075, 10103, 10104, 10105, 10106, 10107, 10110, 10111, 10112, 10115, 10118, 10119, 10120, 10121, 10122, 10123, 10128, 10151, 10152, 10153, 10154, 10155, 10158, 10162, 10165, 10166, 10167, 10168, 10169, 10170, 10171, 10172, 10173, 10174, 10175, 10176, 10177, 10178, 10179, 10199, 10270, 10271, 10274, 10278, 10279, 10280, 10282, 11206, 11211, 11222, 11249);
    public static $chicago_codes = array(60043, 60076, 60091, 60201, 60202, 60203, 60601, 60602, 60603, 60604, 60605, 60606, 60607, 60608, 60609, 60610, 60611, 60612, 60613, 60614, 60615, 60616, 60618, 60622, 60625, 60626, 60630, 60632, 60634, 60640, 60641, 60642, 60645, 60646, 60647, 60651, 60653, 60654, 60657, 60659, 60660, 60661, 60706, 60712);
    public static $san_francisco_codes = array(94102, 94103, 94104, 94105, 94107, 94108, 94109, 94110, 94111, 94112, 94114, 94115, 94116, 94117, 94118, 94121, 94122, 94123, 94124, 94127, 94129, 94130, 94131, 94132, 94133, 94155, 94158, 94501, 94502, 94601, 94602, 94603, 94604, 94605, 94606, 94607, 94608, 94609, 94610, 94611, 94612, 94613, 94614, 94615, 94617, 94618, 94619, 94620, 94621, 94622, 94623, 94624, 94649, 94659, 94660, 94661, 94662, 94666, 94701, 94702, 94703, 94704, 94705, 94707, 94708, 94709, 94710, 94712, 94720);


    public function __construct() {
        $plugin_class = get_called_class();

        $plugin_class::initEnv();
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
        if (in_array('woocommerce/woocommerce.php', $active_plugins)) {            
            add_filter('woocommerce_shipping_methods', array( $plugin_class, 'addUberRuchDeliveryMethod' ));
            add_action('woocommerce_shipping_init', array( $plugin_class, 'uberRuchDeliveryMethodInit' ));
        }
        add_action( 'wp_loaded', array( $this, 'init' ) );
        
        //create_delivery_on_order_created
        add_action('woocommerce_new_order', array( $plugin_class, 'newOrder' ));
        add_action('woocommerce_order_status_pending', array( $plugin_class, 'orderStatusPending' ));
        add_action('woocommerce_order_status_processing', array( $plugin_class, 'orderStatusProcessing' ));
        add_action('woocommerce_order_status_completed', array( $plugin_class, 'orderStatusCompleted' ));
        add_action('woocommerce_order_status_cancelled', array( $plugin_class, 'orderStatusCancelled' ));
        add_action('woocommerce_order_status_failed', array( $plugin_class, 'orderStatusFailed' ));
    }
    
    public function saveSelected()
    {
        if ( isset( $_GET['shipping_method'] ) && !empty( $_GET['shipping_method'] ) ) {
            global $woocommerce;
            $selected_option = $_GET['shipping_method'][0];
            $woocommerce->session->_chosen_shipping_option = sanitize_text_field( $selected_option );
        }
    }
    
    public function postProcess()
    {
        if (isset($_GET['izi_uber_order_id']) && !empty($_GET['izi_uber_order_id']) & isset($_GET['izi_uber_quote_id']) && !empty($_GET['izi_uber_quote_id'])) {
            if ($_GET['izi_uber_quote_id'] == 'same') {
                if (is_int($_GET['izi_uber_order_id'])) {
                    self::createDelivery($_GET['izi_uber_order_id']);
                }
            } else {
                self::createDeliveryWithQuote($_GET['izi_uber_order_id'], $_GET['izi_uber_quote_id']);
            }
        }

        if (isset ($_GET['izi_uber_cancel_order_id']) && !empty($_GET['izi_uber_cancel_order_id'])) {
            self::cancelDelivery((int)$_GET['izi_uber_cancel_order_id']);
        }
    }
    
    public static function getUberShipping()
    {
        if (!self::$uber_shipping || self::$uber_shipping == null) {
            self::$uber_shipping = new \UBER\Classes\WC_UberShippingMethod();
        }
        return self::$uber_shipping;
    }
    
    public static function orderGetQuotes($order_id)
    {
        $order = new \WC_Order($order_id);
        $delivery = self::getUberShipping();

        $city = $order->get_shipping_city();
        $state = $order->get_shipping_state();
        $country = $order->get_shipping_country();
        $postcode = $order->get_shipping_postcode();

        $address = $order->get_shipping_address_1();
        $address_2 = $order->get_shipping_address_2();

        $store_address = $delivery->address;
        $store_address_2 = $delivery->address_2;
        $store_codes = explode(",", $delivery->codes);

        $customer_array = false;

        if (in_array($postcode, self::$san_francisco_codes)) {
            $customer_array = self::$san_francisco_codes;
        }

        if (in_array($postcode, self::$chicago_codes)) {
            $customer_array = self::$chicago_codes;
        }

        if (in_array($postcode, self::$new_york_codes)) {
            $customer_array = self::$new_york_codes;
        }
        
        $store_codes_av = array();

        foreach ($store_codes as $key => $value) {
            if ($customer_array != false) {
                if (in_array($value, $customer_array)) {
                    $store_codes_av[] = $value;
                }
            }
        }

        if (is_array($store_codes_av) && !empty($store_codes_av)) {
            $store_code = $store_codes_av[0];
        } else {
            $store_code = $store_codes_av;
        }

        $quotes = self::getQuotes($delivery->uber_id, $delivery->uber_secret, $city, $state, $country, $store_address, $store_address_2, $store_code, $address, $address_2, $postcode);
        return $quotes;
    }
    
    public static function createDelivery($order_id) {
        $new_quote_id = get_post_meta($order_id, 'izi_uber_new_quote_id');
        if ($new_quote_id != NULL) {
            $quota_id = $new_quote_id[0];
        } else {
            $quota_id = self::getOrderQuoteId($order_id);
        }

        $order_reference_id = $order_id;

        $items = self::getOrderItems($order_id);
        $pickup = self::getPickup($order_id);
        $dropoff = self::getDropoff($order_id);

        $delivery = self::getUberShipping();
        $auth = self::auth($delivery->uber_id, $delivery->uber_secret);
        $atoken = $auth->access_token;

        if ($delivery->uber_live == 'yes') {
            $url = 'https://api.uber.com/v1/deliveries';
        } else {
            $url = 'https://sandbox-api.uber.com/v1/deliveries';
        }

        $data = array('quote_id' => $quota_id, 'order_reference_id' => strval($order_reference_id), 'items' => $items, 'pickup' => $pickup, 'dropoff' => $dropoff);

        $data = json_encode($data);

        $context = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Authorization: Bearer " . $atoken . "\r\n" . "Content-Type: application/json\r\n",
                'content' => $data,
                'ignore_errors' => true,
            )
        );

        $context = stream_context_create($context);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);
        if (!isset($result->delivery_id))
            return false;
        $delivery_id = $result->delivery_id;

        update_post_meta($order_id, 'izi_uber_delivery_id', $delivery_id);
    }
    
    public static function createDeliveryWithQuote($order_id, $quote_id)
    {
        update_post_meta($order_id, 'izi_uber_new_quote_id', $quote_id);
        self::createDelivery($order_id);
    }
    
    public static function getDelivery($order_id)
    {
        $delivery_id = get_post_meta($order_id, 'izi_uber_delivery_id');
        $delivery_id = $delivery_id[0];

        $delivery = self::getUberShipping();
        $auth = self::auth($delivery->uber_id, $delivery->uber_secret);
        $atoken = $auth->access_token;
        
        if ($delivery->uber_live == 'yes') {
            $url = 'https://api.uber.com/v1/deliveries/' . $delivery_id;
        } else {
            $url = 'https://sandbox-api.uber.com/v1/deliveries/'.$delivery_id;
        }

        $context = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Authorization: Bearer " . $atoken . "\r\n" . "Content-Type: application/json\r\n",
                //'content' => $data,
                'ignore_errors' => true,
            )
        );

        $context = stream_context_create($context);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);

        return $result;
    }
    
    public static function getDeliveries($offset, $limit, $status) {
        $delivery = self::getUberShipping();
        $auth = self::auth($delivery->uber_id, $delivery->uber_secret);
        $atoken = $auth->access_token;
        
        if ($delivery->uber_live == 'yes') {
            $url = 'https://api.uber.com/v1/deliveries';
        } else {
            $url = 'https://sandbox-api.uber.com/v1/deliveries';
        }

        $data = array('offset' => $offset, 'limit' => $limit, 'status' => $status);
        $data = json_encode($data);

        $context = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Authorization: Bearer " . $atoken . "\r\n" . "Content-Type: application/json\r\n",
                'content' => $data,
                'ignore_errors' => true,
            )
        );

        $context = stream_context_create($context);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);

        return $result;
    }
    
    public static function getDeliveryId($order_id) {
        $delivery_id = get_post_meta($order_id, 'izi_uber_delivery_id');
        $delivery_id = $delivery_id[0];

        if (!empty($delivery_id)) {
            return $delivery_id;
        }
    }
    
    public static function cancelDelivery($order_id) {
        $order = new \WC_Order($order_id);
        $delivery = self::getUberShipping();

        $delivery_id = get_post_meta($order_id, 'izi_uber_delivery_id');
        $delivery_id = $delivery_id[0];

        $auth = self::auth($delivery->uber_id, $delivery->uber_secret);
        $atoken = $auth->access_token;

        if ($delivery->uber_live == 'yes') {
            $url = 'https://api.uber.com/v1/deliveries/' . $delivery_id . '/cancel';
        } else {
            $url = 'https://sandbox-api.uber.com/v1/deliveries/'.$delivery_id.'/cancel';
        }

        $context = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Authorization: Bearer " . $atoken . "\r\n" . "Content-Type: application/json\r\n",
                'ignore_errors' => true,
            )
        );

        $context = stream_context_create($context);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);

        delete_post_meta($order_id, 'izi_uber_delivery_id');
        delete_post_meta($order_id, 'izi_uber_new_quote_id');
    }

    protected static function auth($uber_id, $uber_secret)
    {
        $url = 'https://login.uber.com/oauth/v2/token';
        $headers = array('Content-Type: application/x-www-form-urlencoded');
        $options = array(
            'http' => array(
                'header' => $headers,
                'method' => 'POST',
                'content' => 'client_secret=' . $uber_secret . '&client_id=' . $uber_id . '&grant_type=client_credentials&scope=delivery'
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);
        
        return $result;
    }
    
    public static function getQuotes($uber_id, $uber_secret, $city, $state, $country, $pickup_address, $pickup_address_2, $pickup_code, $dropoff_address, $dropoff_address_2, $dropoff_code)
    {
        $pickup_data = array('location' => array('address' => $pickup_address, 'address_2' => $pickup_address_2, 'city' => $city, 'state' => $state, 'postal_code' => $pickup_code, 'country' => $country));

        $dropoff_data = array('location' => array('address' => $dropoff_address, 'address_2' => $dropoff_address_2, 'city' => $city, 'state' => $state, 'postal_code' => $dropoff_code, 'country' => $country));

        $auth = self::auth($uber_id, $uber_secret);
        $atoken = $auth->access_token;
        
        $delivery = self::getUberShipping();
        
        if ($delivery->uber_live == 'yes') {
            $url = 'https://api.uber.com/v1/deliveries/quote';
        } else {
            $url = 'https://sandbox-api.uber.com/v1/deliveries/quote';
        }

        $data = array('pickup' => $pickup_data, 'dropoff' => $dropoff_data);
        $data = json_encode($data);

        $context = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Authorization: Bearer " . $atoken . "\r\n" . "Content-Type: application/json\r\n",
                'content' => $data,
                'ignore_errors' => true,
            )
        );

        $context = stream_context_create($context);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);

        return $result;
    }

    public static function getOrderQuoteId($order_id)
    {
        $order = new \WC_Order($order_id);
        $shipping_methods = $order->get_shipping_methods();
        $array_keys = array_keys($shipping_methods);
        if (!$array_keys || empty($array_keys)) {
            return false;
        }
        $quota_id = false;
        $key = $array_keys[0];
        if (isset($shipping_methods[$key])) {
            $shipping_line = $shipping_methods[$key]->get_method_id();
            $shipping_arr = explode(':', $shipping_line);
            if ($shipping_arr[0] != 'uber_shipping_method') {
                $quota_id = false;
            } else {
                $key = $shipping_arr[1];
                $quotes = self::orderGetQuotes($order_id);
                if (isset($quotes->quotes) && !empty($quotes->quotes)) {
                    if (isset($quotes->quotes[$key])) {
                        $quota = $quotes->quotes[$key];
                        $quota_id = $quota->quote_id;
                    } else {
                        $quota = $quotes->quotes[0];
                        $quota_id = $quota->quote_id;
                    }
                }
            }
        } else {
            $quota_id = 0;
        }
        return $quota_id;
    }
    
    public static function getOrderItems($order_id)
    {
        $order = new \WC_Order($order_id);
        $produts = $order->get_items();
        
        foreach ($produts as $key => $value) {
            $wc_product = new \WC_Product((int)$value["product_id"]);
            $title = $value["name"];
            $quantity = (int)$value["qty"];
            $price = (float)$value["line_total"];
            $currency_code = get_woocommerce_currency();
            $items[] = array(
                'title' => $title,
                'quantity' => $quantity,
                'price' => $price,
                'width' => (float)$wc_product->get_width(),
                'height' => (float)$wc_product->get_height(),
                'weight' => (float)$wc_product->get_weight(),
                'currency_code' => $currency_code
            );
        }

        if (isset($items) && !empty($items)) {
            return $items;
        } else {
            return false;
        }
    }
    
    public static function getPickupCode($order_id)
    {
        $order = new \WC_Order($order_id);
        $delivery = self::getUberShipping();

        $postcode = $order->get_shipping_postcode();

        $store_codes = explode(",", $delivery->codes);
        $customer_array = array();

        if (in_array($postcode, self::$san_francisco_codes)) {
            $customer_array = self::$san_francisco_codes;
        }

        if (in_array($postcode, self::$chicago_codes)) {
            $customer_array = self::$chicago_codes;
        }

        if (in_array($postcode, self::$new_york_codes)) {
            $customer_array = self::$new_york_codes;
        }
        
        $store_codes_av = array();
        foreach ($store_codes as $key => $value) {
            if (in_array($value, $customer_array)) {
                $store_codes_av[] = $value;
            }
        }

        if (is_array($store_codes_av) && !empty($store_codes_av)) {
            $store_code = $store_codes_av[0];
        }

        if (isset($store_code) && !empty($store_code)) {
            return $store_code;
        }
    }
    
    public static function getPickup($order_id)
    {
        $store_code = self::getPickupCode($order_id);

        $order = new \WC_Order($order_id);
        $delivery = self::getUberShipping();

        $city = $order->get_shipping_city();
        $state = $order->get_shipping_state();
        $country = $order->get_shipping_country();

        $address = $delivery->address;
        $address_2 = $delivery->address_2;
        $first_name = $delivery->first_name;
        $last_name = $delivery->last_name;
        $company_name = $delivery->company_name;
        $email = $delivery->email;
        $phone_num = $delivery->phone;
        if (substr($phone_num, 0, 1) !== '+') {
            $phone_num = '+' . $phone_num;
        }

        $location = array('address' => $address, 'address_2' => $address_2, 'city' => $city, 'state' => $state, 'postal_code' => $store_code, 'country' => $country);
        $phone = array('number' => $phone_num);
        $contact = array('first_name' => $first_name, 'last_name' => $last_name, 'company_name' => $company_name, 'email' => $email, 'phone' => $phone,);

        if ($delivery->sign_pickup == 'yes') {
            $pickup_sign = 1;
        } else {
            $pickup_sign = 0;
        }

        $pickup_spec = $delivery->spec;
        $pickup_spec = substr($pickup_spec, 0, 200);

        $pickup_data = array('location' => $location, 'contact' => $contact, 'signature_required' => $pickup_sign, 'special_instructions' => $pickup_spec);
        $pickup = array('pickup' => $pickup_data);

        return $pickup_data;
    }
    
    public static function getDropoff($order_id)
    {
        $order = new \WC_Order($order_id);
        $delivery = self::getUberShipping();

        $city = $order->get_shipping_city();
        $state = $order->get_shipping_state();
        $country = $order->get_shipping_country();

        $address = $order->get_shipping_address_1();
        $address_2 = $order->get_shipping_address_2();
        $first_name = $order->get_shipping_first_name();
        $last_name = $order->get_shipping_last_name();
        $company_name = $order->get_shipping_company();
        $email = $order->get_billing_email();

        $phone_num = $order->get_billing_phone();
        if (substr($phone_num, 0, 1) !== '+') {
            $phone_num = '+' . $phone_num;
        }

        $dropoff_code = $order->get_shipping_postcode();

        $location = array('address' => $address, 'address_2' => $address_2, 'city' => $city, 'state' => $state, 'postal_code' => $dropoff_code, 'country' => $country);
        $phone = array('number' => $phone_num);
        $contact = array('first_name' => $first_name, 'last_name' => $last_name, 'company_name' => $company_name, 'email' => $email, 'phone' => $phone,);

        if ($delivery->sign_dropoff == 'yes') {
            $dropoff_sign = 1;
        } else {
            $dropoff_sign = 0;
        }

        $dropoff_data = array('location' => $location, 'contact' => $contact, 'signature_required' => $dropoff_sign);
        $dropoff = array('dropoff' => $dropoff_data);

        return $dropoff_data;
    }

    public static function addUberRuchDeliveryMethod($method)
    {
        $methods[] = '\UBER\Classes\WC_UberShippingMethod';
        return $methods;
    }
    
    public static function uberRuchDeliveryMethodInit()
    {
        self::$uber_shipping = new \UBER\Classes\WC_UberShippingMethod();
    }

    public function init()
    {
        $this->postProcess();
        $plugin_class = get_called_class();
        
        add_filter('manage_edit-shop_order_columns', array( $plugin_class, 'orderColumns' ));
        add_action('manage_shop_order_posts_custom_column', array( $plugin_class, 'orderColumnsFunction' ), 2);
        add_filter('woocommerce_my_account_my_orders_actions', array( $plugin_class, 'orderActionUber'), 10, 2);
    }
    
    // Woocommerce Actions
    public static function newOrder($order_id)
    {
        $delivery = self::getUberShipping();
        $quote_id = self::getOrderQuoteId($order_id);

        if ($delivery->create_delivery == 'created' && $delivery->enabled == 'yes' && !empty($delivery->uber_id) && !empty($delivery->uber_secret) && !empty($quote_id)) {
            self::createDelivery($order_id);
        }
    }
    
    public static function orderStatusPending($order_id)
    {
        $delivery = self::getUberShipping();
        $quote_id = self::getOrderQuoteId($order_id);

        if ($delivery->create_delivery == 'pending' && $delivery->enabled == 'yes' && !empty($delivery->uber_id) && !empty($delivery->uber_secret) && !empty($quote_id)) {
            self::createDelivery($order_id);
        }
    }
    
    public static function orderStatusProcessing($order_id)
    {
        $delivery = self::getUberShipping();
        $quote_id = self::getOrderQuoteId($order_id);

        if ($delivery->create_delivery == 'processing' && $delivery->enabled == 'yes' && !empty($delivery->uber_id) && !empty($delivery->uber_secret) && !empty($quote_id)) {
            self::createDelivery($order_id);
        }
    }
    
    public static function orderStatusCompleted($order_id)
    {
        $delivery = self::getUberShipping();
        $quote_id = self::getOrderQuoteId($order_id);

        if ($delivery->create_delivery == 'completed' && $delivery->enabled == 'yes' && !empty($delivery->uber_id) && !empty($delivery->uber_secret) && !empty($quote_id)) {
            self::createDelivery($order_id);
        }
    }
    
    public static function orderStatusCancelled($order_id)
    {
        $delivery = self::getUberShipping();
        if ($delivery->cancel_delivery_on_order_cancel == 'yes' && $delivery->enabled == 'yes' && !empty($delivery->uber_id) && !empty($delivery->uber_secret)) {
            self::cancelDelivery($order_id);
        }
    }
    
    public static function orderStatusFailed($order_id)
    {
        $delivery = self::getUberShipping();
        if ($delivery->cancel_delivery_on_order_failed == 'yes' && $delivery->enabled == 'yes' && !empty($delivery->uber_id) && !empty($delivery->uber_secret)) {
            self::cancelDelivery($order_id);
        }
    }
    
    public static function orderStatusRefunded($order_id)
    {
        $delivery = self::getUberShipping();
        if ($delivery->cancel_delivery_on_order_refund == 'yes' && $delivery->enabled == 'yes' && !empty($delivery->uber_id) && !empty($delivery->uber_secret)) {
            self::cancelDelivery($order_id);
        }
    }
    
    public static function orderColumns($columns) {
        $delivery = self::getUberShipping();
        if ($delivery->enabled == 'yes') {
            $new_columns = (is_array($columns)) ? $columns : array();
            unset($new_columns['order_actions']);

            $new_columns['izi_uber'] = __('UberRush Delivery');

            $new_columns['order_actions'] = $columns['order_actions'];
            return $new_columns;
        } else {
            return $columns;
        }
    }
    
    public static function orderColumnsFunction($column) {
        global $post;
        $uber_delivery = self::getUberShipping();
        if ($uber_delivery->enabled == 'yes') {
            if ($column == 'izi_uber') {
                $izi_uber_delivery_id = get_post_meta($post->ID, 'izi_uber_delivery_id');
                
                $order = new \WC_Order($post);

                if (isset($izi_uber_delivery_id[0]) && !empty($izi_uber_delivery_id[0]) && $order->get_status() != 'cancelled' & $order->get_status() != 'refunded' && $order->get_status() != 'failed') {
                    $delivery = self::getDelivery($post->ID);
                    if (!empty($delivery) && isset($delivery->status)) {
                        print '<p>'.__('Cost').': <strong>' . wc_price($delivery->fee) . '</strong></p>';
                        print '<p>'.__('Status').': <strong>' . $delivery->status . '</strong></p>';
                        print '<p><a href="' . $delivery->tracking_url . '" target="_blank" class="button button-primary">'.__('Track delivery').'</a></p>';

                        add_thickbox();

                        print '<div id="izi-uber-delivery-info" style="display:none;">';
                        print '<p style="text-align:center;">';
                        if ($delivery->courier != NULL) {
                            print '<h2>'.__('Courier').'</h2>';
                            print '<p><img src="' . $delivery->courier->picture_url . '" style="width:128px;height:128px;"></p>';
                            print '<p>' . $delivery->courier->name . '"></p>';
                            print '<p>' . $delivery->courier->phone . '"></p>';
                        } else {
                            print '<h3>'.__('Courier not assinget yet').'.</h3>';
                        }
                        print '</p>';
                        print '</div>';
                        print '<a href="#TB_inline?width=300&height=150&inlineId=izi-uber-delivery-info" class="thickbox button button-primary">'.__('INFO Delivery').'</a>';
                        print '<p><a style="color:red" href="/wp-admin/edit.php?post_type=shop_order&izi_uber_cancel_order_id=' . $post->ID . '" >'.__('Cancel Delivery').'</a></p>';
                    }
                } else {
                    $quote_id = self::getOrderQuoteId($post->ID);
                    $quotes = self::orderGetQuotes($post->ID);
                    
                    if (isset($quotes->quotes) && !empty($quotes->quotes) && $order->get_status() != 'cancelled' & $order->get_status() != 'refunded' && $order->get_status() != 'failed') {

                        add_thickbox();

                        print '<div id="izi-uber-choose-quotes" style="display:none;">';
                        print '<p style="text-align:center;">';

                        if (isset($quotes->quotes) && !empty($quotes->quotes)) {
                            print sprintf('<strong>'.__('Choose delivery quote for order ').'#%s</strong><br /><br />', $post->ID);
                            foreach ($quotes->quotes as $key => $value) {
                                $start_time = date('D - g:i A', $value->start_time);
                                $end_time = date('D - g:i A', $value->end_time);
                                if ($value->quote_id == $quote_id) {
                                    print '<a href="/wp-admin/edit.php?post_type=shop_order&izi_uber_order_id=' . $post->ID . '&izi_uber_quote_id=same" class="button button-primary">' . 'Price: ' . wc_price($value->fee) . ' / Time: ' . $start_time . ' &rarr; ' . $end_time . ' (Customer choise)</a><br /><br />';
                                } else {
                                    print '<a href="/wp-admin/edit.php?post_type=shop_order&izi_uber_order_id=' . $post->ID . '&izi_uber_quote_id=' . $value->quote_id . '" class="button button-primary">' . 'Price: ' . wc_price($value->fee) . ' / Time: ' . $start_time . ' &rarr; ' . $end_time . '</a><br /><br />';
                                }
                            }
                        }
                        print '</p>';
                        print '</div>';
                        print '<a href="#TB_inline?width=300&height=300&inlineId=izi-uber-choose-quotes" class="thickbox button">'.__('Create UberRush Delivery').'</a>';
                    }
                }
            }
        }
    }
    
    public static function orderActionUber($actions, $order)
    {
        $delivery = self::getUberShipping();
        if ($delivery->enabled= 'yes' && $delivery->customer_tracking == 'yes') {
            $izi_uber_delivery_id = get_post_meta($order->get_id(), 'izi_uber_delivery_id');
            if (isset($izi_uber_delivery_id[0]) && !empty($izi_uber_delivery_id[0]) && $order->get_status() != 'cancelled' & $order->get_status() != 'refunded' && $order->get_status() != 'failed') {
                $uber_del = self::getDelivery($order->get_id());
                $actions['izi-uber-tracking'] = array(
                    'url' => $uber_del->tracking_url,
                    'name' => __('Uber track delivery')
                );
            }
        }
        return $actions;
    }
}

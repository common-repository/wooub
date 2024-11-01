<?php
namespace UBER\Classes;

class WC_UberShippingMethod extends \WC_Shipping_Method {

    public function __construct() {
        $this->id = 'uber_shipping_method';
        $this->method_title = __('UberRush Shipping Method', 'woocommerce');
        $this->init_form_fields();
        $this->init_settings();
        $this->uber_live = $this->get_option('uber_live');
        $this->uber_id = $this->get_option('uber_id');
        $this->uber_secret = $this->get_option('uber_secret');
        $this->enabled = $this->get_option('enabled');
        $this->sign_pickup = $this->get_option('sign_pickup');
        $this->customer_tracking = $this->get_option('customer_tracking');
        $this->sign_dropoff = $this->get_option('sign_dropoff');
        $this->fee = $this->get_option('fee');
        $this->title = $this->get_option('title');
        $this->codes = $this->get_option('codes');
        $this->address = $this->get_option('address');
        $this->address_2 = $this->get_option('address_2');

        $this->first_name = $this->get_option('first_name');
        $this->last_name = $this->get_option('last_name');
        $this->company_name = $this->get_option('company_name');
        $this->email = $this->get_option('email');
        $this->phone = $this->get_option('phone');
        $this->spec = $this->get_option('spec');

        $this->create_delivery = $this->get_option('create_delivery');

        $this->cancel_delivery_on_order_cancel = $this->get_option('cancel_delivery_on_order_cancel');
        $this->cancel_delivery_on_order_failed = $this->get_option('cancel_delivery_on_order_failed');
        $this->cancel_delivery_on_order_refund = $this->get_option('cancel_delivery_on_order_refund');
        
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {

        $blog_title = get_bloginfo('name');
        $blog_email = get_bloginfo('admin_email');

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable UberRush Delivery Shipping', 'woocommerce'),
                'default' => 'no'
            ),
            'uber_live' => array(
                'title' => __('Use live', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Uber in live mode', 'woocommerce'),
                'description' => __('Use uber in live mode or test mode? Leave uncheck for test', 'woocommerce'),
                'default' => __('no', 'woocommerce'),
            ),
            'uber_id' => array(
                'title' => __('Uber client ID', 'woocommerce'),
                'type' => 'text',
                'description' => __('', 'woocommerce'),
                'default' => __('', 'woocommerce'),
            ),
            'uber_secret' => array(
                'title' => __('Uber client secret', 'woocommerce'),
                'type' => 'text',
                'description' => __('', 'woocommerce'),
                'default' => __('', 'woocommerce'),
            ),
            'fee' => array(
                'title' => __('Who pays fo delivey?', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Customer to pay for delivery', 'woocommerce'),
                'default' => 'yes'
            ),
            'sign_pickup' => array(
                'title' => __('Need courier signature on pickup?', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Uber Courier to signature on pickup products', 'woocommerce'),
                'default' => 'no'
            ),
            'customer_tracking' => array(
                'title' => __('Allo customer to track delivery?', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show tracking button in customer order page', 'woocommerce'),
                'default' => 'no'
            ),
            'sign_dropoff' => array(
                'title' => __('Need customer signature on delivery?', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Uber Courier to get customer signature on delivery', 'woocommerce'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Method Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('Delivery with UberRush', 'woocommerce'),
            ),
            'address' => array(
                'title' => __('Address of your store', 'woocommerce'),
                'type' => 'text',
                'description' => __('Street, building', 'woocommerce'),
                'default' => __('', 'woocommerce'),
            ),
            'address_2' => array(
                'title' => __('Address 2 of your store', 'woocommerce'),
                'type' => 'text',
                'description' => __('Office number or floor or flat', 'woocommerce'),
                'default' => __('', 'woocommerce'),
            ),
            'codes' => array(
                'title' => __('Your store/stores<br />ZIP/Post Codes', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('What are ZIP/post codes of your store available for local delivery with UBER?', 'woocommerce'),
                'default' => '',
                'description' => __('Separate codes with a comma, if you have more than one local store. <a href="https://docs.google.com/spreadsheets/d/13kVd9UtmB9KkquDQALLRXtb4noyitTMg3EpNoJwAOZE/edit#gid=0" target="_blank">List of available codes</a>', 'woocommerce'),
                'placeholder' => 'e.g. 12345,54321'
            ),
            'first_name' => array(
                'title' => __('Pickup info: first name', 'woocommerce'),
                'type' => 'text',
                'description' => __('First name of person to pickup products (i.e. you manager)', 'woocommerce'),
                'default' => __('John', 'woocommerce'),
            ),
            'last_name' => array(
                'title' => __('Pickup info: last name', 'woocommerce'),
                'type' => 'text',
                'description' => __('Last name of person to pickup products (i.e. you manager)', 'woocommerce'),
                'default' => __('Smith', 'woocommerce'),
            ),
            'company_name' => array(
                'title' => __('Pickup info: company name', 'woocommerce'),
                'type' => 'text',
                'description' => __('Name of your company', 'woocommerce'),
                'default' => __($blog_title, 'woocommerce'),
            ),
            'email' => array(
                'title' => __('Pickup info: e-mail', 'woocommerce'),
                'type' => 'text',
                'description' => __('E-mail of your company/site/person', 'woocommerce'),
                'default' => __($blog_email, 'woocommerce'),
            ),
            'phone' => array(
                'title' => __('Pickup info: phone number', 'woocommerce'),
                'type' => 'text',
                'description' => __('Phone number of your company/site/person', 'woocommerce'),
                'default' => __('', 'woocommerce'),
            ),
            'spec' => array(
                'title' => __('Pickup info: special instructions', 'woocommerce'),
                'type' => 'text',
                'description' => __('Some special instructions for Uber courier to pickup', 'woocommerce'),
                'default' => __('', 'woocommerce'),
            ),
            'create_delivery' => array(
                'title' => __('Action to auto create Uber Delivery', 'woocommerce'),
                'type' => 'select',
                'options' => array('manual' => 'Manual only', 'created' => 'Order Created', 'pending' => 'Order Pending', 'processing' => 'Order Processing', 'completed' => 'Order Completed'),
                'description' => __('Choose action to create Uber Delivery', 'woocommerce'),
                'default' => __('', 'woocommerce'),
            ),
            'cancel_delivery_on_order_cancel' => array(
                'title' => __('Cancel Uber Delivery on order cancelation', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable to cancel Uber delivery, when order is canceled', 'woocommerce'),
                'default' => 'yes'
            ),
            'cancel_delivery_on_order_failed' => array(
                'title' => __('Cancel Uber Delivery on order failure', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable to cancel Uber delivery, when order is failed', 'woocommerce'),
                'default' => 'yes'
            ),
            'cancel_delivery_on_order_refund' => array(
                'title' => __('Cancel Uber Delivery on order refunded', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable to cancel Uber delivery, when order is refunded', 'woocommerce'),
                'default' => 'yes'
            ),
        );
    }

    public function is_available($package) {
        $all_codes = array_merge(\UBER\Classes\UberRush::$new_york_codes, \UBER\Classes\UberRush::$chicago_codes, \UBER\Classes\UberRush::$san_francisco_codes);
        $store_codes = explode(",", $this->codes);
        $store_av = false;
        foreach ($store_codes as $key => $value) {
            if (in_array($value, $all_codes)) {
                $store_av = true;
                $store_av_codes[] = $value;
            }
        }

        if ($store_av !== true) {
            return false;
        } else {
            if (empty($store_av_codes)) {
                return false;
            } else {
                $customer_code = $package['destination']['postcode'];
                if (!in_array($customer_code, $all_codes)) {
                    return false;
                } else {
                    $customer_store_av = false;
                    foreach ($store_av_codes as $key => $value) {
                        if ((in_array($value, $all_codes) && in_array($customer_code, $all_codes))) {
                            $customer_store_av = true;
                        }
                    }
                    if ($customer_store_av !== true) {
                        return false;
                    }
                }
            }
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', true, $package);
    }

    public function calculate_shipping($package = array()) {
        $sf_codes = \UBER\Classes\UberRush::$san_francisco_codes;
        $ch_codes = \UBER\Classes\UberRush::$chicago_codes;
        $ny_codes = \UBER\Classes\UberRush::$new_york_codes;
        
        $country = $package['destination']['country'];
        $postcode = $package['destination']['postcode'];
        $state = $package['destination']['state'];
        $city = $package['destination']['city'];
        $address = $package['destination']['address'];
        $address_2 = $package['destination']['address_2'];

        $store_address = $this->address;
        $store_address_2 = $this->address_2;
        $store_codes = explode(",", $this->codes);

        $customer_array = false;

        if (in_array($postcode, $sf_codes)) {
            $customer_array = $sf_codes;
            $city = 'San Francisco';
        }

        if (in_array($postcode, $ch_codes)) {
            $customer_array = $ch_codes;
            $city = 'Chicago';
        }

        if (in_array($postcode, $ny_codes)) {
            $customer_array = $ny_codes;
            $city = 'New York City';
        }

        foreach ($store_codes as $key => $value) {
            if (in_array($value, $customer_array)) {
                $store_codes_av[] = $value;
            }
        }

        if (is_array($store_codes_av)) {
            $store_code = $store_codes_av[0];
        } else {
            $store_code = $store_codes_av;
        }

        $quotes = \UBER\Classes\UberRush::getQuotes($this->uber_id, $this->uber_secret, $city, $state, $country, $store_address, $store_address_2, $store_code, $address, $address_2, $postcode);

        if (isset($quotes->quotes) && !empty($quotes->quotes)) {
            foreach ($quotes->quotes as $key => $value) {
                if ($this->fee !== 'yes') {
                    $value->fee = 0;
                }

                if ($value->start_time == NULL && $value->end_time == NULL) {
                    $rate = array(
                        'id' => $this->id . ':' . $key,
//                        'id' => $this->id . ':' . $value->quote_id,
                        'label' => $this->title . ' [ The nearest time ]',
                        'cost' => $value->fee,
                    );
                    $this->add_rate($rate);
                } else {
                    $start_time = date('D - g:i A', $value->start_time);
                    $end_time = date('D - g:i A', $value->end_time);
                    $rate = array(
                        'id' => $this->id . ':' . $key,
//                        'id' => $this->id . ':' . $value->quote_id,
                        'label' => $this->title . ' [ ' . $start_time . ' &rarr; ' . $end_time . ' ]',
                        'cost' => $value->fee,
                    );
                    $this->add_rate($rate);
                }
            }
        }
    }

}

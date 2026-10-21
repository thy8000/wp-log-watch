<?php

namespace WPTrace\Builder;

use DateTime;

if (!defined('ABSPATH')) {
    exit;
}

class EventBuilder
{
    private $User = null;

    public function __construct()
    {
        $this->User = \wp_get_current_user();
    }

    public function get_user_ID()
    {
        return $this->User->ID;
    }

    public function get_user_login()
    {
        return $this->User->user_login;
    }

    public function get_actor_type()
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            if (!empty($_SERVER['HTTP_X_WP_NONCE'])) {
                return 'WP Admin';
            }

            return 'REST API';
        }
        if (defined('WP_CLI') && WP_CLI) {
            return 'WP-CLI';
        }
        if (wp_doing_cron()) {
            return 'Cron';
        }
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return 'XML-RPC';
        }
        if (wp_doing_ajax()) {
            return 'AJAX';
        }
    }

    public function get_ip_address()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public function get_user_agent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? esc_html__('Desconhecido', 'wp-trace');
    }

    public function get_request_UUID4()
    {
        $random_bytes_data = random_bytes(16);

        $random_bytes_data[6] = chr(ord($random_bytes_data[6]) & 0x0f | 0x40);
        $random_bytes_data[8] = chr(ord($random_bytes_data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($random_bytes_data), 4));
    }

    public function get_occurred_at()
    {
        $date_time = new DateTime();

        return $date_time->format('Y-m-d H:i:s');
    }
}

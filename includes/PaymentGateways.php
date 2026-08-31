<?php
/**
 * PaymentGateways.php
 * Handles eSewa (ePay v2) and Khalti (ePayment v2) integrations for MedLife.
 */

require_once __DIR__ . '/../config.php';

class PaymentGateways {

    /**
     * Detects base website URL (e.g., http://localhost/medlife)
     */
    public static function getBaseUrl() {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $script_dir = str_replace('\\', '/', $script_dir);
            $script_dir = rtrim($script_dir, '/');
            
            // If script is in a subdirectory like /actions, /includes or /scratch, get root
            if (basename($script_dir) === 'includes' || basename($script_dir) === 'scratch' || basename($script_dir) === 'actions') {
                $script_dir = dirname($script_dir);
            }
            $script_dir = rtrim($script_dir, '/');

            return $protocol . $host . ($script_dir ? $script_dir : '');
        }

        return env('APP_URL', 'http://localhost/medlife');
    }

    // ==========================================
    // 1. eSewa (ePay v2) Integration Methods
    // ==========================================

    /**
     * Get eSewa configurations
     */
    public static function getEsewaConfig() {
        $env = strtolower(env('ESEWA_ENV', 'sandbox'));
        $is_live = ($env === 'live' || $env === 'production');

        return [
            'env' => $env,
            'is_live' => $is_live,
            'merchant_code' => env('ESEWA_MERCHANT_CODE', 'EPAYTEST'),
            'secret_key' => env('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q'),
            'form_url' => $is_live 
                ? 'https://epay.esewa.com.np/api/epay/main/v2/form' 
                : 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
            'status_url' => $is_live 
                ? 'https://epay.esewa.com.np/api/epay/transaction/status/' 
                : 'https://rc-epay.esewa.com.np/api/epay/transaction/status/'
        ];
    }

    /**
     * Generates HMAC-SHA256 Base64 signature for eSewa ePay v2
     * Message format: total_amount=XXX,transaction_uuid=YYY,product_code=ZZZ (strictly no spaces)
     */
    public static function generateEsewaSignature($total_amount, $transaction_uuid, $product_code, $secret_key = null) {
        if ($secret_key === null) {
            $cfg = self::getEsewaConfig();
            $secret_key = $cfg['secret_key'];
        }

        // Format total_amount properly (if integer or float)
        $formatted_amount = is_numeric($total_amount) ? (string)$total_amount : trim($total_amount);
        $message = "total_amount={$formatted_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
        
        $s = hash_hmac('sha256', $message, $secret_key, true);
        return base64_encode($s);
    }

    /**
     * Verify eSewa callback payload
     */
    public static function verifyEsewaCallback($encoded_data) {
        $cfg = self::getEsewaConfig();
        
        if (empty($encoded_data)) {
            return ['success' => false, 'message' => 'Empty response received from eSewa.'];
        }

        $decoded_json = base64_decode($encoded_data);
        if (!$decoded_json) {
            return ['success' => false, 'message' => 'Invalid base64 payload from eSewa.'];
        }

        $data = json_decode($decoded_json, true);
        if (!is_array($data) || empty($data)) {
            return ['success' => false, 'message' => 'Invalid JSON structure from eSewa.'];
        }

        $status = $data['status'] ?? '';
        $total_amount = $data['total_amount'] ?? '';
        $transaction_uuid = $data['transaction_uuid'] ?? '';
        $product_code = $data['product_code'] ?? $cfg['merchant_code'];
        $transaction_code = $data['transaction_code'] ?? '';
        $received_signature = $data['signature'] ?? '';
        $signed_field_names = $data['signed_field_names'] ?? '';

        if (strtoupper($status) !== 'COMPLETE') {
            return [
                'success' => false,
                'status' => $status,
                'message' => 'Payment was not completed. Status: ' . $status,
                'data' => $data
            ];
        }

        // Verify HMAC signature if signed_field_names are provided
        if (!empty($signed_field_names) && !empty($received_signature)) {
            $fields = explode(',', $signed_field_names);
            $message_parts = [];
            foreach ($fields as $field) {
                $field = trim($field);
                if (isset($data[$field])) {
                    $message_parts[] = "{$field}={$data[$field]}";
                }
            }
            $reconstructed_message = implode(',', $message_parts);
            $expected_sig = base64_encode(hash_hmac('sha256', $reconstructed_message, $cfg['secret_key'], true));

            if ($expected_sig !== $received_signature) {
                // Secondary check using standard 3 fields as fallback
                $fallback_sig = self::generateEsewaSignature($total_amount, $transaction_uuid, $product_code, $cfg['secret_key']);
                if ($fallback_sig !== $received_signature) {
                    return [
                        'success' => false,
                        'message' => 'eSewa signature verification failed. Potential data tampering.',
                        'data' => $data
                    ];
                }
            }
        }

        // Optional server-to-server transaction status check API
        $verify_url = $cfg['status_url'] . "?product_code=" . urlencode($product_code) . "&total_amount=" . urlencode($total_amount) . "&transaction_uuid=" . urlencode($transaction_uuid);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verify_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $api_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200 && $api_response) {
            $api_data = json_decode($api_response, true);
            if (is_array($api_data) && isset($api_data['status']) && strtoupper($api_data['status']) === 'COMPLETE') {
                $transaction_code = $api_data['ref_id'] ?? $transaction_code;
            }
        }

        return [
            'success' => true,
            'status' => 'COMPLETE',
            'transaction_code' => $transaction_code,
            'transaction_uuid' => $transaction_uuid,
            'total_amount' => $total_amount,
            'data' => $data,
            'raw' => $decoded_json
        ];
    }

    // ==========================================
    // 2. Khalti (ePayment v2) Integration Methods
    // ==========================================

    /**
     * Get Khalti configurations
     */
    public static function getKhaltiConfig() {
        $env = strtolower(env('KHALTI_ENV', 'sandbox'));
        $is_live = ($env === 'live' || $env === 'production');

        return [
            'env' => $env,
            'is_live' => $is_live,
            'secret_key' => env('KHALTI_SECRET_KEY', '5fc9a00aa780487ba683ad37a5f1dd2e'),
            'public_key' => env('KHALTI_PUBLIC_KEY', '8e1c096fc79d46d7a4defc69d147fae7'),
            'initiate_url' => $is_live 
                ? 'https://khalti.com/api/v2/epayment/initiate/' 
                : 'https://dev.khalti.com/api/v2/epayment/initiate/',
            'lookup_url' => $is_live 
                ? 'https://khalti.com/api/v2/epayment/lookup/' 
                : 'https://dev.khalti.com/api/v2/epayment/lookup/'
        ];
    }

    /**
     * Initiate Khalti ePayment v2 payment
     * Returns ['success' => bool, 'pidx' => '...', 'payment_url' => '...', 'message' => '...']
     */
    public static function initiateKhaltiPayment($order_id, $tracking_order, $grand_total, $customer_info = []) {
        $cfg = self::getKhaltiConfig();
        $base_url = self::getBaseUrl();

        $return_url = $base_url . '/khalti_callback.php';
        $website_url = $base_url;

        // Khalti amount must be in Paisa (1 NPR = 100 Paisa)
        $amount_paisa = (int)round((float)$grand_total * 100);

        $payload = [
            'return_url' => $return_url,
            'website_url' => $website_url,
            'amount' => $amount_paisa,
            'purchase_order_id' => 'order_' . $order_id,
            'purchase_order_name' => 'MedLife Order #' . $tracking_order,
            'customer_info' => [
                'name' => !empty($customer_info['name']) ? $customer_info['name'] : 'Customer',
                'email' => !empty($customer_info['email']) ? $customer_info['email'] : 'customer@medlife.com',
                'phone' => !empty($customer_info['phone']) ? $customer_info['phone'] : '9800000000'
            ]
        ];

        $post_data = json_encode($payload);

        $headers = [
            'Authorization: Key ' . trim($cfg['secret_key']),
            'Content-Type: application/json'
        ];

        // Endpoints to try (primary and fallback)
        $endpoints_to_try = [
            $cfg['initiate_url'],
            ($cfg['is_live'] ? 'https://dev.khalti.com/api/v2/epayment/initiate/' : 'https://khalti.com/api/v2/epayment/initiate/'),
            'https://a.khalti.com/api/v2/epayment/initiate/'
        ];
        $endpoints_to_try = array_unique($endpoints_to_try);

        $last_response = '';
        $last_http_code = 0;
        $last_res_data = [];

        foreach ($endpoints_to_try as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err = curl_error($ch);
            curl_close($ch);

            if ($curl_err) {
                continue;
            }

            $res_data = json_decode($response, true);
            $last_response = $response;
            $last_http_code = $http_code;
            $last_res_data = $res_data;

            if ($http_code >= 200 && $http_code < 300 && !empty($res_data['pidx']) && !empty($res_data['payment_url'])) {
                return [
                    'success' => true,
                    'pidx' => $res_data['pidx'],
                    'payment_url' => $res_data['payment_url'],
                    'expires_at' => $res_data['expires_at'] ?? null,
                    'raw' => $response
                ];
            }
        }

        $error_msg = 'Failed to initiate Khalti payment.';
        if (isset($last_res_data['detail'])) {
            $error_msg = is_array($last_res_data['detail']) ? json_encode($last_res_data['detail']) : $last_res_data['detail'];
        } elseif (isset($last_res_data['error_key'])) {
            $error_msg = $last_res_data['error_key'];
        } elseif (!empty($last_response)) {
            $error_msg = $last_response;
        }

        return [
            'success' => false,
            'http_code' => $last_http_code,
            'message' => $error_msg,
            'response' => $last_res_data
        ];
    }

    /**
     * Verify Khalti ePayment status via Lookup API
     */
    public static function lookupKhaltiPayment($pidx) {
        $cfg = self::getKhaltiConfig();

        if (empty($pidx)) {
            return ['success' => false, 'message' => 'Empty pidx provided for Khalti lookup.'];
        }

        $payload = json_encode(['pidx' => trim($pidx)]);

        $headers = [
            'Authorization: Key ' . trim($cfg['secret_key']),
            'Content-Type: application/json'
        ];

        $lookup_urls = [
            $cfg['lookup_url'],
            ($cfg['is_live'] ? 'https://dev.khalti.com/api/v2/epayment/lookup/' : 'https://khalti.com/api/v2/epayment/lookup/'),
            'https://a.khalti.com/api/v2/epayment/lookup/'
        ];
        $lookup_urls = array_unique($lookup_urls);

        $last_res = '';
        $last_code = 0;
        $last_data = [];

        foreach ($lookup_urls as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err = curl_error($ch);
            curl_close($ch);

            if ($curl_err) {
                continue;
            }

            $res_data = json_decode($response, true);
            $last_res = $response;
            $last_code = $http_code;
            $last_data = $res_data;

            if ($http_code == 200 && is_array($res_data) && !empty($res_data['status'])) {
                $status = $res_data['status'] ?? '';
                $is_completed = (strcasecmp($status, 'Completed') === 0);

                return [
                    'success' => $is_completed,
                    'status' => $status,
                    'transaction_id' => $res_data['transaction_id'] ?? $pidx,
                    'total_amount' => isset($res_data['total_amount']) ? ($res_data['total_amount'] / 100) : 0,
                    'fee' => isset($res_data['fee']) ? ($res_data['fee'] / 100) : 0,
                    'data' => $res_data,
                    'raw' => $response
                ];
            }
        }

        return [
            'success' => false,
            'http_code' => $last_code,
            'message' => 'Khalti verification failed: ' . ($last_res ? $last_res : 'HTTP ' . $last_code),
            'response' => $last_data
        ];
    }
}

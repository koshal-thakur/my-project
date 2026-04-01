<?php

if (!function_exists('razorpay_load_local_env')) {
    function razorpay_load_local_env(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '') {
                continue;
            }

            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (!function_exists('razorpay_get_key_id')) {
    function razorpay_get_key_id(): string
    {
        razorpay_load_local_env();
        return trim((string)(getenv('RAZORPAY_KEY_ID') ?: ''));
    }
}

if (!function_exists('razorpay_get_key_secret')) {
    function razorpay_get_key_secret(): string
    {
        razorpay_load_local_env();
        return trim((string)(getenv('RAZORPAY_KEY_SECRET') ?: ''));
    }
}

if (!function_exists('razorpay_is_configured')) {
    function razorpay_is_configured(): bool
    {
        return razorpay_get_key_id() !== '' && razorpay_get_key_secret() !== '';
    }
}

if (!function_exists('razorpay_keys_need_setup')) {
    function razorpay_keys_need_setup(): bool
    {
        $keyId = strtolower(razorpay_get_key_id());
        $keySecret = strtolower(razorpay_get_key_secret());

        if ($keyId === '' || $keySecret === '') {
            return true;
        }

        $placeholderHints = array('replace', 'your_key', 'your-secret', 'placeholder', 'change_me');
        foreach ($placeholderHints as $hint) {
            if (str_contains($keyId, $hint) || str_contains($keySecret, $hint)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('razorpay_is_mock_mode_enabled')) {
    function razorpay_is_mock_mode_enabled(): bool
    {
        razorpay_load_local_env();
        return trim((string)(getenv('RAZORPAY_MOCK_MODE') ?: '')) === '1';
    }
}

if (!function_exists('razorpay_api_request')) {
    function razorpay_api_request(string $endpoint, array $payload): array
    {
        $keyId = razorpay_get_key_id();
        $keySecret = razorpay_get_key_secret();

        if ($keyId === '' || $keySecret === '') {
            return array('ok' => false, 'error' => 'Razorpay API keys are not configured.');
        }

        $ch = curl_init('https://api.razorpay.com/v1/' . ltrim($endpoint, '/'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $customCaBundle = trim((string)(getenv('RAZORPAY_CA_BUNDLE') ?: getenv('CURL_CA_BUNDLE') ?: ''));
        $allowInsecureSsl = trim((string)(getenv('RAZORPAY_SSL_NO_VERIFY') ?: '')) === '1';

        if ($allowInsecureSsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            if ($customCaBundle !== '' && is_file($customCaBundle)) {
                curl_setopt($ch, CURLOPT_CAINFO, $customCaBundle);
            }
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $responseBody === null) {
            return array('ok' => false, 'error' => $curlError !== '' ? $curlError : 'Unable to reach Razorpay API.');
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            return array('ok' => false, 'error' => 'Invalid Razorpay API response.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = $decoded['error']['description'] ?? ($decoded['error']['code'] ?? 'Razorpay request failed.');
            return array('ok' => false, 'error' => (string)$errorMessage);
        }

        return array('ok' => true, 'data' => $decoded);
    }
}

if (!function_exists('razorpay_create_order')) {
    function razorpay_create_order(int $amountInPaise, string $receipt, array $notes = array()): array
    {
        if (razorpay_is_mock_mode_enabled()) {
            return array(
                'ok' => true,
                'data' => array(
                    'id' => 'order_mock_' . bin2hex(random_bytes(6)),
                    'amount' => $amountInPaise,
                    'currency' => 'INR',
                    'receipt' => $receipt,
                    'notes' => $notes,
                    'mock' => true
                )
            );
        }

        return razorpay_api_request('orders', array(
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'receipt' => $receipt,
            'notes' => $notes,
            'payment_capture' => 1
        ));
    }
}

if (!function_exists('razorpay_verify_signature')) {
    function razorpay_verify_signature(string $orderId, string $paymentId, string $signature): bool
    {
        if (razorpay_is_mock_mode_enabled()) {
            return true;
        }

        $secret = razorpay_get_key_secret();
        if ($secret === '') {
            return false;
        }

        $payload = $orderId . '|' . $paymentId;
        $generated = hash_hmac('sha256', $payload, $secret);
        return hash_equals($generated, $signature);
    }
}

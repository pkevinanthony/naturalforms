<?php

namespace App\Services\NMI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\NMI\PaymentException;
use App\Exceptions\NMI\GatewayException;

class NMIGatewayService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://secure.networkmerchants.com/api/transact.php';
    protected bool $testMode;

    public function __construct()
    {
        $this->apiKey = config('nmi.api_key');
        $this->testMode = config('nmi.test_mode', true);
    }

    /**
     * Send request to NMI gateway
     */
    protected function sendRequest(array $params): array
    {
        $params['security_key'] = $this->apiKey;

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->baseUrl, $params);

            $result = $this->parseResponse($response->body());

            if ($result['response'] != 1) {
                throw new PaymentException(
                    $result['responsetext'] ?? 'Transaction failed',
                    $result['response_code'] ?? 0
                );
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('NMI Gateway Error', [
                'message' => $e->getMessage(),
                'params' => array_diff_key($params, ['security_key' => '']),
            ]);
            throw new GatewayException('Payment gateway error: ' . $e->getMessage());
        }
    }

    /**
     * Parse NMI response string to array
     */
    protected function parseResponse(string $response): array
    {
        $result = [];
        parse_str($response, $result);
        return $result;
    }

    /**
     * Create a sale transaction using a payment token
     */
    public function sale(string $paymentToken, float $amount, array $options = []): array
    {
        $params = array_merge([
            'type' => 'sale',
            'payment_token' => $paymentToken,
            'amount' => number_format($amount, 2, '.', ''),
        ], $options);

        return $this->sendRequest($params);
    }

    /**
     * Authorize a transaction (capture later)
     */
    public function authorize(string $paymentToken, float $amount, array $options = []): array
    {
        $params = array_merge([
            'type' => 'auth',
            'payment_token' => $paymentToken,
            'amount' => number_format($amount, 2, '.', ''),
        ], $options);

        return $this->sendRequest($params);
    }

    /**
     * Capture a previously authorized transaction
     */
    public function capture(string $transactionId, float $amount = null): array
    {
        $params = [
            'type' => 'capture',
            'transactionid' => $transactionId,
        ];

        if ($amount !== null) {
            $params['amount'] = number_format($amount, 2, '.', '');
        }

        return $this->sendRequest($params);
    }

    /**
     * Void a transaction
     */
    public function void(string $transactionId): array
    {
        return $this->sendRequest([
            'type' => 'void',
            'transactionid' => $transactionId,
        ]);
    }

    /**
     * Refund a transaction
     */
    public function refund(string $transactionId, float $amount = null): array
    {
        $params = [
            'type' => 'refund',
            'transactionid' => $transactionId,
        ];

        if ($amount !== null) {
            $params['amount'] = number_format($amount, 2, '.', '');
        }

        return $this->sendRequest($params);
    }

    /**
     * Add a customer to the vault using a payment token
     */
    public function addToVault(string $paymentToken, array $customerData = []): array
    {
        $params = array_merge([
            'customer_vault' => 'add_customer',
            'payment_token' => $paymentToken,
        ], $customerData);

        return $this->sendRequest($params);
    }

    /**
     * Update customer vault record
     */
    public function updateVault(string $vaultId, array $customerData = []): array
    {
        $params = array_merge([
            'customer_vault' => 'update_customer',
            'customer_vault_id' => $vaultId,
        ], $customerData);

        return $this->sendRequest($params);
    }

    /**
     * Delete a customer from the vault
     */
    public function deleteFromVault(string $vaultId): array
    {
        return $this->sendRequest([
            'customer_vault' => 'delete_customer',
            'customer_vault_id' => $vaultId,
        ]);
    }

    /**
     * Charge a stored customer vault record
     */
    public function chargeVault(string $vaultId, float $amount, array $options = []): array
    {
        $params = array_merge([
            'type' => 'sale',
            'customer_vault_id' => $vaultId,
            'amount' => number_format($amount, 2, '.', ''),
        ], $options);

        return $this->sendRequest($params);
    }

    /**
     * Create a recurring subscription
     */
    public function createSubscription(array $params): array
    {
        $subscriptionParams = array_merge([
            'recurring' => 'add_subscription',
        ], $params);

        return $this->sendRequest($subscriptionParams);
    }

    /**
     * Update a subscription
     */
    public function updateSubscription(string $subscriptionId, array $params): array
    {
        $subscriptionParams = array_merge([
            'recurring' => 'update_subscription',
            'subscription_id' => $subscriptionId,
        ], $params);

        return $this->sendRequest($subscriptionParams);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        return $this->sendRequest([
            'recurring' => 'delete_subscription',
            'subscription_id' => $subscriptionId,
        ]);
    }

    /**
     * Validate a payment token without charging
     */
    public function validateToken(string $paymentToken): array
    {
        return $this->sendRequest([
            'type' => 'validate',
            'payment_token' => $paymentToken,
        ]);
    }
}

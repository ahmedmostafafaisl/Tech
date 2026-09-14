<?php

namespace App\Services\Payment;

use App\Models\Appointment;
use Illuminate\Support\Str;
use App\Models\DyPaymentLink;
use App\Models\ClickPayPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ClickPayService
{
    protected string $baseUrl;
    protected string $serverKey;
    protected int $profileId;
    protected string $publicUrl;

    public function __construct()
    {

        $this->baseUrl   = (string) config('services.clickpay.base_url', 'https://secure.clickpay.com.sa');
        $this->serverKey = (string) config('services.clickpay.server_key', '');
        $this->profileId = (int) config('services.clickpay.profile_id', 0);

        $this->publicUrl = rtrim((string) config('services.clickpay.public_url', config('app.url')), '/');
    }

    /**
     * Builds an absolute callback/return URL using $this->publicUrl as
     * the domain instead of route()'s default host.
     */
    protected function publicRoute(string $name, array $params = []): string
    {
        // `false` = relative path only (e.g. "/api/integration/clickpay/callback?...")
        $path = route($name, $params, false);

        return $this->publicUrl . $path;
    }

    /**
     * Create ClickPay payment
     */
    public function createInvoice(Appointment $appointment, float $amount,  $phone, $isSingle): array
    {
        $user = $appointment->customer ?? auth()->user();
        $address = $appointment->appAddress;

        $payload = [
            'profile_id'       => $this->profileId,
            'tran_type'        => 'sale',
            'tran_class'       => 'ecom',
            'cart_id'          => 'ORDER-' . Str::uuid(),
            'cart_currency'    => 'SAR',
            'cart_amount'      => round($amount, 2),
            'cart_description' => "Appointment #{$appointment->id}",
            'paypage_lang'     => 'en',

            'customer_details' => [
                'name'     => $user->username ?? 'first last',
                'email'    => $user->email ?? 'email@domain.com',
                'phone'    => $phone ?? '0522222222',
                'street1'  => $address->street ?? 'address street',
                'city'     => $address->city ?? 'Riyadh',
                'state'    => 'Ar Riyadh',
                'country'  => 'SA',
                'zip'      => $address->zip ?? '12345',
                'ip'       => request()->ip() ?? '1.1.1.1',
            ],

            'shipping_details' => [
                'name'     => $user->username ?? 'Receiver Name',
                'email'    => $user->email ?? 'email1@domain.com',
                'phone'    => $user->phone ?? '971555555555',
                'street1'  => $address->street ?? 'Street 123',
                'city'     => $address->city ?? 'Riyadh',
                'state'    => 'Ar Riyadh',
                'country'  => 'SA',
                'zip'      => $address->zip ?? '54321',
                'ip'       => request()->ip() ?? '2.2.2.2',
            ],

            'callback' => $this->publicRoute('clickpay.callback', [
                'reference_id' => $appointment->id,
                'payment_type' => "clickpay",
                'type' => 'appointment',
                'is_single' => $isSingle,
            ]),
            'return' => $this->publicRoute('clickpay.return', [
                'reference_id' => $appointment->id,
                'payment_type' => "clickpay",
                'type' => 'appointment',
                'amount' => $amount
            ]),
        ];

        try {
            $response = Http::withHeaders([
                'authorization' => $this->serverKey,
                'Content-Type'  => 'application/json',
            ])->post("{$this->baseUrl}/payment/request", $payload);

            $json = $response->json();

            if (!empty($json['redirect_url'])) {
                ClickPayPayment::create([
                    'user_id' => $user->id,
                    'reference_id' => $json['tran_ref'],
                    'appointment_id' => $appointment->id,
                    'session_url' => $json['redirect_url'],
                    'amount' => $amount,
                    'status' => 'created',
                ]);
                return [
                    'success' => true,
                    'redirect_url' => $json['redirect_url'],
                    'reference_id' => $json['tran_ref'],
                ];
            }

            return [
                'success'  => false,
                'message'  => $json['response']['message'] ?? 'Payment initiation failed',
                'response' => $json['response'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('ClickPay Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    public function createInvoiceNew($payment, $price, $phone, $sales_order_id)
    {
        $reference_id = $payment['reference_id'];
        $payload = [
            'profile_id'       => $this->profileId,
            'tran_type'        => 'sale',
            'tran_class'       => 'ecom',
            'cart_id'          => 'ORDER-' . Str::uuid(),
            'cart_currency'    => 'SAR',
            'cart_amount'      => round($price, 2),
            'cart_description' => "Appointment #{$reference_id}",
            'paypage_lang'     => 'en',

            'customer_details' => [
                'name'     =>   'first last',
                'email'    => 'email@domain.com',
                'phone'    => $phone ?? '0522222222',
                'street1'  => 'address street',
                'city'     => 'Riyadh',
                'state'    => 'Ar Riyadh',
                'country'  => 'SA',
                'zip'      => '12345',
                'ip'       => request()->ip() ?? '1.1.1.1',
            ],

            'shipping_details' => [
                'name'     => 'Receiver Name',
                'email'    => 'email1@domain.com',
                'phone'    => '971555555555',
                'street1'  => 'Street 123',
                'city'     => 'Riyadh',
                'state'    => 'Ar Riyadh',
                'country'  => 'SA',
                'zip'      => '54321',
                'ip'       => request()->ip() ?? '2.2.2.2',
            ],

            'callback' => $this->publicRoute('new.clickpay.callback', [
                'reference_id' => $reference_id,
                'sales_order_id' => $sales_order_id,
            ]),
            'return' => $this->publicRoute('new.clickpay.return', [
                'reference_id' => $reference_id,
                'sales_order_id' => $sales_order_id,
            ]),
        ];

        try {
            $response = Http::withHeaders([
                'authorization' => $this->serverKey,
                'Content-Type'  => 'application/json',
            ])->post("{$this->baseUrl}/payment/request", $payload);

            $json = $response->json();

            if (!empty($json['redirect_url'])) {
                $payment->reference_id = $json['tran_ref'];
                $payment->save();
                return [
                    'success' => true,
                    'redirect_url' => $json['redirect_url'],
                    'reference_id' => $json['tran_ref'],
                ];
            }

            return [
                'success'  => false,
                'message'  => $json['response']['message'] ?? 'Payment initiation failed',
                'response' => $json['response'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('ClickPay Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    public function dyCreateInvoice($data, $payment, float $amount, array $overrides = [])
    {
        $payload = [
            'profile_id'       => $this->profileId,
            'tran_type'        => 'sale',
            'tran_class'       => 'ecom',
            'cart_id'          => $overrides['cart_id'] ?? 'ORDER-' . Str::uuid(),
            'cart_currency'    => 'SAR',
            'cart_amount'      => round($amount, 2),
            'cart_description' => $overrides['description'] ?? "Appointment #{$data['phone']}",
            'paypage_lang'     => $overrides['lang'] ?? 'en',

            'customer_details' => [
                'name'     => $data['name'] ?? 'first last',
                'email'    => $data['email'] ?? 'email@domain.com',
                'phone'    => $data['phone'] ?? '0522222222',
                'street1'  => $data['street1'] ?? 'address street',
                'city'     => $data['city'] ?? 'Riyadh',
                'state'    => $data['state'] ?? 'Ar Riyadh',
                'country'  => 'SA',
                'zip'      => $data['zip'] ?? '12345',
                'ip'       => request()->ip() ?? '1.1.1.1',
            ],

            'shipping_details' => [
                'name'     => $data['name'] ?? 'Receiver Name',
                'email'    => $data['email'] ?? 'email1@domain.com',
                'phone'    => $data['phone'] ?? '971555555555',
                'street1'  => $data['street1'] ?? 'Street 123',
                'city'     => $data['city'] ?? 'Riyadh',
                'state'    => $data['state'] ?? 'Ar Riyadh',
                'country'  => 'SA',
                'zip'      => $data['zip'] ?? '54321',
                'ip'       => request()->ip() ?? '2.2.2.2',
            ],
            'callback' => $this->publicUrl . '/api/clickpay/callback?' . http_build_query([
                'reference_id' => $payment['reference_id'],
                'payment_type' => $payment['payment_type'],
                'type' => 'dy',
            ]),

            'return' => $this->publicUrl . '/api/clickpay/return-dy?' . http_build_query([
                'reference_id' => $payment['reference_id'],
                'payment_type' => $payment['payment_type'],
                'type' => 'dy',
            ]),
        ];

        if (isset($overrides['card_details'])) {
            $payload['card_details'] = $overrides['card_details'];
        }

        try {
            $response = Http::withHeaders([
                'authorization' => $this->serverKey,
                'Content-Type'  => 'application/json',
            ])->post("{$this->baseUrl}/payment/request", $payload);

            $json = $response->json();
            if (
                ($json['redirect_url'])
            ) {
                $dyPayment = DyPaymentLink::create([
                    'payment_method' => 'clickpay',
                    'payment_reference_id' => $json['tran_ref'],
                    'dy_reference_id' => $payment['reference_id'],
                    'checkout_url' => $json['redirect_url'],
                    'status' => 'created',
                    'amount' => $amount,
                    'phone' => $data['phone'] ?? '0522222222',
                ]);
                $dyPayment->lines()->createMany($payment['items']);
                return [
                    'success'      => true,
                    'redirect_url' => $json['redirect_url'],
                    'transaction'  => $json,
                    'reference_id' => $json['tran_ref'],
                ];
            }

            return [
                'success'  => false,
                'message'  => $json['response']['message'] ?? 'Payment initiation failed',
                'response' => $json['response'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('ClickPay Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Queries ClickPay's real, current status for a transaction.
     *
     * Extracted as its own clean method — previously this logic was
     * duplicated inline inside handleReturn() but made unreachable by
     * an early `return $data = $response->json();` statement above it.
     * All authorization-checking/DB-updating logic now lives in the
     * callers (ClickPayPaymentSyncService, controller methods) instead
     * of inside the service, matching how retrieveTabbyPayment() and
     * getOrderStatus() (Tamara) are just plain query methods too.
     */
    public function queryPayment(string $tranRef): array
    {
        $payload = [
            'tran_ref'   => $tranRef,
            'profile_id' => $this->profileId,
        ];

        $response = Http::withHeaders([
            'authorization' => $this->serverKey,
            'content-type'  => 'application/json',
        ])->post("{$this->baseUrl}/payment/query", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'ClickPay Query Payment failed. HTTP ' . $response->status() . ': ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * @deprecated Use queryPayment() instead. Kept only so any existing
     * callers don't break, but the old dead-code path (early return
     * before the authorization check/capture logic below it) has been
     * removed — this now just delegates to queryPayment() and returns
     * the raw response, matching what it actually did in practice before
     * (the unreachable code after the early return never ran anyway).
     */
    public function handleReturn($tran_ref)
    {
        return $this->queryPayment($tran_ref);
    }

    /**
     * Process refund
     */
    public function refund(string $tranRef, float $amount, string $reason = 'Customer refund'): array
    {
        $payload = [
            'tran_ref'      => $tranRef,
            'refund_amount' => $amount,
            'refund_reason' => $reason,
        ];

        $response = Http::withHeaders([
            'authorization' => $this->serverKey,
            'content-type'  => 'application/json',
        ])->post("{$this->baseUrl}/v2/refund", $payload);

        return $response->json();
    }
}

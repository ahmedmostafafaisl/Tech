<?php

namespace App\Services\Payment;


use App\Models\Appointment;
use App\Models\TabbyPayment;
use App\Models\DyPaymentLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class TabbyService
{
    private string $tabbyBaseUrl;
    private string $tabbySecretKey;
    private string $tabbyPublicKey;
    private string $merchantCode;

    public function __construct()
    {
        $this->tabbyBaseUrl   = (string) config('services.tabby.base_url', 'https://api.tabby.ai/api/v2/');
        $this->tabbySecretKey = (string) config('services.tabby.secret_key', '');
        $this->tabbyPublicKey = (string) config('services.tabby.public_key', '');
        $this->merchantCode   = (string) config('services.tabby.merchant_code', 'Naqiappsau');
    }

    // 1- create checkout
    public function checkout(Appointment $appointment, float $amount,  $phone, $isSingle)
    {
        $user = $appointment->customer ?? auth()->user();
        $address = $appointment->appAddress;

        // Build routes with array sales_ids (Laravel will serialize them into multiple query params)
        $urls = [
            "success" => route('tabby.success', [
                'appointment_id' => $appointment->id,
                'is_single' => $isSingle,
                'amount' => $amount,
                'phone' => $phone ?? "500000001",
            ]),
            "cancel" => route('tabby.cancel', [
                'appointment_id' => $appointment->id,
                'is_single' => $isSingle,
                'amount' => $amount,
                'phone' => $phone ?? "500000001",
            ]),
            "failure" => route('tabby.failure', [
                'appointment_id' => $appointment->id,
                'is_single' => $isSingle,
                'amount' => $amount,
                'phone' => $phone ?? "500000001",
            ]),
        ];
        $latitude = $appointment->latitude ?? "25.867589";
        $longitude = $appointment->longitude ?? "45.367350";

        $payload = [
            "payment" => [
                "amount" => (string)$amount,
                "currency" => "SAR",
                "description" => "Appointment #{$appointment->id}",
                "buyer" => [
                    "phone" => $phone ?? "500000001",
                    // "email" => $user->email,
                    "name" => $user->username ?? "Naqi",
                    "dob" => $user->birth_date ?? "1996-08-24",
                ],
                // "shipping_address" => [
                //     "city" => $address->city ?? "Riyadh",
                //     "address" => $address->country ?? "Saudi Arabia",
                //     "zip" => "1234",
                // ],
                "order" => [
                    "reference_id" => (string) $appointment->id,
                    "updated_at" => Carbon::now()->toIso8601String(),
                    "tax_amount" => "0.00",
                    "shipping_amount" => "0.00",
                    "discount_amount" => "0.00",
                    "items" => $appointment->lines->map(function ($item) {
                        return [
                            "title" => $item->name ?? "Service",
                            "description" => $item->description ?? "Appointment item",
                            "quantity" => $item->quantity ?? 1,
                            "unit_price" => number_format($item->price, 2, '.', ''),
                            "discount_amount" => number_format($item->discount ?? 0.00, 2, '.', ''),
                            "reference_id" => (string) $item->id,
                            "category" => "Services",
                            "is_refundable" => true,
                        ];
                    })->toArray(),
                ],
                "attachment" => [
                    "body"         => json_encode([
                        // put location
                        "location" => "{$latitude}, {$longitude}"
                    ]),
                    "content_type" => "application/vnd.tabby.v1+json"
                ],
            ],
            "lang" => "ar",
            "merchant_code" => $this->merchantCode,
            "merchant_urls" => $urls,
            // "token" => null
        ];


        $response = Http::baseUrl($this->tabbyBaseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->tabbySecretKey,
                'Content-Type' => 'application/json',
            ])
            ->post("checkout", $payload);

        $responseData = $response->json();

        // ✅ Only proceed if no error AND id exists
        if (
            isset($responseData['status']) &&
            $responseData['status'] === 'created' &&
            isset($responseData['id']) &&
            isset($responseData['configuration']['available_products']['installments'][0]['web_url'])
        ) {
            $webUrl = $responseData['configuration']['available_products']['installments'][0]['web_url'];

            // 🔹 Now it's safe to call send_hpp_link
            Http::baseUrl($this->tabbyBaseUrl)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->tabbySecretKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("checkout/{$responseData['id']}/send_hpp_link", $payload);

            // Save to DB
            TabbyPayment::create([
                'user_id'       => $user->id,
                'reference_id'  => $responseData['id'],
                'appointment_id' => $appointment->id,
                'session_url'   => $webUrl,
                'amount'        => $amount,
                'status'        => 'created',
            ]);

            return response()->json([
                'web_url'      => $webUrl,
                'reference_id' => $responseData['id']
            ], 200);
        }

        // ❌ Error case
        return response()->json([
            'error'   => $responseData['error']['message'] ?? 'Failed to create Tabby session.',
            'details' => $responseData
        ], 422);
    }
    // dy checkout
    public function dyCheckout(array $data, array $payment)
    {

        $payload = [
            "payment" => [
                "amount" => $payment['amount'],
                "currency" => "SAR",
                "description" => "DY365 Payment",
                "buyer" => [
                    "phone" => str_replace("+966", "", $data['phone'] ?? "500000001"),
                    "email" => $data['email'] ?? "card.success@tabby.ai", // ✅ Hardcoded to satisfy Tabby's test environment
                    "name" => $data['name'] ?? "Naqi",
                    "dob" => $data['dob'] ?? "1996-08-24",
                ],

                "shipping_address" => [
                    "city" => $data['city'] ?? "Riyadh",
                    "address" => $data['country'] ?? "Saudi Arabia",
                    "zip" => $data['shipping_address']['zip'] ?? "1234",
                ],
                "order" => [
                    "reference_id" => (string) $payment['reference_id'],
                    "updated_at" => Carbon::now()->toIso8601String(),
                    "tax_amount" => "0.00",
                    "shipping_amount" => "0.00",
                    "discount_amount" => "0.00",
                    "items" => collect($payment['items'] ?? [])->map(function ($item) {
                        return [
                            "title" => $item['name'] ?? "Service",
                            "description" => $item['description'] ?? "Sales item",
                            "quantity" => $item['quantity'] ?? 1,
                            "unit_price" => number_format($item['price'], 2, '.', ''),
                            "discount_amount" => number_format($item['discount'] ?? 0.00, 2, '.', ''),
                            "reference_id" => (string) ($item['rec_id'] ?? ''),
                            "category" => "Services",
                            "is_refundable" => true,
                        ];
                    })->toArray(),
                ]
            ],
            "lang" => "ar",
            "merchant_code" => $this->merchantCode,
            "merchant_urls" => [
                "success" => route('dy.tabby.success', [
                    'reference_id'   => $payment['reference_id'],
                    'payment_method' => $payment['payment_type'], // e.g., 'tabby', 'visa'
                ]),
                "cancel" => route('dy.tabby.cancel', [
                    'reference_id'   => $payment['reference_id'],
                    'payment_method' => $payment['payment_type'],
                ]),
                "failure" => route('dy.tabby.failure', [
                    'reference_id'   => $payment['reference_id'],
                    'payment_method' => $payment['payment_type'],
                ]),
            ],
            "token" => null
        ];

        $response = Http::baseUrl($this->tabbyBaseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' .  $this->tabbySecretKey,
                'Content-Type' => 'application/json',
            ])
            ->post("checkout", $payload);

        $responseData = $response->json();


        if (isset($responseData['id'])) {
            $response = Http::baseUrl($this->tabbyBaseUrl)
                ->withHeaders([
                    'Authorization' => 'Bearer ' .  $this->tabbySecretKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("checkout/{$responseData['id']}/send_hpp_link", $payload);
        }
        if (
            isset($responseData['status']) &&
            $responseData['status'] === 'created' &&
            isset($responseData['configuration']['available_products']['installments'][0]['web_url'])
        ) {
            $webUrl = $responseData['configuration']['available_products']['installments'][0]['web_url'];
            // Save tabby session info
            $dyPayment = DyPaymentLink::create([
                'payment_method' => 'tabby',
                'payment_reference_id' => $responseData['payment']['id'],
                'dy_reference_id' => $payment['reference_id'],
                'checkout_url' => $webUrl,
                'status' => 'created',
                'amount' => $payment['amount'],
                'phone' => $data['phone'] ?? '0522222222',
            ]);
            $dyPayment->lines()->createMany($payment['items']);

            // dd($webUrl, $responseData['id'], $responseData['payment']['id']);
            return response()->json([
                'web_url'    => $webUrl,
                'checkout_id' => $responseData['id'],
                'payment_id' => $responseData['payment']['id'],
            ], 200);
        }

        return response()->json([
            'error' => $responseData['error']['message'] ?? 'Failed to create Tabby session.',
            'details' => $responseData
        ], 422);
    }


    // new capture payment
    public function checkoutNew($payment, $price, $phone, $sales_order_id)
    {
        $reference_id = $payment['reference_id'];

        // ⚠ This system doesn't currently capture customer email/name/DOB
        // anywhere on DirectAppointment or DirectAppointmentPayment — there
        // was never a real $data source for these fields (the previous
        // code referenced an undefined $data variable, so these fallbacks
        // were ALWAYS being sent regardless, silently). If real customer
        // name/email becomes available somewhere, wire it in here instead
        // of these static placeholders.
        $buyerEmail   = "card.success@tabby.ai";
        $buyerName    = "Naqi";
        $buyerDob     = "1996-08-24";
        $buyerCity    = "Riyadh";
        $buyerAddress = "Saudi Arabia";
        $buyerZip     = "1234";

        $payload = [
            "payment" => [
                "amount" => $price,
                "currency" => "SAR",
                "description" => "DY365 Payment",
                "buyer" => [
                    "phone" => str_replace("+966", "", $phone ?? "500000001"),
                    "email" => $buyerEmail,
                    "name"  => $buyerName,
                    "dob"   => $buyerDob,
                ],

                "shipping_address" => [
                    "city"    => $buyerCity,
                    "address" => $buyerAddress,
                    "zip"     => $buyerZip,
                ],
                "order" => [
                    "reference_id" => (string) $reference_id,
                    "updated_at" => Carbon::now()->toIso8601String(),
                    "tax_amount" => "0.00",
                    "shipping_amount" => "0.00",
                    "discount_amount" => "0.00",
                    "items" => [
                        [
                            "title" =>  "Service",
                            "description" =>  "Sales item",
                            "quantity" => 1,
                            "unit_price" =>  number_format($price ?? 0, 2, '.', ''),
                            "discount_amount" => 0.00,
                            "reference_id" => (string) $reference_id,
                            "category" => "Services",
                            "is_refundable" => true,
                        ]
                    ],

                ]
            ],
            "lang" => "ar",
            "merchant_code" => $this->merchantCode,
            "merchant_urls" => [
                "success" => route('new.tabby.success', [
                    'reference_id'   => $reference_id,
                    'sales_order_id' => $sales_order_id,
                ]),
                "cancel" => route('new.tabby.cancel', [
                    'reference_id'   => $reference_id,
                    'sales_order_id' => $sales_order_id,
                ]),
                "failure" => route('new.tabby.failure', [
                    'reference_id'   => $reference_id,
                    'sales_order_id' => $sales_order_id,
                ]),
            ],
            "token" => null
        ];

        $response = Http::baseUrl($this->tabbyBaseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' .  $this->tabbyPublicKey,
                'Content-Type' => 'application/json',
            ])
            ->post("checkout", $payload);

        if (!$response->successful()) {
            Log::error('Tabby checkout request failed.', [
                'reference_id' => $reference_id,
                'status'       => $response->status(),
                'body'         => $response->body(),
            ]);

            return response()->json([
                'error'   => 'Failed to create Tabby session.',
                'details' => $response->json(),
            ], 400);
        }

        $responseData = $response->json();

        if (isset($responseData['id'])) {
            $payment->payment_id = $responseData['payment']['id'] ?? null;
            $payment->save();

            $hppResponse = Http::baseUrl($this->tabbyBaseUrl)
                ->withHeaders([
                    'Authorization' => 'Bearer ' .  $this->tabbyPublicKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("checkout/{$responseData['id']}/send_hpp_link", $payload);

            if (!$hppResponse->successful()) {
                Log::error('Failed to send Tabby hosted payment page link.', [
                    'reference_id' => $reference_id,
                    'checkout_id'  => $responseData['id'],
                    'status'       => $hppResponse->status(),
                    'body'         => $hppResponse->body(),
                ]);
                // Not fatal — the checkout itself succeeded and web_url
                // below still works as a fallback delivery method.
            }
        }

        if (
            isset($responseData['status']) &&
            $responseData['status'] === 'created' &&
            isset($responseData['configuration']['available_products']['installments'][0]['web_url'])
        ) {
            $webUrl = $responseData['configuration']['available_products']['installments'][0]['web_url'];

            return response()->json([
                'web_url' => $webUrl,
                // Fixed: this is now OUR actual reference_id (used to look
                // up the payment later, including by the webhook), not
                // Tabby's checkout session ID as it was before.
                'reference_id' => $reference_id,
            ], 200);
        }

        Log::warning('Unexpected Tabby checkout response shape.', [
            'reference_id' => $reference_id,
            'response'     => $responseData,
        ]);

        return response()->json(
            [
                'error' => $responseData['error']['message'] ?? 'Failed to create Tabby session.',
                'details' => $responseData
            ],
            400
        );
    }

    public function capturePaymentRequest($payment_id, $reference_id, $amount)
    {
        $response = Http::withToken($this->tabbySecretKey)
            ->baseUrl($this->tabbyBaseUrl)
            ->acceptJson()
            ->asJson()
            ->post("payments/{$payment_id}/captures", [
                'amount' => number_format(
                    (float) $amount,
                    2,
                    '.',
                    ''
                ),
                'currency' => 'SAR',
                'tax_amount' => '0.00',
                'shipping_amount' => '0.00',
                'discount_amount' => '0.00',
                'reference_id' => $reference_id,
            ]);


        if (!$response->successful()) {
            throw new \RuntimeException(
                'Tabby Capture failed. HTTP ' .
                    $response->status() .
                    ': ' .
                    $response->body()
            );
        }

        return $response->json();
    }


    public function createSession($data)
    {
        $body = $this->getConfig($data);

        Log::info(json_encode($body));
        $http = Http::withToken($this->tabbyPublicKey)->baseUrl(url: $this->tabbyBaseUrl)->withHeaders(['Content-Type' => 'application/json']);
        $response = $http->post('checkout', data: $body);
        $response = json_decode($response->getBody()->getContents(), true);
        return $response;
    }

    public function getConfig($data)
    {
        $now = Carbon::now();

        return [
            "payment" => [
                "amount" => $data['amount'],
                "currency" => "SAR",
                "description" => "Centrial Payment",
                "buyer" => [
                    "phone" =>   $data['user']->phone,
                    "email" =>   $data['user']->email ?? "card.success@tabby.ai",
                    "name" => $data['user']->username ?? "Centerial Mall",
                    "dob" => $data['user']->birth_date ?? "1996-08-24",
                ],
                "shipping_address" =>  [
                    "city" => "Riyadh",
                    "address" => "Saudi Riyadh",
                    "zip" => "1234"
                ],
                "order" => [
                    "tax_amount" => "0.00",
                    "shipping_amount" => "0.00",
                    "discount_amount" => "0.00",
                    "updated_at" => $now,
                    "reference_id" => $data['reference_id'],
                    "items" => [
                        [
                            "title" => $data['lang'] == "ar" ? $data['item']->name_ar : $data['item']->name_en,
                            "description" => $data['lang'] == "ar" ? $data['item']->name_ar : $data['item']->name_en,
                            "quantity" => (int)$data['qty'],
                            "unit_price" => (string)$data['item']->price,
                            "discount_amount" => "0.00",
                            "reference_id" => (string)$data['item']->id,
                            "category" => "Car Services",
                        ],
                    ],
                ],

                "order_history" => null,
                "meta" => [
                    "order_id" => null,
                    "customer" => null
                ],
                "attachment" => null
            ],
            "lang" => "ar",
            "merchant_code" => $this->merchantCode,
            "merchant_urls" => [
                "success" => "https://new.xn--shopperl-i1a.com/api/payment/success",
                "cancel" => "https://new.xn--shopperl-i1a.com/api/payment/cancel",
                "failure" => "https://new.xn--shopperl-i1a.com/api/payment/failure"
            ],
            "token" => null
        ];
    }


    public function retrieveTabbySession($id)
    {
        $http = Http::withToken($this->tabbySecretKey)->baseUrl($this->tabbyBaseUrl);
        $response = $http->get(url: "checkout/$id");
        return json_decode($response->getBody()->getContents(), true);
    }

    public function retrieveTabbyPayment($id)
    {
        $response = Http::withToken($this->tabbySecretKey)
            ->baseUrl($this->tabbyBaseUrl)
            ->acceptJson()
            ->get("payments/{$id}");


        if (!$response->successful()) {
            throw new \RuntimeException(
                'Tabby Retrieve Payment failed. HTTP ' .
                    $response->status() .
                    ': ' .
                    $response->body()
            );
        }

        return $response->json();
    }
}

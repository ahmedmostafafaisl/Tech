<?php

namespace App\Services\Payment;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use App\Models\DyPaymentLink;
use App\Models\TamaraPayment;
use Illuminate\Support\Facades\Log;
use App\Models\DirectAppointmentPayment;

class TamaraService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected Client $client;

    public function __construct()
    {

        // php artisan config:cache runs in production.
        $this->apiUrl = (string) config('services.tamara.api_url', 'https://api.tamara.co/');
        $this->apiKey = (string) config('services.tamara.api_key', '');



        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    public function pre_checkout($phone, $amount)
    {
        try {
            $response = $this->client->get("/checkout/payment-types?country=SA&phone={$phone}&currency=SAR&order_value={$amount}");
            $types = json_decode($response->getBody(), true);
            if ($types == []) {
                return response()->json([
                    'status' => 'rejected',
                    'data' => $types,
                ], 400);
            }
            $response = $this->client->post('/checkout/payment-options-pre-check', [
                'json' => [
                    "country" => "SA",
                    "order_value" => [
                        "amount" => 1,
                        "currency" => "SAR"
                    ],
                    "phone_number" => $phone,
                    "is_vip" => "false"
                ]
            ]);

            $options = json_decode($response->getBody(), true);

            if (isset($options["error"])) {
                return response()->json([
                    'status' => 'rejected',
                    'data' => $options,
                ], 400);
            } elseif ($options["has_available_payment_options"] == false) {
                return response()->json([
                    'status' => 'rejected',
                    'data' => $options,
                ], 400);
            }

            return  "true";
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }


    public function createOrder($appointment, $paymentAmount,  $phone, $isSingle)
    {
        try {
            $user = $appointment->customer;
            $address = $appointment->appAddress;

            $orderReferenceId = (string) Str::uuid();
            $orderNumber = 'APP-' . $appointment->id;

            $merchantUrls = [
                "cancel" => route('tamara.cancel', [
                    'appointment_id' => $appointment->id,
                    'is_single' => $isSingle,
                    'amount' => $paymentAmount
                ]),
                "failure" => route('tamara.failure', [
                    'appointment_id' => $appointment->id,
                    'is_single' => $isSingle,
                    'amount' => $paymentAmount
                ]),
                "success" => route('tamara.success', [
                    'appointment_id' => $appointment->id,
                    'is_single' => $isSingle,
                    'amount' => $paymentAmount
                ]),
                "notification" => route('tamara.webhook'),
            ];

            $orderData = [
                "total_amount" => [
                    "amount" => round($paymentAmount, 2),
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "order_reference_id" => $orderReferenceId,
                "order_number" => $orderNumber,
                "discount" => [
                    "amount" => [
                        "amount" => round($appointment->discount_value ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "name" => "Appointment Discount"
                ],
                "items" => $appointment->lines->map(function ($item) {
                    return [
                        "name" => $item->name ?? "Item",
                        "type" => "Digital",
                        "reference_id" => (string) $item->id,
                        "sku" => "SKU-" . $item->id,
                        "quantity" => $item->quantity ?? 1,
                        "discount_amount" => [
                            "amount" => round($item->discount_value ?? 0, 2),
                            "currency" => "SAR"
                        ],
                        "tax_amount" => [
                            "amount" => 0,
                            "currency" => "SAR"
                        ],
                        "unit_price" => [
                            "amount" => round($item->price ?? 0, 2),
                            "currency" => "SAR"
                        ],
                        "total_amount" => [
                            "amount" => round($item->total_price ?? 0, 2),
                            "currency" => "SAR"
                        ]
                    ];
                })->values()->toArray(),
                "consumer" => [
                    "email" => $user->email ?? "customer@example.com",
                    "first_name" => $user->first_name ?? "Customer",
                    "last_name" => $user->last_name ?? "User",
                    "phone_number" => preg_replace('/\D/', '', $phone ?? "500000000")
                ],
                "country_code" => "SA",
                "description" => "Appointment #" . $appointment->id,
                "merchant_url" => $merchantUrls,
                "payment_type" => "PAY_BY_INSTALMENTS",
                "instalments" => 3,
                "billing_address" => [
                    "city" => $address->city ?? "Riyadh",
                    "country_code" => "SA",
                    "first_name" => $user->first_name ?? "Customer",
                    "last_name" => $user->last_name ?? "User",
                    "line1" => $address->line1 ?? "Street Line 1",
                    "line2" => $address->line2 ?? "Building Info",
                    "phone_number" => preg_replace('/\D/', '', $user->phone ?? "500000000"),
                    "region" => $address->region ?? "Region"
                ],
                "shipping_address" => [
                    "city" => $address->city ?? "Riyadh",
                    "country_code" => "SA",
                    "first_name" => $user->first_name ?? "Customer",
                    "last_name" => $user->last_name ?? "User",
                    "line1" => $address->line1 ?? "Street Line 1",
                    "line2" => $address->line2 ?? "Building Info",
                    "phone_number" => preg_replace('/\D/', '', $user->phone ?? "500000000"),
                    "region" => $address->region ?? "Region"
                ],
                "platform" => "Naqi",
                "is_mobile" => false,
                "locale" => "en_US"
            ];

            $response = $this->client->post('/checkout', [
                'json' => $orderData,
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (!isset($responseData['checkout_url'])) {
                return [
                    'error' => true,
                    'message' => 'Missing checkout_url in Tamara response',
                    'response' => $responseData
                ];
            }

            $checkoutUrl = $responseData['checkout_url'];
            $orderId = $responseData['order_id'] ?? null;

            TamaraPayment::create([
                'user_id' => $user->id,
                'appointment_id' => $appointment->id,
                'reference_id' => $orderId,
                'order_number' => $orderNumber,
                'checkout_url' => $checkoutUrl,
                'status' => 'created',
                'amount' => $paymentAmount,
            ]);


            return [
                'checkout_url' => $checkoutUrl,
                'reference_id' => $orderId
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function dyCreateOrder($data, $payment, $paymentAmount)
    {

        try {

            $orderData = [
                "total_amount" => [
                    "amount" => round($paymentAmount, 2),
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "order_reference_id" => $payment['reference_id'],
                "order_number" => $payment['reference_id'],
                "discount" => [
                    "amount" => [
                        "amount" => round($payment['discount'] ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "name" => "Appointment Discount"
                ],
                "items" => collect($payment['items'])->map(function ($item) {
                    return [
                        "name" => $item['name'] ?? "Item",
                        "type" => "Digital",
                        "reference_id" => (string) ($item['rec_id'] ?? $item['id'] ?? uniqid()),
                        "sku" => "SKU-" . ($item['rec_id'] ?? $item['id'] ?? uniqid()),
                        "quantity" => $item['quantity'] ?? 1,
                        "discount_amount" => [
                            "amount" => round($item['discount'] ?? $item['discount_value'] ?? 0, 2),
                            "currency" => "SAR"
                        ],
                        "tax_amount" => [
                            "amount" => 0,
                            "currency" => "SAR"
                        ],
                        "unit_price" => [
                            "amount" => round($item['price'] ?? 0, 2),
                            "currency" => "SAR"
                        ],
                        "total_amount" => [
                            "amount" => round(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2),
                            "currency" => "SAR"
                        ]
                    ];
                })->values()->toArray(),

                "consumer" => [
                    "email" => $data['email'] ?? "customer@example.com",
                    "first_name" => $data['name'] ?? "Customer",
                    "last_name" => $data['name'] ?? "User",
                    "phone_number" => preg_replace('/\D/', '', $data['phone'] ?? "500000000")
                ],
                "country_code" => "SA",
                "description" => "Appointment #" . $data['phone'],
                "merchant_url" => [
                    "success" => route('dy.tabby.success', [
                        'reference_id'   => $payment['reference_id'],
                        'payment_method' =>    $payment['payment_type'],
                    ]),
                    "cancel" => route('dy.tabby.cancel', [
                        'reference_id'   => $payment['reference_id'],
                        'payment_method' =>    $payment['payment_type'],
                    ]),
                    "failure" => route('dy.tabby.failure', [
                        'reference_id'   => $payment['reference_id'],
                        'payment_method' =>    $payment['payment_type'],
                    ]),
                    "notification" => route('dy.tabby.webhook', [
                        'reference_id'   => $payment['reference_id'],
                        'payment_method' => 'tamara',
                    ]),
                ],
                "payment_type" => "PAY_BY_INSTALMENTS",
                "instalments" => 3,
                "billing_address" => [
                    "city" => $data['city'] ?? "Riyadh",
                    "country_code" => "SA",
                    "first_name" => $data['name'] ?? "Customer",
                    "last_name" => $data['name'] ?? "User",
                    "line1" => $data['address_line1'] ?? "Street Line 1",
                    "line2" => $data['address_line2'] ?? "Building Info",
                    "phone_number" => preg_replace('/\D/', '', $data['phone'] ?? "500000000"),
                    "region" => $data['region'] ?? "Region"
                ],
                "shipping_address" => [
                    "city" => $data['city'] ?? "Riyadh",
                    "country_code" => "SA",
                    "first_name" => $data['name'] ?? "Customer",
                    "last_name" => $data['name'] ?? "User",
                    "line1" => $data['address_line1'] ?? "Street Line 1",
                    "line2" => $data['address_line2'] ?? "Building Info",
                    "phone_number" => preg_replace('/\D/', '', $data['phone'] ?? "500000000"),
                    "region" => $data['region'] ?? "Region"
                ],
                "platform" => "Naqi",
                "is_mobile" => false,
                "locale" => "en_US"
            ];

            $response = $this->client->post('/checkout', [
                'json' => $orderData,
            ]);
            $responseData = json_decode($response->getBody(), true);

            if (!isset($responseData['checkout_url'])) {
                return [
                    'error' => true,
                    'message' => 'Missing checkout_url in Tamara response',
                    'response' => $responseData
                ];
            }

            $dyPayment = DyPaymentLink::create([
                'payment_method' => 'tamara',
                'payment_reference_id' => $responseData['order_id'],
                'dy_reference_id' => $payment['reference_id'],
                'checkout_url' => $responseData['checkout_url'],
                'status' => 'created',
                'amount' => $paymentAmount,
                'phone' => $data['phone'] ?? '0522222222',
            ]);
            $dyPayment->lines()->createMany($payment['items']);

            return [
                'checkout_url' => $responseData['checkout_url'],
                'reference_id' => $responseData['order_id']
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function createOrderNew($payment, $price, $phone, $sales_order_id)
    {
        $reference_id = $payment['reference_id'];
        try {

            $orderData = [
                "total_amount" => [
                    "amount" => round($price, 2),
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "order_reference_id" => $reference_id,
                "order_number" => $reference_id,
                "discount" => [
                    "amount" => [
                        "amount" => round($payment['discount'] ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "name" => "Appointment Discount"
                ],
                "items" => [
                    [
                        "name" =>  "Item",
                        "type" => "Digital",
                        "reference_id" => (string) $reference_id,
                        "sku" => "SKU-" . $reference_id,
                        "quantity" =>  1,
                        "discount_amount" => [
                            "amount" => 0.00,
                            "currency" => "SAR"
                        ],
                        "tax_amount" => [
                            "amount" => 0,
                            "currency" => "SAR"
                        ],
                        "unit_price" => [
                            "amount" => round($price ?? 0, 2),
                            "currency" => "SAR"
                        ],
                        "total_amount" => [
                            "amount" => round(($price ?? 0) * 1, 2),
                            "currency" => "SAR"
                        ]
                    ]
                ],


                "consumer" => [
                    "email" =>  "customer@example.com",
                    "first_name" =>  "Customer",
                    "last_name" =>  "User",
                    "phone_number" => $phone ?? preg_replace('/\D/', '', "500000000")
                ],
                "country_code" => "SA",
                "description" => "Appointment #" . "500000000",
                "merchant_url" => [
                    "success" => route('new.tamara.success', [
                        'reference_id'   => $reference_id,
                        'sales_order_id' => $sales_order_id,

                    ]),
                    "cancel" => route('new.tamara.cancel', [
                        'reference_id'   => $reference_id,
                        'sales_order_id' => $sales_order_id,

                    ]),
                    "failure" => route('new.tamara.failure', [
                        'reference_id'   => $reference_id,
                        'sales_order_id' => $sales_order_id,

                    ]),
                    "notification" => route('new.tamara.notification', [
                        'reference_id'   => $reference_id,
                        'sales_order_id' => $sales_order_id,

                    ]),
                ],
                "payment_type" => "PAY_BY_INSTALMENTS",
                "instalments" => 3,
                "billing_address" => [
                    "city" => "Riyadh",
                    "country_code" => "SA",
                    "first_name" => "Customer",
                    "last_name" => "User",
                    "line1" =>    "Street Line 1",
                    "line2" => "Building Info",
                    "phone_number" => preg_replace('/\D/', '', "500000000"),
                    "region" => "Region"
                ],
                "shipping_address" => [
                    "city" => "Riyadh",
                    "country_code" => "SA",
                    "first_name" => "Customer",
                    "last_name" => "User",
                    "line1" => "Street Line 1",
                    "line2" => "Building Info",
                    "phone_number" => preg_replace('/\D/', '', "500000000"),
                    "region" => "Region"
                ],
                "platform" => "Naqi",
                "is_mobile" => false,
                "locale" => "en_US"
            ];
            $response = $this->client->post('/checkout', [
                'json' => $orderData,
            ]);

            $responseData = json_decode($response->getBody(), true);
            $payment->payment_id = $responseData['order_id'];
            $payment->save();
            if (!isset($responseData['checkout_url'])) {
                return [
                    'error' => true,
                    'message' => 'Missing checkout_url in Tamara response',
                    'response' => $responseData
                ];
            }
            return [
                'checkout_url' => $responseData['checkout_url'],
                'reference_id' => $responseData['order_id']
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function authorizeOrder($order_id)
    {
        try {
            $response = $this->client->post('orders/' . $order_id . '/authorise');

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function captureOrder(string $referenceId)
    {
        try {
            $payment = TamaraPayment::with('appointment.items')->where('reference_id', $referenceId)->firstOrFail();
            $appointment = $payment->appointment;

            if (!$appointment) {
                return ['error' => true, 'message' => 'Appointment not found.'];
            }
            $items = $appointment->items->map(function ($item) {
                return [
                    "name" => $item->name ?? "Unnamed Item",
                    "type" => "Digital",
                    "reference_id" => (string) $item->id,
                    "sku" => "SKU-" . $item->id,
                    "quantity" => (int) $item->quantity ?? 1,
                    "discount_amount" => [
                        "amount" => round($item->discount_value ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "tax_amount" => [
                        "amount" => 0,
                        "currency" => "SAR"
                    ],
                    "unit_price" => [
                        "amount" => round($item->price ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "total_amount" => [
                        "amount" => round($item->total_price ?? 0, 2),
                        "currency" => "SAR"
                    ]
                ];
            })->toArray();


            $payload = [
                "order_id" => $payment->reference_id,
                "total_amount" => [
                    "amount" => round($payment->amount, 2),
                    "currency" => "SAR"
                ],
                "items" => $items,
                "discount_amount" => [
                    "amount" => round($appointment->discount_value ?? 0, 2),
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "shipping_info" => [
                    "shipped_at" => Carbon::now()->toIso8601String(),
                    "shipping_company" => "Naqi Delivery",
                    "tracking_number" => "TRK" . $appointment->id,
                    "tracking_url" => "https://tracking.example.com?id=" . $appointment->id
                ]
            ];

            $response = $this->client->post('/payments/capture', [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody(), true);

            $payment->status = 'captured';
            $payment->save();

            return [
                'success' => true,
                'message' => 'Payment captured successfully.',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function captureOrderNew(string $referenceId, string $orderId)
    {

        try {
            $payment = DirectAppointmentPayment::with('directAppointment.lines')->where('reference_id', $referenceId)->firstOrFail();
            $payment->payment_id = $orderId;
            $payment->save();
            $appointment = $payment->directAppointment;

            if (!$appointment) {
                return ['error' => true, 'message' => 'Appointment not found.'];
            }
            $items = $appointment->lines->map(function ($item) {
                return [
                    "name" => $item->ItemNumber ?? "Unnamed Item",
                    "type" => "Digital",
                    "reference_id" => (string) $item->id ?? "unnamed",
                    "sku" => "SKU-" . $item->ItemNumber,
                    "quantity" => max(1, (int) ($item->quantity ?? 0)),
                    "discount_amount" => [
                        "amount" => round(0, 2),
                        "currency" => "SAR"
                    ],
                    "tax_amount" => [
                        "amount" => 0,
                        "currency" => "SAR"
                    ],
                    "unit_price" => [
                        "amount" => round($item->UnitPrice ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "total_amount" => [
                        "amount" => round($item->TotalAmount ?? 0, 2),
                        "currency" => "SAR"
                    ]
                ];
            })->toArray();


            $payload = [
                "order_id" => $orderId,
                "total_amount" => [
                    "amount" => round($payment->price, 2),
                    "currency" => "SAR"
                ],
                "items" => $items,
                "discount_amount" => [
                    "amount" => round($appointment->discount ?? 0, 2),
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "shipping_info" => [
                    "shipped_at" => Carbon::now()->toIso8601String(),
                    "shipping_company" => "Naqi Delivery",
                    "tracking_number" => "TRK" . $appointment->book_id,
                    "tracking_url" => "https://tracking.example.com?id=" . $appointment->book_id
                ]
            ];
            Log::info("Sending Capture for Appointment ID {$appointment->book_id}: " . json_encode($payload));

            $response = $this->client->post('/payments/capture', [
                'json' => $payload,
                'http_errors' => false
            ]);

            Log::info("Response Capture for Appointment ID {$appointment->book_id}: " . json_encode($payload));

            $result = json_decode($response->getBody(), true);
            Log::info("Response Capture for Appointment ID {$appointment->book_id}: " . json_encode($result));

            return [
                'success' => true,
                'message' => 'Payment captured successfully.',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error("Error capturing payment: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
    public function captureOrderNew2(string $referenceId, string $orderId, $installments)
    {

        try {
            $payment = DirectAppointmentPayment::with('directAppointment.lines')->where('reference_id', $referenceId)->firstOrFail();
            $payment->payment_id = $orderId;
            $payment->save();
            $appointment = $payment->directAppointment;

            if (!$appointment) {
                return ['error' => true, 'message' => 'Appointment not found.'];
            }
            $items = $appointment->lines->map(function ($item) {
                return [
                    "name" => $item->ItemNumber ?? "Unnamed Item",
                    "type" => "Digital",
                    "reference_id" => (string) $item->id ?? "unnamed",
                    "sku" => "SKU-" . $item->ItemNumber,
                    "quantity" => max(1, (int) ($item->quantity ?? 0)),
                    "discount_amount" => [
                        "amount" => round(0, 2),
                        "currency" => "SAR"
                    ],
                    "tax_amount" => [
                        "amount" => 0,
                        "currency" => "SAR"
                    ],
                    "unit_price" => [
                        "amount" => round($item->UnitPrice ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "total_amount" => [
                        "amount" => round($item->TotalAmount ?? 0, 2),
                        "currency" => "SAR"
                    ]
                ];
            })->toArray();


            $payload = [
                "order_id" => $orderId,
                "total_amount" => [
                    "amount" => round($payment->price, 2),
                    "currency" => "SAR"
                ],
                "items" => $items,
                "discount_amount" => [
                    "amount" => round($appointment->discount ?? 0, 2),
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "shipping_info" => [
                    "shipped_at" => Carbon::now()->toIso8601String(),
                    "shipping_company" => "Naqi Delivery",
                    "tracking_number" => "TRK" . $payment->dy_reference_id,
                    "tracking_url" => "https://tracking.example.com?id=" . $payment->dy_reference_id
                ]
            ];

            $response = $this->client->post('/payments/capture', [
                'json' => $payload,
                'http_errors' => false
            ]);


            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'message' => 'Payment captured successfully.',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error("Error capturing payment: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
    public function captureOrderDy(string $referenceId, string $orderId)
    {

        try {
            $payment = DyPaymentLink::with('lines')
                ->where('reference_id', $referenceId)
                ->orderByDesc('created_at')
                ->firstOrFail();
            $payment->payment_id = $orderId;
            $payment->save();

            if (!$payment) {
                return ['error' => true, 'message' => 'Appointment not found.'];
            }
            $items = $payment->lines->map(function ($item) {
                return [
                    "name" => $item->rec_id ?? "Unnamed Item",
                    "type" => "Digital",
                    "reference_id" => (string) $item->rec_id ?? "unnamed",
                    "sku" => "SKU-" . ($item->rec_id ?? ""),
                    "quantity" => max(1, (int) ($item->quantity ?? 0)),
                    "discount_amount" => [
                        "amount" => round(0, 2),
                        "currency" => "SAR"
                    ],
                    "tax_amount" => [
                        "amount" => 0,
                        "currency" => "SAR"
                    ],
                    "unit_price" => [
                        "amount" => round($item->price ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "total_amount" => [
                        "amount" => round($item->price * ($item->quantity ?? 1), 2),
                        "currency" => "SAR"
                    ]
                ];
            })->toArray();


            $payload = [
                "order_id" => $orderId,
                "total_amount" => [
                    "amount" => round($payment->amount, 2),
                    "currency" => "SAR"
                ],
                "items" => $items,
                "discount_amount" => [
                    "amount" => round($payment->discount ?? 0, 2),
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "shipping_info" => [
                    "shipped_at" => Carbon::now()->toIso8601String(),
                    "shipping_company" => "Naqi Delivery",
                    "tracking_number" => "TRK" . $payment->dy_reference_id,
                    "tracking_url" => "https://tracking.example.com?id=" . $payment->dy_reference_id
                ]
            ];

            $response = $this->client->post('/payments/capture', [
                'json' => $payload,
                'http_errors' => false
            ]);


            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'message' => 'Payment captured successfully.',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error("Error capturing payment: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getOrderStatus($orderId)
    {
        try {
            $response = $this->client->get("/orders/{$orderId}");
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}

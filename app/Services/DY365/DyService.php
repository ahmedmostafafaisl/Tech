<?php

namespace App\Services\DY365;

use App\Services\TaqnyatSmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Payment\TabbyService;
use App\Services\Payment\TamaraService;
use App\Services\Payment\ClickPayService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Response;

class DyService
{

    private $environment = "test";
    // private $environment = "prod";
    private $baseUrl;
    private $tokenUrl;
    private $clientSecret;
    private $clientId;
    private $storeCustomer;
    private $getItems;
    private $salesOrder;
    private $customerPayment;
    private $getInvoiceDetails;

    // new properties
    private $getWarehouses;
    private $getCategories;
    private $paymentMethods;
    private $getTechnicians;
    private $getCustomers;
    private $smsService;

    private $getTechnicianTransfers; // new

    //     new
    private $getTechnicianStock;
    private $getWarehouseStock;
    private $createTransferOrder;
    public $newCreateTransferOrder;
    public $newUpdateTransferOrder;
    private $updateTransferOrder;
    private $deleteTransferOrder;
    private $getAppointments;
    private $getTechnicianAppointments;
    private $getAppointmentBySalesOrderId; // new
    private $changeAppointmentStatus;
    private $getTechnicianChangeStatusRequests; // new
    private $addSalesLine; // new
    private $updateSalesLine; // new
    private $deleteSalesLine; // new
    private $successPayments; // new
    private $successPaymentsV2; // new
    private $dyPaymentStatus;  // new

    // invoice
    private $getOrCreateInvoice; // new
    private $getOrCreateInvoiceByBookId; // new
    private $salesHistory; // new
    public $customerChangeRequest; // new
    private $getCustomerChangeRequests; // new
    private $completeAppointmentAttachments; // new
    private $getSingleTechnician; // new
    private $getSingleTransferOrder; // new
    private $getAppointmentByBookId; // new

    // tech confirm  request
    private $techConfirmation; // new

    private $changeRequestReasons; // new

    private $changeCustomerName; // new

    private $addRegistrationNumber; // new

    private $productLimit; // new

    private $getProducts; // new
    private $getBundleProducts; // new

    private $getTechnicianDistributions; // new

    private  $updateCallListScore; // new


    public function __construct(TaqnyatSmsService $smsService)
    {
        $this->smsService = $smsService;
        if ($this->environment == 'test') {

            // $this->baseUrl = "https://hamat-uat.sandbox.operations.eu.dynamics.com";
            // $this->baseUrl = "https://naqi-dev07e0d2be09243f5188devaos.axcloud.dynamics.com";
            // $this->baseUrl = "https://naqi-dev05d11a9e2701c26003devaos.axcloud.dynamics.com";

            // $this->baseUrl = "https://hamat-uat02.sandbox.operations.eu.dynamics.com";
            // $this->baseUrl = "https://naqi-dev0614ec34becbf5112bdevaos.axcloud.dynamics.com";
            // $this->baseUrl = "https://naqi-dev10f17f23242541dcafdevaos.axcloud.dynamics.com";
            $this->baseUrl = "https://hamat-prod.operations.eu.dynamics.com";
            $this->tokenUrl = "https://login.microsoftonline.com/015ce0d4-cd51-4914-9ada-bdaff52b5c3d/oauth2/token";
            $this->clientId = config('services.dy365.client_id', '');
            $this->clientSecret = config('services.dy365.client_secret', '');
            $this->getWarehouses = "/api/services/INDXIntTechGroupSvc/INDXIntTechWarehouseSvc/getWarehouses";
            $this->getCategories = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getProductCategories";
            $this->paymentMethods = "/api/services/INDXIntTechGroupSvc/INDXIntTechPaymentMethodSvc/getPaymentMethods";
            $this->getTechnicians = "/api/services/INDXIntTechGroupSvc/INDXIntTechTechnicianSvc/getTechnicians";
            $this->getSingleTechnician = "/api/services/INDXIntTechGroupSvc/INDXIntTechTechnicianSvc/getSingleTechnician"; // new
            $this->getCustomers = "/api/services/INDXIntTechGroupSvc/INDXIntTechCustomerSvc/getCustomers";
            $this->getTechnicianStock = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getTechnicianStockV2";
            $this->getWarehouseStock = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getStockByWarehouseV3";
            $this->getTechnicianTransfers = "/api/services/INDXIntTechGroupSvc/INDXIntTechTransferOrderSvc/getTechnicianTransferOrders";
            $this->getSingleTransferOrder = "/api/services/INDXIntTechGroupSvc/INDXIntTechTransferOrderSvc/getSingleTechnicianTransferOrder"; // new
            $this->createTransferOrder = "/api/services/INDXIntTechGroupSvc/INDXIntTechTransferOrderSvc/createTransferOrder";
            $this->updateTransferOrder = "/api/services/INDXIntTechGroupSvc/INDXIntTechTransferOrderSvc/updateTransferOrderStatus";
            $this->deleteTransferOrder = "/api/services/INDXIntTechGroupSvc/INDXIntTechTransferOrderSvc/cancelTransferOrder"; // new
            $this->getAppointments = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getAppointments";
            $this->getAppointmentBySalesOrderId = "/api/services/INDXIntTechGroupSvcV2/INDXIntTechAppointmentSvc/getAppointmentsBySalesOrderV2";
            $this->getAppointmentByBookId = "/api/services/INDXIntTechGroupSvcV2/INDXIntTechAppointmentSvc/getAppointmentByBookId";
            $this->getTechnicianAppointments = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getTechnicianAppointments";
            $this->changeAppointmentStatus = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/submitTechnicianChangeRequest";
            $this->getTechnicianChangeStatusRequests = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getTechnicianChangeRequests";
            $this->addSalesLine = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/addSalesLinesToAppointment";
            $this->updateSalesLine = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/updateSalesLineForAppointment";
            $this->deleteSalesLine = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/deleteSalesLineForAppointment";
            $this->successPayments = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/completeAppointment";
            $this->successPaymentsV2 = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/completeAppointmentV2";
            $this->completeAppointmentAttachments = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/addAppointmentAttachments";
            $this->dyPaymentStatus = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/updatePaymentLinkStatus";
            $this->getOrCreateInvoice = "/api/services/INDXIntTechGroupSvc/INDXIntTechInvoiceSvc/getInvoiceBySalesOrderId";
            $this->getOrCreateInvoiceByBookId = "/api/services/INDXIntTechGroupSvc/INDXIntTechInvoiceSvc/getInvoiceByBookId";
            $this->salesHistory = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getSalesHistory"; // new
            $this->customerChangeRequest = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/updateConfirmStatusCreateChangeRequest"; // new
            $this->getCustomerChangeRequests = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getCustomerChangeRequests"; // new
            // tech confirm  request
            $this->techConfirmation = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/updateTechnicianConfirmation"; // new
            $this->changeRequestReasons = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getRescheduleCancelReasons"; // new
            $this->changeCustomerName = "/api/services/INDXIntTechGroupSvc/INDXIntTechCustomerSvc/changeCustomerNameByAppointmentId"; // new
            $this->addRegistrationNumber = "/api/services/INDXIntTechGroupSvc/INDXIntTechCustomerSvc/addRegistrationByAppointmentId"; // new
            $this->productLimit = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getTechnicianProductLimit"; // new
            $this->getProducts = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getProducts"; // new
            $this->getBundleProducts = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getProductsBundle"; // new
            $this->getTechnicianDistributions = "/api/services/INDXIntTechGroupSvc/INDXIntTechTechnicianSvc/getTechnicianDistributions"; // new
            $this->updateCallListScore = "/api/services/INDXTeleSvcGrp/INDXTeleSvc/updateCallListTargetScore"; // new




            $this->storeCustomer = "https://hamat-prod.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_CustomersService/CreateUpdateCustomer";
            $this->getItems = "https://hamat-prod.operations.eu.dynamics.com/data/TMK_ItemDetailsEntity";
            $this->salesOrder =  "https://hamat-prod.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_SalesOrderService/createSalesTransactions";
            $this->customerPayment =  "https://hamat-prod.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_CustPaymService/CreateCustomerPayment";
            $this->getInvoiceDetails =  "https://hamat-prod.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_SalesOrderService/GetInvoiceDetails";
        } else {
            $this->baseUrl = "https://hamat-prod.operations.eu.dynamics.com/";
            $this->tokenUrl = "https://login.microsoftonline.com/015ce0d4-cd51-4914-9ada-bdaff52b5c3d/oauth2/token";
            $this->clientId = config('services.dy365.client_id', '');
            $this->clientSecret = config('services.dy365.client_secret', '');
            $this->getWarehouses = "/api/services/INDXIntTechGroupSvc/INDXIntTechWarehouseSvc/getWarehouses";
            $this->getCategories = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getProductCategories";
            $this->paymentMethods = "/api/services/INDXIntTechGroupSvc/INDXIntTechPaymentMethodSvc/getPaymentMethods";
            $this->getTechnicians = "/api/services/INDXIntTechGroupSvc/INDXIntTechTechnicianSvc/getTechnicians";
            $this->getCustomers = "/api/services/INDXIntTechGroupSvc/INDXIntTechCustomerSvc/getCustomers";
            $this->getTechnicianStock = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getTechnicianStock";
            $this->getWarehouseStock = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getStockByWarehouse";
            $this->getTechnicianTransfers = "/api/services/INDXIntTechGroupSvc/INDXIntTechTransferOrderSvc/getTechnicianTransferOrders";
            $this->createTransferOrder = "/api/services/INDXIntTechGroupSvc/INDXIntTechTransferOrderSvc/createTransferOrder";
            $this->updateTransferOrder = "/api/services/INDXIntTechGroupSvc/INDXIntTechTransferOrderSvc/updateTransferOrderStatus";
            $this->getAppointments = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getAppointments";
            $this->getAppointmentBySalesOrderId = "/api/services/INDXIntTechGroupSvcV2/INDXIntTechAppointmentSvc/getAppointmentsBySalesOrder";
            $this->getTechnicianAppointments = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getTechnicianAppointments";
            $this->changeAppointmentStatus = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/submitTechnicianChangeRequest";
            $this->getTechnicianChangeStatusRequests = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getTechnicianChangeRequests";
            $this->addSalesLine = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/addSalesLinesToAppointment";
            $this->updateSalesLine = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/updateSalesLineForAppointment";
            $this->deleteSalesLine = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/deleteSalesLineForAppointment";
            $this->successPayments = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/completeAppointment";
            $this->dyPaymentStatus = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/updatePaymentLinkStatus";
            $this->getOrCreateInvoice = "/api/services/INDXIntTechGroupSvc/INDXIntTechInvoiceSvc/getInvoiceBySalesOrderId";
            $this->salesHistory = "/api/services/INDXIntTechGroupSvc/INDXIntTechProductSvc/getSalesHistory"; // new
            $this->customerChangeRequest = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/submitCustomerChangeRequest"; // new
            $this->getCustomerChangeRequests = "/api/services/INDXIntTechGroupSvc/INDXIntTechAppointmentSvc/getCustomerChangeRequests"; // new
        }
    }


    // 🔹 Centralized error logger
    private function logError(string $functionName, string $message, array $context = []): void
    {
        $logPath = storage_path("logs/dyservice/{$functionName}.log");

        if (!file_exists(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }

        $date = now()->format('Y-m-d H:i:s');

        $ctx = '';
        if (!empty($context)) {
            $ctx = ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $line = "[{$date}] {$message}{$ctx}\n";
        file_put_contents($logPath, $line, FILE_APPEND);
    }

    public function getToken(): ?string
    {
        $cacheKey = 'dy:oauth:access_token';

        $cached = Cache::get($cacheKey);
        if (!empty($cached)) {
            return $cached;
        }

        $body = [
            "grant_type"    => "client_credentials",
            "client_id"     => $this->clientId,
            "client_secret" => $this->clientSecret,
            "resource"      => $this->baseUrl,
        ];

        try {
            $response = Http::asForm()
                ->connectTimeout(5)
                ->timeout(20)
                ->retry(2, 500, function ($exception) {
                    if ($exception instanceof ConnectionException) return true;
                    if ($exception instanceof RequestException) {
                        $status = $exception->response?->status();
                        return in_array($status, [429, 500, 502, 503, 504], true);
                    }
                    return false;
                })
                ->post($this->tokenUrl, $body);

            $response->throw();

            $res = $response->json();
            $token = $res['access_token'] ?? null;

            if (!$token) {
                throw new \RuntimeException('Token response missing access_token');
            }

            $expiresIn = (int)($res['expires_in'] ?? 3600);
            $ttl = max(60, $expiresIn - 120);

            Cache::put($cacheKey, $token, now()->addSeconds($ttl));

            return $token;
        } catch (\Throwable $e) {
            $this->logError(__FUNCTION__, $e->getMessage());
            return null;
        }
    }

    private function sendRequest(string $method, string $endpoint, array $data = [])
    {
        $functionName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        $payload = empty($data) ? new \stdClass() : $data;

        try {
            $token = $this->getToken();
            if (!$token) {
                throw new \RuntimeException("Missing access token");
            }

            $doRequest = function (string $token) use ($method, $endpoint, $payload) {
                return Http::withToken($token)
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout(5)
                    ->timeout(60)
                    ->retry(2, 1000, function ($exception) {
                        if ($exception instanceof ConnectionException) return true;
                        if ($exception instanceof RequestException) {
                            $status = $exception->response?->status();
                            return in_array($status, [429, 500, 502, 503, 504], true);
                        }
                        return false;
                    })
                    ->$method($this->baseUrl . $endpoint, $payload);
            };

            $response = $doRequest($token);

            if ($response->status() === 401) {
                Cache::forget('dy:oauth:access_token');
                $token = $this->getToken();
                if (!$token) {
                    throw new \RuntimeException("Token refresh failed after 401");
                }
                $response = $doRequest($token);
            }

            $response->throw();

            return $response->json();
        } catch (\Throwable $e) {
            $this->logError($functionName, $e->getMessage());
            return null;
        }
    }
    public function sendRequest2($method, $endpoint, $data = [])
    {
        $functionName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        try {
            $token = $this->getToken();

            if (!$token) {
                throw new \Exception("Missing access token");
            }

            // Convert to JSON string (ensures raw JSON sent)
            $jsonPayload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $url = $this->baseUrl . $endpoint;



            // Send as raw JSON body
            $response = Http::retry(3, 2000)
                ->withOptions([
                    'timeout' => 500,
                    'connect_timeout' => 300,
                ])
                ->withHeaders([
                    'Authorization' => "Bearer $token",
                    'Content-Type' => 'application/json',
                ])
                ->withBody($jsonPayload, 'application/json') // ✅ Force raw JSON
                ->send($method, $url);



            // Decode JSON safely
            return $response->json();
        } catch (\Throwable $e) {
            $this->logError($functionName, $e->getMessage());

            Log::channel('whatsapp')->error('❌ Dy365 API Exception', [
                'function' => $functionName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
    //  new send request functions
    public function sendRequest3($method, $endpoint, $data = [])
    {
        $functionName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        try {
            $token = $this->getToken();

            if (!$token) {
                throw new \Exception("Missing access token");
            }

            $jsonPayload = json_encode($data, JSON_UNESCAPED_UNICODE);

            $url = $this->baseUrl . $endpoint;

            $response = Http::retry(3, 2000)
                ->withOptions([
                    'timeout' => 500,
                    'connect_timeout' => 300,
                ])
                ->withHeaders([
                    'Authorization' => "Bearer $token",
                    'Content-Type'  => 'application/json',
                ])
                ->withBody($jsonPayload, 'application/json')
                ->send($method, $url);

            // ❌ لو فشل
            if ($response->failed()) {

                Log::channel('whatsapp')->error('❌ Dy365 API Error', [
                    'function' => $functionName,
                    'status'   => $response->status(),
                    'body'     => $response->json() ?? $response->body(),
                ]);

                return [
                    'ok'     => false,
                    'status' => $response->status(),
                    'error'  => $response->json() ?? [
                        'message' => $response->body()
                    ],
                ];
            }

            // ✅ نجاح
            return [
                'ok'     => true,
                'status' => $response->status(),
                'data'   => $response->json(),
            ];
        } catch (\Throwable $e) {

            $this->logError($functionName, $e->getMessage());

            Log::channel('whatsapp')->error('❌ Dy365 API Exception', [
                'function' => $functionName,
                'error'    => $e->getMessage(),
            ]);

            return [
                'ok'     => false,
                'status' => 500,
                'error'  => [
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    // get warehouses // done
    public function getWarehouses()
    {
        return $warehouses =  $this->sendRequest('post',  $this->getWarehouses);
    }
    // get categories // done
    public function getProductCategories()
    {
        return $categories =  $this->sendRequest('post',  $this->getCategories);
    }
    // get payment methods
    public function getPaymentMethods()
    {
        return $methods =  $this->sendRequest('post',  $this->paymentMethods);
    }
    // get technicians // done
    public function getTechnicians(array $payload = [])
    {
        $payload = ["currentPage" => 1, "pageSize" => 400];

        $technicians =  $this->sendRequest3('post',  $this->getTechnicians, $payload);

        return $technicians;
    }
    // get customers // done
    public function getCustomers(array $payload = [])

    {
        return $customers =  $this->sendRequest('post',  $this->getCustomers, $payload);
    }
    // get technician stock // done
    public function getTechnicianStock(array $payload = [])
    {
        return $technicianStock =  $this->sendRequest('post',  $this->getTechnicianStock, $payload);
    }
    // get warehouse stock // done
    public function getWarehouseStock(array $payload = [])
    {
        return $warehouseStock =  $this->requestDynamics('post',  $this->getWarehouseStock, $payload);
    }
    // get technician transfers  // done
    public function getTechnicianTransfers(array $payload = [])
    {
        return $technicianTransfers =  $this->sendRequest('post',  $this->getTechnicianTransfers, $payload);
    }
    // create transfer order // done
    public function createTransferOrder(array $payload = [])
    {
        return $this->requestDynamics2('post', $this->createTransferOrder, $payload);
    }

    // update transfer order // done
    public function updateTransferOrder(array $payload = [])
    {
        return $this->sendRequest('post', $this->updateTransferOrder, $payload);
    }

    // delete transfer order // done
    public function deleteTransferOrder(array $payload = [])
    {
        return $this->sendRequest('post', $this->deleteTransferOrder, $payload);
    }
    // get appointments // done
    public function getAppointments(array $payload = [])
    {
        return $appointments = $this->sendRequest('post', $this->getAppointments, $payload);
    }
    public function getAppointmentBySalesOrder($salesOrderId)
    {
        return $appointment = $this->sendRequest('post', $this->getAppointmentBySalesOrderId, ['salesOrderId' => $salesOrderId]);
    }

    public function getAppointmentByBookId($bookId)
    {
        return $appointment = $this->requestDynamics2('post', $this->getAppointmentByBookId, ['bookId' => $bookId]);
    }
    // get technician appointments  // done
    public function getTechnicianAppointments(array $payload = [])
    {
        return $technicianAppointments =  $this->sendRequest('post',  $this->getTechnicianAppointments, $payload);
    }
    // change appointment status
    public function changeAppointmentStatus(array $payload = [])
    {
        return $this->sendRequest('post', $this->changeAppointmentStatus, $payload);
    }
    // get technician change status requests
    public function getTechnicianChangeStatusRequests(array $payload = [])
    {
        return $this->sendRequest('post', $this->getTechnicianChangeStatusRequests, $payload);
    }
    //add sales line to appointment
    public function addSalesLine(array $payload = [])
    {
        return $this->requestDynamics2('post', $this->addSalesLine, $payload);
    }
    // update sales line for appointment
    public function updateSalesLine(array $payload = [])
    {

        return $this->requestDynamics2('post', $this->updateSalesLine, $payload);
    }
    public function updateSalesLineNew(array $payload = []): ?array
    {
        return $this->requestDynamics2('post', $this->updateSalesLine, $payload);
    }
    // delete sales line for appointment
    public function deleteSalesLine(array $payload = [])
    {
        return $this->sendRequest('post', $this->deleteSalesLine, $payload);
    }
    // get payment links
    public function getPaymentLinks(array $data)
    {
        $results = [];
        foreach ($data['payments'] as $payment) {
            $payment_type = strtolower($payment['payment_type'] ?? '');
            $responseData = [
                'status'        => false,
                'paymentStatus' => null, // optional
                'referenceId'   => null, // optional
                'error'  => (string)"null",
            ];

            try {
                if ($payment_type === 'tabby') {
                    $tabby = app(TabbyService::class)->dyCheckout($data, $payment);
                    if ($tabby instanceof \Illuminate\Http\JsonResponse) {
                        $respData = $tabby->getData(true);

                        if ($tabby->getStatusCode() === 200 && !empty($respData['web_url'])) {

                            $responseData['url'] = $respData['web_url'];
                            $responseData['payment_id'] = $respData['payment_id'] ?? null;
                            $responseData['checkout_id'] =   $responseData['referenceId'] = $respData['checkout_id'] ?? null;
                            $responseData['status'] = true;
                            $responseData['paymentStatus'] = 'pending'; // optional

                            $phone = str_replace('+966', '', $respData['phone'] ?? '500000001');
                            $this->smsService->sendOtp($phone, $responseData['url']);
                        } else {

                            $responseData['status'] = false;
                            $responseData['error'] = $respData['error'] ?? 'Failed to create Tabby checkout.';
                            $responseData['details'] = $respData['details'] ?? null;
                        }
                    } else {

                        $responseData['status'] = false;
                        $responseData['error'] = 'Invalid Tabby response';
                    }
                } elseif ($payment_type === 'tamara') {
                    $tamara = app(TamaraService::class)->dyCreateOrder($data, $payment, $payment['amount']);
                    $responseData['url'] = $tamara['checkout_url'] ?? null;
                    $responseData['referenceId'] = $tamara['reference_id'] ?? null;
                    $responseData['status'] = !empty($responseData['url']);
                    if ($responseData['status']) {
                        $this->smsService->sendOtp($data['phone'], $responseData['url']);
                    } else {
                        $responseData['error'] = 'Tamara checkout URL missing';
                    }
                } else {
                    $clickpay = app(ClickPayService::class)->dyCreateInvoice($data, $payment, $payment['amount']);
                    $responseData['url'] = $clickpay['redirect_url'] ?? null;
                    $responseData['referenceId'] = $clickpay['reference_id'] ?? null;
                    $responseData['status'] = !empty($responseData['url']);
                    if ($responseData['status']) {
                        $this->smsService->sendOtp($data['phone'], $responseData['url']);
                    } else {
                        $responseData['error'] = 'ClickPay redirect URL missing';
                    }
                }
            } catch (\Exception $e) {
                $responseData['error'] = $e->getMessage();
            }

            $results[$payment_type] = $responseData;
        }

        return $results;
    }
    // complete success payments
    public function completeSuccessPayments(array $payload = [])
    {
        return $this->sendRequest('post', $this->successPayments, $payload);
    }
    // complete success payments v2
    public function completeSuccessPaymentsV2(array $payload = [])
    {
        return $this->sendRequest('post', $this->successPaymentsV2, $payload);
    }

    // complete appointment with attachments
    public function completeAppointmentWithAttachments(array $payload = [])
    {
        return $this->sendRequest(
            'post',
            $this->completeAppointmentAttachments,
            $payload
        );
    }
    // return dy payment status
    public function dyPaymentStatus(array $payload = [])
    {
        return $this->sendRequest('post', $this->dyPaymentStatus, $payload);
    }
    // get or create invoice
    public function getOrCreateInvoice(array $payload = [])
    {
        return $this->sendRequest('post', $this->getOrCreateInvoice, $payload);
    }
    // get or create invoice by book id
    public function getOrCreateInvoiceByBookId(array $payload = [])
    {
        return $this->requestDynamics2('post', $this->getOrCreateInvoiceByBookId, $payload);
    }
    // get sales history
    public function getSalesHistory(array $payload = [])
    {
        return $this->sendRequest('post', $this->salesHistory, $payload);
    }

    // submit customer change request
    public function submitCustomerChangeRequest(array $payload = [])
    {
        return $this->sendRequest2('post', $this->customerChangeRequest, $payload);
    }

    // get customer change requests
    public function getCustomerChangeRequests(array $payload = [])
    {
        return $this->sendRequest('post', $this->getCustomerChangeRequests, $payload);
    }


    // get single technician
    public function getSingleTechnician(array $payload = [])
    {
        return $this->requestDynamics2('post', $this->getSingleTechnician, $payload);
    }


    // get single transfer order
    public function getSingleTransferOrder(array $payload = [])
    {
        return $this->sendRequest('post', $this->getSingleTransferOrder, $payload);
    }

    // tech confirm  request
    public function techConfirmation(array $payload = [])
    {
        return $this->sendRequest('post', $this->techConfirmation, $payload);
    }

    // get change request reasons
    public function getChangeRequestReasons(array $payload = [])
    {
        return $this->requestDynamics2('post', $this->changeRequestReasons, $payload);
    }

    // change customer name
    public function changeCustomerName(array $payload = [])
    {
        return $this->sendRequest('post', $this->changeCustomerName, $payload);
    }

    // add registration number
    public function addRegistrationNumber(array $payload = [])
    {
        return $this->sendRequest('post', $this->addRegistrationNumber, $payload);
    }

    // get technician product limit
    public function getTechnicianProductLimit(array $payload = [])
    {

        return $this->requestDynamics2('post', $this->productLimit, $payload);
    }

    // ... add more methods as needed for enhance performance and error handling



    public function getTechnicianAppointmentsCached(array $payload, int $ttlSeconds = 60)
    {
        $cacheKey   = $this->buildTechAppointmentsCacheKey($payload);
        $breakerKey = "dy:breaker:getTechnicianAppointments";

        // Circuit breaker
        $fails = (int) Cache::get($breakerKey, 0);
        if ($fails >= 5) {
            $stale = Cache::get($cacheKey);
            return $stale; // ممكن null
        }

        $response = Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), function () use ($payload) {
            return $this->getTechnicianAppointments($payload);
        });

        if ($response === null) {
            Cache::put($breakerKey, $fails + 1, now()->addSeconds(60));
            return Cache::get($cacheKey); // fallback stale
        }

        Cache::forget($breakerKey);
        return $response;
    }

    private function buildTechAppointmentsCacheKey(array $payload): string
    {
        return sprintf(
            "dy:techAppointments:%s:%s:%s:%s:%s",
            $payload['worker'] ?? 'na',
            $payload['fromDate'] ?? 'na',
            $payload['toDate'] ?? 'na',
            $payload['currentPage'] ?? 1,
            $payload['pageSize'] ?? 10
        );
    }


    //  dynamic function


    private function requestDynamics(string $method, string $endpoint, array $data = []): ?array
    {
        $functionName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $payload = empty($data) ? new \stdClass() : $data;

        try {
            $token = $this->getToken();
            if (!$token) {
                throw new \RuntimeException("Missing access token");
            }

            $send = function (string $token) use ($method, $endpoint, $payload) {
                return Http::withToken($token)
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout(5)
                    ->timeout(60)
                    ->retry(2, 1000, function ($exception) {
                        if ($exception instanceof ConnectionException) return true;

                        if ($exception instanceof RequestException) {
                            $status = $exception->response?->status();
                            return in_array($status, [429, 500, 502, 503, 504], true);
                        }

                        return false;
                    })
                    ->$method($this->baseUrl . $endpoint, $payload);
            };

            $response = $send($token);

            // Refresh token once on 401
            if ($response->status() === 401) {
                Cache::forget('dy:oauth:access_token');
                $token = $this->getToken();
                if (!$token) {
                    throw new \RuntimeException("Token refresh failed after 401");
                }
                $response = $send($token);
            }

            $response->throw();
            return $response->json();
        } catch (\Throwable $e) {
            $this->logError($functionName, $e->getMessage(), [
                'endpoint' => $endpoint,
            ]);
            return null;
        }
    }

    private function requestDynamics2(string $method, string $endpoint, array $data = []): array
    {
        $functionName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? __FUNCTION__;
        $payload      = empty($data) ? new \stdClass() : $data;

        try {
            $token = $this->getToken();

            if (!$token) {
                throw new \RuntimeException('Missing access token');
            }

            $send = function (string $token) use ($method, $endpoint, $payload): Response {
                return Http::withToken($token)
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout(5)
                    ->timeout(60)
                    ->retry(
                        2,
                        1000,
                        function ($exception) {
                            if ($exception instanceof ConnectionException) {
                                return true;
                            }

                            if ($exception instanceof RequestException) {
                                $status = $exception->response?->status();
                                return in_array($status, [429, 500, 502, 503, 504], true);
                            }

                            return false;
                        },
                        throw: false
                    )
                    ->$method($this->baseUrl . $endpoint, $payload);
            };

            $response = $send($token);

            // Token expired — refresh once and retry
            if ($response->status() === 401) {
                Cache::forget('dy:oauth:access_token');
                $token = $this->getToken();

                if (!$token) {
                    throw new \RuntimeException('Token refresh failed after 401');
                }

                $response = $send($token);
            }

            if ($response->failed()) {
                $body = $response->json();

                if (!is_array($body)) {
                    $body = ['message' => $response->body()];
                }

                $this->logError($functionName, 'Dynamics request failed', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                    'body'     => $body,
                ]);

                throw new \RuntimeException(
                    $body['message']
                        ?? $body['Message']
                        ?? $body['error']['message']
                        ?? $response->body()
                        ?? 'Dynamics request failed'
                );
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            $this->logError($functionName, $e->getMessage(), [
                'endpoint' => $endpoint,
            ]);

            throw $e;
        }
    }
    private function callCached(string $cacheKey, string $breakerKey, int $ttlSeconds, callable $call): ?array
    {
        $fails = (int) Cache::get($breakerKey, 0);

        // ── Circuit breaker open — return stale immediately ───────────────────────
        if ($fails >= 5) {
            $this->logError('callCached', 'Circuit breaker open — returning stale cache', [
                'cache_key'   => $cacheKey,
                'breaker_key' => $breakerKey,
                'fails'       => $fails,
            ]);

            return Cache::get($cacheKey); // may be null if no stale exists
        }

        // ── Try to get fresh data ─────────────────────────────────────────────────
        try {
            $fresh = $call();

            if ($fresh === null) {
                // Callable returned null (e.g. empty Dynamics response)
                Cache::put($breakerKey, $fails + 1, now()->addSeconds(60));
                return Cache::get($cacheKey); // stale fallback
            }

            // Success — cache it, reset breaker
            Cache::put($cacheKey, $fresh, now()->addSeconds($ttlSeconds));
            Cache::forget($breakerKey);

            return $fresh;
        } catch (\Throwable $e) {
            // Exception (ConnectionException, RuntimeException, etc.)
            Cache::put($breakerKey, $fails + 1, now()->addSeconds(60));

            $this->logError('callCached', 'Call failed — incrementing breaker', [
                'cache_key'   => $cacheKey,
                'breaker_key' => $breakerKey,
                'fails'       => $fails + 1,
                'error'       => $e->getMessage(),
            ]);

            return Cache::get($cacheKey); // stale fallback — may be null
        }
    }

    //  new get technician appointments with caching and circuit breaker
    public function getTechnicianAppointmentsNew(array $payload = [])
    {
        $cacheKey = $this->cacheKey('getTechnicianAppointments', $payload);
        $breakerKey = 'dy:breaker:getTechnicianAppointments';

        return $this->callCached($cacheKey, $breakerKey, 0, function () use ($payload) {
            return $this->requestDynamics2('post', $this->getTechnicianAppointments, $payload);
        });
    }

    private function cacheKey(string $name, array $payload): string
    {
        // key قصير وثابت
        return 'dy:' . $name . ':' . md5(json_encode($payload));
    }
    // new single appointment by book id with caching and circuit breaker
    public function getAppointmentByBookIdNew(string $bookId, int $ttlSeconds = 60): ?array
    {
        $payload = ['bookId' => $bookId];

        $cacheKey   = "dy:getAppointmentByBookId:" . md5($bookId);
        $breakerKey = "dy:breaker:getAppointmentByBookId";

        return $this->callCached($cacheKey, $breakerKey, $ttlSeconds, function () use ($payload) {
            return $this->requestDynamics('post', $this->getAppointmentByBookId, $payload);
        });
    }

    //  new warehouse stock with caching and circuit breaker


    private function callCachedWithStale(string $freshKey, string $staleKey, string $breakerKey, int $freshTtlSeconds, int $staleTtlSeconds, callable $call): ?array
    {
        $fails = (int) Cache::get($breakerKey, 0);

        // Circuit breaker open => return stale only
        if ($fails >= 5) {
            return Cache::get($staleKey);
        }

        // Fast path: fresh cache
        $cachedFresh = Cache::get($freshKey);
        if ($cachedFresh !== null) {
            return $cachedFresh;
        }

        try {
            $response = $call();
        } catch (\Throwable $e) {
            Cache::put($breakerKey, $fails + 1, now()->addSeconds(60));
            return Cache::get($staleKey);
        }

        if ($response === null) {
            Cache::put($breakerKey, $fails + 1, now()->addSeconds(60));
            return Cache::get($staleKey);
        }

        // Success
        Cache::forget($breakerKey);

        Cache::put($freshKey, $response, now()->addSeconds($freshTtlSeconds));
        Cache::put($staleKey, $response, now()->addSeconds($staleTtlSeconds));

        return $response;
    }
    public function getWarehouseStockNew(array $payload = [], int $freshTtlSeconds = 3): ?array
    {
        $baseKey = $this->cacheKey('getWarehouseStock', $payload);

        $freshKey   = $baseKey . ':fresh';
        $staleKey   = $baseKey . ':stale';
        $breakerKey = 'dy:breaker:getWarehouseStock';

        return $this->callCachedWithStale(
            $freshKey,
            $staleKey,
            $breakerKey,
            $freshTtlSeconds,
            600, // stale 10 minutes
            fn() => $this->requestDynamics2('post', $this->getWarehouseStock, $payload)
        );
    }

    public function getTechnicianDistributions(array $payload = [], int $freshTtlSeconds = 3): ?array
    {
        $baseKey = $this->cacheKey('getTechnicianDistributions', $payload);

        $freshKey   = $baseKey . ':fresh';
        $staleKey   = $baseKey . ':stale';
        $breakerKey = 'dy:breaker:getTechnicianDistributions';

        return $this->callCachedWithStale(
            $freshKey,
            $staleKey,
            $breakerKey,
            $freshTtlSeconds,
            600, // stale 10 minutes
            fn() => $this->requestDynamics2('post', $this->getTechnicianDistributions, $payload)
        );
    }

    public function updateCallListScore(array $payload = [], int $freshTtlSeconds = 3): ?array
    {
        $baseKey = $this->cacheKey('updateCallListScore', $payload);

        $freshKey   = $baseKey . ':fresh';
        $staleKey   = $baseKey . ':stale';
        $breakerKey = 'dy:breaker:updateCallListScore';

        return $this->callCachedWithStale(
            $freshKey,
            $staleKey,
            $breakerKey,
            $freshTtlSeconds,
            600, // stale 10 minutes
            fn() => $this->requestDynamics2('post', $this->updateCallListScore, $payload)
        );
    }

    // get Products with caching and circuit breaker
    public function getProducts(array $payload = [])
    {
        return $this->requestDynamics2('post', $this->getProducts, $payload);
    }

    // get Bundle Products with caching and circuit breaker
    public function getBundleProducts(array $payload = [])
    {
        return $this->requestDynamics2('post', $this->getBundleProducts, $payload);
    }
}

<?php

namespace Vinnia\Fortnox;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Client {

    const API_URL = 'api.fortnox.se/3';

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $url;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * Client constructor.
     * @param ClientInterface $client
     * @param string $accessToken
     * @param string $clientSecret
     * @param bool $https
     */
    function __construct(ClientInterface $client, $accessToken, $clientSecret, $https = true) {
        $this->httpClient = $client;
        $this->accessToken = $accessToken;
        $this->clientSecret = $clientSecret;

        $this->url = $https ? 'https://' : 'http://' . self::API_URL;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return ResponseInterface
     */
    protected function sendRequest($method, $endpoint, array $options = []) {
        $res = $this->httpClient->request($method, self::API_URL . $endpoint, array_merge([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Client-Secret' => $this->clientSecret,
                'Access-Token' => $this->accessToken
            ]
        ], $options));

        $this->response = $res;

        return $res;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param string $dataKey json key to move to before returning
     * @param array $options
     * @return array
     */
    protected function sendParseRequest($method, $endpoint, $dataKey = '', array $options = []) {
        $res = $this->sendRequest($method, $endpoint, $options);
        $json = $this->parseJsonResponse($res);

        if ( $dataKey !== '' ) {
            $json = $json[$dataKey];
        }

        return $json;
    }

    /**
     * @param string $endpoint
     * @param string $dataKey
     * @param array $options
     * @return array
     */
    protected function getPaginatedEndpoint($endpoint, $dataKey, array $options = []) {
        $items = [];
        $totalPages = 1;
        for ( $i = 1; $i <= $totalPages; $i++ ) {
            $res = $this->sendRequest('GET', $endpoint, array_merge($options, [
                'query' => [
                    'page' => $i,
                    'limit' => 500,
                ],
            ]));
            $parsed = $this->parseJsonResponse($res);
            $totalPages = (int) $parsed['MetaInformation']['@TotalPages'];
            $moreItems = $parsed[$dataKey];
            $items = array_merge($items, $moreItems);
        }

        return $items;
    }

    /**
     * @param ResponseInterface $res
     * @return array
     */
    protected function parseJsonResponse(ResponseInterface $res) {
        return json_decode((string) $res->getBody(), $assoc = true);
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * @return string[][] array of customers
     */
    public function getCustomers() {
        return $this->getPaginatedEndpoint('/customers', 'Customers');
    }

    /**
     * @param int $customerNumber
     * @return string[] customer
     */
    public function getCustomer($customerNumber) {
        return $this->sendParseRequest('GET', '/customers/' . $customerNumber, 'Customer');
    }

    /**
     * @param string[] $data customer data
     * @return string[] customer
     */
    public function createCustomer(array $data) {
        return $this->sendParseRequest('POST', '/customers', 'Customer', [
            'json' => [
                'Customer' => $data
            ]
        ]);
    }

    /**
     * @param int $customerNumber
     * @param string[] $data
     * @return string[] customer
     */
    public function updateCustomer($customerNumber, array $data) {
        return $this->sendParseRequest('PUT', '/customers/' . $customerNumber, 'Customer', [
            'json' => [
                'Customer' => $data
            ]
        ]);
    }

    /**
     * @param $customerNumber
     * @return string response body
     */
    public function deleteCustomer($customerNumber) {
        $response = $this->sendRequest('DELETE', '/customers/' . $customerNumber);
        return (string) $response->getBody();
    }

    /**
     * @return string[][] array of suppliers
     */
    public function getSuppliers() {
        return $this->getPaginatedEndpoint('/suppliers', 'Suppliers');
    }

    /**
     * @param string $id
     * @return string[] supplier
     */
    public function getSupplier($id) {
        return $this->sendParseRequest('GET', '/suppliers/' . $id, 'Supplier');
    }

    /**
     * @param int $id
     * @return string file body
     */
    public function getInboxFile($id) {
        $response = $this->sendRequest('GET', '/inbox/' . $id);
        return $this->parseJsonResponse($response);
    }

    /**
     * @param mixed $id
     * @return string file body
     */
    public function deleteInboxFile($id) {
        $response = $this->sendRequest('DELETE', '/inbox/' . $id);
        return (string) $response->getBody();
    }

    /**
     * @param StreamInterface $data
     * @return string[]
     */
    public function putInboxFile(StreamInterface $data) {
        return $this->sendParseRequest('POST', '/inbox', 'File', [
            'body' => $data
        ]);
    }

    /**
     * @param mixed $supplierInvoiceNumber
     * @param mixed $fileId
     * @return string[]
     */
    public function createSupplierInvoiceFileConnection($supplierInvoiceNumber, $fileId) {
        return $this->sendParseRequest('POST', '/supplierinvoicefileconnections', 'SupplierInvoiceFileConnection', [
            'json' => [
                'SupplierInvoiceFileConnection' => [
                    'SupplierInvoiceNumber' => $supplierInvoiceNumber,
                    'FileId' => $fileId,
                ]
            ]
        ]);
    }

    /**
     * @return string[][]
     */
    public function getSupplierInvoiceFileConnections() {
        return $this->sendParseRequest('GET', '/supplierinvoicefileconnections', 'SupplierInvoiceFileConnections');
    }

    /**
     * @param array $options guzzle request options
     * @return string[][]
     */
    public function getSupplierInvoices(array $options = []) {
        return $this->getPaginatedEndpoint('/supplierinvoices', 'SupplierInvoices', $options);
    }

    /**
     * @param int $id
     * @return string[]
     */
    public function getSupplierInvoice($id) {
        return $this->sendParseRequest('GET', '/supplierinvoices/' . $id, 'SupplierInvoice');
    }

    /**
     * @param array $data
     * @return string[]
     */
    public function createSupplierInvoice(array $data) {
        return $this->sendParseRequest('POST', '/supplierinvoices', 'SupplierInvoice', [
            'json' => [
                'SupplierInvoice' => $data
            ]
        ]);
    }

    /**
     * @param int $id
     * @param array $data the supplier invoice data
     * @return array
     */
    public function updateSupplierInvoice($id, array $data) {
        return $this->sendParseRequest('PUT', '/supplierinvoices/' . $id, 'SupplierInvoice', [
            'json' => [
                'SupplierInvoice' => $data,
            ],
        ]);
    }

    /**
     * @return string[][] array of projects
     */
    public function getProjects() {
        return $this->sendParseRequest('GET', '/projects', 'Projects');
    }

    /**
     * @param int $projectNumber
     * @return string[] project
     */
    public function getProject($projectNumber) {
        return $this->sendParseRequest('GET', '/projects/' . $projectNumber, 'Project');
    }

    /**
     * @param string[] $data
     * @return string[] project
     */
    public function createProject(array $data) {
        return $this->sendParseRequest('POST', '/projects', 'Project', [
            'json' => [
                'Project' => $data
            ]
        ]);
    }

    /**
     * @param int $projectNumber
     * @param string[] $data
     * @return mixed
     */
    public function updateProject($projectNumber, array $data) {
        return $this->sendParseRequest('PUT', '/projects/' . $projectNumber, 'Project', [
            'json' => [
                'Project' => $data
            ]
        ]);
    }

    /**
     * @param int $projectNumber
     * @return string
     */
    public function deleteProject($projectNumber) {
        $response = $this->sendRequest('DELETE', '/projects/' . $projectNumber);
        return (string) $response->getBody();
    }

    /**
     * @param string $financialYearDate date of the financial year to use (Y-m-d)
     * @return array
     */
    public function getVouchers($financialYearDate) {
        return $this->getPaginatedEndpoint('/vouchers', 'Vouchers', [
            'query' => [
                'financialyeardate' => $financialYearDate
            ]
        ]);
    }

    /**
     * @param string $series
     * @param string $voucherNumber
     * @param string $financialYearDate date of the financial year to use (Y-m-d)
     * @return array
     */
    public function getVoucher($series, $voucherNumber, $financialYearDate) {
        $endpoint = sprintf('/vouchers/%s/%s', $series, $voucherNumber);
        return $this->sendParseRequest('GET', $endpoint, 'Voucher', [
            'query' => [
                'financialyeardate' => $financialYearDate
            ]
        ]);
    }

    /**
     * @param array $data
     * @return array
     */
    public function createVoucher(array $data) {
        return $this->sendParseRequest('POST', '/vouchers', 'Voucher', [
            'json' => [
                'Voucher' => $data
            ]
        ]);
    }

    /**
     * @return array
     */
    public function getOrders() {
        return $this->getPaginatedEndpoint('/orders', 'Orders');
    }

    /**
     * @param $documentNumber
     * @return array
     */
    public function getOrder($documentNumber) {
        $endpoint = sprintf('/orders/%s', $documentNumber);
        return $this->sendParseRequest('GET', $endpoint, 'Order');
    }

    /**
     * @param array $data
     * @return array
     */
    public function createOrder(array $data) {
        return $this->sendParseRequest('POST', '/orders', 'Order', [
            'json' => [
                'Order' => $data
            ]
        ]);
    }

    /**
     * @param $orderId
     * @return array
     */
    public function cancelOrder($orderId) {
        $endpoint = sprintf('/orders/%s/cancel', $orderId);
        return $this->sendParseRequest('PUT', $endpoint);
    }

}

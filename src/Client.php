<?php

namespace Vinnia\Fortnox;

use GuzzleHttp\Client as HttpClient;
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
     * @param HttpClient $client
     * @param string $accessToken
     * @param string $clientSecret
     * @param bool $https
     */
    function __construct(HttpClient $client, $accessToken, $clientSecret, $https = true) {
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
        $res = $this->httpClient->request($method, self::API_URL . $endpoint, array_merge_recursive([
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
        $response = $this->sendRequest('GET', '/customers');
        $parsed = $this->parseJsonResponse($response);
        $customers = $parsed['Customers'];
        $totalPages = (int) $parsed['MetaInformation']['@TotalPages'];

        for ($i = 2; $i <= $totalPages; $i++) {
            $response = $this->sendRequest('GET', '/customers', ['query' => ['page' => $i]]);
            $parsed = $this->parseJsonResponse($response);
            $customers = array_merge($customers, $parsed['Customers']);
        }

        return $customers;
    }

    /**
     * @param int $customerNumber
     * @return string[] customer
     */
    public function getCustomer($customerNumber) {
        $response = $this->sendRequest('GET', '/customers/' . $customerNumber);
        $parsed = $this->parseJsonResponse($response);
        return $parsed['Customer'];
    }

    /**
     * @param string[] $data customer data
     * @return string[] customer
     */
    public function createCustomer(array $data) {
        $response = $this->sendRequest('POST', '/customers', [
            'json' => [
                'Customer' => $data
            ]
        ]);

        $parsed = $this->parseJsonResponse($response);
        return $parsed['Customer'];
    }

    /**
     * @param int $customerNumber
     * @param string[] $data
     * @return string[] customer
     */
    public function updateCustomer($customerNumber, array $data) {
        $response = $this->sendRequest('PUT', '/customers/' . $customerNumber, [
            'json' => [
                'Customer' => $data
            ]
        ]);
        $parsed = $this->parseJsonResponse($response);
        return $parsed['Customer'];
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
        $response = $this->sendRequest('GET', '/suppliers');
        $parsed = $this->parseJsonResponse($response);
        $suppliers = $parsed['Suppliers'];
        $totalPages = (int) $parsed['MetaInformation']['@TotalPages'];
        for ($i = 2; $i <= $totalPages; $i++) {
            $response = $this->sendRequest('GET', '/suppliers', ['query' => ['page' => $i]]);
            $parsed = $this->parseJsonResponse($response);
            $suppliers = array_merge($suppliers, $parsed['Suppliers']);
        }

        return $suppliers;
    }

    /**
     * @param string $id
     * @return string[] supplier
     */
    public function getSupplier($id) {
        $response = $this->sendRequest('GET', '/suppliers/' . $id);
        $parsed = $this->parseJsonResponse($response);
        return $parsed['Supplier'];
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
        $response = $this->sendRequest('POST', '/inbox', [
            'body' => $data
        ]);

        return $this->parseJsonResponse($response)['File'];
    }

    /**
     * @param mixed $supplierInvoiceNumber
     * @param mixed $fileId
     * @return string[]
     */
    public function createSupplierInvoiceFileConnection($supplierInvoiceNumber, $fileId) {
        $r = $this->sendRequest('POST', '/supplierinvoicefileconnections', [
            'json' => [
                'SupplierInvoiceFileConnection' => [
                    'SupplierInvoiceNumber' => $supplierInvoiceNumber,
                    'FileId' => $fileId,
                ]
            ]
        ]);
        return $this->parseJsonResponse($r)['SupplierInvoiceFileConnection'];
    }

    /**
     * @return string[][]
     */
    public function getSupplierInvoiceFileConnections() {
        $r = $this->sendRequest('GET', '/supplierinvoicefileconnections');
        return $this->parseJsonResponse($r)['SupplierInvoiceFileConnections'];
    }

    /**
     * @param int $id
     * @return string[]
     */
    public function getSupplierInvoice($id) {
        $r = $this->sendRequest('GET', '/supplierinvoices/' . $id);
        return $this->parseJsonResponse($r)['SupplierInvoice'];
    }

    /**
     * @param array $data
     * @return string[]
     */
    public function createSupplierInvoice(array $data) {
        $r = $this->sendRequest('POST', '/supplierinvoices', [
            'json' => [
                'SupplierInvoice' => $data
            ]
        ]);
        return $this->parseJsonResponse($r)['SupplierInvoice'];
    }

    /**
     * @return string[][] array of projects
     */
    public function getProjects() {
        $response = $this->sendRequest('GET', '/projects');
        $parsed = $this->parseJsonResponse($response);
        return $parsed['Projects'];
    }

    /**
     * @param int $projectNumber
     * @return string[] project
     */
    public function getProject($projectNumber) {
        $response = $this->sendRequest('GET', '/projects/' . $projectNumber);
        $parsed = $this->parseJsonResponse($response);
        return $parsed['Project'];
    }

    /**
     * @param string[] $data
     * @return string[] project
     */
    public function createProject(array $data) {
        $response = $this->sendRequest('POST', '/projects', [
            'json' => [
                'Project' => $data
            ]
        ]);
        $parsed = $this->parseJsonResponse($response);
        return $parsed['Project'];
    }

    /**
     * @param int $projectNumber
     * @param string[] $data
     */
    public function updateProject($projectNumber, array $data) {
        $response = $this->sendRequest('PUT', '/projects/' . $projectNumber, [
            'json' => [
                'Project' => $data
            ]
        ]);
        $parsed = $this->parseJsonResponse($response);
        return $parsed['Project'];
    }

    /**
     * @param int $projectNumber
     * @return string
     */
    public function deleteProject($projectNumber) {
        $response = $this->sendRequest('DELETE', '/projects/' . $projectNumber);
        return (string) $response->getBody();
    }

}

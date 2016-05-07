<?php

namespace Vinnia\Fortnox;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use DateTimeInterface;

/**
 * Class Client
 * @package Vinnia\Fortnox
 *
 * @method ResponseInterface[]  getAccounts(array $data = [])
 * @method ResponseInterface    getAccount(string $accountNumber)
 * @method ResponseInterface    updateAccount(string $accountNumber, array $data)
 * @method ResponseInterface    createAccount(array $data)
 *
 * @method ResponseInterface[]  getArticles(array $data = [])
 * @method ResponseInterface    getArticle(string $articleNumber)
 * @method ResponseInterface    updateArticle(string $articleNumber, array $data)
 * @method ResponseInterface    createArticle(array $data)
 * @method ResponseInterface    deleteArticle(string $articleNumber, array $data)
 *
 * @method ResponseInterface[]  getOrders(array $data = [])
 * @method ResponseInterface    getOrder(string $orderNumber)
 * @method ResponseInterface    updateOrder(string $orderNumber, array $data)
 * @method ResponseInterface    createOrder(array $data)
 *
 * @method ResponseInterface[]  getProjects(array $data = [])
 * @method ResponseInterface    getProject(string $projectNumber)
 * @method ResponseInterface    updateProject(string $projectNumber, array $data)
 * @method ResponseInterface    createProject(array $data)
 * @method ResponseInterface    deleteProject(string $projectNumber, array $data)
 *
 * @method ResponseInterface[]  getCustomers(array $data = [])
 * @method ResponseInterface    getCustomer(string $customerNumber)
 * @method ResponseInterface    updateCustomer(string $customerNumber, array $data)
 * @method ResponseInterface    createCustomer(array $data)
 * @method ResponseInterface    deleteCustomer(string $customerNumber, array $data)
 *
 * @method ResponseInterface[]  getSupplierInvoices(array $data = [])
 * @method ResponseInterface    getSupplierInvoice(string $supplierInvoiceNumber)
 * @method ResponseInterface    updateSupplierInvoice(string $supplierInvoiceNumber, array $data)
 * @method ResponseInterface    createSupplierInvoice(array $data)
 *
 * @method ResponseInterface[]  getSuppliers(array $data = [])
 * @method ResponseInterface    getSupplier(string $supplierNumber)
 * @method ResponseInterface    updateSupplier(string $supplierNumber, array $data)
 * @method ResponseInterface    createSupplier(array $data)
 *
 * @method ResponseInterface    createVoucher(array $data)
 */
class Client
{

    const API_URL = 'https://api.fortnox.se/3';

    /**
     * @var ClientInterface
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

    const REST_METHODS = [
        'Account' => '/accounts',
        'Article' => '/articles',
        'Order' => '/orders',
        'Project' => '/projects',
        'Customer' => '/customers',
        'SupplierInvoice' => '/supplierinvoices',
        'Supplier' => '/suppliers',
        'Voucher' => '/vouchers',
    ];

    /**
     * Client constructor.
     * @param ClientInterface $client
     * @param string $accessToken
     * @param string $clientSecret
     */
    function __construct(ClientInterface $client, string $accessToken, string $clientSecret)
    {
        $this->httpClient = $client;
        $this->accessToken = $accessToken;
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param string $accessToken
     * @param string $clientSecret
     * @return Client
     */
    public static function make(string $accessToken, string $clientSecret): self
    {
        $guzzle = new \GuzzleHttp\Client();
        return new self($guzzle, $accessToken, $clientSecret);
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return ResponseInterface
     */
    protected function sendRequest(string $method, string $endpoint, array $options = []): ResponseInterface
    {
        return $this->httpClient->request($method, self::API_URL . $endpoint, array_merge([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Client-Secret' => $this->clientSecret,
                'Access-Token' => $this->accessToken,
            ],
        ], $options));
    }

    /**
     * @param string $endpoint
     * @param array $options
     * @return ResponseInterface[]
     */
    protected function getPaginatedEndpoint(string $endpoint, array $options = []): array
    {
        $responses = [];
        $totalPages = 1;
        for ($i = 1; $i <= $totalPages; $i++) {
            $res = $this->sendRequest('GET', $endpoint, array_merge($options, [
                'query' => [
                    'page' => $i,
                    'limit' => 500,
                ],
            ]));
            $parsed = Util::parseResponse($res)['MetaInformation'];
            $totalPages = (int)$parsed['@TotalPages'];
            $responses[] = $res;
        }

        return $responses;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return ResponseInterface
     */
    public function __call($name, $arguments)
    {
        preg_match('/^(get|update|create|delete)(.+)$/', $name, $matches);
        if (count($matches) !== 3) {
            throw new \BadMethodCallException("Method {$name} not found");
        }
        $isPaginated = substr($matches[2], -1) === 's';
        $method = $isPaginated ? substr($matches[2], 0, -1) : $matches[2];
        $methods = self::REST_METHODS;
        if (!isset($methods[$method])) {
            throw new \BadMethodCallException("Method {$name} not found");
        }
        $endpoint = $methods[$method];

        switch($matches[1]) {
            case 'get':
                if ($isPaginated) {
                    $params = $arguments[0] ?? [];
                    return $this->getPaginatedEndpoint($endpoint, $params);
                }
                return $this->sendRequest('GET', $endpoint . '/' . $arguments[0]);
                break;
            case 'update':
                return $this->sendRequest('PUT', $endpoint . '/' . $arguments[0], [
                    'json' => [
                        $method => $arguments[1],
                    ],
                ]);
                break;
            case 'create':
                return $this->sendRequest('POST', $endpoint, [
                    'json' => [
                        $method => $arguments[0],
                    ],
                ]);
                break;
            case 'delete':
                return $this->sendRequest('DELETE', $endpoint . '/' . $arguments[0]);
                break;
        }
    }

    #region Inbox

    /**
     * @param string $id
     * @return ResponseInterface
     */
    public function getInboxFile(string $id): ResponseInterface
    {
        return $this->sendRequest('GET', '/inbox/' . $id);
    }

    /**
     * @param string $id
     * @return ResponseInterface
     */
    public function deleteInboxFile(string $id): ResponseInterface
    {
        return $this->sendRequest('DELETE', '/inbox/' . $id);
    }

    /**
     * @param StreamInterface $data
     * @return ResponseInterface
     */
    public function putInboxFile(StreamInterface $data): ResponseInterface
    {
        return $this->sendRequest('POST', '/inbox', [
            'body' => $data,
        ]);
    }

    #endregion

    #region Supplier Invoice File Connections

    /**
     * @param string $fileId
     * @return ResponseInterface
     */
    public function getSupplierInvoiceFileConnections(string $fileId): ResponseInterface
    {
        return $this->getPaginatedEndpoint('/supplierinvoicefileconnections/' . $fileId);
    }

    /**
     * @param string $supplierInvoiceNumber
     * @param string $fileId
     * @return ResponseInterface
     */
    public function createSupplierInvoiceFileConnection(string $supplierInvoiceNumber, string $fileId): ResponseInterface
    {
        return $this->sendRequest('POST', '/supplierinvoicefileconnections', [
            'json' => [
                'SupplierInvoiceFileConnection' => [
                    'SupplierInvoiceNumber' => $supplierInvoiceNumber,
                    'FileId' => $fileId,
                ],
            ],
        ]);
    }

    /**
     * @param string $fileId
     * @return ResponseInterface
     */
    public function deleteSupplierInvoiceFileConnections(string $fileId): ResponseInterface
    {
        return $this->sendRequest('DELETE', '/supplierinvoicefileconnections/' . $fileId);
    }

    #endregion

    #region Voucher

    /**
     * @param DateTimeInterface $financialYearDate date of the financial year to use (Y-m-d)
     * @return ResponseInterface[]
     */
    public function getVouchers(DateTimeInterface $financialYearDate): array
    {
        return $this->getPaginatedEndpoint('/vouchers', [
            'query' => [
                'financialyeardate' => $financialYearDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * @param string $series
     * @param string $voucherNumber
     * @param DateTimeInterface $financialYearDate date of the financial year
     * @return ResponseInterface
     */
    public function getVoucher(string $series, string $voucherNumber, DateTimeInterface $financialYearDate): ResponseInterface
    {
        $endpoint = sprintf('/vouchers/%s/%s', $series, $voucherNumber);
        return $this->sendRequest('GET', $endpoint, [
            'query' => [
                'financialyeardate' => $financialYearDate->format('Y-m-d')
            ]
        ]);
    }

    #endregion

}

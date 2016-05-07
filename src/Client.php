<?php

namespace Vinnia\Fortnox;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use DateTimeInterface;

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
     * @param array $options
     * @return ResponseInterface[]
     */
    public function getCustomers(array $options = []): array
    {
        return $this->getPaginatedEndpoint('/customers', $options);
    }

    /**
     * @param string $customerNumber
     * @return ResponseInterface
     */
    public function getCustomer(string $customerNumber): ResponseInterface
    {
        return $this->sendRequest('GET', '/customers/' . $customerNumber);
    }

    /**
     * @param string[] $data customer data
     * @return ResponseInterface
     */
    public function createCustomer(array $data): ResponseInterface
    {
        return $this->sendRequest('POST', '/customers', [
            'json' => [
                'Customer' => $data,
            ],
        ]);
    }

    /**
     * @param string $customerNumber
     * @param string[] $data
     * @return ResponseInterface
     */
    public function updateCustomer(string $customerNumber, array $data): ResponseInterface
    {
        return $this->sendRequest('PUT', '/customers/' . $customerNumber, [
            'json' => [
                'Customer' => $data,
            ],
        ]);
    }

    /**
     * @param string $customerNumber
     * @return ResponseInterface
     */
    public function deleteCustomer(string $customerNumber): ResponseInterface
    {
        return $this->sendRequest('DELETE', '/customers/' . $customerNumber);
    }

    /**
     * @param array $options
     * @return ResponseInterface[]
     */
    public function getSuppliers(array $options = []): array
    {
        return $this->getPaginatedEndpoint('/suppliers', $options);
    }

    /**
     * @param string $id
     * @return ResponseInterface
     */
    public function getSupplier(string $id): ResponseInterface
    {
        return $this->sendRequest('GET', '/suppliers/' . $id, 'Supplier');
    }

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
     * @return ResponseInterface
     */
    public function getSupplierInvoiceFileConnections(): ResponseInterface
    {
        return $this->getPaginatedEndpoint('/supplierinvoicefileconnections');
    }

    /**
     * @param array $options guzzle request options
     * @return ResponseInterface[]
     */
    public function getSupplierInvoices(array $options = []): array
    {
        return $this->getPaginatedEndpoint('/supplierinvoices', $options);
    }

    /**
     * @param string $id
     * @return ResponseInterface
     */
    public function getSupplierInvoice(string $id): ResponseInterface
    {
        return $this->sendRequest('GET', '/supplierinvoices/' . $id);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     */
    public function createSupplierInvoice(array $data): ResponseInterface
    {
        return $this->sendRequest('POST', '/supplierinvoices', [
            'json' => [
                'SupplierInvoice' => $data,
            ],
        ]);
    }

    /**
     * @param string $id
     * @param array $data the supplier invoice data
     * @return ResponseInterface
     */
    public function updateSupplierInvoice(string $id, array $data): ResponseInterface
    {
        return $this->sendRequest('PUT', '/supplierinvoices/' . $id, [
            'json' => [
                'SupplierInvoice' => $data,
            ],
        ]);
    }

    #region Project methods

    /**
     * @param array $options
     * @return ResponseInterface[]
     */
    public function getProjects(array $options = []): array
    {
        return $this->getPaginatedEndpoint('/projects', $options);
    }

    /**
     * @param string $projectNumber
     * @return ResponseInterface
     */
    public function getProject(string $projectNumber): ResponseInterface
    {
        return $this->sendRequest('GET', '/projects/' . $projectNumber);
    }

    /**
     * @param string[] $data
     * @return ResponseInterface
     */
    public function createProject(array $data): ResponseInterface
    {
        return $this->sendRequest('POST', '/projects', [
            'json' => [
                'Project' => $data,
            ],
        ]);
    }

    /**
     * @param string $projectNumber
     * @param string[] $data
     * @return ResponseInterface
     */
    public function updateProject(string $projectNumber, array $data): ResponseInterface
    {
        return $this->sendRequest('PUT', '/projects/' . $projectNumber, [
            'json' => [
                'Project' => $data,
            ],
        ]);
    }

    /**
     * @param string $projectNumber
     * @return ResponseInterface
     */
    public function deleteProject(string $projectNumber): ResponseInterface
    {
        return $this->sendRequest('DELETE', '/projects/' . $projectNumber);
    }

    #endregion

    #region Voucher methods

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

    /**
     * @param array $data
     * @return ResponseInterface
     */
    public function createVoucher(array $data): ResponseInterface
    {
        return $this->sendRequest('POST', '/vouchers', [
            'json' => [
                'Voucher' => $data
            ]
        ]);
    }

    #endregion

    #region Order methods

    /**
     * @param array $options
     * @return ResponseInterface[]
     */
    public function getOrders(array $options = []): array
    {
        return $this->getPaginatedEndpoint('/orders', $options);
    }

    /**
     * @param string $documentNumber
     * @return ResponseInterface
     */
    public function getOrder(string $documentNumber): ResponseInterface
    {
        return $this->sendRequest('GET', '/orders/' . $documentNumber);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     */
    public function createOrder(array $data): ResponseInterface
    {
        return $this->sendRequest('POST', '/orders', [
            'json' => [
                'Order' => $data,
            ],
        ]);
    }

    /**
     * @param string $orderId
     * @return ResponseInterface
     */
    public function cancelOrder(string $orderId): ResponseInterface
    {
        $endpoint = sprintf('/orders/%s/cancel', $orderId);
        return $this->sendRequest('PUT', $endpoint);
    }

    #endregion


    #region Article methods

    /**
     * @param array $options
     * @return ResponseInterface[]
     */
    public function getArticles(array $options = []): array
    {
        return $this->getPaginatedEndpoint('/articles', $options);
    }

    /**
     * @param $articleNumber
     * @return ResponseInterface
     */
    public function getArticle(string $articleNumber): ResponseInterface
    {
        return $this->sendRequest('GET', '/articles/' . $articleNumber);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     */
    public function createArticle(array $data): ResponseInterface
    {
        return $this->sendRequest('POST', '/articles', [
            'json' => [
                'Article' => $data,
            ],
        ]);
    }


    /**
     * @param $articleNumber
     * @return ResponseInterface
     */
    public function deleteArticle(string $articleNumber): ResponseInterface
    {
        return $this->sendRequest('DELETE', '/articles/' . $articleNumber);
    }

    #endregion

    #region Account

    /**
     * @param array $options
     * @return ResponseInterface[]
     */
    public function getAccounts(array $options = []): array
    {
        return $this->getPaginatedEndpoint('/accounts', $options);
    }

    /**
     * @param string $accountNumber
     * @return ResponseInterface
     */
    public function getAccount(string $accountNumber): ResponseInterface
    {
        return $this->sendRequest('GET', '/accounts/' . $accountNumber);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     */
    public function createAccount(array $data): ResponseInterface
    {
        return $this->sendRequest('POST', '/accounts', [
            'json' => [
                'Account' => $data,
            ],
        ]);
    }

    /**
     * @param string $accountNumber
     * @param array $data
     * @return ResponseInterface
     */
    public function updateAccount(string $accountNumber, array $data): ResponseInterface
    {
        return $this->sendRequest('PUT', '/accounts/' . $accountNumber, [
            'json' => [
                'Account' => $data,
            ],
        ]);
    }

    #endregion

}

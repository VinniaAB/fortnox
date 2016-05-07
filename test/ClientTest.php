<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 15-11-13
 * Time: 14:54
 */

namespace Vinnia\Fortnox\Test;

use GuzzleHttp\Exception\ClientException;
use Vinnia\Fortnox\Client;
use Vinnia\Fortnox\Util;

class ClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Client
     */
    public $client;

    public function setUp()
    {
        parent::setUp();

        $config = require __DIR__ . '/../env.php';
        $this->client = Client::make($config['ACCESS_TOKEN'], $config['CLIENT_SECRET']);
    }

    public function testGetCustomers()
    {
        $responses = $this->client->getCustomers();
        $data = Util::parseResponses($responses);
        $customers = [];
        foreach ($data as $res) {
            $customers = array_merge($customers, $res['Customers']);
        }
        var_dump($customers);

        $this->assertNotEmpty($customers);
        $this->assertArrayHasKey('Address1', $customers[0]);
    }

    public function testCreateDeleteCustomer()
    {
        $customer = [
            'Name' => 'Helmut Schneider'
        ];

        $result = $this->client->createCustomer($customer);
        $result = Util::parseResponse($result);
        $result = $result['Customer'];

        $this->assertEquals($customer['Name'], $result['Name']);

        $this->client->deleteCustomer($result['CustomerNumber']);
    }

    public function testUpdateCustomer()
    {
        $customer = [
            'Name' => 'Helmut'
        ];
        $result = $this->client->createCustomer($customer);
        $result = Util::parseResponse($result);
        $result = $result['Customer'];
        $customer['Name'] = 'Helmut Schneider';
        $result = $this->client->updateCustomer(
            $result['CustomerNumber'],
            $customer
        );
        $result = Util::parseResponse($result);
        $result = $result['Customer'];

        $this->assertEquals('Helmut Schneider', $result['Name']);

        $this->client->deleteCustomer($result['CustomerNumber']);
    }

    public function testGetProjects()
    {
        $result = $this->client->getProjects();


        $this->assertTrue(is_array($result));
    }

    public function testGetVouchers()
    {
        $result = $this->client->getVouchers(new \DateTimeImmutable('2015-01-01'));
        var_dump($result);
        $this->assertTrue(is_array($result));
    }

    public function testGetSingleVoucher()
    {
        $result = $this->client->getVoucher('A', 1, new \DateTimeImmutable('2014-01-01'));

        var_dump($result);
    }

    public function testCreateVoucher()
    {
        $r = $this->client->createVoucher([
            'TransactionDate' => '2014-06-01',
            'VoucherSeries' => 'A',
            'Description' => 'Mat & sånt',
            'VoucherRows' => [[
                'Account' => 1930,
                'Debit' => 1500
            ], [
                'Account' => 1910,
                'Credit' => 1500
            ]]
        ]);

        var_dump($r);
    }

    public function testGetSupplierInvoices()
    {
        $r = $this->client->getSupplierInvoices();

        var_dump($r);
    }

    public function testGetOrders()
    {
        $result = $this->client->getOrders();
        $result = Util::parseResponses($result);
        $orders = [];

        foreach ($result as $res) {
            $orders = array_merge($orders, $res['Orders']);
        }

        var_dump($result);
        $this->assertTrue(is_array($result));
    }

    public function testGetSingleOrder()
    {
        $result = $this->client->getOrder(13);
        $result = Util::parseResponse($result)['Order'];
        $this->assertEquals('Helmut AB', $result['CustomerName']);
    }

    public function testCreateOrder()
    {
        // if the customer does not have an organization number this will fail
        $result = $this->client->createOrder([
            'CustomerNumber' => 6,
            'OrderRows' => [
                [
                    'ArticleNumber' => 3,
                    'DeliveredQuantity' => 10,
                    'Description' => 'Hello',
                    'OrderedQuantity' => 10,
                    'Unit' => 'st',
                ],
            ],

        ]);
        $result = Util::parseResponse($result)['Order'];
        $this->assertEquals('Vinnia AB!', $result['CustomerName']);
    }

    public function testCreateDeleteArticle()
    {
        $result = $this->client->createArticle([
            'Description' => 'A testarticle'
        ]);
        $article = Util::parseResponse($result)['Article'];

        $result = $this->client->deleteArticle($article['ArticleNumber']);
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function testGetArticle()
    {
        $result = $this->client->getArticle(1);
        $result = Util::parseResponse($result)['Article'];
        var_dump($result);
        $this->assertEquals('1', $result['ArticleNumber']);
    }

    public function testGetArticles()
    {
        $result = $this->client->getArticles();
        var_dump($result);
        $this->assertTrue(is_array($result));
    }

    public function testGetAccounts()
    {
        $responses = $this->client->getAccounts();
        $responses = Util::parseResponses($responses);
        $accounts = [];

        foreach ($responses as $res) {
            $accounts = array_merge($accounts, $res['Accounts']);
        }

        var_dump($accounts);
    }

}

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

class ClientTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Client
     */
    public $client;

    public function setUp() {
        parent::setUp();

        $guzzle = new \GuzzleHttp\Client();
        var_dump($_ENV);
        $this->client = new Client(
            $guzzle,
            $_ENV['ACCESS_TOKEN'],
            $_ENV['CLIENT_SECRET'],
            $https = false
        );
    }

    public function testGetCustomers() {
        $customers = $this->client->getCustomers();

        var_dump($customers);

        $this->assertNotEmpty($customers);
        $this->assertArrayHasKey('Address1', $customers[0]);
    }

    public function testCreateDeleteCustomer() {
        $customer = [
            'Name' => 'Helmut Schneider'
        ];

        $result = $this->client->createCustomer($customer);

        $this->assertEquals($customer['Name'], $result['Name']);

        $this->client->deleteCustomer($result['CustomerNumber']);
    }

    public function testUpdateCustomer() {
        $customer = [
            'Name' => 'Helmut'
        ];
        $result = $this->client->createCustomer($customer);
        $customer['Name'] = 'Helmut Schneider';
        $result = $this->client->updateCustomer(
            $result['CustomerNumber'],
            $customer
        );

        $this->assertEquals('Helmut Schneider', $result['Name']);

        $this->client->deleteCustomer($result['CustomerNumber']);
    }

    public function testGetProjects() {
        $result = $this->client->getProjects();

        $this->assertTrue(is_array($result));
    }

    public function testGetVouchers() {
        $result = $this->client->getVouchers('2015-01-01');
        var_dump($result);
        $this->assertTrue(is_array($result));
    }

    public function testGetSingleVoucher() {
        $result = $this->client->getVoucher('A', 1, '2014-01-01');

        var_dump($result);
    }

    public function testCreateVoucher() {
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

    public function testGetSupplierInvoices() {
        $r = $this->client->getSupplierInvoices();

        var_dump($r);
    }

}

<?php
namespace Phonect\SOAP;
use Phonect\SOAP\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
	public function testCreateInstance()
	{
		$params = [
		"id" => 123,
		'file' => [
			'content'  => 'content',
			'filename' => 'name'
			
			]
		];
		$client = Client::createInstance(
				'https://core-api.phonect.net/phonect-api/PhonectAPIService?wsdl',
				'http://service.phonect.no/',
				__DIR__ . '/../../../api-consumer.log' //Here log entries are stored under laravels storage path. 
		);

		$this->assertInstanceOf(Client::class, $client);
	}
}
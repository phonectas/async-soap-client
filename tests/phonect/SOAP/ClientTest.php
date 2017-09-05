<?php
namespace Phonect\SOAP;
use Phonect\SOAP\Client;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

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
				'https://www.example.com/phonect-api/PhonectAPIService?wsdl',
				'http://service.phonect.no/',
				__DIR__ . '/../../../api-consumer.log' //Here log entries are stored under laravels storage path. 
		);

		$this->assertInstanceOf(Client::class, $client);
	}

	public function testSoapSerialize() {
		$_SERVER['REQUEST_URI'] = 'https://www.example.com/phonect-api/PhonectAPIService?wsdl';
		$client = Client::createInstance(
			'https://www.example.com/phonect-api/PhonectAPIService?wsdl',
			'http://service.phonect.no/',
			'/tmp/' . md5(uniqid(rand(), true)) . 'api-consumer.log' //Here log entries are stored under laravels storage path. 
		);
		$body1 = \GuzzleHttp\Stream\Stream::factory('');
		$body2 = \GuzzleHttp\Stream\Stream::factory('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <ns2:addPersonResponse xmlns:ns2="http://service.phonect.no/">
            <return>335</return>
        </ns2:addPersonResponse>
    </soap:Body>
</soap:Envelope>');

		$mock = new MockHandler([
			new Response(200, ['Content-Type' => 'text/xml; charset="UTF-8'], $body1),
		    new Response(200, ['Content-Type' => 'text/xml; charset="UTF-8'], $body2)
		]);


		$config = $client->getClient()->getConfig();
		$handler = $config['handler'];
		$handler->setHandler($mock);
		$response = $client->getClient()->request('POST', 'https://www.example.com/phonect-api/PhonectAPIService?wsdl');
		$this->assertEquals(200, $response->getStatusCode());
		$response2 = null;
		$promise = $client->addPerson(
			['person' => ['firstName' => 'Phonect', 'lastName' => 'Nissen', 'iCustomer' => 123]]
		)->then(function ($response) use (&$response2) { //someSoapAction == SOAPAction
			$response2 = $response;
	    }, function ($exception) {
			throw $exception;
	    });
    	$promise->wait();
		$this->assertEquals(200, $response2->getStatusCode());
		$this->assertEquals(
			'<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <ns2:addPersonResponse xmlns:ns2="http://service.phonect.no/">
            <return>335</return>
        </ns2:addPersonResponse>
    </soap:Body>
</soap:Envelope>',
			(string)$response2->getBody());
		$this->assertEquals((object)array('return' => 335), $response2->getBody()->soapSerialize());
	}
}
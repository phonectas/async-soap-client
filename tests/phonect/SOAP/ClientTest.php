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
	private $client;

	public function __construct() {
		$this->client = Client::createInstance(
			'https://www.example.com/phonect-api/PhonectAPIService?wsdl',
			'http://service.phonect.no/',
			'/tmp/' . md5(uniqid(rand(), true)) . 'api-consumer.log' //Here log entries are stored under laravels storage path. 
		);

		$_SERVER['REQUEST_URI'] = 'https://www.example.com/phonect-api/PhonectAPIService?wsdl';
		$body = \GuzzleHttp\Stream\Stream::factory(
		'<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
			<soap:Body>
				<ns2:addPersonResponse xmlns:ns2="http://service.phonect.no/">
					<return>335</return>
				</ns2:addPersonResponse>
			</soap:Body>
		</soap:Envelope>');

		$mock = new MockHandler([
			new Response(200, ['Content-Type' => 'text/xml; charset="UTF-8'], $body)
		]);

		$config = $this->client->getClient()->getConfig();
		$handler = $config['handler'];
		$handler->setHandler($mock);
	}

	public function createInstance() {
		return Client::createInstance(
			'https://www.example.com/phonect-api/PhonectAPIService?wsdl',
			'http://service.phonect.no/',
			'/tmp/' . md5(uniqid(rand(), true)) . 'api-consumer.log' //Here log entries are stored under laravels storage path. 
		);
	}

	public function testThatSoapServiceIsRunning() {
		$response = $this->client->getClient()->request('POST', 'https://www.example.com/phonect-api/PhonectAPIService?wsdl');
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function testSyncPost() {
		$result = null;
		$result = $this->client->postSync(
			'addPerson',
			['person' => ['firstName' => 'Phonect', 'lastName' => 'Nissen', 'iCustomer' => 123]]
		);
		$this->assertEquals(200, $result->getStatusCode());
		$this->assertEquals(
		'<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
			<soap:Body>
				<ns2:addPersonResponse xmlns:ns2="http://service.phonect.no/">
					<return>335</return>
				</ns2:addPersonResponse>
			</soap:Body>
		</soap:Envelope>',
			(string) $result->getBody());
		$this->assertEquals((object)array('return' => 335), $result->getBody()->soapSerialize());
	}

	public function testAsyncPost() {
		$result = null;
		$promise = $this->client->postAsync(
			'addPerson',
			['person' => ['firstName' => 'Phonect', 'lastName' => 'Nissen', 'iCustomer' => 123]]
		)->then(function ($response) use (&$result){ //someSoapAction == SOAPAction
			$result = $response;
	    }, function ($exception) {
			throw $exception;
	    });;
		$promise->wait();
		$this->assertEquals(200, $result->getStatusCode());
		$this->assertEquals(
		'<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
			<soap:Body>
				<ns2:addPersonResponse xmlns:ns2="http://service.phonect.no/">
					<return>335</return>
				</ns2:addPersonResponse>
			</soap:Body>
		</soap:Envelope>',
			(string) $result->getBody());
		$this->assertEquals((object)array('return' => 335), $result->getBody()->soapSerialize());
	}
}
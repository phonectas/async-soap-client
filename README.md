## A SOAP client that can do Asynchronous SOAP calls

The client uses Guzzle and Guzzle promises to make soap requests.
see: [async-requests](http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests);

### Usage:

    use Phonect\SOAP\Client;

    $params = [
		"id" => 123,
		'file' => [
			'content'  => 'content',
			'filename' => 'name'
			
		]
	];
	$client = Client::createInstance(
			env('API_URL', 'https://core-api.phonect.net/phonect-api/PhonectAPIService?wsdl'),
			'http://service.phonect.no/',
			storage_path('logs/api-consumer.log') //Here log entries are stored under laravels storage path. 
	);
	$promise = $client->setIvrIntro($params)->then(function ($response) {
		Log::info((string)$response->getBody(), array('status' => $response->getStatusCode()));
		return (string)$response->getBody();
	}, function ($exception) use ($params) {
		Log::error($e->getMessage(),
			array(
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'method' => $e->getRequest()->getMethod(),
				'response' => $e->getResponseBodySummary(),
				'status' => $e->getResponse()->getStatusCode(),
				'params' => array(
					'id' => $params['id'],
					'fileName' => $params['file']['filename'],
					'content_char_length' => strlen($params['file']['content'])
				)
			)
		);
	});
	//Echo the response
	echo $promise->wait():
## A SOAP client that can do Asynchronous SOAP calls

The client uses Guzzle and Guzzle promises to make soap requests.
see: [async-requests](http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests);

### Install:
	{
	    "repositories": [
	           {
	                   "type": "vcs",
	                   "url": "https://github.com/phonectas/async-soap-client"
	           }
	    ]
    },
    "require": {
    	 "phonectas/async-soap-client": "^1.0"
    }

### Usage:

    use Phonect\SOAP\Client;
    use Monlog\Logger as Log;

    $params = [
		"id" => 123,
		'file' => [
			'content'  => 'content',
			'filename' => 'name'
			
		]
	];
	$client = Client::createInstance(
			'https://example.com/api/service?wsdl', //soap api path
			'http://example.com', //soap namespace
			'logs/api-consumer.log' //Here log entries are stored under laravels storage path. 
	);
	$promise = $client->someSoapAction($params)->then(function ($response) { //someSoapAction == SOAPAction
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

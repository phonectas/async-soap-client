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
    },
    "require": {
    	 "phonectas/async-soap-client": "^1.0"
    }
    }
### Usage:

```php
    use Phonect\SOAP\Client;
    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;

    // create a log channel
    $log = new Logger('name');
    $log->pushHandler(new StreamHandler('path/to/your.log', Logger::INFO));
    $params = [
		"id" => 123,
		'file' => ['content'  => 'content', 'filename' => 'name']
    ];
    $client = Client::createInstance(
		'https://example.com/api/service?wsdl', //soap api path
		'http://example.com', //soap namespace
		'logs/api-consumer.log'
    );
    $promise = $client->someSoapAction($params)->then(function ($response) use ($log) { //someSoapAction == SOAPAction
		return (string)$response->getBody(); //returning xml
    }, function ($exception) use ($params, $log) {
		$log->error($e->getMessage());
    });
    //Echo the response as soap
    echo $promise->wait();

    $promise = $client->someSoapAction($params)->then(function ($response) use ($log) { //someSoapAction == SOAPAction
    	//returning the node named return ex. "<return><value>test</value></return>" as an object formated as: {'return': ['value': 'test'] or null if return doesn't exist
		return (string)$response->getBody()->soapSerialize(); 
    }, function ($exception) use ($params, $log) {
		$log->error($e->getMessage());
    });
    //dump the response as an object
    var_dump($promise->wait());

    
```

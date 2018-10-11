<?php
namespace Phonect\SOAP;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\BadResponseException as BadResponseException;

class Client {
		private static $logger;
		private static $baseUri;
		private $bodyFactory;
		private $client;
		private $POST = 'POST';
		
		/**
		 * @param string $baseUri
		 * @param string $xmlns
		 * @param string $logFile
		 * @param int 	 $connectionTimeout
		 * @param int 	 $readTimeout
		 * @param int 	 $timeout
		 * @return Phonect\SOAP\Client
		 */
		public static function createInstance($baseUri, $xmlns, $logFile, $orderid = '', $connectionTimeout = 60, $readTimeout = 180, $timeout = 240) {
			self::$baseUri = $baseUri;
			return new self($baseUri, $xmlns, self::createHandlerStack([
				'{method} {uri} HTTP/{version}',
				'RESPONSE: {code}'
			], $logFile), $orderid, $connectionTimeout, $readTimeout, $timeout);
		} //GuzzleHttp\HandlerStack
		
		/**
		 * @param string 				  $baseUri
		 * @param string 				  $xmlns
		 * @param GuzzleHttp\HandlerStack                 $handler
		 * @param int 					  $connectionTimeout
		 * @param int 					  $readTimeout
		 * @param int 	 				  $timeout
		 */
		public function __construct($baseUri, $xmlns, $handler = null, $orderid = '', $connectionTimeout = 60, $readTimeout = 180, $timeout = 240) {
			$config = [
				'base_uri' => self::$baseUri,
				'connection_timeout' =>  $connectionTimeout,
				'read_timeout' => $readTimeout,
				'timeout' => $timeout
			];
			if ($handler) {
				$config['handler'] = $handler;
			}
			$this->setBodyFactory(new SoapRequestBodyFactory($xmlns));
			$this->client = new \GuzzleHttp\Client($config);
			$this->orderid = $orderid;
		}

		public function setBodyFactory(RequestCreate $bodyFactory) {
			$this->bodyFactory = $bodyFactory;
		}
		
		/**
		 * @return Guzzle\Http\Client
		 */
		public function getClient() {
			return $this->client;
		}
		
		/**
		 * IMPORTANT - support this method until we can refactor all usages.
		 * Convienience method for using soap actions directly on the client.
		 * @example ```php Phonect\SOAP\Client::getClient()->soapAction($parameters);```
		 * @param string $soapAction the SOAP action
		 * @param array $arguments   the method arguments
		 * @return Promise @see http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests
		 */
		public function __call($soapAction, $arguments) {
			$parameters = empty($arguments) ? array() : $arguments[0];
			return $this->sendAsyncRequest($soapAction, $parameters, $this->POST);
		}

		/**
		 * Method for posting synchronous requests
		 * @example ```php Phonect\SOAP\Client::getClient()->post($soapAction, $parameters);```
		 * @param string $soapAction the SOAP action
		 * @param array $arguments   the method arguments
		 * @return Promise @see http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests
		 */
		public function postAsync($soapAction, $arguments) {
			try {
				$parameters = empty($arguments) ? array() : $arguments;
				return $this->sendAsyncRequest($soapAction, $parameters, $this->POST);
			}
			catch (BadResponseException $e) {
				throw $e;
			}
		}

		/**
		 * Method for posting asynchronous requests
		 * @example ```php Phonect\SOAP\Client::getClient()->postAsync($soapAction, $parameters);```
		 * @param string $soapAction the SOAP action
		 * @param array $arguments   the method arguments
		 * @return Promise @see http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests
		 */
		public function postSync($soapAction, $arguments) {
			try {
				$promise = $this->postAsync($soapAction, $arguments)->then(function($result) {
					return $result;
				});
				$result = $promise->wait();
				return $result;
			}
			catch (BadResponseException $e) {
				throw $e;
			}
		}
		
		/**
		 * Make an asynchronous SOAP POST request and returns a promise.
		 * @param  String $method SOAPAction
		 * @param  array  $params parameters to post
		 * @return Promise @see http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests
		 */
		public function sendAsyncRequest($method, $params, $requestType) {
			if (!$this->client) {
				return;
			}
			$promise = $this->client->requestAsync($requestType, self::$baseUri,
				[
					'body'    => $this->bodyFactory->create($method, $params),
					'headers' => [
						'User-Agent' => 'Phonect SOAP client',
						'Origin' => \parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
						'Accept' => '*/*',
						'Content-Type' => 'text/xml; charset="UTF-8"',
						'SOAPAction' => '',
						'track_app' => $_SERVER["HTTP_HOST"],
						'track_ip' => self::getUserIP(),
						'phonect-req-id' => uniqid('wf_'),
						'phonect-order-id' => $this->orderid
					]
				]
			);
			return $promise;
		}
		
		public static function createHandlerStack(array $messageFormats, $logFile) {
		    $stack = \GuzzleHttp\HandlerStack::create();
			$stack->unshift(Middleware::mapResponse(function (Response $response) {
				$soapStream = new SoapStream($response->getBody());
				return $response->withBody($soapStream);
			}), 'soap');
			foreach ($messageFormats as $messageFormat) {
		        // We'll use unshift instead of push, to add the middleware to the bottom of the stack, not the top
		        $stack->unshift(
		            self::createGuzzleLoggingMiddleware($messageFormat, $logFile),
			    'logger'
		        );
			}
		    return $stack;
		}
		
		private static function createGuzzleLoggingMiddleware($messageFormat, $logFile) {
			return \GuzzleHttp\Middleware::log(
				self::getLogger($logFile),
				new \GuzzleHttp\MessageFormatter($messageFormat)
			);
		}
		
		private static function getLogger($logFile)
		{
			if (!self::$logger) {
				self::$logger = (new \Monolog\Logger('soap-api-consumer'))->pushHandler(
					new \Monolog\Handler\RotatingFileHandler($logFile)
				);
			}
			return self::$logger;
		}
	
		private static function getUserIP() {
			$client  = @$_SERVER['HTTP_CLIENT_IP'];
			$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
			$remote  = $_SERVER['REMOTE_ADDR'];

			if (filter_var($client, FILTER_VALIDATE_IP)) $ip = $client;
			elseif (filter_var($forward, FILTER_VALIDATE_IP)) $ip = $forward;
			else $ip = $remote;

			return $ip;
		}

}

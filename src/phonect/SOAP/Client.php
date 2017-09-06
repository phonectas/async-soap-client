<?php
namespace Phonect\SOAP;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Middleware;

class Client {
		private static $logger;
		private static $baseUri;
		private $xmlns;
		private $client;
		
		/**
		 * @param string $baseUri
		 * @param string $xmlns
		 * @param string $logFile
		 * @param int 	 $connectionTimeout
		 * @param int 	 $readTimeout
		 * @param int 	 $timeout
		 * @return Phonect\SOAP\Client
		 */
		public static function createInstance($baseUri, $xmlns, $logFile, $connectionTimeout = 60, $readTimeout = 180, $timeout = 240) {
			self::$baseUri = $baseUri;
			return new self($baseUri, $xmlns, self::createHandlerStack([
				'{method} {uri} HTTP/{version}',
				'RESPONSE: {code}'
			], $logFile), $connectionTimeout, $readTimeout, $timeout);
		} //GuzzleHttp\HandlerStack
		
		/**
		 * @param string 				  $baseUri
		 * @param string 				  $xmlns
		 * @param GuzzleHttp\HandlerStack                 $handler
		 * @param int 					  $connectionTimeout
		 * @param int 					  $readTimeout
		 * @param int 	 				  $timeout
		 */
		public function __construct($baseUri, $xmlns, $handler = null, $connectionTimeout = 60, $readTimeout = 180, $timeout = 240) {
			$config = [
				'base_uri' => self::$baseUri,
				'connection_timeout' =>  $connectionTimeout,
				'read_timeout' => $readTimeout,
				'timeout' => $timeout
			];
			if ($logHandler) {
				$config['handler'] = $logHandler;
			}
			$this->xmlns = $xmlns;
			$this->client = new \GuzzleHttp\Client($config);
		}
		
		/**
		 * @return Guzzle\Http\Client
		 */
		public function getClient() {
			return $this->client;
		}
		
		/**
		 * Convienience method for using soap actions directly on the client.
		 * @example ```php Phonect\SOAP\Client::getClient()->soapAction($parameters);```
		 * @param string $soapAction the SOAP action
		 * @param array $arguments   the method arguments
		 * @return Promise @see http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests
		 */
		public function __call($soapAction, $arguments) {
			$parameters = empty($arguments) ? array() : $arguments[0];
			return $this->post($soapAction, $parameters);
		}
		
		/**
		 * Make an asynchronous SOAP POST request and returns a promise.
		 * @param  String $method SOAPAction
		 * @param  array  $params parameters to post
		 * @return Promise @see http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests
		 */
		public function post($method, $params) {
			if (!$this->client) {
				return;
			}
			
			$promise = $this->client->requestAsync('POST', self::$baseUri,
				[
					'body'    => self::createSOAPEnvelope($method, $params, $this->xmlns),
					'headers' => [
						'User-Agent' => 'Workflow Phonect SOAP client',
						'Origin' => \parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
						'Accept' => '*/*',
						'Content-Type' => 'text/xml; charset="UTF-8"',
						'SOAPAction' => ''
					]
				]
			);
			return $promise;
		}
		
		/**
		 * Create a SOAP envelope that will be used around the SOAP action
		 * @param  array $data
		 * @return string SOAP
		 */
		private static function createSOAPEnvelope($method, $data, $xmlns = '') {
			$xml_data = new \SimpleXMLElement('<' . $method . ' xmlns="' . (string)$xmlns . '" />');
			self::arrayToXml($data, $xml_data);
			$dom = \dom_import_simplexml($xml_data);
			return '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/"><Body>' .
				$dom->ownerDocument->saveXML($dom->ownerDocument->documentElement) .
				'</Body></Envelope>';
		}
		
		private static function arrayToXml( $data, &$xml_data ) {
			foreach( $data as $key => $value ) {
				if( is_numeric($key) ){
					$key = 'item'.$key; //dealing with <0/>..<n/> issues
				}
				if( is_array($value) ) {
					$subnode = $xml_data->addChild($key);
					$subnode->addAttribute('xmlns', '');
					self::arrayToXml($value, $subnode);
				} else {
					$xml_data->addChild("$key",htmlspecialchars("$value"))->addAttribute('xmlns', '');
				}
			}
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
}

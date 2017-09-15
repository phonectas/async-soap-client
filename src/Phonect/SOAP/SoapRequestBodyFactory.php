<?php
namespace Phonect\SOAP;

class SoapRequestBodyFactory implements RequestCreate{

	private $xmlns;

	/**
	 * @param $xmlns string main namespace
	 */
	public function __construct($xmlns = '') {
		$this->xmlns = $xmlns;
	}

	/**
	 * @param  string $method the SOAPAction 
	 * @param  array  $data the soap data to send.
	 *  
	 * @return string
	 */
	public function create($method, $data) {
		return self::createSOAPEnvelope($method, $data, $this->xmlns);
	}

	/**
	 * Create a SOAP envelope that will be used around the SOAP action
	 * @param  array $data
	 * @return string SOAP
	 */
	private static function createSOAPEnvelope($method, $data, $xmlns) {
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
	
}
<?php
namespace Phonect\SOAP;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
class SoapStream implements StreamInterface
{
  use StreamDecoratorTrait;
  public function soapSerialize()
  {
  	//fseek($this->getContents(), 0);
  	$this->seek(0);
	$contents = (string) $this->getContents();

	if ($contents === '') {
		return null;
	}

	try {
		$xml = simplexml_load_string($contents);
		$match = $xml->xpath('//return');
		if (empty($match)) {
		  return null;
		}
		$data = [];
		if (count($match) == 1) {
			$data = $match[0];
			$match = array();
		}
		foreach ($match as $item) {
			$data[] = $item;
		}
		$json = json_encode($data);
		if (empty($json)) {
		  return null;
		}
		$return = json_decode($json,true);
		if (empty($return)) {
		  return null;
		}
		if (!empty($return[0]) && count($return) == 1 && !is_array($return[0])) {
		  $return = array_pop($return);
		}
		$response = (object) ['return' => $return];
		return $response;
	} catch (\Exception $e) {
		throw new RuntimeException(
			'Error trying to decode response: ' .
			$e->getMessage() . ', line: ' . $e->getLine(),
			500,
			$e
		);
	}
	return $decodedContents;
  }
}

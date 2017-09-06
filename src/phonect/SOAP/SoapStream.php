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
		if (count($return) == 1) {
		  $return = $return[0];
		}
		$response = (object) ['return' => $return];
		return $response;
	} catch (\Exception $e) {
		throw new RuntimeException(
			'Error trying to decode response: ' .
			$e->getMessage(),
			500,
			$e
		);
	}
	return $decodedContents;
  }
}

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
		$json = json_encode($match[0]);

		if (empty($json)) {
		  return null;
		}
		$return = json_decode($json,true);

		if (empty($return)) {
		  return null;
		}
		if (!empty($return[0])) {
		  $return = $return[0];
		}

		return (object) ['return' => $return];
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
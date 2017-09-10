<?php

namespace Phonect\SOAP;

use GuzzleHttp\Psr7\Stream;
use PHPUnit_Framework_TestCase;

class SoapStreamTest extends PHPUnit_Framework_TestCase
{

    public function testIsInstanceOfJsonStream()
    {
        $soapStream = $this->getSoapStream('');

        $this->assertInstanceOf('Phonect\SOAP\SoapStream', $soapStream);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $soapStream);
    }

    /**
     * @dataProvider dataSoapSerialize
     */
    public function testSoapSerialize($expectedArray, $string)
    {
        $expectedObject = $expectedArray ? (object) $expectedArray : null;
        $soapStream = $this->getSoapStream($string);
        $object = $soapStream->soapSerialize();

        $this->assertEquals($expectedObject, $object);
    }

    public static function dataSoapSerialize()
    {
        return [
            [
                'expectedArray' => ['return' => '335'],
                'string' => '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <ns2:addPersonResponse xmlns:ns2="http://service.phonect.no/">
            <return>335</return>
        </ns2:addPersonResponse>
    </soap:Body>
</soap:Envelope>',
            ],
            [
                'expectedArray' => ['return' => ['person' => ['name' => ['first' => 'Phonect', 'last' => 'Nisse'],'id' => 123]]],
                'string' => '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <ns2:addPersonResponse xmlns:ns2="http://service.phonect.no/">
            <return><person><name><first>Phonect</first><last>Nisse</last></name><id>123</id></person></return>
        </ns2:addPersonResponse>
    </soap:Body>
</soap:Envelope>',
            ],
            [
                'expectedArray' => ['return' => [
                    ['person' => ['name' => ['first' => 'Phonect', 'last' => 'Nisse'],'id' => 123]],
                    ['person' => ['name' => ['first' => 'Phonect', 'last' => 'Nisse'],'id' => 123]]
                ]],
                'string' => '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <ns2:addPersonResponse xmlns:ns2="http://service.phonect.no/">
            <return>
            <person><name><first>Phonect</first><last>Nisse</last></name><id>123</id></person>
            </return>
            <return>
            <person><name><first>Phonect</first><last>Nisse</last></name><id>123</id></person>
            </return>
        </ns2:addPersonResponse>
    </soap:Body>
</soap:Envelope>',
            ],
            [
                'expectedArray' => null,
                'string' => '',
            ],
        ];
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSoapSerializeException()
    {
        $jsonStream = $this->getSoapStream('words');
        $jsonStream->soapSerialize();
    }

    protected function getSoapStream($string)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $string);
        fseek($stream, 0);
        $streamObject = new Stream($stream);

        return new SoapStream($streamObject);
    }
}
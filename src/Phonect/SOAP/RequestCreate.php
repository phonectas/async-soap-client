<?php

namespace Phonect\SOAP;

interface RequestCreate {

	/**
	 * @param  string $method the SOAPAction 
	 * @param  array  $data the soap data to send.
	 *  
	 * @return string
	 */
	public function create($method, $data);
}
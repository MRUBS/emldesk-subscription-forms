<?php
	if ( ! defined( 'ABSPATH' ) ) exit;   // Exit if accessed directly

	$url = 'https://go.emldesk.com/libraries/form_wizard/process_subscribe.asp';
	$data = $_POST;
	
	$query = http_build_query($data);
	
	$opts = array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $query
		)
	);

	$context  = stream_context_create($opts);

	$body = file_get_contents($url, false, $context);

	$responses = parse_http_response_header($http_response_header);

	$firstResponse = end($responses); // responses are last to first, thus using end() to get the first response.
	$firstStatusCode = $firstResponse['status']['code'];

	return $firstStatusCode;

	//echo "Status code (before first redirect): $firstStatusCode<br>\n";

	/**
	 * parse_http_response_header
	 *
	 * @param array $headers as in $http_response_header
	 * @return array status and headers grouped by response, last first
	 */

	function parse_http_response_header(array $headers)
	{
		$responses = array();
		$buffer = NULL;
		foreach ($headers as $header)
		{
			if ('HTTP/' === substr($header, 0, 5))
			{
				// add buffer on top of all responses
				if ($buffer) array_unshift($responses, $buffer);
				$buffer = array();

				list($version, $code, $phrase) = explode(' ', $header, 3) + array('', FALSE, '');

				$buffer['status'] = array(
					'line' => $header,
					'version' => $version,
					'code' => (int) $code,
					'phrase' => $phrase
				);
				$fields = &$buffer['fields'];
				$fields = array();
				continue;
			}
			list($name, $value) = explode(': ', $header, 2) + array('', '');
			// header-names are case insensitive
			$name = strtoupper($name);
			// values of multiple fields with the same name are normalized into
			// a comma separated list (HTTP/1.0+1.1)
			if (isset($fields[$name]))
			{
				$value = $fields[$name].','.$value;
			}
			$fields[$name] = $value;
		}
		unset($fields); // remove reference
		array_unshift($responses, $buffer);

		return $responses;
	}	
?>
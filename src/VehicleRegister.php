<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

namespace JuniWalk\RSV;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

/**
 * @phpstan-type Vehicle object{
 * 	VIN: string,
 * 	TovarniZnacka: string,
 * 	ObchodniOznaceni: string,
 * 	VozidloKaroserieBarva: string,
 * }
 */
class VehicleRegister
{
	const API_URI = 'https://api.dataovozidlech.cz/api/vehicletechnicaldata/v2';

	private Client $http;


	public function __construct(string $secret)
	{
		$this->http = new Client([
			'headers' => [
				'Content-Type' => 'application/json',
				'API_KEY' => $secret,
			],
			'base_uri' => static::API_URI,
			'timeout' => 6,
		]);
	}


	/**
	 * @param  scalar|null $vin
	 * @return ?Vehicle
	 * @throws RequestException
	 */
	public function findByVIN(mixed $vin = null): ?object
	{
		return $this->request('GET', '/', [
			'vin' => $vin,
		]);
	}


	/**
	 * @param  array<string, mixed> $query
	 * @return ?Vehicle
	 * @throws ConnectException
	 * @throws RequestException
	 * @throws RuntimeException
	 */
	private function request(string $method, string $path, array $query = []): ?object
	{
		$path = trim($path, '/');

		if (!empty($query)) {
			$path .= '?'.http_build_query($query);
		}

		try {
			$response = $this->http->request($method, $path);

		} catch (RequestException $e) {
			if (!$e->hasResponse()) {
				throw $e;
			}

			$response = $e->getResponse();
		}

		if (!$result = $response?->getBody()->getContents()) {
			throw new RuntimeException('Cannot read response body.');
		}

		if (!$result = json_decode($result)) {
			throw new RuntimeException('Cannot read response body.');
		}

		/** @var object $result */

		if (isset($result->Message)) {
			// $result->Type === 2 // maximum request (27 in 60 seconds)
			throw new RuntimeException($result->Message);
		}

		/** @var object{Status: int, Data: ?Vehicle} $result */

		if (!in_array($result->Status, [1, 3])) {
			// 1 - OK, 3 - Not found
			throw new RuntimeException('Request failed.');
		}

		return $result->Data;
	}
}

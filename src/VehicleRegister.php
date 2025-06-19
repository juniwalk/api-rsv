<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

namespace JuniWalk\RSV;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use JuniWalk\RSV\Entity\Vehicle;
use JuniWalk\RSV\Exceptions\ContentMalformedException;
use JuniWalk\RSV\Exceptions\RequestFailedException;
use JuniWalk\RSV\Exceptions\VehicleNotFoundException;

/**
 * @link https://dataovozidlech.cz/wwwroot/data/RSV_Verejna_API_DK_v1_0.pdf
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
	 * @throws ContentMalformedException
	 * @throws RequestFailedException
	 * @throws VehicleNotFoundException
	 */
	public function findByVIN(mixed $vin = null): Vehicle
	{
		$data = $this->request('GET', '/', [
			'vin' => $vin,
		]);

		return new Vehicle((array) $data);
	}


	/**
	 * @param  array<string, mixed> $query
	 * @return object
	 * @throws ContentMalformedException
	 * @throws RequestFailedException
	 * @throws VehicleNotFoundException
	 */
	private function request(string $method, string $path, array $query = []): object
	{
		$path = trim($path, '/');

		if (!empty($query)) {
			$path .= '?'.http_build_query($query);
		}

		try {
			$response = $this->http->request($method, $path);

		} catch (RequestException $e) {
			if (!$e->hasResponse()) {
				throw new RequestFailedException(previous: $e);
			}

			$response = $e->getResponse();
		}

		if (!$content = $response?->getBody()->getContents()) {
			throw new ContentMalformedException('Response body is empty.');
		}

		if (!$json = json_decode($content)) {
			throw new ContentMalformedException('Unable to decode JSON.');
		}

		/** @var object $json */

		if (isset($json->Message)) {
			// $json->Type === 2 // maximum request (27 in 60 seconds)
			throw new RequestFailedException($json->Message);
		}

		/** @var object{Status: int, Data: object} $json */

		return match ($json->Status) {
			1 => $json->Data,
			// 2 => ?
			3 => throw new VehicleNotFoundException,

			default => throw new RequestFailedException,
		};
	}
}

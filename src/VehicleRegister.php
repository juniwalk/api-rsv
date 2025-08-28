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
use JuniWalk\RSV\Exceptions\RateLimitedException;
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
	 * @throws RateLimitedException
	 * @throws RequestFailedException
	 * @throws VehicleNotFoundException
	 */
	public function findByVIN(mixed $vin = null): Vehicle
	{
		if (empty($vin)) {
			throw new VehicleNotFoundException;
		}

		$data = $this->request('GET', '/', [
			'vin' => $vin,
		]);

		return new Vehicle((array) $data);
	}


	/**
	 * @param  array<string, mixed> $query
	 * @return object
	 * @throws ContentMalformedException
	 * @throws RateLimitedException
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

		if (!$json = json_decode($content, false)) {
			throw new ContentMalformedException('Unable to decode JSON.');
		}

		/** @var object{Status: int, Data: object, Message: string, Type: int} $json */

		if (isset($json->Message)) {
			throw match ($json->Type) {
				2		=> new RateLimitedException($json->Message),
				default => new RequestFailedException($json->Message),
			};
		}

		return match ($json->Status) {
			1		=> $json->Data,
			3		=> throw new VehicleNotFoundException,
			4		=> throw new VehicleNotFoundException,	// ? Invalid VIN format ?
			default => throw new RequestFailedException,
		};
	}
}

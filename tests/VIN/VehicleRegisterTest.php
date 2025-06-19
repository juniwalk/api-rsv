<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

use JuniWalk\RSV\VehicleRegister;
use Tester\Assert;
use Tester\TestCase;

require __DIR__.'/../bootstrap.php';

/**
 * @testCase
 */
final class VehicleRegisterTest extends TestCase
{
	private VehicleRegister $rsv;
	private string $vin;

	public function tearDown() {}
	public function setUp() {
		$this->rsv = new VehicleRegister(env('API_KEY'));
		$this->vin = env('VIN');
	}


	public function testExistingVin(): void
	{
		$info = $this->rsv->findByVIN(
			$this->vin
		);

		Assert::notNull($info);
	}


	public function testVinNotFound(): void
	{
		$info = $this->rsv->findByVIN(
			substr($this->vin, -7)
		);

		Assert::null($info);
	}
}

(new VehicleRegisterTest)->run();

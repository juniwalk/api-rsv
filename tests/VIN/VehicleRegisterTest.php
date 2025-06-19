<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

use JuniWalk\RSV\Exceptions\VehicleNotFoundException;
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

	public function tearDown() {}
	public function setUp() {
		$this->rsv = new VehicleRegister(env('API_KEY'));
	}


	public function testExistingVin(): void
	{
		$info = $this->rsv->findByVIN(env('VIN'));
		Assert::notNull($info);
	}


	public function testVinNotFound(): void
	{
		Assert::exception(
			fn() => $this->rsv->findByVIN('does not exist'),
			VehicleNotFoundException::class,
		);
	}
}

(new VehicleRegisterTest)->run();

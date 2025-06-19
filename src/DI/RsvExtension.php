<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

namespace JuniWalk\RSV\DI;

use JuniWalk\RSV\VehicleRegister;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class RsvExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'secret' => Expect::string()->required(),
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = (array) $this->getConfig();

		$builder->addDefinition($this->prefix('register'))
			->setFactory(VehicleRegister::class, $config);
	}
}

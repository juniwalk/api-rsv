<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

use Dotenv\Dotenv as DotENV;
use Tester\Environment;
use Tracy\Debugger;

if (@!include __DIR__.'/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}

Debugger::enable(Debugger::Development);
Environment::setup();

$dotenv = DotENV::createImmutable(__DIR__);
$dotenv->load();

$dotenv->required(['API_KEY', 'VIN'])->notEmpty();

function env(string $key): string
{
	/** @var string */
	return $_ENV[$key] ?? '';
}

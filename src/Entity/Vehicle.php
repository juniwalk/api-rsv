<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

namespace JuniWalk\RSV\Entity;

use DateTime;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;

readonly class Vehicle
{
	public DateTime $DatumPrvniRegistrace;
	public DateTime $DatumPrvniRegistraceVCr;
	public string $TovarniZnacka;
	public string $VIN;
	public ?string $ObchodniOznaceni;
	public string $Palivo;
	public float $MotorZdvihObjem;
	public string $VozidloKaroserieBarva;
	public ?DateTime $PravidelnaTechnickaProhlidkaDo;
	public int $PocetVlastniku;
	public int $PocetProvozovatelu;


	/**
	 * @param array<string, int|float|string|null> $snapshot
	 */
	public function __construct(array $snapshot)
	{
		$class = new ReflectionClass($this);

		foreach ($class->getProperties() as $property) {
			$name = $property->getName();
			$value = $this->cast(
				$snapshot[$name] ?? null,
				$type = $property->getType(),
			);

			$isNullable = $type?->allowsNull() ?? true;

			if (!$isNullable && is_null($value)) {
				continue;
			}

			$property->setValue($this, match ($isNullable) {
				true => $value ?: null,
				default => $value,
			});

			unset($snapshot[$name]);
		}
	}


	private function cast(mixed $value, ?ReflectionType $type): mixed
	{
		if (!$type instanceof ReflectionNamedType) {
			return $value;
		}

		$name = $type->getName();

		if (is_array($value) && $name === 'array') {
			return (array) $value;
		}

		if (!is_scalar($value)) {
			return null;
		}

		return match ($name) {
			DateTime::class => match (true) {
				$value === '0000-00-00 00:00:00' => null,
				$value === '0000-00-00' => null,
				is_string($value) => new DateTime($value),
				default => null,
			},

			'float' => floatval($value),
			'string' => strval($value),
			'bool' => boolval($value),
			'int' => intval($value),
			default => null,
		};
	}
}

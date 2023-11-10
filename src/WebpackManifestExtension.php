<?php

declare(strict_types=1);

namespace Daku\Nette;

use Latte\Extension;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Statement;

class WebpackManifestExtension extends CompilerExtension
{

	public function __construct(private string $manifestFile) {}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$latteFactory = $builder->getDefinitionByType(LatteFactory::class);
		assert($latteFactory instanceof FactoryDefinition);
		$definition = $latteFactory->getResultDefinition();
		$manifest = $builder->parameters['debugMode'] ? $this->manifestFile : self::readManifestFile($this->manifestFile);
		$definition->addSetup('addExtension', [new Statement(self::class . '::createLatteExtension', [$manifest])]);
	}


	public static function createLatteExtension(string|array $manifest): Extension
	{
		$manifest = is_string($manifest) ? self::readManifestFile($manifest) : $manifest;
		return new class($manifest) extends Extension {

			public function __construct(private array $manifest) {}

			public function getFunctions(): array
			{
				return [
					'hasWebpackAsset' => fn(string $name) => isset($this->manifest[$name]),
					'getWebpackAsset' => fn(string $name) => $this->manifest[$name] ?? throw new \InvalidArgumentException("Webpack asset '$name' not found in the manifest."),
				];
			}
		};
	}


	public static function readManifestFile(string $path): array
	{
		return is_file($path) ? json_decode(file_get_contents($path), true) : [];
	}

}

<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\PayPalExpress\DI;

use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;
use Nette\Utils\Validators;



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PayPalExtension extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	public $defaults = array(
		'sandbox' => TRUE,
		'currency' => 'CZK',
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		Validators::assertField($config, 'account');
		Validators::assertField($config, 'username');
		Validators::assertField($config, 'password');
		Validators::assertField($config, 'signature');
		Validators::assertField($config, 'sandbox', 'bool');

		$client = $builder->addDefinition($this->prefix('client'))
			->setClass('Kdyby\PayPalExpress\PayPal')
			->setArguments(array($config))
			->addSetup('setCurrency', array($config['currency']));

		if ($config['sandbox'] === FALSE) {
			$client->addSetup('disableSandbox');
		}
	}



	/**
	 * @param Code\ClassType $class
	 */
	public function afterCompile(Code\ClassType $class)
	{
		$container = $this->getContainerBuilder();
		$init = $class->methods['initialize'];
		/** @var Code\Method $init */

		$init->addBody($container->formatPhp(
			'Nette\Diagnostics\Debugger::$blueScreen->addPanel(?);',
			Nette\DI\Compiler::filterArguments(array(
				'Kdyby\PayPalExpress\Diagnostics\Panel::renderException'
			))
		));
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('paypalExpress', new PayPalExtension());
		};
	}

}

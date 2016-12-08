<?php
/**
 * DecoratorsManager.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:Widgets!
 * @subpackage     Managers
 * @since          1.0.0
 *
 * @date           16.06.15
 */

declare(strict_types = 1);

namespace IPub\Widgets\Managers;

use Nette;

use IPub;
use IPub\Widgets\Decorators;

/**
 * Registered widgets decorators manager
 *
 * @package        iPublikuj:Widgets!
 * @subpackage     Managers
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class DecoratorsManager extends Nette\Object implements \ArrayAccess, \IteratorAggregate
{
	/**
	 * @var Decorators\IFactory[]
	 */
	protected $decorators = [];

	/**
	 * Check if a decorator is registered
	 *
	 * @param string $name
	 *
	 * @return boolean
	 */
	public function has(string $name) : bool
	{
		return isset($this->decorators[$name]);
	}

	/**
	 * Returns a registered decorator class
	 *
	 * @param string $name
	 *
	 * @return Decorators\IFactory|NULL
	 */
	public function get(string $name)
	{
		return $this->has($name) ? $this->decorators[$name] : NULL;
	}

	/**
	 * Register a decorator component factory
	 *
	 * @param Decorators\IFactory $decorator
	 * @param string $name
	 */
	public function register(Decorators\IFactory $decorator, string $name)
	{
		$this->unregister($name);

		$this->decorators[$name] = $decorator;

		krsort($this->decorators);
	}

	/**
	 * Un-registers a decorator
	 *
	 * @param string $name
	 */
	public function unregister(string $name)
	{
		if (array_key_exists($name, $this->decorators)) {
			unset($this->decorators[$name]);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->decorators);
	}

	/**
	 * Implements the \ArrayAccess
	 *
	 * @param  string $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->decorators[$offset]);
	}

	/**
	 * Implements the \ArrayAccess
	 *
	 * @param string $offset
	 *
	 * @return Decorators\IFactory
	 */
	public function offsetGet($offset)
	{
		return $this->decorators[$offset];
	}

	/**
	 * Implements the \ArrayAccess
	 *
	 * @param string $offset
	 * @param Decorators\IFactory|NULL $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->decorators[$offset] = $value;
	}

	/**
	 * Implements the \ArrayAccess
	 *
	 * @param string $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->decorators[$offset]);
	}
}
<?php
/**
 * Control.php
 *
 * @copyright	Vice v copyright.php
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Widgets!
 * @subpackage	Components
 * @since		5.0
 *
 * @date		24.07.13
 */

namespace IPub\Widgets\Components;

use Nette;
use Nette\Application;
use Nette\ComponentModel;
use Nette\Localization;
use Nette\Utils;

use IPub;
use IPub\Widgets\Decorators;
use IPub\Widgets\Entities;
use IPub\Widgets\Exceptions;
use IPub\Widgets\Widgets;

use IPub\Widgets\WidgetsManager;
use IPub\Widgets\DecoratorsManager;

/**
 * @method onAttached(Nette\Application\UI\Control $component)
 */
class Control extends Application\UI\Control
{
	const CLASSNAME = __CLASS__;

	/**
	 * @var array
	 */
	public $onAttached = [];

	/**
	 * @var string
	 */
	protected $templatePath;

	/**
	 * @var WidgetsManager
	 */
	protected $widgetsManager;

	/**
	 * @var DecoratorsManager
	 */
	protected $decoratorsManager;

	/**
	 * @var Localization\ITranslator
	 */
	protected $translator;

	/**
	 * @var string
	 */
	protected $position;

	/**
	 * @var Decorators\IFactory
	 */
	protected $decorator;

	/**
	 * @param Localization\ITranslator $translator
	 */
	public function injectTranslator(Localization\ITranslator $translator = NULL)
	{
		$this->translator = $translator;
	}

	/**
	 * @param string $position
	 * @param WidgetsManager $widgetsManager
	 * @param DecoratorsManager $decoratorsManager
	 * @param ComponentModel\IContainer $parent
	 * @param null $name
	 */
	public function __construct(
		$position,
		WidgetsManager $widgetsManager,
		DecoratorsManager $decoratorsManager,
		ComponentModel\IContainer $parent = NULL, $name = NULL
	) {
		// TODO: remove, only for tests
		parent::__construct(NULL, NULL);

		// Store info about widgets position
		$this->position = $position;

		// Extension managers
		$this->widgetsManager = $widgetsManager;
		$this->decoratorsManager = $decoratorsManager;
	}

	/**
	 * Attach component to presenter
	 *
	 * @param Application\UI\Presenter $presenter
	 */
	protected function attached($presenter)
	{
		parent::attached($presenter);

		if (!$presenter instanceof Application\UI\Presenter) return;

		// Register widgets container
		$this->addComponent(new ComponentModel\Container(), 'widgets');

		// Register default raw widget decorator
		$this->setDecorator($this->decoratorsManager->get('raw'));

		// Call attached event
		$this->onAttached($this);
	}

	/**
	 * Render widgets in selected position
	 */
	public function render()
	{
		// Check if control has template
		if ($this->template instanceof Nette\Bridges\ApplicationLatte\Template) {
			// Assign vars to template
			$this->template->widgets = $this->getWidgets();

			// Check if translator is available
			if ($this->getTranslator() instanceof Localization\ITranslator) {
				$this->template->setTranslator($this->getTranslator());
			}

			// If template was not defined before...
			if ($this->template->getFile() === NULL) {
				// ...try to get base component template file
				$templatePath = !empty($this->templatePath) ? $this->templatePath : __DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR .'default.latte';
				$this->template->setFile($templatePath);
			}

			// Render component template
			$this->template->render();

		} else {
			throw new Exceptions\InvalidStateException('Widgets container control is without template.');
		}
	}

	/**
	 * Set widgets outer decorator
	 *
	 * @param Decorators\IFactory $decorator
	 *
	 * @return $this
	 */
	public function setDecorator(Decorators\IFactory $decorator)
	{
		// Register decorator component
		$this->addComponent($decorator->create(), 'decorator');

		return $this;
	}

	/**
	 * Get all registered widgets in position
	 *
	 * @return array
	 */
	public function getWidgets()
	{
		if ($container = $this->getComponent('widgets')->getComponent($this->position, TRUE) AND $widgets = $container->getComponents()) {
			return $widgets;
		}

		return [];
	}

	/**
	 * Add widget to container
	 *
	 * @param string $name
	 * @param array $data
	 *
	 * @return $this
	 *
	 * @throws Exceptions\WidgetNotRegisteredException
	 */
	public function addWidget($name, array $data = [])
	{
		// Prepare widget settings data
		$data = $this->createData($data);

		if (!$factory = $this->widgetsManager->get($name)) {
			throw new Exceptions\WidgetNotRegisteredException(sprintf('Widget of type %s is not registered.', $name));
		}

		// Check container exist
		$container = $this->getComponent('widgets')->getComponent($this->position, FALSE);
		if (!$container) {
			$this->getComponent('widgets')->addComponent(new Nette\ComponentModel\Container, $this->position);
			$container = $this->getComponent('widgets')->getComponent($this->position);
		}

		// Create component
		$widget = $factory->create($data);

		// Add widget component to container/position
		$container->addComponent($widget, ($widget->getName() . spl_object_hash($data)));

		return $this;
	}

	/**
	 * Change default control template path
	 *
	 * @param string $templatePath
	 *
	 * @return $this
	 *
	 * @throws Exceptions\FileNotFoundException
	 */
	public function setTemplateFile($templatePath)
	{
		// Check if template file exists...
		if (!is_file($templatePath)) {
			// Remove extension
			$template = basename($templatePath, '.latte');

			// ...check if extension template is used
			if (is_file(__DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $template .'.latte')) {
				$templatePath = __DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $template .'.latte';

			} else {
				// ...if not throw exception
				throw new Exceptions\FileNotFoundException(sprintf('Template file "%s" was not found.', $templatePath));
			}
		}

		$this->templatePath = $templatePath;

		return $this;
	}

	/**
	 * @param Localization\ITranslator $translator
	 *
	 * @return $this
	 */
	public function setTranslator(Localization\ITranslator $translator)
	{
		$this->translator = $translator;

		return $this;
	}

	/**
	 * @return Localization\ITranslator|null
	 */
	public function getTranslator()
	{
		if ($this->translator instanceof Localization\ITranslator) {
			return $this->translator;
		}

		return NULL;
	}

	/**
	 * Convert data to object
	 *
	 * @param mixed $data
	 *
	 * @return Entities\Data|null
	 */
	protected function createData($data)
	{
		// Data are in required object
		if ($data instanceof Entities\IData) {
			return $data;

			// or data are in array
		} else if (is_array($data)) {
			// Create new data object
			return (new Entities\Data($data));
		}

		return NULL;
	}

	/**
	 * @param string $name
	 * @param mixed $args
	 *
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		if (Utils\Strings::startsWith($name, 'render')) {
			// Get component name
			if ($decorator = Utils\Strings::capitalize(Utils\Strings::substring($name, 6))) {
				// Set template name
				//$this->setDecorator($this->decoratorsManager->get($decorator));
			}

			// Call component rendering
			$this->render();

		} else {
			return parent::__call($name, $args);
		}
	}
}
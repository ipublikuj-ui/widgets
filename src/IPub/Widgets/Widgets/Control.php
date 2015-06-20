<?php
/**
 * Widget.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Widgets!
 * @subpackage	Widgets
 * @since		5.0
 *
 * @date		15.09.14
 */

namespace IPub\Widgets\Widgets;

use Nette;
use Nette\Application;
use Nette\ComponentModel;
use Nette\Localization;
use Nette\Utils;

use IPub;
use IPub\Widgets;
use IPub\Widgets\Decorators;
use IPub\Widgets\Entities;
use IPub\Widgets\Exceptions;

abstract class Control extends Application\UI\Control implements IControl
{
	const CLASSNAME = __CLASS__;

	/**
	 * @var string
	 */
	protected $templatePath;

	/**
	 * @var Entities\IData
	 */
	protected $data;

	/**
	 * @var Localization\ITranslator
	 */
	protected $translator;

	/**
	 * @param Localization\ITranslator $translator
	 */
	public function injectTranslator(Localization\ITranslator $translator = NULL)
	{
		$this->translator = $translator;
	}

	/**
	 * @param Entities\IData $data
	 * @param ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(
		Entities\IData $data,
		ComponentModel\IContainer $parent = NULL, $name = NULL
	) {
		// TODO: remove, only for tests
		parent::__construct(NULL, NULL);

		$this->data = $data;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setData(Entities\IData $data)
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		// Widget data must be loaded
		if (!$this->data instanceof Entities\IData) {
			throw new \LogicException('Missing call ' . get_called_class() . '::setData($entity)');
		}

		return $this->data->getName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDescription()
	{
		// Widget data must be loaded
		if (!$this->data instanceof Entities\IData) {
			throw new \LogicException('Missing call ' . get_called_class() . '::setData($entity)');
		}

		return $this->data->getDescription();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority()
	{
		// Widget data must be loaded
		if (!$this->data instanceof Entities\IData) {
			throw new \LogicException('Missing call ' . get_called_class()  . '::setData($entity)');
		}

		return $this->data->getPriority();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPosition()
	{
		// Widget data must be loaded
		if (!$this->data instanceof Entities\IData) {
			throw new \LogicException('Missing call ' . get_called_class()  . '::setData($entity)');
		}

		return $this->data->getPosition();
	}

	/**
	 * Render widget
	 */
	public function render()
	{
		// Check if control has template
		if ($this->template instanceof Nette\Bridges\ApplicationLatte\Template) {
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

			// Process actions before render
			$this->beforeRender();

			// Render component template
			$this->template->render();

		} else {
			throw new Exceptions\InvalidStateException('Widgets container control is without template.');
		}
	}

	/**
	 * Before render actions
	 */
	protected function beforeRender()
	{
		// Get widget title
		$name = $this->data->getName();

		// If widget name has space...
		$pos = mb_strpos($this->data->getName(), ' ');
		if ($pos !== FALSE && mb_strpos($this->data->getName(), '||') === FALSE) {
			$name = Utils\Html::el('span')
				->addAttributes(['class' => 'color'])
				->setText(mb_substr($this->data->getName(), 0, $pos))
				->render();

			// Modify widget name
			$name = $name . mb_substr($this->data->getName(), $pos);
		}

		// If widget name has subtitle...
		$pos = mb_strpos($this->data->getName(), '||');
		if ($pos !== FALSE) {
			$title = Utils\Html::el('span')
				->addAttributes(['class' => 'title'])
				->setText(mb_substr($this->data->getName(), 0, $pos))
				->render();

			$subtitle = Utils\Html::el('span')
				->addAttributes(['class' => 'subtitle'])
				->setText(mb_substr($this->data->getName(), $pos + 2))
				->render();

			// Split name to title & subtitle
			$name = $title . $subtitle;
		}

		// Set badge if exists
		if ($badge = $this->data->getBadge()) {
			$badge = Utils\Html::el('span')
				->addAttributes(['class' => 'badge badge-'. $badge])
				->render();
		}

		// Set icon if exists
		if ($icon = $this->data->getIcon()) {
			$icon = Utils\Html::el('span')
				->addAttributes(['class' => 'icon icon-'. $icon])
				->render();
		}

		// Assign basic widget data to template
		$this->template->badge	= $badge;
		$this->template->icon	= $icon;
		$this->template->title	= [
			'text'		=> $name,
			'insert'	=> $this->data->getParam('widget.title.insert', TRUE),
			'hidden'	=> $this->data->getParam('widget.title.hidden', FALSE)
		];
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
	 * Convert widget name to string representation
	 *
	 * @return string
	 */
	public function __toString()
	{
		$class = explode('\\', get_class($this));

		return end($class);
	}
}
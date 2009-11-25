<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template;

use \RuntimeException;
use \lithium\core\Libraries;
use \lithium\g11n\Message;

class View extends \lithium\core\Object {

	public $outputFilters = array();

	/**
	 * Holds the details of the current request that originated the call to this view, if
	 * applicable.  May be empty if this does not apply.  For example, if the View class is
	 * created to render an email.
	 *
	 * @var object Request object instance.
	 * @see lithium\action\Request
	 */
	protected $_request = null;

	protected $_loader = null;

	protected $_renderer = null;

	protected $_autoConfig = array('outputFilters' => 'merge', 'request');

	public function __construct($config = array()) {
		$defaults = array(
			'request' => null,
			'vars' => array(),
			'loader' => 'File',
			'renderer' => 'File',
			'outputFilters' => array()
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		foreach (array('loader', 'renderer') as $key) {
			if (is_object($this->_config[$key])) {
				$this->{'_' . $key} = $this->_config[$key];
				continue;
			}

			if (!$class = Libraries::locate('adapters.template.view', $this->_config[$key])) {
				throw new RuntimeException("Template adapter {$this->_config[$key]} not found");
			}
			$this->{'_' . $key} = new $class($this->_config);
		}

		$h = function($data) use (&$h) {
			return is_array($data) ? array_map($h, $data) : htmlspecialchars((string)$data);
		};
		$t = function($message, $options = array()) {
			return Message::translate($message, $options + array(
				'default' => $message
			));
		};
		$tn = function($message1, $message2, $count, $options = array()) {
			return Message::translate($message1, $options + compact('count') + array(
				'default' => $count == 1 ? $message1 : $message2
			));
		};
		$this->outputFilters += compact('h', 't', 'tn');
	}

	public function render($type, $data = array(), $options = array()) {
		$defaults = array('context' => array());
		$options += $defaults;

		switch ($type) {
			case 'all':
				$content = $this->render('template', $data, $options);

				if (!$options['layout']) {
					return $content;
				}
				$options['context'] += compact('content');
				return $this->render('layout', $data, $options);
			case 'template':
			case 'layout':
				$template = $this->_loader->template($type, $options);
				$data = (array)$data + $this->outputFilters;
				return $this->_renderer->render($template, $data, $options);
		}
	}
}

?>
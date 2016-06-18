<?php

namespace Faces\Admin\Widget;

use ArrayIterator;
// Dependencies from `pimple`
use \Pimple\Container;

use \Charcoal\Admin\Widget\SidemenuWidget;

// Model Aware
use Faces\Support\Traits\ModelAwareTrait;
use Faces\Support\Interfaces\ModelAwareInterface;

// Model Aware
use Faces\Support\Traits\ConfigAwareTrait;
use Faces\Support\Interfaces\ConfigAwareInterface;

class BaseWidget extends SidemenuWidget implements
	ModelAwareInterface,
	ConfigAwareInterface
{
	use ModelAwareTrait;
	use ConfigAwareTrait;

	protected $context;
	protected $dashboardMeta;
	protected $objMeta;
	protected $adminMeta;
	protected $title;

	/**
	 * Set the modelFactory and Appconfig necessary
	 * for the widget.
	 * @param Container $container global container
	 */
	public function setDependencies(Container $container)
	{
		$this->setModelFactory($container['model/factory']);
		$this->setAppConfig($container['config']);
	}

	public function hasLinks()
	{
		return true;
	}

	/**
	 * Proto meta root
	 * @return []
	 */
	public function objMeta()
	{
		if ($this->objMeta) {
			return $this->objMeta;
		}

		$obj_type = $this->objType();

		if (!$obj_type) {
			return [];
		}
		$proto = $this->proto($obj_type);
		return $proto->metadata();
	}

	/**
	 * Admin meta related to current obj
	 * @return []
	 */
	public function adminMeta()
	{
		if ($this->adminMeta) {
			return $this->adminMeta;
		}

		$meta = $this->objMeta();

		$this->adminMeta = isset($meta['admin']) ? $meta['admin'] : [];

		return $this->adminMeta;
	}

	/**
	 * Dashboard meta related to current object AND current dashboard (edit || table)
	 * @return []
	 */
	public function dashboardMeta()
	{
		if ($this->dashboardMeta) {
			return $this->dashboardMeta;
		}

		$meta = $this->adminMeta();

		if (!isset($meta['dashboards'])) {
			return [];
		}
		$dashboards = $meta['dashboards'];

		if (!isset($dashboards[$this->context()])) {
			return [];
		}
		$this->dashboardMeta = $dashboards[$this->context()];

		return $this->dashboardMeta;
	}

	public function adminSidemenu()
	{
		return $this->appConfig()->get('admin.sidemenu');
	}

	public function title()
	{
		if ($this->title) {
			return $this->title;
		}

		$opts = $this->dashboardMeta();
		$sidemenu = isset($opts['sidemenu']) ? $opts['sidemenu'] : [];
		$adminSidemenu = $this->adminSidemenu();

		if (isset($sidemenu['ident']) && isset($adminSidemenu[$sidemenu['ident']])) {
			$title = isset($adminSidemenu[$sidemenu['ident']]['title']) ? $adminSidemenu[$sidemenu['ident']]['title'] : '';
			return $this->title;
		}

		if (isset($sidemenu['title'])) {
			$this->title = $sidemenu['title'];
		}
		return $this->title;
	}

	/**
	 * Links to be displayed in the menu
	 * @return []
	 */
	public function links()
	{
		$opts = $this->dashboardMeta();
		$obj_type = $this->objType();

		$sidemenu = [];
		if (isset($opts['sidemenu'])) {
			$sidemenu = $opts['sidemenu'];
		}

		if (isset($sidemenu['ident'])) {
			$adminSidemenu = $this->adminSidemenu();
			$links = [];
			if (isset($adminSidemenu[$sidemenu['ident']])) {
				$links = $adminSidemenu[$sidemenu['ident']]['links'];
			}

			foreach ($links as $ident => $obj) {
				$links[$ident]['name'] = $obj['name'];
				if ($ident == $obj_type) {
					$links[$ident]['selected'] = true;
				}
			}
			return new ArrayIterator($links);
		}
	}

	public function hasActions()
	{
		return false;
	}

	public function actions()
	{
		return [];
	}

	public function objType()
	{
		if (isset($_GET['obj_type'])) {
			return $_GET['obj_type'];
		}
		return false;
	}



	public function setContext($context)
	{
		$this->context = $contect;
		return $this;
	}

	public function context()
	{
		return $this->context;
	}

}

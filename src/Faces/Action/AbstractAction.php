<?php

namespace Faces\Action;

use Pimple\Container;
use \Charcoal\Factory\FactoryInterface;
// Dependency from `charcoal-app`
use \Charcoal\App\Action\AbstractAction as AbstractCharcoalAction;

// Model Aware
use Faces\Support\Traits\ModelAwareTrait;
use Faces\Support\Interfaces\ModelAwareInterface;

use Faces\Support\Traits\ConfigAwareTrait;
use Faces\Support\Interfaces\ConfigAwareInterface;

abstract class AbstractAction extends AbstractCharcoalAction implements
	ModelAwareInterface,
    ConfigAwareInterface
{
	use ModelAwareTrait;
    use ConfigAwareTrait;

	public function setDependencies(Container $dependencies)
	{
		// ModelAwareTrait
		$this->setModelFactory($dependencies['model/factory']);
        // ConfigAwareTrait
        $this->setAppConfig($dependencies['config']);
	}

    /**
     * AppConfig uses the "URL" property in config
     *
     * @see AppConfig::baseUrl
     * @return string Base URL
     */
    public function baseUrl()
    {
        return $this->appConfig()->baseUrl();
    }
}

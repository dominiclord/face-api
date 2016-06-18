<?php
namespace Faces\Support\Traits;

use Charcoal\App\AppConfig;

/**
 * Use in objects that needs the appConfig to work.
 * Appconfig is the global config of the project.
 *
 */
trait ConfigAwareTrait
{
	/**
	 * AppConfig
	 * @var Charcoal\App\AppConfig $appConfig
	 */
	protected $appConfig;

	public function setAppConfig(AppConfig $config)
	{
		$this->appConfig = $config;
		return $this;
	}

	public function appConfig()
	{
		return $this->appConfig;
	}

}

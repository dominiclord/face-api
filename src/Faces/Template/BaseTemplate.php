<?php

namespace Faces\Template;

// Pimple dependencies
use \Pimple\Container;

// RequestInterface
use Psr\Http\Message\RequestInterface;

// From `charcoal-app`
use \Charcoal\App\Template\AbstractTemplate;

class BaseTemplate extends AbstractTemplate {

	/**
	 * Init with request parameters
	 * @param array $request GET or POST parameters.
	 * @return AbstractFacesTemplate $this.
	 */
	public function init(RequestInterface $request)
	{
		return $this;
	}
}

<?php

namespace Faces\Object;

use \Pimple\Container;

// Dependencies from `charcoal-core`
use \Charcoal\Model\AbstractModel;

// Model Aware
use Faces\Support\Traits\ModelAwareTrait;
use Faces\Support\Interfaces\ModelAwareInterface;

class Tag extends AbstractModel implements
    ModelAwareInterface
{
    use ModelAwareTrait;

    protected $name;

    /**
     * @param Container  $container  The dependencies.
     */
    public function setDependencies(Container $container)
    {
        $this->setModelFactory($container['model/factory']);
    }

    /** Getters */
    public function name() { return $this->name; }

    /** Setters */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}

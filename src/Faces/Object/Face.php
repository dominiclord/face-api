<?php

namespace Faces\Object;

use \Pimple\Container;

// Dependencies from `charcoal-core`
use \Charcoal\Model\AbstractModel;

// Model Aware
use Faces\Support\Traits\ModelAwareTrait;
use Faces\Support\Interfaces\ModelAwareInterface;

class Face extends AbstractModel implements
    ModelAwareInterface
{
    use ModelAwareTrait;

    protected $active = true;
    protected $image;
    protected $filename;
    protected $tags;

    /**
     * @param Container  $container  The dependencies.
     */
    public function setDependencies(Container $container)
    {
        $this->setModelFactory($container['model/factory']);
    }

    /**
     * @return Boolean
     */
    public function postSave()
    {
        $this->setFilename(basename($this->image()));
        $this->update(['filename']);
        return parent::postSave();
    }

    /** Getters */
    public function active() { return $this->active; }
    public function image() { return $this->image; }
    public function filename() { return $this->filename; }
    public function tags() { return $this->tags; }

    /** Setters */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }
    public function setImage($image)
    {
        $image = preg_replace('~www/~', '', $image, 1);
        $this->image = $image;
        return $this;
    }
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }
    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }
}

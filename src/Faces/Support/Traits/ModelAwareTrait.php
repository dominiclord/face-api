<?php
namespace Faces\Support\Traits;

use \Exception;

use Pimple\Container;

use \Charcoal\Loader\CollectionLoader;
use \Charcoal\Factory\FactoryInterface;

trait ModelAwareTrait
{
	/**
	 * ModelFactory
	 *
	 * @var FactoryInterface
	 */
	protected $modelFactory;

	/**
	 * Protos to be kept in an associative array
	 * @var array $proto
	 */
	protected $proto = [];

	/**
	 * Collection loader
	 *
	 * @var collectionloader
	 */
	protected $loader;

	/**
     * @return Self
     */
    public function setModelFactory(FactoryInterface $factory)
    {
        $this->modelFactory = $factory;
        return $this;
    }

    /**
     * @return FactoryInterface
     */
    public function modelFactory()
    {
    	if (!$this->modelFactory) {
    		throw new Exception(
    		 	'ModelFactory wasn\'t set in '. get_called_class() .'. Probably missing a setDependencies()'
            );
    	}
        return $this->modelFactory;
    }

    /**
     * Returns a model prototype
     * Not to be used when calling multiple object
     * instances.
     *
     * @param  [type] $objType [description]
     * @return [type]          [description]
     */
    public function proto($objType)
    {
    	if (isset($this->proto[$objType])) {
    		return $this->proto[$objType];
    	}
    	$this->proto[$objType] = $this->obj($objType);

        return $this->proto[$objType];
    }

    /**
     * Return new instance of objType no matter what
     *
     * @param  string $objType
     * @return {$objType}
     */
    public function obj($objType)
    {
    	$factory = $this->modelFactory();
    	$obj = $factory->create($objType);
    	return $obj;
    }

    /**
     * @param string $objType
     * @return CollectionLoader
     */
    public function collection($objType)
    {
        $obj = $this->obj($objType);
        $loader = new CollectionLoader([
            'logger'=>$this->logger,
            'factory' => $this->modelFactory()
        ]);
        $loader->setModel($obj);
        return $loader;
    }

}

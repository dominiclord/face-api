<?php
namespace Faces\Support\Interfaces;

use \Charcoal\Factory\FactoryInterface;

/**
 *
 */
interface ModelAwareInterface
{
    /**
     * ModelFactory getter and setter
     *
     * @param  FactoryInterface $factory ModelFactory.
     * @return FactoryInterface
     */
    // public function setModelFactory(FactoryInterface $factory);
    // public function modelFactory();

    /**
     * Helper functions
     */
    public function proto($objType);
    public function obj($objType);
    public function collection($objType);
}

<?php

namespace Awaresoft\Sonata\AdminBundle\Reference;

/**
 * Class ReferenceObject
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class ReferenceObject
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $object;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @var string
     */
    protected $routeParams;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ReferenceObject
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param mixed $object
     *
     * @return ReferenceObject
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     *
     * @return ReferenceObject
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * @param string $routeParams
     *
     * @return ReferenceObject
     */
    public function setRouteParams($routeParams)
    {
        $this->routeParams = $routeParams;

        return $this;
    }
}
<?php

namespace Awaresoft\Sonata\AdminBundle\Reference\Type;

use Awaresoft\Sonata\AdminBundle\Reference\ReferenceObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityObjectType
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
abstract class AbstractType implements TypeInterface
{
    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var mixed
     */
    protected $adminObject;

    /**
     * @var ReferenceObject[]
     */
    protected $referenceObjects = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * AbstractType constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getMessages()
    {
        return $this->prepareMessages();
    }

    /**
     * @inheritdoc
     */
    public function hasReferences()
    {
        if (count($this->referenceObjects) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Prepare message to show
     *
     * @return null|string
     */
    protected function prepareMessages()
    {
        foreach ($this->referenceObjects as $referenceObject) {
            $this->messages[] =
                '<a href="' .
                $this->container->get('router')->generate(
                    $referenceObject->getRouteName(),
                    $referenceObject->getRouteParams()
                ) . '">' .
                $referenceObject->getName() .
                ' </a>';
        }

        return $this->messages;
    }

    /**
     * @param string $className
     *
     * @return mixed
     */
    protected function prepareObjectNameFromClass($className)
    {
        $classElements = explode('\\', $className);

        return end($classElements);
    }
}
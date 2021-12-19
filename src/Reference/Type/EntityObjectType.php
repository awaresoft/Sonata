<?php

namespace Awaresoft\Sonata\AdminBundle\Reference\Type;

use Awaresoft\Sonata\AdminBundle\Reference\ReferenceObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityObjectType
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class EntityObjectType extends AbstractType
{
    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $route;

    /**
     * EntityObjectType constructor.
     *
     * @param ContainerInterface $container
     * @param mixed $adminObject
     * @param string $entity
     * @param string $column
     * @param string $route
     */
    public function __construct(ContainerInterface $container, $adminObject, $entity, $column, $route)
    {
        parent::__construct($container);

        $this->adminObject = $adminObject;
        $this->entity = $entity;
        $this->column = $column;
        $this->route = $route;
    }

    /**
     * @inheritdoc
     */
    public function createReferenceObjects()
    {
        $entityObjects = $this->findEntityReference($this->adminObject, $this->entity, $this->column);

        $referenceObjects = [];
        foreach ($entityObjects as $i => $entityObject) {
            $referenceObjects[$i] = new ReferenceObject();
            $referenceObjects[$i]->setName($this->prepareObjectNameFromClass(get_class($entityObject)));
            $referenceObjects[$i]->setObject($entityObject);
            $referenceObjects[$i]->setRouteName($this->route);
            $referenceObjects[$i]->setRouteParams([
                'id' => $entityObject->getId(),
            ]);
        }

        $this->referenceObjects = $referenceObjects;
    }

    /**
     * @param $object
     * @param $entity
     * @param $column
     *
     * @return array
     */
    protected function findEntityReference($object, $entity, $column)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        return $em->getRepository($entity)->findBy([
            $column => $object->getId(),
        ]);
    }

    /**
     * Prepare message to show
     *
     * @return null|string
     */
    protected function prepareMessages()
    {
        $trans = $this->container->get('translator');
        $count = count($this->referenceObjects);
        if ($count > 5) {
            $routeParams = explode('_', $this->route);
            $methodName = 'get' . ucfirst($this->column);
            $url = $this->container->get('router')->generate(str_replace('_' . end($routeParams), '_list', $this->route), [
                'filter[' . $this->column . '][value]' => $this->referenceObjects[0]->getObject()->$methodName()->getId(),
            ]);
            $this->messages[] = $trans->trans('admin.admin.delete.error.more_then_x_relations', [
                '%url%' => '<a href="' . $url . '">' . $trans->trans('admin.admin.delete.error.more_then_x_relations_url') . '</a>',
            ]);
        } else {
            parent::prepareMessages();
        }

        return $this->messages;
    }
}
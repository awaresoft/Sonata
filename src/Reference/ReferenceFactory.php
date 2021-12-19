<?php

namespace Awaresoft\Sonata\AdminBundle\Reference;

use Awaresoft\Sonata\AdminBundle\Reference\Type\TypeInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ReferenceManager
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class ReferenceFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var TypeInterface[]
     */
    protected $referenceTypes = [];

    /**
     * @var mixed
     */
    protected $adminObject;

    /**
     * @var AdminInterface
     */
    protected $admin;

    /**
     * ReferenceService constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param TypeInterface[] $types
     * @param AdminInterface $admin
     * @param $adminObject
     */
    public function create(array $types, AdminInterface $admin, $adminObject)
    {
        $this->referenceTypes = $types;
        $this->admin = $admin;
        $this->adminObject = $adminObject;

        foreach ($this->referenceTypes as $referenceType) {
            $referenceType->createReferenceObjects();
        }
    }

    /**
     * @return bool
     */
    public function hasReferences()
    {
        foreach ($this->referenceTypes as $referenceType) {
            if ($referenceType->hasReferences()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare alert to show
     *
     * @return string
     */
    public function showAlertMessage()
    {
        $objectUrl = sprintf('<a href="%s">%s</a>', $this->admin->generateObjectUrl('edit', $this->adminObject), $this->adminObject);
        $message[] = '<h5>' . $this->admin->trans('admin.admin.reference.title', ['%url%' => $objectUrl], 'AwaresoftSonataAdminBundle') . ':</h5>';
        $message[] = '<ul>';

        foreach ($this->referenceTypes as $referenceType) {
            foreach ($referenceType->getMessages() as $typeMessage) {
                $message[] = '<li>' . $typeMessage . '</li>';
            }
        }

        $message[] = '</ul>';

        return implode('', $message);
    }
}
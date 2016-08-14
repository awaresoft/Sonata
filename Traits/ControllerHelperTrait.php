<?php

namespace Awaresoft\Sonata\AdminBundle\Traits;

use Sonata\AdminBundle\Admin\AdminInterface;

/**
 * Trait ControllerHelperTrait
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
trait ControllerHelperTrait
{
    /**
     * Check if object has deletable flag and can be delete
     *
     * @param $object
     * @param AdminInterface $admin
     *
     * @return null|string
     */
    public function checkObjectIsBlocked($object, AdminInterface $admin)
    {
        if (!$object->isDeletable()) {
            return $admin->trans('admin.admin.cannot_delete_item_is_blocked');
        }

        return null;
    }

    /**
     * Check if object has relations with other system elements and can be delete
     *
     * @param mixed $object
     * @param $admin
     * @param array $relationTypes
     *
     * @return null|string
     */
    protected function checkObjectHasRelations($object, AdminInterface $admin, array $relationTypes)
    {
        $referenceFactory = $this->get('awaresoft.admin.reference.factory');
        $referenceFactory->create($relationTypes, $admin, $object);
        if ($referenceFactory->hasReferences()) {
            return $referenceFactory->showAlertMessage();
        }

        return null;
    }
}
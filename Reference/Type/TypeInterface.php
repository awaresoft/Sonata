<?php

namespace Awaresoft\Sonata\AdminBundle\Reference\Type;

use Awaresoft\Sonata\AdminBundle\Reference\ReferenceObject;

/**
 * Interface TypeInterface
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
interface TypeInterface
{
    /**
     * Create ReferenceObject for reference type
     *
     * @return ReferenceObject
     */
    public function createReferenceObjects();

    /**
     * Return simple message for type
     *
     * @return mixed
     */
    public function getMessages();

    /**
     * Check if deleting item has references to other objects
     *
     * @return bool
     */
    public function hasReferences();
}
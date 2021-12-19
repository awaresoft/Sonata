<?php

namespace Awaresoft\Sonata\AdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * AwaresoftSonataAdminBundle class
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class AwaresoftSonataAdminBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataAdminBundle';
    }
}
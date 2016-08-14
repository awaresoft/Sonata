<?php

namespace Awaresoft\Sonata\AdminBundle\Form\Extension\Field\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Sonata\AdminBundle\Form\Extension\Field\Type\FormTypeFieldExtension as BaseFormTypeFieldExtension;
use Gedmo\Translatable\TranslatableListener;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Class FormTypeFieldExtension
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class FormTypeFieldExtension extends BaseFormTypeFieldExtension
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var TranslatableListener
     */
    private $listener;

    /**
     * @param array $defaultClasses
     * @param array $options
     * @param ObjectManager $om
     * @param TranslatableListener $listener
     */
    public function __construct(array $defaultClasses = [], array $options, ObjectManager $om, TranslatableListener $listener)
    {
        parent::__construct($defaultClasses, $options);

        $this->om = $om;
        $this->listener = $listener;
    }

    /**
     * Check if field is translatable
     *
     * @param mixed $object
     * @param string $name
     *
     * @return bool
     */
    private function isTranslatableField($object, $name)
    {
        if ($object instanceof ArrayCollection) {
            return false;
        }

        if ($object instanceof PersistentCollection) {
            return false;
        }

        if ($object instanceof \DateTime) {
            return false;
        }

        $config = $this->listener->getConfiguration($this->om, get_class($object));

        if (isset($config['fields']) && in_array($name, $config['fields'])) {
            return true;
        }

        return false;
    }

    /**
     * Add form variable which helps recognize if field is translatable
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        if (!$form->getParent()) {
            return;
        }

        $view->vars['translatable'] = false;
        if (is_object($form->getParent()->getData())) {
            if ($this->isTranslatableField($form->getParent()->getData(), $form->getName())) {
                $view->vars['translatable'] = true;
            }
        }
    }
}

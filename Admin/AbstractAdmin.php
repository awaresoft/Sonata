<?php

namespace Awaresoft\Sonata\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin as BaseAbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\SiteManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\PersistentCollection;

abstract class AbstractAdmin extends BaseAbstractAdmin
{
    /**
     * Seo parameter - title max length
     */
    const SEO_TITLE_MAX_LENGTH = 100;

    /**
     * Seo parameter - description max length
     */
    const SEO_DESCRIPTION_MAX_LENGTH = 200;

    /**
     * Settings determine if site is multisite and provide multisite functionality for current Admin
     *
     * @var bool
     */
    protected $multisite = false;

    /**
     * @var SiteManagerInterface
     */
    protected $siteManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param SiteManagerInterface $siteManager
     */
    public function setSiteManager(SiteManagerInterface $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        // prevent before inactive scope
        if ($request = $this->container->get('request_stack')->getCurrentRequest()) {
            $this->setRequest($request);
        }
    }

    /**
     * @return bool
     */
    public function getMultisite()
    {
        return $this->multisite;
    }

    /**
     * @inheritdoc
     */
    public function getNewInstance()
    {
        $instance = parent::getNewInstance();

        if ($this->multisite && $this->multisite = true) {
            if (!$this->hasRequest()) {
                return $instance;
            }

            if ($site = $this->prepareMultisite()) {
                $instance->setSite($site);
            }
        }

        return $instance;
    }

    /**
     * Return associate array with value in keys
     *
     * @param array $array
     *
     * @return array
     */
    protected function prepareArrayKeyFromValue(array $array)
    {
        $output = [];

        foreach ($array as $key => $value) {
            $output[$value] = $value;
        }

        return $output;
    }

    /**
     * Delete data for persistent collections
     */
    protected function updateCollection()
    {
        $form = $this->getForm();
        $children = $form->getIterator();

        foreach ($children as $childForm) {
            $data = $childForm->getData();

            if ($data instanceof PersistentCollection) {
                $proxies = $childForm->getIterator();

                foreach ($proxies as $proxy) {
                    $entity = $proxy->getData();

                    if (!$data->contains($entity)) {
                        $this->getModelManager()->delete($entity);
                    }
                }
            }
        }
    }

    /**
     * Get site in create/edit view
     *
     * @return SiteInterface|false
     */
    protected function prepareMultisite()
    {
        if (!$this->hasRequest()) {
            return false;
        }

        $siteId = null;

        if ($this->getRequest()->getMethod() == 'POST') {
            $values = $this->getRequest()->get($this->getUniqid());
            $siteId = isset($values['site']) ? $values['site'] : null;
        }

        $siteId = (null !== $siteId) ? $siteId : $this->getRequest()->get('siteId');

        if ($siteId) {
            $site = $this->getConfigurationPool()
                ->getContainer()
                ->get('sonata.page.manager.site')
                ->findOneBy(['id' => $siteId]);

            if (!$site) {
                throw new \RuntimeException('Unable to find the site with id=' . $this->getRequest()->get('siteId'));
            }

            return $site;
        }

        return false;
    }

    /**
     * Prepare datagrid filter for multisite
     *
     * @param DatagridMapper $datagridMapper
     *
     * @return DatagridMapper
     */
    protected function prepareFilterMultisite(DatagridMapper $datagridMapper)
    {
        $data = null;
        $siteManager = $this->getConfigurationPool()->getContainer()->get('sonata.page.manager.site');
        $sites = $siteManager->findBy(['enabled' => true]);
        $selectedId = null;

        foreach ($sites as $site) {
            $choices[$site->getId()] = $site->getName();
        }

        if (!isset($this->request->get('filter')['site'])) {
            if ($site = $siteManager->findOneBy(['isDefault' => true, 'enabled' => true])) {
                $selectedId = $choices[$site->getId()];
            }
        } else {
            $selectedId = $this->request->get('filter')['site']['value'];
        }

        $datagridMapper->add('site', 'doctrine_orm_choice', [], 'choice', [
            'choices' => $choices,
            'empty_data' => $this->trans('all', [], 'AwaresoftSonataAdminBundle'),
            'choice_attr' => [
                $selectedId => [
                    'selected' => true,
                ],
            ],
        ]);

        return $datagridMapper;
    }

    /**
     * Prepare old object for prePersist/Update functions
     *
     * @param $object
     * @param $oldObject
     *
     * @return mixed
     */
    protected function prepareOldObjectDataFromObject($object, $oldObject)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $original = $entityManager->getUnitOfWork()->getOriginalEntityData($object);

        foreach ($original as $key => $value) {
            $methodName = 'set' . ucfirst($key);

            if (method_exists($oldObject, $methodName)) {
                $oldObject->$methodName($value);
            }
        }

        return $oldObject;
    }
}

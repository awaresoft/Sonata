<?php

namespace Awaresoft\Sonata\AdminBundle\Controller;

use Awaresoft\Sonata\AdminBundle\Admin\AbstractAdmin as AwaresoftAbstractAdmin;
use Awaresoft\TreeBundle\Admin\AbstractTreeAdmin as AwaresoftAbstractTreeAdmin;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Controller\CRUDController as BaseCRUDController;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class CRUDController
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class CRUDController extends BaseCRUDController
{
    /**
     * The template registry of the related Admin class.
     *
     * @var TemplateRegistryInterface
     */
    private $templateRegistry;
    
    public function configure()
    {
        parent::configureAdmin($this->getRequest());
    
        $this->templateRegistry = $this->container->get($this->admin->getCode().'.template_registry');
        if (!$this->templateRegistry instanceof TemplateRegistryInterface) {
            throw new \RuntimeException(sprintf(
                'Unable to find the template registry related to the current admin (%s)',
                $this->admin->getCode()
            ));
        }
    }
    
    /**
     * @inheritdoc
     */
    public function listAction()
    {
        $request = $this->getRequest();

        if ($this->admin instanceof AwaresoftAbstractTreeAdmin && !$request->get('filter') && !$request->get('filters')) {
            return new RedirectResponse($this->admin->generateUrl('tree', $request->query->all()));
        }

        if ($this->admin instanceof AwaresoftAbstractAdmin && $this->admin->getMultisite()) {
            return $this->listActionMultisite();
        }

        return parent::listAction();
    }

    /**
     * Dedicated treeAction for Admins which extends AbstractTreeAdmin
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function treeAction(Request $request)
    {
//        $this->admin->checkAccess('tree');
        $sites = $this->get('sonata.page.manager.site')->findBy([]);

        $currentSite = null;
        $siteId = $request->get('site');
        foreach ($sites as $site) {
            if ($siteId && $site->getId() == $siteId) {
                $currentSite = $site;
            } elseif (!$siteId && $site->getIsDefault()) {
                $currentSite = $site;
            }
        }
        if (!$currentSite && count($sites) == 1) {
            $currentSite = $sites[0];
        }

        $elements = $this->findTreeElements($currentSite);
        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();
        $treeTemplate = $this->templateRegistry->getTemplate('tree') ?: 'AwaresoftTreeBundle:CRUD:base_tree.html.twig';
    
        $this->setFormTheme($formView, $this->admin->getFilterTheme());

        return $this->renderWithExtraParams($treeTemplate, [
            'action' => 'tree',
            'multisite' => $this->admin->getMultisite(),
            'sites' => $sites,
            'currentSite' => $currentSite,
            'elements' => $elements,
            'form' => $formView,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function createAction()
    {
        if ($this->admin instanceof AwaresoftAbstractAdmin && $this->admin->getMultisite()) {
            return $this->createActionMultisite();
        }

        return parent::createAction();
    }

    /**
     * @inheritdoc
     */
    public function deleteAction($id)
    {
        $request = $this->getRequest();
        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if (false === $this->admin->isGranted('DELETE', $object)) {
            throw new AccessDeniedException();
        }

        if (method_exists($this, 'preDeleteAction')) {
            $message = call_user_func([$this, 'preDeleteAction'], $object);

            if ($message) {
                $this->addFlash('sonata_flash_info', $this->admin->trans($message));

                return new RedirectResponse($this->admin->generateObjectUrl('edit', $object));
            }
        }

        return parent::deleteAction($id);
    }

    /**
     * Create action for multisite modules
     *
     * @return mixed
     *
     * @throws AccessDeniedException
     */
    public function createActionMultisite()
    {
        if (false === $this->admin->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }

        if ($this->getRequest()->getMethod() == 'GET' && !$this->getRequest()->get('siteId')) {
            $sites = $this->get('sonata.page.manager.site')->findBy([]);

            if (count($sites) == 1) {
                return $this->redirect($this->admin->generateUrl('create', [
                    'siteId' => $sites[0]->getId(),
                    'uniqid' => $this->admin->getUniqid(),
                ]));
            }

            try {
                $current = $this->get('sonata.page.site.selector')->retrieve();
            } catch (\RuntimeException $e) {
                $current = false;
            }

            return $this->renderWithExtraParams('AwaresoftSonataAdminBundle:CRUD:select_site.html.twig', [
                'sites' => $sites,
                'current' => $current,
            ]);
        }

        return parent::createAction();
    }

    /**
     * List action for modules with multisite
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function listActionMultisite()
    {
        $siteManager = $this->get('sonata.page.manager.site');

        if (!$this->getRequest()->get('filter')) {
            return new RedirectResponse($this->admin->generateUrl('list', [
                'filter[site][value]' => $siteManager->findOneBy(['isDefault' => true])->getId(),
            ]));
        }

        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $sites = $siteManager->findBy(['enabled' => true]);

        $currentSite = null;
        $siteId = null;
        $filters = $this->getRequest()->get('filter');

        if (isset($filters['site']['value'])) {
            $siteId = $filters['site']['value'];
        }

        foreach ($sites as $site) {
            if ($siteId && $site->getId() == $siteId) {
                $currentSite = $site;
            } elseif (null === $siteId && $site->getIsDefault()) {
                $currentSite = $site;
            }
        }

        if (!$currentSite && null === $siteId) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $siteId));
        }

        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFilterTheme());

        return $this->renderWithExtraParams('AwaresoftSonataAdminBundle:CRUD:list_multisite.html.twig', [
            'action' => 'list',
            'sites' => $sites,
            'currentSite' => $currentSite,
            'form' => $formView,
            'datagrid' => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ]);
    }

    /**
     * @param SiteInterface $currentSite
     *
     * @return array
     */
    protected function findTreeElements(SiteInterface $currentSite)
    {
        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->admin->getModelManager()
            ->createQuery($this->admin->getClass())
            ->getQueryBuilder();

        if ($this->admin->getMultisite()) {
            $qb
                ->andWhere('o.site = :site')
                ->setParameter('site', $currentSite);
        }

        $qb->orderBy('o.root', 'ASC');
        $qb->orderBy('o.left', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Sets the admin form theme to form view. Used for compatibility between Symfony versions.
     */
    protected function setFormTheme(FormView $formView, array $theme = null): void
    {
        $twig = $this->get('twig');

        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $theme);
    }
}

<?php

namespace Awaresoft\Sonata\AdminBundle\Reference\Type;

use Awaresoft\Sonata\AdminBundle\Reference\ReferenceObject;
use Awaresoft\Sonata\PageBundle\Entity\Block;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PageBlockType
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class PageBlockType extends AbstractType
{
    /**
     * @var string
     */
    protected $blockType;

    /**
     * @var string
     */
    protected $blockObjectFieldName;

    /**
     * PageBlockType constructor.
     *
     * @param ContainerInterface $container
     * @param mixed $adminObject
     * @param string $blockType
     * @param string $blockObjectFieldName
     */
    public function __construct(ContainerInterface $container, $adminObject, $blockType, $blockObjectFieldName)
    {
        parent::__construct($container);

        $this->adminObject = $adminObject;
        $this->blockType = $blockType;
        $this->blockObjectFieldName = $blockObjectFieldName;
    }

    /**
     * @inheritdoc
     */
    public function createReferenceObjects()
    {
        $blocks = $this->findBlockReference($this->adminObject, $this->blockType, $this->blockObjectFieldName);

        $referenceObjects = [];
        foreach ($blocks as $i => $block) {
            $typeElements = explode('.', $block->getType());
            $referenceObjects[$i] = new ReferenceObject();
            $referenceObjects[$i]->setName(sprintf('%s (%s)', $this->prepareObjectNameFromClass(get_class($block)), end($typeElements)));
            $referenceObjects[$i]->setObject($block);
            $referenceObjects[$i]->setRouteName('admin_awaresoft_cms_compose');
            $referenceObjects[$i]->setRouteParams([
                'id' => $block->getPage()->getId(),
            ]);
        }

        $this->referenceObjects = $referenceObjects;
    }

    /**
     * @param $object
     * @param $blockType
     * @param $blockObjectFieldName
     *
     * @return bool|Block[]
     */
    protected function findBlockReference($object, $blockType, $blockObjectFieldName)
    {
        /**
         * @var Block[] $blocks
         */
        $blocks = $this->container->get('sonata.page.manager.block')->findBy([
            'type' => $blockType,
        ]);
        $referenceBlocks = [];

        foreach ($blocks as $block) {
            $blockObjectId = $block->getSetting($blockObjectFieldName);

            if ($blockObjectId == $object->getId()) {
                $referenceBlocks[] = $block;
            }
        }

        return $referenceBlocks;
    }
}
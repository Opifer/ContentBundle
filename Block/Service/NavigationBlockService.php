<?php

namespace Opifer\ContentBundle\Block\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Opifer\ContentBundle\Block\Tool\ContentTool;
use Opifer\ContentBundle\Block\Tool\ToolsetMemberInterface;
use Opifer\ContentBundle\Entity\NavigationBlock;
use Opifer\ContentBundle\Form\Type\ContentTreePickerType;
use Opifer\ContentBundle\Model\BlockInterface;
use Opifer\ContentBundle\Model\ContentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Navigation Block Service
 */
class NavigationBlockService extends AbstractBlockService implements BlockServiceInterface, ToolsetMemberInterface
{
    /** @var ContentManagerInterface */
    protected $contentManager;

    protected $collection;

    /**
     * Constructor
     *
     * @param EngineInterface         $templating
     * @param ContentManagerInterface $contentManager
     * @param array                   $config
     */
    public function __construct(EngineInterface $templating, ContentManagerInterface $contentManager, array $config)
    {
        parent::__construct($templating, $config);

        $this->contentManager = $contentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildManageForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildManageForm($builder, $options);

        // Default panel
        $builder->add(
            $builder->create('default', FormType::class, ['virtual' => true])
                ->add('value', ContentTreePickerType::class)
        );
    }

    /**
     * @param BlockInterface $block
     */
    public function load(BlockInterface $block)
    {
        /** @var NavigationBlock $block */
        $block->setTree($this->GetTree($block->getValue()));
    }

    /**
     * @param  string $json
     * @return array
     */
    public function getTree($json)
    {
        $simpleTree = json_decode($json, true);
        if (!$simpleTree) {
            return [];
        }

        $collection = $this->contentManager->getRepository()
            ->createQueryBuilder('c')
            ->where('c.id IN (:ids)')->setParameter('ids', $this->gatherIds($simpleTree))
            ->getQuery()
            ->getArrayResult(); // TODO Serialize instead of transforming the complete object to array

        $this->setCollection($collection);

        return $this->buildTree($simpleTree);
    }

    /**
     * Gather all ids, so we can retrieve all content in a single query
     *
     * @param array $array
     * @param array $ids
     * @return array
     */
    protected function gatherIds(array $array, array $ids = array())
    {
        foreach ($array as $item) {
            $ids[] = $item['id'];
            if (isset($item['__children']) && count($item['__children'])) {
                $ids = $this->gatherIds($item['__children'], $ids);
            }
        }

        return $ids;
    }

    /**
     * Keep collection as key-value
     *
     * @param $collection
     */
    protected function setCollection($collection)
    {
        $array = [];
        foreach ($collection as $content) {
            $array[$content['id']] = $content;
        }

        $this->collection = $array;
    }

    /**
     * Build the tree from the simpletree and the collection
     *
     * @param array $simpleTree
     * @param array $tree
     * @return array
     */
    protected function buildTree(array $simpleTree, $tree = [])
    {
        foreach ($simpleTree as $item) {
            if (!isset($this->collection[$item['id']])) {
                continue;
            }

            $content = $this->collection[$item['id']];

            unset($this->collection[$item['id']]); // TODO Fix multi-usage of single item

            if (isset($item['__children']) && count($item['__children'])) {
                $content['__children'] = $this->buildTree($item['__children']);
            } else {
                $content['__children'] = [];
            }

            $tree[] = $content;
        }

        return $tree;
    }

    /**
     * {@inheritdoc}
     */
    public function createBlock()
    {
        return new NavigationBlock();
    }

    /**
     * {@inheritdoc}
     */
    public function getTool()
    {
        $tool = new ContentTool('Navigation', 'OpiferContentBundle:NavigationBlock');

        $tool->setIcon('menu')
            ->setDescription('Generates a simple page navigation');

        return $tool;
    }
}

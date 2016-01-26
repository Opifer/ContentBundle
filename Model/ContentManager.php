<?php

namespace Opifer\ContentBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Opifer\ContentBundle\Exception\NestedContentFormException;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

use Opifer\EavBundle\Form\Type\NestedType;
use Opifer\EavBundle\Manager\EavManager;
use Opifer\EavBundle\Entity\NestedValue;

class ContentManager implements ContentManagerInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var EavManager */
    protected $eavManager;

    /** @var string */
    protected $class;

    /** @var string */
    protected $templateClass;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $em
     * @param FormFactoryInterface   $formFactory
     * @param EavManager             $eavManager
     */
    public function __construct(EntityManagerInterface $em, FormFactoryInterface $formFactory, EavManager $eavManager, $class, $templateClass)
    {
        if (!is_subclass_of($class, 'Opifer\ContentBundle\Model\ContentInterface')) {
            throw new \Exception($class .' must implement Opifer\ContentBundle\Model\ContentInterface');
        }

        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->eavManager = $eavManager;
        $this->class = $class;
        $this->templateClass = $templateClass;
    }

    /**
     * Initialize the content entity
     *
     * @param int $type
     * @return ContentInterface
     */
    public function initialize($type = 0)
    {
        $class = $this->getClass();

        return new $class();
    }

    /**
     * Get the class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getClass());
    }

    /**
     * {@inheritDoc}
     */
    public function getPaginatedByRequest(Request $request)
    {
        $qb = $this->getRepository()->getQueryBuilderFromRequest($request);

        $paginator = new Pagerfanta(new DoctrineORMAdapter($qb));
        $paginator->setMaxPerPage($request->get('limit', 25));
        $paginator->setCurrentPage($request->get('p', 1));

        return $paginator;
    }

    /**
     * {@inheritDoc}
     */
    public function findOneBySlug($slug)
    {
        return $this->getRepository()->findOneBySlug($slug);
    }

    /**
     * Find published content
     *
     * @param string $slug
     *
     * @return ContentInterface
     */
    public function findActiveBySlug($slug)
    {
        return $this->getRepository()->findActiveBySlug($slug);
    }

    /**
     * Find content by alias
     *
     * @param string $alias
     *
     * @return ContentInterface
     */
    public function findOneByAlias($alias)
    {
        return $this->getRepository()->findOneByAlias($alias);
    }

    /**
     * Find published content by alias
     *
     * @param string $alias
     *
     * @return ContentInterface
     */
    public function findActiveByAlias($alias)
    {
        return $this->getRepository()->findActiveByAlias($alias);
    }

    /**
     * Get the content by a reference
     *
     * If the passed reference is a numeric, it must be the content ID from a
     * to-be-updated content item.
     * If not, the reference must be the template name for a to-be-created
     * content item.
     *
     * @param int|string $reference
     *
     * @return ContentInterface
     */
    public function getContentByReference($reference)
    {
        if (is_numeric($reference)) {
            // If the reference is numeric, it must be the content ID from an existing
            // content item, which has to be updated.
            $nestedContent = $this->getRepository()->find($reference);
        } else {
            // If not, $reference is a template name for a to-be-created content item.
            $template = $this->em->getRepository($this->templateClass)->findOneByName($reference);

            $nestedContent = $this->eavManager->initializeEntity($template);
            $nestedContent->setNestedDefaults();
        }

        return $nestedContent;
    }

    /**
     * {@inheritDoc}
     */
    public function save(ContentInterface $content)
    {
        if (!$content->getId()) {
            $this->em->persist($content);
        } else {
            $cacheDriver = $this->em->getConfiguration()->getResultCacheImpl();
            $cacheDriver->deleteAll();
        }

        $this->em->flush();

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($content)
    {
        if (!is_array($content)) {
            $content = [$content];
        }

        $content = $this->getRepository()->findByIds($content);
        foreach ($content as $item) {
            $this->em->remove($item);
        }

        // Clear the result cache
        $cacheDriver = $this->em->getConfiguration()->getResultCacheImpl();
        $cacheDriver->deleteAll();

        $this->em->flush();
    }

    /**
     * Duplicate a content item
     *
     * @param ContentInterface $content
     */
    public function duplicate(ContentInterface $content)
    {
        //get valueset to clone
        $valueset = $content->getValueSet();

        //clone valueset
        $duplicatedValueset = clone $valueset;

        $this->detachAndPersist($duplicatedValueset);

        //duplicate content
        $duplicatedContent = clone $content;
        $duplicatedContent->setSlug(null);
        $duplicatedContent->setValueSet($duplicatedValueset);

        $this->detachAndPersist($duplicatedContent);

        //iterate values, clone each and assign duplicate valueset to it
        foreach ($valueset->getValues() as $value) {

            //skip empty attributes
            if (is_null($value->getId())) continue;

            $duplicatedValue = clone ($value);
            $duplicatedValue->setValueSet($duplicatedValueset);

            $this->detachAndPersist($duplicatedValue);
        }
        $this->em->flush();

        return $duplicatedContent->getId();
    }

    /**
     * For cloning purpose
     *
     * @param ContentInterface|\Opifer\EavBundle\Model\ValueSetInterface|\Opifer\EavBundle\Entity\Value $entity
     */
    private function detachAndPersist($entity)
    {
        $this->em->detach($entity);
        $this->em->persist($entity);
    }
}

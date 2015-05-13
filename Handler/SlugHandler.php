<?php

namespace Opifer\ContentBundle\Handler;

use Gedmo\Sluggable\SluggableListener;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Sluggable\Handler\SlugHandlerInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;

/**
 * class SlugHandler
 *
 * @author denis
 */
class SlugHandler extends RelativeSlugHandler
{
    
    /**
     * {@inheritDoc}
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        parent::onSlugCompletion($ea, $config, $object, $slug);
        
        $this->slug = &$slug;
        $this->usedOptions = $config['handlers'][get_called_class()];
        $this->object = $object;
        $this->ea = $ea;
        
        if(isset($this->usedOptions[__FUNCTION__])) {
            foreach($this->usedOptions[__FUNCTION__] as $method) {
                if(method_exists($this, $method)) {
                    $this->$method();
                }
            }
        }
        
    }
    
    /**
     * slug trim
     */
    private function rightTrim()
    {
        $this->slug = rtrim($this->slug, $this->usedOptions['separator']);
    }
    
    /**
     * 
     */
    private function appendIndex()
    {
        if(substr($this->slug, -1) == '/') {
            $this->slug = $this->slug . 'index';
        }
    }
    
    /**
     * Update slug if index page exists
     */
    private function checkIndexPage()
    {
        if(!$this->slug) {
            return;
        }
        
        $this->repository = $this->ea->getObjectManager()->getRepository(get_class($this->object));
        
        $this->_checkSlugIndex();
    }
    
    /**
     * Loop through all pages with same slug and increment by one
     * 
     * @param int $i
     */
    private function _checkSlugIndex($i = 0)
    {
        $query = $this->repository->createQueryBuilder('c');
        $query->where("c.slug = :slug");
        
        if($this->object->getId()) {
            $query->andWhere("c.id != :id");
            $query->setParameter('id', $this->object->getId());
        }
        
        $slugBase = ($i > 0) ? $this->slug.'-'.$i : $this->slug;
        
        $query->setParameter('slug', $slugBase.'/index');
        $results = $query->getQuery()->getResult();
        
        if($results) {
            $i++;
            return $this->_checkSlugIndex($i);
        }
        
        if($i) {
            $this->slug = $slugBase;
        }
    }
}

<?php

namespace Opifer\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Opifer\MediaBundle\Model\MediaInterface;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * JumbotronBlock
 *
 * @ORM\Entity
 */
class JumbotronBlock extends Block
{
    /**
     * @var MediaInterface
     *
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="Opifer\MediaBundle\Model\MediaInterface", fetch="EAGER")
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $media;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return MediaInterface
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param MediaInterface $media
     *
     * @return $this
     */
    public function setMedia($media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return string
     */
    public function getBlockType()
    {
        return 'jumbotron';
    }
}
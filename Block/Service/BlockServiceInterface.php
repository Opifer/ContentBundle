<?php

namespace Opifer\ContentBundle\Block\Service;

use Opifer\ContentBundle\Model\BlockInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface BlockServiceInterface
 *
 * @Widget;
 *
 * @package Opifer\ContentBundle\Block
 */
interface BlockServiceInterface
{

    /**
     * @param BlockInterface $block
     *
     * @return mixed
     */
    public function getName(BlockInterface $block = null);

    /**
     * @return mixed
     */
    public function getView(BlockInterface $block);

    /**
     * @return mixed
     */
    public function execute(BlockInterface $block, Response $response = null);

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildManageForm(FormBuilderInterface $builder, array $options);

    /**
     * Executed before the form handles the request and officially submits the form
     *
     * @param BlockInterface $block
     */
    public function preFormSubmit(BlockInterface $block);

    /**
     * Executed after the form is defined valid and before the block is actually persisted
     *
     * @param FormInterface $form
     * @param BlockInterface $block
     */
    public function postFormSubmit(FormInterface $form, BlockInterface $block);
}

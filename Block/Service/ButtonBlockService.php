<?php

namespace Opifer\ContentBundle\Block\Service;

use Opifer\ContentBundle\Block\Tool\ContentTool;
use Opifer\ContentBundle\Block\Tool\ToolsetMemberInterface;
use Opifer\ContentBundle\Entity\ButtonBlock;
use Opifer\ContentBundle\Model\BlockInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Button Block Service
 */
class ButtonBlockService extends AbstractBlockService implements BlockServiceInterface, ToolsetMemberInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildManageForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildManageForm($builder, $options);

        $propertiesForm = $builder->create('properties', FormType::class)
            ->add('url', TextType::class, ['label' => 'label.url'])
            ->add('target', ChoiceType::class, [
                'label' => 'label.target',
                'choices' => ['_blank' => '_blank', '_self' => '_self'],
                'required' => false,
            ])
            ->add('id', TextType::class, ['attr' => ['help_text' => 'help.html_id']])
            ->add('extra_classes', TextType::class, ['attr' => ['help_text' => 'help.extra_classes']]);


        if ($this->config['styles']) {
            $propertiesForm->add('styles', ChoiceType::class, [
                'label' => 'label.styling',
                'choices'  => array_combine($this->config['styles'], $this->config['styles']),
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ]);
        }

        $builder->add(
            $builder->create('default', FormType::class, ['inherit_data' => true])
                ->add('value', TextType::class, ['label' => 'label.label'])
        )->add(
            $propertiesForm
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createBlock()
    {
        return new ButtonBlock;
    }

    /**
     * @return array
     */
    public function getStyles()
    {
        return $this->config['styles'];
    }

    /**
     * {@inheritDoc}
     */
    public function getTool()
    {
        $tool = new ContentTool('Button link', 'OpiferContentBundle:ButtonBlock');

        $tool->setIcon('label_outline')
            ->setDescription('Creates a link to a (external) page or content');

        return $tool;
    }
}

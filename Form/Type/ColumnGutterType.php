<?php

namespace Opifer\ContentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class ColumnGutterType
 *
 * @package Opifer\ContentBundle\Form\Type
 */
class ColumnGutterType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $columnCount = $form->getParent()->getConfig()->getOption('column_count');

            for ($i = 0; $i < $columnCount; $i++) {
                $form->add($i, ChoiceType::class, [
                    'label' => 'label.gutter_sizing',
                    'choices' => ['md' => 'md', '0' => '0', 'sm' => 'sm', 'lg' => 'lg'],
                    'required' => true,
                ]);
            }

        });
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'column_span';
    }
}

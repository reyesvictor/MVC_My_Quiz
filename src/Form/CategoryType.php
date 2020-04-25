<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CategoryType extends AbstractType
{
    /**
     * Get configuration for form inpout.
     *
     * @param string $label
     * @param string $placeholder
     * @return array
     */
    private function getConfiguration($value, $placeholder)
    {
        return [
            'attr' => [
                'value' => $value,
                // 'label' => $label,
                'placeholder' => $placeholder,
            ]
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['category'] !== 'category') {
            $name_value = $options['category']->getName();
        } else {
            $name_value = "";
        }
        $builder
            ->add('name', TextType::class, $this->getConfiguration($name_value, 'Title here'))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'category' => 'category', 
        ]);
    }
}

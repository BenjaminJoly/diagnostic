<?php

namespace Admin\Form;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Language Form Factory
 *
 * @package Admin\Factory
 * @author Jerome De Almeida <jerome.dealmeida@vesperiagroup.com>
 */
class LanguageFormFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $languages = $serviceLocator->getServiceLocator()->get('Diagnostic\Service\LanguageService')->getLanguages();

        $form = new LanguageForm();
        $form->setLanguages($languages);

        return $form;
    }
}

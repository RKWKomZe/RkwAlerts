<?php

namespace RKW\RkwAlerts\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Madj2k\FeRegister\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Madj2k\CoreExtended\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class ConsentViewHelper
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CategoryViewHelper extends AbstractViewHelper
{

    /**
     * @const string
     */
    const NAMESPACE = 'tx_rkwalerts_create';

    /**
     * Initialize arguments.
     *
     * @return void
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('category', '\RKW\RkwAlerts\Domain\Model\Category', 'The category to check', true);

    }


    /**
     * Returns true if the given category is part of _GP
     *
     * @return string
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \Exception
     */
    public function render(): string
    {
        /** @var \RKW\RkwAlerts\Domain\Model\Category $category */
        $category = $this->arguments['category'];

        /** @var array $formData */
        $formData = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_rkwalerts_create');

        if (
            key_exists('newCategoryList', $formData)
            && in_array($category->getUid(), $formData['newCategoryList'])
        ){
            return true;
        }

        return false;
    }


}

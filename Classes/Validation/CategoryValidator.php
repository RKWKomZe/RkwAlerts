<?php
namespace RKW\RkwAlerts\Validation;

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

use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class FrontendUserValidator
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Rkw_Alerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CategoryValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{


    /**
     * @var bool
     */
    protected bool $isValid = true;


    /**
     * validation
     *
     * @return boolean
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser $value
     */
    public function isValid($value): bool
    {
        if (empty($value)) {
            $this->result->forProperty('categoryList')->addError(
                new Error(
                    sprintf(
                        LocalizationUtility::translate(
                            'categoryValidator.error.required',
                            'rkw_alerts'
                        ),
                    ),
                    1718332132
                )
            );
            $this->isValid = false;
        }

        return $this->isValid;
    }


}


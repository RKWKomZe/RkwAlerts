<?php

namespace RKW\RkwAlerts\Controller;


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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use RKW\RkwMailer\Utility\FrontendLocalizationUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use RKW\RkwBasics\Helper\Common;
use RKW\RkwBasics\Utility\FrontendSimulatorUtility;

/**
 * Class SendCommandController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SendCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{

    /**
     * alertManager
     *
     * @var \RKW\RkwAlerts\Alerts\AlertManager
     * @inject
     */
    protected $alertManager = null;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


    /**
     * Sends notifications for pages
     *
     * @param string $filterField Field that will be used for filtering pages by their time of creation, only integer fields allowed! (default: lastUpdate)
     * @param int $timeSinceCreation Time in seconds from now since creation, matching against the field defined in filterField (default: 432000 = 5 days)
     * @param int $settingsPid Pid to fetch TypoScript-settings from
     */
    public function sendCommand($filterField = 'lastUpdated', $timeSinceCreation = 432000, $settingsPid = 0)
    {

        try {

            // simulate frontend
            FrontendSimulatorUtility::simulateFrontendEnvironment($settingsPid);

            // send alerts
            $this->alertManager->sendNotification($filterField, $timeSinceCreation);

            // reset frontend
            FrontendSimulatorUtility::resetFrontendEnvironment();

        } catch (\Exception $e) {
            $this->getLogger()->log(
                \TYPO3\CMS\Core\Log\LogLevel::ERROR,
                sprintf(
                    'An error occurred while trying to create alert-mails. Message: %s',
                    str_replace(array("\n", "\r"), '', $e->getMessage())
                )
            );
        }
    }



    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {

        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
        }

        return $this->logger;
    }


    /**
     * Returns TYPO3 settings
     *
     * @param string $which Which type of settings will be loaded
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings($which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS)
    {

        return \RKW\RkwBasics\Utility\GeneralUtility::getTyposcriptConfiguration('Rkwalerts', $which);
    }

}

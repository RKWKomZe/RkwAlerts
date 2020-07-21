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
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;


    /**
     * PagesRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\PagesRepository
     * @inject
     */
    protected $pagesRepository;


    /**
     * AlertsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\AlertsRepository
     * @inject
     */
    protected $alertsRepository;


    /**
     * ProjectsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\ProjectsRepository
     * @inject
     */
    protected $projectsRepository;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


    /**
     * Removes old service and registration requests
     *
     * @param string $filterField Field that will be used for filtering pages by their time of creation (default: crdate)
     * @param integer $timeSinceCreation Criterion for including pages into sending an alert. Now minus $timeSinceCreation in seconds (default: 432000 = 5 days)
     * @param int $settingsPid Pid to fetch TypoScript-settings from
     */
    public function sendCommand($filterField = null, $timeSinceCreation = 0, $settingsPid = 0)
    {

        try {

            // simulate frontend
            FrontendSimulatorUtility::simulateFrontendEnvironment($settingsPid);


            // get configuration
            $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            // set from TypoScript if not given via BE-planer
            if (
                (!$filterField)
                && ($settings['settings']['filterField'])
            ) {
                $filterField = $settings['settings']['filterField'];
            }

            if (
                (!$timeSinceCreation)
                && ($settings['settings']['timeSinceCreation'])
            ) {
                $timeSinceCreation = $settings['settings']['timeSinceCreation'];
            }


            $cnt = 0;
            if ($settings['view']['templateRootPaths']) {

                /** @var \RKW\RkwMailer\Service\MailService $mailService */
                $mailService = GeneralUtility::makeInstance('RKW\\RkwMailer\\Service\\MailService');

                // find all pages that have not been send alerts for
                /** @var \RKW\RkwAlerts\Domain\Model\Pages $page */
                $projectArray = array();

                foreach ($pages = $this->pagesRepository->findByTxRkwalertsSendStatusAndProject($filterField, $timeSinceCreation) as $page) {

                    // get project of page
                    // check if project was already mailed
                    /** @var \RKW\RkwProjects\Domain\Model\Projects $project */
                    if (
                        ($project = $page->getTxRkwprojectsProjectUid())
                        && ($project instanceof \RKW\RkwProjects\Domain\Model\Projects)
                        && (!in_array($project->getUid(), $projectArray))
                    ) {

                        // find all alerts for project
                        if ($alerts = $this->alertsRepository->findByProject($project->getUid())) {

                            // set recipients
                            /** @var \RKW\RkwAlerts\Domain\Model\Alerts $alert */
                            foreach ($alerts as $alert) {

                                // check if FE-User exists
                                if (
                                    ($frontendUser = $alert->getFrontendUser())
                                    && ($frontendUser instanceof \RKW\RkwRegistration\Domain\Model\FrontendUser)
                                ) {

                                    $mailService->setTo(
                                        $frontendUser,
                                        array(
                                            'marker'  => array(
                                                'alert'                    => $alert,
                                                'frontendUser'             => $frontendUser,
                                                'searchPid'                => intval($settings['settings']['searchPid']),
                                                'loginPid'                 => intval($settings['settings']['loginPid']),
                                                'linkSortingField'         => $settings['settings']['linkSortingField'],
                                                'linkSortingSortAscending' => $settings['settings']['linkSortingSortAscending'] ? 1 : 0,
                                            ),
                                            'subject' => FrontendLocalizationUtility::translate(
                                                'rkwMailService.sendAlert.subject',
                                                'rkw_alerts',
                                                array($project->getName()),
                                                $frontendUser->getTxRkwregistrationLanguageKey() ? $frontendUser->getTxRkwregistrationLanguageKey() : 'default'
                                            ),
                                        )
                                    );
                                }
                            }

                            // set default subject
                            $mailService->getQueueMail()->setSubject(
                                FrontendLocalizationUtility::translate(
                                    'rkwMailService.sendAlert.subjectDefault',
                                    'rkw_alerts',
                                    array($project->getName()),
                                    $settings['settings']['defaultLanguageKey'] ? $settings['settings']['defaultLanguageKey'] : 'default'
                                )
                            );

                            // send mail
                            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
                            $mailService->getQueueMail()->addPartialPaths($settings['view']['partialRootPaths']);
                            $mailService->getQueueMail()->setPlaintextTemplate('Email/Alert');
                            $mailService->getQueueMail()->setHtmlTemplate('Email/Alert');
                            $mailService->getQueueMail()->setType(2);

                            if ($recipientCount = count($mailService->getTo())) {
                                $mailService->send();
                                $cnt++;

                                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully created alert mail for topic "%s" on page "%s" with %s recipients.', $project->getName(), $page->getUid(), $recipientCount));
                            }
                        }

                        // add project-uid to list of already mailed projects
                        $projectArray[] = $project->getUid();
                    }

                    // update corresponding field in page
                    $page->setTxRkwalertsSendStatus(1);
                    $this->pagesRepository->update($page);
                }

                // persist
                $this->persistenceManager->persistAll();
            }

            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully created %s alert mails.', $cnt));

            // reset frontend
            FrontendSimulatorUtility::resetFrontendEnvironment();

        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('An error occurred while trying to create alert-mails. Message: %s', str_replace(array("\n", "\r"), '', $e->getMessage())));
        }
    }


    /**
     * Sends test mails for given project
     *
     * @param integer $projectId
     * @param string $email
     * @param int $settingsPid Pid to fetch TypoScript-settings from
     */
    public function testCommand($projectId = 0, $email = '', $settingsPid = 0)
    {

        try {

            // simulate frontend
            FrontendSimulatorUtility::simulateFrontendEnvironment($settingsPid);

            // get configuration
            $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            $cnt = 0;
            if ($settings['view']['templateRootPaths']) {


                /** @var \RKW\RkwAlerts\Domain\Model\Projects $project */
                $project = $this->projectsRepository->findByUid($projectId);
                if ($project) {

                    /** @var \RKW\RkwMailer\Service\MailService $mailService */
                    $mailService = GeneralUtility::makeInstance('RKW\\RkwMailer\\Service\\MailService');

                    /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
                    $frontendUser = GeneralUtility::makeInstance('RKW\\RkwRegistration\\Domain\\Model\\FrontendUser');
                    $frontendUser->setFirstName('Clemens');
                    $frontendUser->setLastName('Queißner');
                    $frontendUser->setEmail($email);

                    /** @var \RKW\RkwAlerts\Domain\Model\Alerts $alert */
                    $alert = GeneralUtility::makeInstance('RKW\\RkwAlerts\\Domain\\Model\\Alerts');
                    $alert->setFrontendUser($frontendUser);
                    $alert->setProject($project);

                    for ($i = 0; $i < 5; $i++) {

                        $mailService->setTo(
                            $frontendUser,
                            array(
                                'marker'  => array(
                                    'alert'                    => $alert,
                                    'frontendUser'             => $frontendUser,
                                    'searchPid'                => intval($settings['settings']['searchPid']),
                                    'loginPid'                 => intval($settings['settings']['loginPid']),
                                    'linkSortingField'         => $settings['settings']['linkSortingField'],
                                    'linkSortingSortAscending' => $settings['settings']['linkSortingSortAscending'] ? 1 : 0,
                                ),
                                'subject' =>
                                    'TEST: ' .
                                    FrontendLocalizationUtility::translate(
                                        'rkwMailService.sendAlert.subjectDefault',
                                        'rkw_alerts',
                                        array($project->getName()),
                                        $frontendUser->getTxRkwregistrationLanguageKey() ? $frontendUser->getTxRkwregistrationLanguageKey() : 'default'
                                    ),
                            )
                        );
                    }

                    // set default subject
                    $mailService->getQueueMail()->setSubject(
                        'TEST: ' .
                        FrontendLocalizationUtility::translate(
                            'rkwMailService.sendAlert.subjectDefault',
                            'rkw_alerts',
                            array($project->getName()),
                            $settings['settings']['defaultLanguageKey'] ? $settings['settings']['defaultLanguageKey'] : 'default'
                        )
                    );

                    // send mail
                    $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
                    $mailService->getQueueMail()->addPartialPaths($settings['view']['partialRootPaths']);
                    $mailService->getQueueMail()->setPlaintextTemplate('Email/Alert');
                    $mailService->getQueueMail()->setHtmlTemplate('Email/Alert');
                    $mailService->getQueueMail()->setType(2);

                    if ($recipientCount = count($mailService->getTo())) {
                        $mailService->send();
                        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully created test alert mail for topic "%s".', $project->getName()));
                    }


                } else {
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('Could not send test alert mail. Project with uid %s does not exist.', $projectId));
                }
            }

            // reset frontend
            FrontendSimulatorUtility::resetFrontendEnvironment();

        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('An error occurred while trying to create alert-mails. Message: %s', str_replace(array("\n", "\r"), '', $e->getMessage())));
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
        //===
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

        return Common::getTyposcriptConfiguration('Rkwalerts', $which);
        //===
    }

}

?>
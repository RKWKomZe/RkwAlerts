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

use RKW\RkwRegistration\Tools\Registration;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Class AlertsController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertsController extends \RKW\RkwAjax\Controller\AjaxAbstractController
{


    /**
     * logged in FrontendUser
     *
     * @var \RKW\RkwRegistration\Domain\Model\FrontendUser
     */
    protected $frontendUser = null;


    /**
     * FrontendUserRepository
     *
     * @var \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository;


    /**
     * alertsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     * @inject
     */
    protected $alertRepository = null;


    /**
     * pagesRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\PageRepository
     * @inject
     */
    protected $pageRepository;

    /**
     * projectsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\ProjectRepository
     * @inject
     */
    protected $projectRepository = null;


    /**
     * alertsManager
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
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_ALERTS_CANCELED_USER = 'afterAlertsCanceledUser';


    /**
     * action list
     *
     * @return void
     */
    public function listAction(): void
    {

        $alerts = $this->alertRepository->findByFrontendUser($this->getFrontendUser());
        if (count($alerts->toArray())) {

            // list all active alerts only!
            $this->view->assign(
                'alerts',
                $this->alertManager->getActiveAlerts($alerts)
            );
        }
    }


     /**
     * action new
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param string $email
     * @param integer $terms
     * @param integer $privacy
     * @ignorevalidation $alert
     * @return void
     */
    public function newAction(
        \RKW\RkwAlerts\Domain\Model\Alert $alert = null,
        $email = null,
        $terms = null,
        $privacy = null
    ): void {

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->alertManager->getSubscribableProjectByPageUid(intval($GLOBALS['TSFE']->id));
        if ($project) {

            // some basic parameters
            $frontendUser = $this->getFrontendUser();
            $displayForm = true;

            // Important security measure because of Varnish:
            // only set individual params if the form was submitted OR it was loaded via AJAX!
            if (
                ($this->ajaxHelper->getIsPostCall())
                || ($this->ajaxHelper->getIsAjaxCall())
            ){

                // check if alert already exists when user is logged in
                /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser */
                if (
                    ($frontendUser)
                    && ($this->alertManager->hasFrontendUserSubscribedProject($frontendUser, $project))
                ) {
                    $displayForm = false;
                }

                $this->view->assignMultiple(
                    [
                        'alert'             => $alert,
                        'termsPid'          => intval($this->settings['termsPid']),
                        'frontendUser'      => $frontendUser,
                        'project'           => $project,
                        'email'             => $email,
                        'terms'             => $terms,
                        'privacy'           => $privacy,
                        'displayForm'       => $displayForm,
                    ]
                );

            // display default values of form.
            // This way everything can be cached without caching sensitive data.
            } else {

                $this->view->assignMultiple(
                    [
                        'alert'             => $alert,
                        'termsPid'          => intval($this->settings['termsPid']),
                        'project'           => $project,
                        'displayForm'       => $displayForm,
                    ]
                );
            }
        }
    }



    /**
     * action create
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param string $email
     * @param integer $terms
     * @param integer $privacy
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function createAction(
        \RKW\RkwAlerts\Domain\Model\Alert $alert,
        $email = null,
        $terms = null,
        $privacy = null
    ): void {

        try {

            $result = $this->alertManager->createAlert(
                $this->request,
                $alert,
                $this->getFrontendUser(),
                $email,
                $terms,
                $privacy
            );

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.' . ($result ? 'message' : 'error') . '.create_' . $result,
                    'rkw_alerts'
                )
            );

        } catch (\RKW\RkwAlerts\Exception $exception) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    $exception->getMessage(),
                    'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->forward('new', null, null,
                [
                    'alert' => $alert,
                    'email' => $email,
                    'terms' => $terms,
                    'privacy' => $privacy
                ]
            );
        }

        $this->redirect('new');

    }


    /**
     * Takes optIn parameters and checks them
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function optInAction(): void
    {
        $tokenYes = preg_replace('/[^a-zA-Z0-9]/', '', ($this->request->hasArgument('token_yes') ? $this->request->getArgument('token_yes') : ''));
        $tokenNo = preg_replace('/[^a-zA-Z0-9]/', '', ($this->request->hasArgument('token_no') ? $this->request->getArgument('token_no') : ''));
        $userSha1 = preg_replace('/[^a-zA-Z0-9]/', '', $this->request->getArgument('user'));

        /** @var \RKW\RkwRegistration\Tools\Registration $register */
        $register = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Registration::class);
        $check = $register->checkTokens($tokenYes, $tokenNo, $userSha1);

        if ($check == 1) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.message.create_1', 'rkw_alerts'
                )
            );

        } elseif ($check == 2) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.message.create_3', 'rkw_alerts'
                )
            );

        } else {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.error.create', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }

        $this->redirect('new');
    }



    /**
     * action delete confirm
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @ignorevalidation $alert
     * @return void
     */
    public function deleteconfirmAction(\RKW\RkwAlerts\Domain\Model\Alert $alert): void
    {

        $this->view->assignMultiple(
            array(
                'alert' => $alert,
            )
        );
    }


    /**
     * action delete
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function deleteAction(\RKW\RkwAlerts\Domain\Model\Alert $alert): void
    {

        try {

            $result = $this->alertManager->deleteAlert(
                $alert,
                $this->getFrontendUser()
            );

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.' . ($result ? 'message' : 'error') . '.delete',
                    'rkw_alerts'
                )
            );

        } catch (\RKW\RkwAlerts\Exception $exception) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    $exception->getMessage(),
                    'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }

        $this->redirect('list');
    }




    /**
     * Remove ErrorFlashMessage
     *
     * @return bool
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::getErrorFlashMessage()
     */
    protected function getErrorFlashMessage(): bool
    {
        return false;
    }


    /**
     * Returns current logged in user object
     *
     * @return \RKW\RkwRegistration\Domain\Model\FrontendUser|NULL
     */
    protected function getFrontendUser()
    {
        if (!$this->frontendUser) {

            $frontendUser = $this->frontendUserRepository->findByUidNoAnonymous($this->getFrontendUserId());
            if ($frontendUser instanceof \RKW\RkwRegistration\Domain\Model\FrontendUser) {
                $this->frontendUser = $frontendUser;
            }
        }

        return $this->frontendUser;
    }


    /**
     * Id of logged User
     *
     * @return integer
     */
    protected function getFrontendUserId(): int
    {

        // is $GLOBALS set?
        if (
            ($GLOBALS['TSFE'])
            && ($GLOBALS['TSFE']->loginUser)
            && ($GLOBALS['TSFE']->fe_user->user['uid'])
        ) {
            return intval($GLOBALS['TSFE']->fe_user->user['uid']);
        }

        return 0;
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): \TYPO3\CMS\Core\Log\Logger
    {

        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
        }

        return $this->logger;
    }
}
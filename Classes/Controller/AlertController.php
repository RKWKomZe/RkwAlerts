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
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


/**
 * Class AlertController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertController extends \RKW\RkwAjax\Controller\AjaxAbstractController
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
                $this->alertManager->filterListBySubscribableProjects($alerts)
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
        $this->newActionBase($alert, $email, $terms, $privacy);
    }


    /**
     * action new non-cached
     * We need this because cached actions do not work with flashMessages
     * and non-cached do not work with empty-ViewHelper
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param string $email
     * @param integer $terms
     * @param integer $privacy
     * @ignorevalidation $alert
     * @return void
     */
    public function newNonCachedAction(
        \RKW\RkwAlerts\Domain\Model\Alert $alert = null,
        $email = null,
        $terms = null,
        $privacy = null
    ): void {
        $this->newActionBase($alert, $email, $terms, $privacy);
    }


    /**
     * basic functions for action new
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param string $email
     * @param integer $terms
     * @param integer $privacy
     * @ignorevalidation $alert
     * @return void
     */
    protected function newActionBase (
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
            // only set individual params if the form was submitted OR if it was loaded via AJAX!
            if (
                ($this->ajaxHelper->getIsPostCall())
                || ($this->ajaxHelper->getIsAjaxCall())
            ){

                // check if alert already exists when user is logged in
                /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser */
                if (
                    ($frontendUser)
                    && ($this->alertManager->hasFrontendUserSubscribedToProject($frontendUser, $project))
                ) {
                    $displayForm = false;
                }

                $this->view->assignMultiple(
                    [
                        'alert'             => $alert,
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
                LocalizationUtility::translate(
                    'alertController.' . ($result ? 'message' : 'error') . '.create_' . $result,
                    'rkw_alerts'
                )
            );

        } catch (\RKW\RkwAlerts\Exception $exception) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
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

        $this->redirect('newNonCached');

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
        $register = GeneralUtility::makeInstance(Registration::class);
        $check = $register->checkTokens($tokenYes, $tokenNo, $userSha1);

        if ($check == 1) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'alertController.message.create_1', 'rkw_alerts'
                )
            );

        } elseif ($check == 2) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'alertController.message.create_3', 'rkw_alerts'
                )
            );

        } else {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'alertController.error.create', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }

        $this->redirect('newNonCached');
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
                LocalizationUtility::translate(
                    'alertController.' . ($result ? 'message' : 'error') . '.delete',
                    'rkw_alerts'
                )
            );

        } catch (\RKW\RkwAlerts\Exception $exception) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
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
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }
}
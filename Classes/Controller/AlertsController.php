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
     * action index
     *
     * @return void
     */
    public function indexAction()
    {
        // nothing to do here - simply a fallback action
    }

    /**
     * action list
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function listAction()
    {

        // check if user is logged in!
        if (!$this->getFrontendUser()) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.error.not_logged_in', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('index');
            //===
        }

        // list all projects
        $alerts = $this->alertsRepository->findByFrontendUser($this->getFrontendUser()->getUid());

        // check if corresponding projects are still available!
        $result = array();
        if (count($alerts)) {

            /** @var \RKW\RkwAlerts\Domain\Model\Alerts $alert */
            foreach ($alerts as $alert) {
                if ($alert->getProject()) {
                    $result[] = $alert;
                }
            }
        }


        $this->view->assign('alerts', $result);
    }


      /**
     * action new
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $newAlert
     * @ignorevalidation $newAlert
     * @param string $email
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function newAction(\RKW\RkwAlerts\Domain\Model\Alert $newAlert = null, $email = null): void
    {


        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->alertManager->getSubscribableProjectByPageUid(intval($GLOBALS['TSFE']->id));
        if ($project) {


            // ajax-call: do personalization
            if ($this->ajaxHelper->getIsAjaxCall()) {

                // check if alert already exists when user is logged in
                /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser */
                if (
                    ($frontendUser = $this->getFrontendUser())
                    && ($this->alertManager->hasFeUserSubscribedToProject($frontendUser, $project))
                ) {
                    $this->view->assignMultiple(
                        [
                            'project'           => $project,
                            'frontendUser'      => $this->getFrontendUser(),
                        ]
                    );

                } else {
                    $this->view->assignMultiple(
                        [
                            'newAlert'          => $newAlert,
                            'termsPid'          => intval($this->settings['termsPid']),
                            'project'           => $project,
                            'frontendUser'      => $this->getFrontendUser(),
                            'email'             => $email,
                            'displayForm'       => true
                        ]
                    );
                }


            // normal call: display simple form
            } else {

                $this->view->assignMultiple(
                    [
                        'newAlert' => $newAlert,
                        'termsPid' => intval($this->settings['termsPid']),
                        'project'  => $project,
                        'displayForm'       => true
                    ]
                );
            }

        }


    }



    /**
     * action create
     * The ignorevalidation instruction is a workaround
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alerts $newAlert
     * @param string $email
     * @param integer $terms
     * @param integer $privacy
     * @ignorevalidation $newAlert
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @return void
     */
    public function createAction(\RKW\RkwAlerts\Domain\Model\Alerts $newAlert, $email = null, $terms = null, $privacy = null)
    {
        // for secure after the ignorevalidation workaround
        if (!$newAlert instanceof \RKW\RkwAlerts\Domain\Model\Alerts) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.message.something_went_wrong', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
            $this->forward('new');
            //===
        }

        //==========================================================
        // check if user is logged in and has a valid email-address
        // this may not be the case for Facebook and Twitter logins!
        if (
            ($this->getFrontendUser())
            && (\RKW\RkwRegistration\Tools\Registration::validEmail($this->getFrontendUser()))
        ) {
            // check privacy
            if (!$privacy) {
                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'registrationController.error.accept_privacy', 'rkw_registration'
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );
                $this->forward('new');
                //===
            }

            $newAlert->setFrontendUser($this->getFrontendUser());

            // check if the user already has an alert for this topic!
            if (!$this->alertsRepository->findExistingAlert($newAlert)) {

                // save it
                $this->alertsRepository->add($newAlert);

                // add privacy info
                \RKW\RkwRegistration\Tools\Privacy::addPrivacyData($this->request, $this->getFrontendUser(), $newAlert, 'new alert');

                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'alertController.message.alert_created', 'rkw_alerts'
                    )
                );
            } else {
                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'alertController.message.alert_already_exists', 'rkw_alerts'
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );
            }


        //==========================================================
        // if user is NOT logged in, we send an opt-in mail
        // this covers two cases:
        // 1) user already exists
        // 2) user does not exist and needs to be created
        } else {

            // check if email is valid
            if (!\RKW\RkwRegistration\Tools\Registration::validEmail($email)) {
                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'alertController.error.no_valid_email', 'rkw_alerts'
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );

                $this->forward('new');
                //===
            }

            // check if email is not already used - relevant for logged in users with no email-address (e.g. via Facebook or Twitter)
            if (
                ($this->getFrontendUser())
                && (!\RKW\RkwRegistration\Tools\Registration::validEmailUnique($email, $this->getFrontendUser()))
            ) {
                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'alertController.error.email_already_in_use', 'rkw_alerts'
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );

                $this->forward('new');
                //===
            }

            // check if terms are checked
            if (!$terms) {

                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'alertController.error.accept_terms', 'rkw_alerts'
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );

                $this->forward('new');
                //===
            }

            if (!$privacy) {
                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'registrationController.error.accept_privacy', 'rkw_registration'
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );
                $this->forward('new');
                //===
            }

            // register new user or simply send opt-in to existing user
            // we also submit the email as additional data to register-function since a logged in user

            // may use a different email and we have to update it after(!!!) opt-in!
            /** @var \RKW\RkwRegistration\Tools\Registration $registration */
            $registration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Registration');
            $registration->register(
                array(
                    'username' => ($this->getFrontendUser() ? $this->getFrontendUser()->getUsername() : $email),
                    'email'    => $email,
                ),
                false,
                array(
                    'alert' => $newAlert,
                    'email' => $email,
                ),
                'rkwAlerts',
                $this->request
            );

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.message.alert_created_email', 'rkw_alerts'
                )
            );

        }

        $this->redirect('new');
        //===
    }


    /**
     * Takes optIn parameters and checks them
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function optInAction()
    {
        $tokenYes = preg_replace('/[^a-zA-Z0-9]/', '', ($this->request->hasArgument('token_yes') ? $this->request->getArgument('token_yes') : ''));
        $tokenNo = preg_replace('/[^a-zA-Z0-9]/', '', ($this->request->hasArgument('token_no') ? $this->request->getArgument('token_no') : ''));
        $userSha1 = preg_replace('/[^a-zA-Z0-9]/', '', $this->request->getArgument('user'));

        /** @var \RKW\RkwRegistration\Tools\Registration $register */
        $register = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Registration');
        $check = $register->checkTokens($tokenYes, $tokenNo, $userSha1);

        if ($check == 1) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.message.alert_created', 'rkw_alerts'
                )
            );

        } elseif ($check == 2) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.message.alert_canceled', 'rkw_alerts'
                )
            );


        } else {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.error.alert_error', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }

        $this->redirect('new');
        //===
    }


    /**
     * action delete confirm
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alerts $alert
     * @ignorevalidation $alert
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function deleteconfirmAction(\RKW\RkwAlerts\Domain\Model\Alerts $alert)
    {
        // for secure after the ignorevalidation workaround
        if (!$alert instanceof \RKW\RkwAlerts\Domain\Model\Alerts) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.message.something_went_wrong', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
            $this->forward('list');
            //===
        }

        // check if user is logged in!
        if (!$this->getFrontendUser()) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.error.not_logged_in', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('index');
            //===
        }

        $this->view->assignMultiple(
            array(
                'alert' => $alert,
            )
        );

    }

    /**
     * action delete
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alerts $alert
     * @ignorevalidation $alert
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function deleteAction(\RKW\RkwAlerts\Domain\Model\Alerts $alert)
    {
        // for secure after the ignorevalidation workaround
        if (!$alert instanceof \RKW\RkwAlerts\Domain\Model\Alerts) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.message.something_went_wrong', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
            $this->forward('list');
            //===
        }

        // check if user is logged in!
        if (!$this->getFrontendUser()) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'alertController.error.not_logged_in', 'rkw_alerts'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('index');
            //===
        }

        $this->alertsRepository->remove($alert);

        $this->addFlashMessage(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'alertController.message.alert_deleted', 'rkw_alerts'
            )
        );

        $this->redirect('list');
    }

    /**
     * Creates alert - used by SignalSlot
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @param \RKW\RkwRegistration\Domain\Model\Registration $registration
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @return void
     */
    public function createAlert(\RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser, \RKW\RkwRegistration\Domain\Model\Registration $registration)
    {
        // set frontendUser to alert
        if (
            ($data = $registration->getData())
            && ($newAlert = $data['alert'])
            && ($newAlert instanceof \RKW\RkwAlerts\Domain\Model\Alerts)
        ) {
            if (
                ($data['email'])
                && (!\RKW\RkwRegistration\Tools\Registration::validEmail($frontendUser->getEmail()))
            ) {
                $frontendUser->setEmail(strtolower($data['email']));
                $this->frontendUserRepository->update($frontendUser);
            }

            $newAlert->setFrontendUser($frontendUser);

            // check if the user already has an alert for this topic!
            if (!$this->alertsRepository->findExistingAlert($newAlert)) {
                $this->alertsRepository->add($newAlert);
            }

            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

            /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager */
            $persistenceManager = $objectManager->get('TYPO3\\CMS\Extbase\\Persistence\\Generic\\PersistenceManager');
            $persistenceManager->persistAll();
        }
    }


    /**
     * Removes all open orders of a FE-User
     * Used by Signal-Slot
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @return void
     */
    public function removeAllOfUserSignalSlot(\RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser)
    {
        try {
            // we need it as array here because otherwise after removing the object we have no access any more
            $alerts = $this->alertsRepository->findByFrontendUser($frontendUser)->toArray();
            if (count($alerts) > 0) {

                /** @var \RKW\RkwAlerts\Domain\Model\Alerts $alert */
                foreach ($alerts as $alert) {

                    // 1. delete alert
                    $this->alertsRepository->remove($alert);

                    /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                    $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

                    /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager */
                    $persistenceManager = $objectManager->get('TYPO3\\CMS\Extbase\\Persistence\\Generic\\PersistenceManager');
                    $persistenceManager->persistAll();
                }

                // 2. send final confirmation mail to user
                $this->signalSlotDispatcher->dispatch(__CLASS__, self::SIGNAL_AFTER_ALERTS_CANCELED_USER, array($frontendUser, $alerts));
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Deleted alert with uid %s of user with uid %s via signal-slot.', $alert->getUid(), $frontendUser->getUid()));
            }

        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('Error while deleting alerts of user via signal-slot: %s', $e->getMessage()));
        }
    }


    /**
     * Remove ErrorFlashMessage
     *
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::getErrorFlashMessage()
     */
    protected function getErrorFlashMessage()
    {
        return false;
        //===
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
        //===
    }


    /**
     * Id of logged User
     *
     * @return integer|NULL
     */
    protected function getFrontendUserId()
    {

return 2;

        // is $GLOBALS set?
        if (
            ($GLOBALS['TSFE'])
            && ($GLOBALS['TSFE']->loginUser)
            && ($GLOBALS['TSFE']->fe_user->user['uid'])
        ) {
            return intval($GLOBALS['TSFE']->fe_user->user['uid']);
            //===
        }

        return null;
        //===
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
}
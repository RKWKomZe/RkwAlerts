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

use RKW\RkwAlerts\Alerts\AlertManager;
use RKW\RkwAlerts\Domain\Repository\AlertRepository;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class AlertController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertController extends \Madj2k\AjaxApi\Controller\AjaxAbstractController
{


    /**
     * logged in FrontendUser
     *
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUser = null;


    /**
     * FrontendUserRepository
     *
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected FrontendUserRepository $frontendUserRepository;


    /**
     * alertsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected AlertRepository $alertRepository;


    /**
     * alertsManager
     *
     * @var \RKW\RkwAlerts\Alerts\AlertManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?AlertManager $alertManager;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * action list
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
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
     * @param \RKW\RkwAlerts\Domain\Model\Alert|null $alert
     * @param string $email
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("alert")
     * @return void
     */
    public function newAction(
        \RKW\RkwAlerts\Domain\Model\Alert $alert = null,
        string $email = ''
    ): void {
        $this->newActionBase($alert, $email);
    }


    /**
     * action new non-cached
     * We need this because cached actions do not work with flashMessages
     * and non-cached do not work with empty-ViewHelper
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert|null $alert
     * @param string $email
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("alert")
     * @return void
     */
    public function newNonCachedAction(
        \RKW\RkwAlerts\Domain\Model\Alert $alert = null,
        string $email = ''
    ): void {
        $this->newActionBase($alert, $email);
    }


    /**
     * basic functions for action new
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert|null $alert
     * @param string $email
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("alert")
     * @return void
     */
    protected function newActionBase (
        \RKW\RkwAlerts\Domain\Model\Alert $alert = null,
        string $email = ''
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
                /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser */
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
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @TYPO3\CMS\Extbase\Annotation\Validate("\Madj2k\FeRegister\Validation\Consent\TermsValidator", param="alert")
     * @TYPO3\CMS\Extbase\Annotation\Validate("\Madj2k\FeRegister\Validation\Consent\PrivacyValidator", param="alert")
     * @TYPO3\CMS\Extbase\Annotation\Validate("\Madj2k\FeRegister\Validation\Consent\MarketingValidator", param="alert")
     */
    public function createAction(
        \RKW\RkwAlerts\Domain\Model\Alert $alert,
        string $email = ''
    ): void {

        try {

            $result = $this->alertManager->createAlert(
                $this->request,
                $alert,
                $this->getFrontendUser(),
                $email
            );

            if ($result) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'alertController.message.create_' . $result,
                        'rkw_alerts'
                    )
                );
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'alertController.error.create',
                        'rkw_alerts'
                    ),
                    '',
                     \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );
            }


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
                ]
            );
        }

        $this->redirect('newNonCached');

    }


    /**
     * Takes optIn parameters and checks them
     *
     * @param string $tokenUser
     * @param string $token
     * @return void
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function optInAction(string $tokenUser, string $token): void
    {
        /** @var \Madj2k\FeRegister\Registration\FrontendUserRegistration $registration */
        $registration = $this->objectManager->get(FrontendUserRegistration::class);
        $result = $registration->setFrontendUserToken($tokenUser)
            ->setCategory('rkwAlerts')
            ->setRequest($this->request)
            ->validateOptIn($token);

        if ($result >= 200 && $result < 300) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'alertController.message.create_1', 'rkw_alerts'
                )
            );

        } elseif ($result >= 300 && $result < 400) {

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
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("alert")
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
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
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
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getFrontendUser() :? FrontendUser
    {
        if (!$this->frontendUser) {
            $this->frontendUser = FrontendUserSessionUtility::getLoggedInUser();
        }
        return $this->frontendUser;
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

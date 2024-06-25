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

use Madj2k\FeRegister\Utility\FrontendUserUtility;
use RKW\RkwAlerts\Alerts\AlertManager;
use RKW\RkwAlerts\Domain\Model\Alert;
use RKW\RkwAlerts\Domain\Model\Category;
use RKW\RkwAlerts\Domain\Repository\AlertRepository;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use RKW\RkwAlerts\Domain\Repository\CategoryRepository;
use RKW\RkwAlerts\Exception;
use Solarium\Component\Debug;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
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
    protected ?FrontendUserRepository $frontendUserRepository  = null;


    /**
     * alertsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?AlertRepository $alertRepository = null;


    /**
     * categoryRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\CategoryRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?CategoryRepository $categoryRepository = null;


    /**
     * alertsManager
     *
     * @var \RKW\RkwAlerts\Alerts\AlertManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?AlertManager $alertManager  = null;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     */
    public function injectFrontendUserRepository (FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }


    /**
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     */
    public function injectAlertRepository (AlertRepository $alertRepository)
    {
        $this->alertRepository = $alertRepository;
    }


    /**
     * @var \RKW\RkwAlerts\Domain\Repository\CategoryRepository
     */
    public function injectCategoryRepository (CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }


    /**
     * @var \RKW\RkwAlerts\Alerts\AlertManager
     */
    public function injectAlertManager (AlertManager $alertManager)
    {
        $this->alertManager  = $alertManager;
    }


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
                $this->alertManager->filterListBySubscribableCategory($alerts)
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
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
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
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
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
     * @param string                                 $email
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("alert")
     * @return void
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws InvalidQueryException
     */
    protected function newActionBase (
        \RKW\RkwAlerts\Domain\Model\Alert $alert = null,
        string $email = ''
    ): void {


        $newsDetailPageData = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_news_pi1');

        if (
            key_exists('news', $newsDetailPageData)
            && key_exists('action', $newsDetailPageData)
            && $newsDetailPageData['action'] == 'detail'
        ) {
            // NEWS DETAIL PAGE
            $categoryList = $this->alertManager->getSubscribableCategoryByNewsUid(intval($newsDetailPageData['news']));

        }
        elseif (!empty($this->settings['categoriesList'])) {
            // FLEXFORM

            $categoryList = $this->categoryRepository->findEnabledByIdentifierMultiple(
                \Madj2k\CoreExtended\Utility\GeneralUtility::trimExplode(',', $this->settings['categoriesList'])
            );
        }

        /** @var \RKW\RkwAlerts\Domain\Model\Category $category */
        if (
            is_countable($categoryList)
            && count($categoryList)
        ) {

            // Important security measure because of Varnish:
            // only set individual params if the form was submitted OR if it was loaded via AJAX!
            if (
                ($this->ajaxHelper->getIsPostCall())
                || ($this->ajaxHelper->getIsAjaxCall())
            ){

                $this->view->assignMultiple(
                    [
                        'alert'             => $alert,
                        'frontendUser'      => $this->getFrontendUser(),
                        'categoryList'      => $categoryList,
                        'email'             => $email,
                    ]
                );

                // display default values of form.
                // This way everything can be cached without caching sensitive data.
            } else {

                $this->view->assignMultiple(
                    [
                        'alert'             => $alert,
                        'categoryList'      => $categoryList,

                        // just for development. Remove "displayForm" later
                        'displayForm'       => true,
                    ]
                );
            }
        }

    }


    /**
     * action create
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param string                            $email
     * @param array                             $newCategoryList
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws InvalidQueryException|Exception
     * @TYPO3\CMS\Extbase\Annotation\Validate("RKW\RkwAlerts\Validation\CategoryValidator", param="newCategoryList")
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\Consent\TermsValidator", param="alert")
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\Consent\PrivacyValidator", param="alert")
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\Consent\MarketingValidator", param="alert")
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\CoreExtended\Validation\CaptchaValidator", param="alert")
     */
    public function createAction(
        \RKW\RkwAlerts\Domain\Model\Alert $alert,
        string $email = '',
        array $newCategoryList = []
    ): void {

        $categoryList = $this->categoryRepository->findEnabledByIdentifierMultiple($newCategoryList);

        // create categoryList
        $newAlertsArray = [];
        /** @var Category $category */
        foreach ($categoryList as $category) {

            $newAlert = GeneralUtility::makeInstance(Alert::class);
            $newAlert->setCategory($category);

            $newAlertsArray[] = $newAlert;
        }


        // register alerts
        if ($this->getFrontendUser() instanceof FrontendUser) {

            // logged user
            $this->alertManager->createAlertLoggedUser(
                $this->request,
                $newAlertsArray,
                $this->getFrontendUser()
            );

        } else {

            // by email
            $this->alertManager->createAlertByEmail(
                $this->request,
                $alert,
                $email,
                $newAlertsArray
            );

        }

        // transform AlertMessages to ControllerMessages
        foreach ($this->alertManager->getFlashMessages() as $flashMessage) {
            $this->addFlashMessage(
                $flashMessage->getMessage(),
                $flashMessage->getTitle(),
                $flashMessage->getSeverity()
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

        } elseif (
            $result >= 300
            && $result < 400
            && $result != 302
        ) {

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
                AbstractMessage::ERROR
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
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("alert")
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

        } catch (Exception $exception) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    $exception->getMessage(),
                    'rkw_alerts'
                ),
                '',
                AbstractMessage::ERROR
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

            // get frontendUser - but NO guestUsers!
            if (
                ($frontendUser = FrontendUserSessionUtility::getLoggedInUser())
                && (! FrontendUserUtility::isGuestUser($frontendUser))
            ){
                $this->frontendUser = $frontendUser;
            }
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

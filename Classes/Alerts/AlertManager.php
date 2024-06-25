<?php
namespace RKW\RkwAlerts\Alerts;

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

use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\Postmaster\Mail\MailMessage;
use phpDocumentor\Reflection\Types\Void_;
use RKW\RkwAlerts\Domain\Model\Category;
use RKW\RkwAlerts\Domain\Model\News;
use RKW\RkwAlerts\Domain\Repository\AlertRepository;
use RKW\RkwAlerts\Domain\Repository\CategoryRepository;
use RKW\RkwAlerts\Domain\Repository\NewsRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as FrontendLocalizationUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use RKW\RkwAlerts\Exception;

/**
 * Class AlertManager
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertManager
{

    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_ALERT_CREATED = 'afterAlertCreated';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_ALERT_DELETED = 'afterAlertDeleted';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_ALERT_DELETED_ALL = 'afterAlertDeletedAll';


    /**
     * alertRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?AlertRepository $alertRepository = null;


    /**
     * newsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\NewsRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?NewsRepository $newsRepository = null;


    /**
     * categoryRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\CategoryRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?CategoryRepository $categoryRepository = null;


    /**
     * frontendUserRepository
     *
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?FrontendUserRepository $frontendUserRepository = null;


    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?Dispatcher $signalSlotDispatcher = null;


    /**
     * Persistence Manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?PersistenceManager $persistenceManager = null;

    /**
     * @var array
     */
    protected array $flashMessageContainer = [];

    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;

    /**
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     */
    public function injectAlertRepository (AlertRepository $alertRepository)
    {
        $this->alertRepository= $alertRepository;
    }

    /**
     * @var \RKW\RkwAlerts\Domain\Repository\NewsRepository
     */
    public function injectNewsRepository (NewsRepository $newsRepository)
    {
        $this->newsRepository= $newsRepository;
    }

    /**
     * @var \RKW\RkwAlerts\Domain\Repository\CategoryRepository
     */
    public function injectCategoryRepository (CategoryRepository $categoryRepository)
    {
        $this->categoryRepository= $categoryRepository;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     */
    public function injectFrontendUserRepository (FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository= $frontendUserRepository;
    }


    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    public function injectDispatcher (Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    public function injectPersistenceManager (PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }


    /**
     * Gets the category of the given news-id and also checks if the news has a category set and
     * alerts are activated for that category
     *
     * @param int $newsUid The news uid
     * @return array
     */
    public function getSubscribableCategoryByNewsUid(int $newsUid):? array
    {
        $categoryList = [];

        /** @var $news \RKW\RkwAlerts\Domain\Model\News */
        $news = $this->newsRepository->findByIdentifier($newsUid);

        if ($news instanceof News) {

            /** @var \GeorgRinger\News\Domain\Model\Category $newsCategory */
            foreach ($news->getCategories() as $newsCategory) {

                $alertsCategory = $this->categoryRepository->findByIdentifier($newsCategory->getUid());
                /** @var \RKW\RkwAlerts\Domain\Model\Category $alertsCategory */
                if (
                    $alertsCategory instanceof Category
                    && $alertsCategory->getTxRkwalertsEnableAlerts()
                ) {
                    $categoryList[] = $alertsCategory;
                }
            }

            return $categoryList;
        }

        return $categoryList;
    }


    /**
     * Checks if frontend user has subscribed to the given category
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \RKW\RkwAlerts\Domain\Model\Category $category
     * @return bool
     */
    public function hasFrontendUserSubscribedToCategory (
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser,
        \RKW\RkwAlerts\Domain\Model\Category $category
    ): bool {

        if (
            ($frontendUser->getUid())
            && ($category->getUid())
            && ($this->alertRepository->findOneByFrontendUserAndCategory($frontendUser, $category))
        ) {
            return true;
        }

        return false;
    }


    /**
     * Checks if an email-address has subscribed to the given category
     *
     * @param string $email
     * @param \RKW\RkwAlerts\Domain\Model\Category $category
     * @return bool
     */
    public function hasEmailSubscribedToCategory (
        string $email,
        \RKW\RkwAlerts\Domain\Model\Category $category
    ): bool {

        if (
            ($category->getUid())
            && (
                ($frontendUser = $this->frontendUserRepository->findOneByEmail($email))
                || ($frontendUser = $this->frontendUserRepository->findOneByUsername($email))
            )
            && ($this->alertRepository->findOneByFrontendUserAndCategory($frontendUser, $category))
        ) {
            return true;
        }

        return false;
    }


    /**
     * Gets the enabled alerts from the given list
     *
     * @param QueryResultInterface $alerts
     * @return array
     */
    public function filterListBySubscribableCategory(QueryResultInterface $alerts): array {

        $result = [];
        if ($alerts->count()) {

            /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
            foreach ($alerts as $alert) {
                /** @var \RKW\RkwAlerts\Domain\Model\Category $category */
                if (
                    ($category = $alert->getCategory())
                    && ($category->getTxRkwAlertsEnableAlerts())
                ){
                    $result[] = $alert;
                }
            }
        }

        return $result;
    }


    /**
     * Create Alert
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Request|null $request
     * @param array $newAlertsArray
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return array
     * @throws \RKW\RkwAlerts\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function createAlertLoggedUser (
        \TYPO3\CMS\Extbase\Mvc\Request $request,
        array $newAlertsArray,
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
    ) : array  {

        // check if user is logged in
        if (
            ! $frontendUser->_isNew()
            && FrontendUserSessionUtility::isUserLoggedIn($frontendUser)
        ) {

            foreach ($newAlertsArray as $alert) {


                // check if subscription exists already based on email
                if (
                    ($frontendUser->getEmail())
                    && ($this->hasEmailSubscribedToCategory($frontendUser->getEmail(), $alert->getCategory()))
                ){

                    $this->createFlashMessage(
                        LocalizationUtility::translate('alertManager.error.alreadySubscribed', 'rkw_alerts'),
                        '',
                        FlashMessage::INFO
                    );

                    // do not handle that alert
                    continue;
                }

                try {

                    // save alert
                    if ($this->saveAlert($alert, $frontendUser, false)) {

                        // add privacy info
                        \Madj2k\FeRegister\DataProtection\ConsentHandler::add(
                            $request,
                            $frontendUser,
                            $alert,
                            'new alert'
                        );

                        // log it
                        $this->getLogger()->log(
                            LogLevel::INFO,
                            sprintf(
                                'Successfully created alert for user with uid %s.',
                                $frontendUser->getUid()
                            )
                        );

                        $this->createFlashMessage(
                            LocalizationUtility::translate('alertController.message.create_1', 'rkw_alerts'),
                        );
                    }

                } catch (\Exception $e) {

                    // log error
                    $this->getLogger()->log(
                        LogLevel::ERROR,
                        sprintf(
                            'Could not create alert for existing user with uid %s: %s',
                            $frontendUser->getUid(),
                            $e->getMessage()
                        )
                    );
                }
            }
        }

        return $this->getFlashMessages();
    }


    /**
     * Create Alert
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Request|null $request
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param string $email
     * @param array $newAlertsArray
     * @return FlashMessage
     * @throws \RKW\RkwAlerts\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function createAlertByEmail (
        \TYPO3\CMS\Extbase\Mvc\Request $request,
        \RKW\RkwAlerts\Domain\Model\Alert $alert,
        string $email,
        array $newAlertsArray
    ) : array  {

        // check given e-mail
        if (! \Madj2k\FeRegister\Utility\FrontendUserUtility::isEmailValid($email)) {

            $this->createFlashMessage(
                LocalizationUtility::translate('alertManager.error.invalidEmail', 'rkw_alerts'),
                '',
                FlashMessage::WARNING
            );
            return $this->getFlashMessages();
        }



        // @toDo: I think: Do not show a message for the user. We would show to public which categories are already subscribed
        // check if subscription exists already based on email
        /*
        if (
            ($email)
            && ($this->hasEmailSubscribedToCategory($email, $alert->getCategory()))
        ){
            throw new Exception('alertManager.error.alreadySubscribed');
        }
        */


        // register new user or simply send opt-in to existing user
        // we also submit the email as additional data to register-function since a logged in user
        // may use a different email and we have to update it after(!!!) opt-in!
        try {

            //    DebuggerUtility::var_dump($alert); exit;

            /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
            $frontendUser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(FrontendUser::class);
            $frontendUser->setEmail($email);

            /** @var \Madj2k\FeRegister\Registration\FrontendUserRegistration $registration */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
            $registration = $objectManager->get(FrontendUserRegistration::class);
            $registration->setFrontendUser($frontendUser)
                ->setData($newAlertsArray)
                ->setDataParent($alert->getCategory())
                ->setCategory('rkwAlerts')
                ->setRequest($request)
                ->startRegistration();

            // log it
            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Successfully created alert for user with email %s.',
                    $email
                )
            );

            $this->createFlashMessage(
                LocalizationUtility::translate('alertController.message.create_2', 'rkw_alerts'),
            );
            return $this->getFlashMessages();

        } catch (\Exception $e) {

            // log error
            $this->getLogger()->log(
                \TYPO3\CMS\Core\Log\LogLevel::ERROR,
                sprintf(
                    'Could not create alert for user with email %s: %s',
                    $email,
                    $e->getMessage()
                )
            );
        }

        return $this->getFlashMessages();
    }


    /**
     * saveAlert
     *
     * @param array $alertList
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser|null $frontendUser
     * @return bool
     * @throws \RKW\RkwAlerts\Exception
     */
    public function saveAlertList (
        array $alertList,
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser = null
    ): bool {

        foreach ($alertList as $alert) {
            // @toDo: Do something with the return values?
            $this->saveAlert($alert, $frontendUser, false);

            // log
            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Saved alert with uid %s of user with uid %s.',
                    $alert->getUid(),
                    $frontendUser->getUid()
                )
            );
        }

        // trigger signal slot
        $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            self::SIGNAL_AFTER_ALERT_CREATED,
            array($frontendUser, $alertList)
        );

        return true;
    }


    /**
     * saveAlert
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser|null $frontendUser
     * @param bool $sendSuccessEmailForEverySingleAlert Needed for summery emails. Solve that thing a better way?
     * @return bool
     * @throws \RKW\RkwAlerts\Exception
     */
    public function saveAlert (
        \RKW\RkwAlerts\Domain\Model\Alert $alert,
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser = null,
        bool $sendSuccessEmailForEverySingleAlert = true
    ): bool {

        try {

            // #######################################################################
            // ### START: Basic system checks. Throw error if something went wrong ###
            // check frontendUser
            if (! $frontendUser) {
                throw new Exception('alertManager.error.frontendUserMissing');
            }
            if ($frontendUser->_isNew()) {
                throw new Exception('alertManager.error.frontendUserNotPersisted');
            }
            // check alert
            if (! $alert->_isNew()) {
                throw new Exception('alertManager.error.alertAlreadyPersisted');
            }
            // check if alert has subscribable category
            if (
                (! $category = $alert->getCategory())
                || (! $category->getTxRkwalertsEnableAlerts())
            ){
                throw new Exception('alertManager.error.categoryInvalid');
            }
            // ### END: Basic system checks.                                       ###
            // #######################################################################


            // check if subscription exists already
            if ($this->hasFrontendUserSubscribedToCategory($frontendUser, $alert->getCategory())) {
                //throw new Exception('alertManager.error.alreadySubscribed');
                $this->createFlashMessage(
                    LocalizationUtility::translate('alertManager.error.alreadySubscribed', 'rkw_alerts'),
                    '',
                    FlashMessage::WARNING
                );

                return false;
            }

            // add frontendUser to alert
            $alert->setFrontendUser($frontendUser);

            // save it
            $this->alertRepository->add($alert);
            $this->persistenceManager->persistAll();

            // trigger signal slot
            if ($sendSuccessEmailForEverySingleAlert) {
                $this->signalSlotDispatcher->dispatch(
                    __CLASS__,
                    self::SIGNAL_AFTER_ALERT_CREATED,
                    array($frontendUser, [$alert])
                );
            }


            // log
            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Saved alert with uid %s of user with uid %s.',
                    $alert->getUid(),
                    $frontendUser->getUid()
                )
            );

            return true;

        } catch (\Exception $e) {

            // log error
            $this->getLogger()->log(
                LogLevel::ERROR,
                sprintf(
                    'Could not create alert for user with uid %s: %s',
                    $frontendUser->getUid(),
                    $e->getMessage()
                )
            );
        }

        return false;
    }


    /**
     * Save alert by registration
     * Used by SignalSlot
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @api Used by SignalSlot
     */
    public function saveAlertByRegistration(
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser,
        \Madj2k\FeRegister\Domain\Model\OptIn $optIn
    ) {

        /** @var array $alertArray */
        $alertArray = $optIn->getData();

        if (is_countable($alertArray)) {
            try {
                $this->saveAlertList($alertArray, $frontendUser);
            } catch (\RKW\RkwAlerts\Exception $exception) {
                // do nothing here
            }
        }


    }


    /**
     * deleteAlert
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser|null $frontendUser
     * @return bool
     * @throws \RKW\RkwAlerts\Exception
     */
    public function deleteAlert (
        \RKW\RkwAlerts\Domain\Model\Alert $alert,
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser = null
    ): bool {

        // check frontendUser
        if (! $frontendUser) {
            throw new Exception('alertManager.error.frontendUserNotLoggedIn');
        }

        if ($frontendUser->_isNew()) {
            throw new Exception('alertManager.error.frontendUserNotPersisted');
        }

        // check alert
        if ($alert->_isNew()) {
            throw new Exception('alertManager.error.alertNotPersisted');
        }

        // check if alert belongs to given user
        // frontend user may be deleted already at this point, therefore we load the raw data
        // and compare the uids of the frontend user
        if (
            ($alert->getFrontendUser() !== $frontendUser)
            && (
                ($alertRaw = $this->alertRepository->findByIdentifierRaw($alert->getUid()))
                && ($alertRaw['frontend_user'] !== $frontendUser->getUid())
            )
        ) {
            throw new Exception('alertManager.error.frontendUserInvalid');
        }

        try {

            // delete it
            $this->alertRepository->remove($alert);
            $this->persistenceManager->persistAll();

            // @toDo: Hint: This SignalSlot is currently not connected!
            // trigger signal slot
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL_AFTER_ALERT_DELETED,
                array($frontendUser, $alert)
            );

            // log
            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Deleted alert with uid %s of user with uid %s.',
                    $alert->getUid(),
                    $frontendUser->getUid()
                )
            );

            return true;

        } catch (\Exception $e) {

            // log error
            $this->getLogger()->log(
                LogLevel::ERROR,
                sprintf(
                    'Could not delete alert with uid %s of user with uid %s: %s',
                    $alert->getUid(),
                    $frontendUser->getUid(),
                    $e->getMessage()
                )
            );
        }

        return false;
    }


    /**
     * deleteAlerts
     *
     * @param QueryResultInterface $alerts
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param int $counter
     * @return bool
     */
    public function deleteAlerts (
        QueryResultInterface $alerts,
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser,
        int &$counter = 0
    ): bool {

        $counter = 0;
        $status = true;

        if (count($alerts->toArray()) > 0) {

            /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
            foreach ($alerts as $alert) {

                try {
                    // delete alert and count it
                    if ($this->deleteAlert($alert, $frontendUser)) {
                        $counter++;
                    }

                } catch (\Exception $e) {

                    // log error and continue!
                    $status = false;
                    $this->getLogger()->log(
                        LogLevel::ERROR,
                        sprintf(
                            'Error while trying to delete a list of alerts of user with uid %s: %s',
                            $frontendUser->getUid(),
                            $e->getMessage()
                        )
                    );
                }
            }

            if ($counter) {
                return $status;
            }
        }

        return false;
    }


    /**
     * deleteAlertsByFrontendEndUser
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return void
     * @api Used by SignalSlot
     */
    public function deleteAlertsByFrontendEndUser (
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
    ) {

        try {

            $counter = 0;

            // delete all alerts of user
            /** @var QueryResultInterface $alerts */
            $alerts = $this->alertRepository->findByFrontendUser($frontendUser);
            $this->deleteAlerts($alerts, $frontendUser, $counter);

            // send final confirmation mail to user
            if ($counter) {

                $this->signalSlotDispatcher->dispatch(
                    __CLASS__,
                    self::SIGNAL_AFTER_ALERT_DELETED_ALL,
                    [
                        $frontendUser,
                        $alerts,
                    ]
                );

                // log
                $this->getLogger()->log(
                    LogLevel::INFO,
                    sprintf(
                        'Deleted all alerts of user with uid %s.',
                        $frontendUser->getUid()
                    )
                );

            }

        } catch (\Exception $e) {

            // log error
            $this->getLogger()->log(
                LogLevel::ERROR,
                sprintf(
                    'Error while trying to delete all alerts of user with uid %s: %s',
                    $frontendUser->getUid(),
                    $e->getMessage()
                )
            );
        }
    }


    /**
     * Gets an associative array with the categories to notify
     * and the news to link to
     *
     * @param string $filterField
     * @param int $timeSinceCreation
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function getNewsAndCategoriesToNotify(
        string $filterField,
        int $timeSinceCreation = 432000
    ): array {

        $result = [];

        if ($newsList = $this->newsRepository->findAllToNotify($filterField, $timeSinceCreation)) {

            /**  @var \RKW\RkwAlerts\Domain\Model\News $news */
            foreach ($newsList as $news) {

                foreach ($news->getCategories() as $category) {

                    if (! isset($result[$category->getUid()])) {

                        /** @var ObjectStorage $newsObjectStorage */
                        $newsObjectStorage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectStorage::class);
                        $newsObjectStorage->attach($news);

                        $result[$category->getUid()] = [
                            'category' => $category,
                            'news' => $newsObjectStorage,
                        ];

                    } else {
                        if ($result[$category->getUid()]['news'] instanceof ObjectStorage) {
                            $result[$category->getUid()]['news']->attach($news);
                        }
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Sends the notifications
     *
     * @param string $filterField
     * @param int $timeSinceCreation
     * @param string $debugMail
     * @return int
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function sendNotification(
        string $filterField,
        int $timeSinceCreation = 432000,
        string $debugMail = ''
    ): int {

        // load categories to notify
        $recipientCountGlobal = 0;
        if ($results = $this->getNewsAndCategoriesToNotify($filterField, $timeSinceCreation)) {

            // get configuration
            $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            // build e-mails per project
            foreach ($results as $categoryUid => $subArray) {

                // check of basic information!
                /** @var \RKW\RkwAlerts\Domain\Model\Category $category */
                if (
                    ($category = $subArray['category'])
                    && ($newsList = $subArray['news'])
                ) {

                    // find all alerts for category
                    /** @var \RKW\RkwAlerts\Domain\Model\Category $category */
                    if ($alerts = $this->alertRepository->findByCategory($category)) {

                        try {

                            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
                            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

                            // set recipients
                            /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
                            foreach ($alerts as $alert) {

                                // check if FE-User exists
                                if (
                                    ($frontendUser = $alert->getFrontendUser())
                                    && ($frontendUser instanceof \Madj2k\FeRegister\Domain\Model\FrontendUser)
                                ) {

                                    $recipient = $frontendUser;
                                    if ($debugMail) {
                                        $recipient = ['email' => $debugMail];
                                    }

                                    $mailService->setTo(
                                        $recipient,
                                        array(
                                            'marker'  => array(
                                                'alert'        => $alert,
                                                'frontendUser' => $frontendUser,
                                                'loginPid'     => intval($settings['settings']['loginPid']),
                                                'news'        => $newsList,
                                            ),
                                            'subject' => FrontendLocalizationUtility::translate(
                                                'rkwMailService.sendAlert.subject',
                                                'rkw_alerts',
                                                [$category->getTitle()],
                                                $frontendUser->getTxFeregisterLanguageKey() ?: 'default'
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
                                    [$category->getTitle()],
                                    $settings['settings']['defaultLanguageKey'] ?: 'default'
                                )
                            );

                            // send mail
                            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
                            $mailService->getQueueMail()->addPartialPaths($settings['view']['partialRootPaths']);
                            $mailService->getQueueMail()->setPlaintextTemplate('Email/Alert');
                            $mailService->getQueueMail()->setHtmlTemplate('Email/Alert');
                            $mailService->getQueueMail()->setType(2);

                            if ($recipientCount = count($mailService->getTo())) {

                                $recipientCountGlobal += $recipientCount;
                                $mailService->send();
                                $this->getLogger()->log(
                                    LogLevel::INFO,
                                    sprintf(
                                        'Successfully sent alert notification for category with id %s with %s recipients.',
                                        $categoryUid,
                                        $recipientCount
                                    )
                                );

                            } else {
                                $this->getLogger()->log(
                                    LogLevel::DEBUG,
                                    sprintf(
                                        'No valid recipients found for alert notification for category with id %s.',
                                        $categoryUid
                                    )
                                );
                            }

                        } catch (\Exception $e) {

                            // log error
                            $this->getLogger()->log(
                                LogLevel::ERROR,
                                sprintf(
                                    'Error while trying to send an alert notification for category with uid %s: %s',
                                    $categoryUid,
                                    $e->getMessage()
                                )
                            );
                        }
                    }

                    if ($debugMail) {
                        $this->getLogger()->log(
                            LogLevel::WARNING,
                            sprintf(
                                'You are running this script in debug-mode. All e-mails are sent to %s. News will not be marked as sent.',
                                $debugMail
                            )
                        );

                     // no matter what happens: mark pages as sent
                    } else {
                        /** @var \RKW\RkwAlerts\Domain\Model\News $news */
                        foreach ($newsList as $news) {
                            $news->setTxRkwalertsSendStatus(1);
                            $this->newsRepository->update($news);
                        }

                        // persist
                        $this->persistenceManager->persistAll();
                    }
                }
            }

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Found %s categories for alert notifications.',
                    count($results)
                )
            );

        } else {
            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf('No categories found for alert notifications.')
            );
        }

        return $recipientCountGlobal;
    }

    /**
     * @param string $message
     * @param string $title
     * @param int    $severity
     * @param bool   $storeInSession
     * @return void
     */
    protected function createFlashMessage (
        string $message,
        string $title = '',
        int $severity = AbstractMessage::OK,
        bool $storeInSession = false
    ) {
        $flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            FlashMessage::class,
            $message, $title, $severity, $storeInSession
        );
        $this->addFlashMessage($flashMessage);
    }


    /**
     * @param FlashMessage $flashMessage
     * @return void
     */
    protected function addFlashMessage (FlashMessage $flashMessage): void {
        $this->flashMessageContainer[] = $flashMessage;
    }

    /**
     * @return array
     */
    public function getFlashMessages (): array {
        $returnArray = $this->flashMessageContainer;
        // clear
        //$this->flashMessageContainer = [];
        return $returnArray;
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
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
    protected function getSettings(string $which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS): array
    {
        return GeneralUtility::getTypoScriptConfiguration('Rkwalerts', $which);
    }

}

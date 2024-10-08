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

use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\Postmaster\Mail\MailMessage;
use RKW\RkwAlerts\Domain\Model\Project;
use RKW\RkwAlerts\Domain\Repository\AlertRepository;
use RKW\RkwAlerts\Domain\Repository\PageRepository;
use RKW\RkwAlerts\Domain\Repository\ProjectRepository;
use RKW\RkwAlerts\Exception;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as FrontendLocalizationUtility;


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
     * pagesRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\PageRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?PageRepository $pageRepository = null;


    /**
     * projectsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\ProjectRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?ProjectRepository $projectRepository = null;


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
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     */
    public function injectAlertRepository (AlertRepository $alertRepository)
    {
        $this->alertRepository = $alertRepository;
    }

    /**
     * @var \RKW\RkwAlerts\Domain\Repository\PageRepository
     */
    public function injectPageRepository (PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * @var \RKW\RkwAlerts\Domain\Repository\ProjectRepository
     */
    public function injectProjectRepository (ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     */
    public function injectFrontendUserRepository (FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
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
     * Gets the project of the given page-id and also checks if the page has a project set and
     * alerts are activated for that project
     *
     * @param int $pid The page uid
     * @return \RKW\RkwAlerts\Domain\Model\Project|null
     */
    public function getSubscribableProjectByPageUid(int $pid):? Project
    {
        /**
         * @var $page \RKW\RkwAlerts\Domain\Model\Page
         * @var $projectTemp \RKW\RkwProjects\Domain\Model\Projects
         * @var $project \RKW\RkwAlerts\Domain\Model\Project
         */
        if (
            ($page = $this->pageRepository->findByIdentifier($pid))
            && ($projectTemp = $page->getTxRkwprojectsProjectUid())
            && ($project = $this->projectRepository->findByIdentifier($projectTemp->getUid()))
            && ($project->getTxRkwAlertsEnableAlerts())
        ) {
            return $project;
        }

        return null;
    }


    /**
     * Checks if frontend user has subscribed to the given project
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \RKW\RkwAlerts\Domain\Model\Project $project
     * @return bool
     */
    public function hasFrontendUserSubscribedToProject (
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser,
        \RKW\RkwAlerts\Domain\Model\Project $project
    ): bool {

        if (
            ($frontendUser->getUid())
            && ($project->getUid())
            && ($this->alertRepository->findOneByFrontendUserAndProject($frontendUser, $project))
        ) {
            return true;
        }

        return false;
    }


    /**
     * Checks if an email-address has subscribed to the given project
     *
     * @param string $email
     * @param \RKW\RkwAlerts\Domain\Model\Project $project
     * @return bool
     */
    public function hasEmailSubscribedToProject (
       string $email,
        \RKW\RkwAlerts\Domain\Model\Project $project
    ): bool {

        if (
           ($project->getUid())
            && (
                ($frontendUser = $this->frontendUserRepository->findOneByEmail($email))
                || ($frontendUser = $this->frontendUserRepository->findOneByUsername($email))
           )
            && ($this->alertRepository->findOneByFrontendUserAndProject($frontendUser, $project))
        ) {
            return true;
        }

        return false;
    }


    /**
     * Gets the enabled alerts from the given list
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts
     * @return array
     */
    public function filterListBySubscribableProjects (
        \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts
    ): array {

        $result = [];
        if (count($alerts->toArray())) {

            /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
            foreach ($alerts as $alert) {
                if (
                    ($project = $alert->getProject())
                    && ($project->getTxRkwAlertsEnableAlerts())
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
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser|null $frontendUser
     * @param \TYPO3\CMS\Extbase\Mvc\Request|null $request
     * @param string $email
     * @return int
     * @throws \RKW\RkwAlerts\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function createAlert (
        \TYPO3\CMS\Extbase\Mvc\Request $request,
        \RKW\RkwAlerts\Domain\Model\Alert $alert,
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser = null,
        string $email = ''
    ) : int  {

        // settings for logged-in users
        if (
            ($frontendUser)
            && (! $frontendUser->_isNew())
        ) {
            $email = $frontendUser->getEmail();
        }

        // check given e-mail
        if (! \Madj2k\FeRegister\Utility\FrontendUserUtility::isEmailValid($email)) {
            throw new Exception('alertManager.error.invalidEmail');
        }

        // check if alert has subscribable project
        if (
            (! $project = $alert->getProject())
            || (! $project->getTxRkwalertsEnableAlerts())
        ){
            throw new Exception('alertManager.error.projectInvalid');
        }

        // check if subscription exists already based on email
        if (
            ($email)
            && ($this->hasEmailSubscribedToProject($email, $alert->getProject()))
        ){
            throw new Exception('alertManager.error.alreadySubscribed');
        }

        //==========================================================
        // check if user is logged in
        if (
            ($frontendUser)
            && (! $frontendUser->_isNew())
            && (FrontendUserSessionUtility::isUserLoggedIn($frontendUser))
        ) {

            // check if subscription exists already
            if ($this->hasFrontendUserSubscribedToProject($frontendUser, $alert->getProject())) {
                throw new Exception('alertManager.error.alreadySubscribed');
            }

            try {

                // save alert
                if ($this->saveAlert($alert, $frontendUser)) {

                    // add privacy info
                    \Madj2k\FeRegister\DataProtection\ConsentHandler::add(
                        $request,
                        $frontendUser,
                        $alert,
                        'new alert'
                    );

                    // log it
                    $this->getLogger()->log(
                        \TYPO3\CMS\Core\Log\LogLevel::INFO,
                        sprintf(
                            'Successfully created alert for user with uid %s.',
                            $frontendUser->getUid()
                        )
                    );

                    return 1;
                }

            } catch (\Exception $e) {

                // log error
                $this->getLogger()->log(
                    \TYPO3\CMS\Core\Log\LogLevel::ERROR,
                    sprintf(
                        'Could not create alert for existing user with uid %s: %s',
                        $frontendUser->getUid(),
                        $e->getMessage()
                    )
                );
            }

        //==========================================================
        // if user is NOT logged in, we send an opt-in mail
        // this covers two cases:
        // 1) user already exists
        // 2) user does not exist and needs to be created
        } else {

            // register new user or simply send opt-in to existing user
            // we also submit the email as additional data to register-function since a logged in user
            // may use a different email and we have to update it after(!!!) opt-in!
            try {

                /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
                $frontendUser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(FrontendUser::class);
                $frontendUser->setEmail($email);

                /** @var \Madj2k\FeRegister\Registration\FrontendUserRegistration $registration */
                $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
                $registration = $objectManager->get(FrontendUserRegistration::class);
                $registration->setFrontendUser($frontendUser)
                    ->setData($alert)
                    ->setDataParent($alert->getProject())
                    ->setCategory('rkwAlerts')
                    ->setRequest($request)
                    ->startRegistration();

                // log it
                $this->getLogger()->log(
                    \TYPO3\CMS\Core\Log\LogLevel::INFO,
                    sprintf(
                        'Successfully created alert for user with email %s.',
                        $email
                    )
                );

                return 2;

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
        }

        return 0;
    }


    /**
     * saveAlert
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $alert
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser|null $frontendUser
     * @return bool
     * @throws \RKW\RkwAlerts\Exception
     */
    public function saveAlert (
        \RKW\RkwAlerts\Domain\Model\Alert $alert,
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser = null
    ): bool {

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

        // check if alert has subscribable project
        if (
            (! $project = $alert->getProject())
            || (! $project->getTxRkwalertsEnableAlerts())
        ){
            throw new Exception('alertManager.error.projectInvalid');
        }

        // check if subscription exists already
        if ($this->hasFrontendUserSubscribedToProject($frontendUser, $alert->getProject())) {
            throw new Exception('alertManager.error.alreadySubscribed');
        }

        try {

            // add frontendUser to alert
            $alert->setFrontendUser($frontendUser);

            // save it
            $this->alertRepository->add($alert);
            $this->persistenceManager->persistAll();

            // trigger signal slot
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL_AFTER_ALERT_CREATED,
                array($frontendUser, $alert)
            );

            // log
            $this->getLogger()->log(
                \TYPO3\CMS\Core\Log\LogLevel::INFO,
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
                \TYPO3\CMS\Core\Log\LogLevel::ERROR,
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

        if (
            ($alert = $optIn->getData())
            && ($alert instanceof \RKW\RkwAlerts\Domain\Model\Alert)
        ) {

            try {
                $this->saveAlert($alert, $frontendUser);
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

            // trigger signal slot
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL_AFTER_ALERT_DELETED,
                array($frontendUser, $alert)
            );

            // log
            $this->getLogger()->log(
                \TYPO3\CMS\Core\Log\LogLevel::INFO,
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
                \TYPO3\CMS\Core\Log\LogLevel::ERROR,
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
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param int $counter
     * @return bool
     */
    public function deleteAlerts (
        \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts,
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
                        \TYPO3\CMS\Core\Log\LogLevel::ERROR,
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
            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
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
                    \TYPO3\CMS\Core\Log\LogLevel::INFO,
                    sprintf(
                        'Deleted all alerts of user with uid %s.',
                        $frontendUser->getUid()
                    )
                );

            }

        } catch (\Exception $e) {

            // log error
            $this->getLogger()->log(
                \TYPO3\CMS\Core\Log\LogLevel::ERROR,
                sprintf(
                    'Error while trying to delete all alerts of user with uid %s: %s',
                    $frontendUser->getUid(),
                    $e->getMessage()
                )
            );
        }
    }


    /**
     * Gets an associative array with the projects to notify
     * and the pages to link to
     *
     * @param string $filterField
     * @param int $timeSinceCreation
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function getPagesAndProjectsToNotify(
        string $filterField,
        int $timeSinceCreation = 432000
    ): array {

        $result = [];

        if ($pages = $this->pageRepository->findAllToNotify($filterField, $timeSinceCreation)) {

            /**  @var \RKW\RkwAlerts\Domain\Model\Page $page */
            foreach ($pages as $page) {

                $projectId = $page->getTxRkwprojectsProjectUid()->getUid();

                if (! isset($result[$projectId])) {

                    /** @var ObjectStorage $pagesObjectStorage */
                    $pagesObjectStorage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectStorage::class);
                    $pagesObjectStorage->attach($page);

                    /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
                    $project = $this->projectRepository->findByIdentifier($page->getTxRkwprojectsProjectUid()->getUid());
                    $result[$projectId] = [
                        'project' => $project,
                        'pages' => $pagesObjectStorage
                    ];

                } else {
                    if ($result[$projectId]['pages'] instanceof ObjectStorage) {
                        $result[$projectId]['pages']->attach($page);
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

        // load projects to notify
        $recipientCountGlobal = 0;
        if ($results = $this->getPagesAndProjectsToNotify($filterField, $timeSinceCreation)) {

            // get configuration
            $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            // build e-mails per project
            foreach ($results as $projectId => $subArray) {

                // check of basic information!
                /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
                if (
                    ($project = $subArray['project'])
                    && ($pages = $subArray['pages'])
                ) {

                    // find all alerts for project
                    /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
                    if ($alerts = $this->alertRepository->findByProject($project)) {

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
                                                'pages'        => $pages
                                            ),
                                            'subject' => FrontendLocalizationUtility::translate(
                                                'rkwMailService.sendAlert.subject',
                                                'rkw_alerts',
                                                [$project->getName()],
                                                $frontendUser->getTxFeregisterLanguageKey() ? $frontendUser->getTxFeregisterLanguageKey() : 'default'
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
                                    [$project->getName()],
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

                                $recipientCountGlobal += $recipientCount;
                                $mailService->send();
                                $this->getLogger()->log(
                                    \TYPO3\CMS\Core\Log\LogLevel::INFO,
                                    sprintf(
                                        'Successfully sent alert notification for project with id %s with %s recipients.',
                                        $projectId,
                                        $recipientCount
                                    )
                                );

                            } else {
                                $this->getLogger()->log(
                                    \TYPO3\CMS\Core\Log\LogLevel::DEBUG,
                                    sprintf(
                                        'No valid recipients found for alert notification for project with id %s.',
                                        $projectId
                                    )
                                );
                            }

                        } catch (\Exception $e) {

                            // log error
                            $this->getLogger()->log(
                                \TYPO3\CMS\Core\Log\LogLevel::ERROR,
                                sprintf(
                                    'Error while trying to send an alert notification for project with uid %s: %s',
                                    $projectId,
                                    $e->getMessage()
                                )
                            );
                        }
                    }

                    if ($debugMail) {
                        $this->getLogger()->log(
                            \TYPO3\CMS\Core\Log\LogLevel::WARNING,
                            sprintf(
                                'You are running this script in debug-mode. All e-mails are sent to %s. Pages will not be marked as sent.',
                                $debugMail
                            )
                        );

                     // no matter what happens: mark pages as sent
                    } else {
                        /** @var \RKW\RkwAlerts\Domain\Model\Page $page */
                        foreach ($pages as $page) {
                            $page->setTxRkwalertsSendStatus(1);
                            $this->pageRepository->update($page);
                        }

                        // persist
                        $this->persistenceManager->persistAll();
                    }
                }
            }

            $this->getLogger()->log(
                \TYPO3\CMS\Core\Log\LogLevel::INFO,
                sprintf(
                    'Found %s projects for alert notifications.',
                    count($results)
                )
            );

        } else {
            $this->getLogger()->log(
                \TYPO3\CMS\Core\Log\LogLevel::INFO,
                sprintf('No projects found for  alert notifications.')
            );
        }

        return $recipientCountGlobal;
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

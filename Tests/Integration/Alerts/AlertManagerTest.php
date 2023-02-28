<?php
namespace RKW\RkwAlerts\Tests\Integration\Alerts;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

use RKW\RkwAlerts\Alerts\AlertManager;
use RKW\RkwAlerts\Domain\Model\Alert;
use RKW\RkwAlerts\Domain\Model\Page;
use RKW\RkwAlerts\Domain\Model\Project;
use RKW\RkwAlerts\Domain\Repository\AlertRepository;
use RKW\RkwAlerts\Domain\Repository\PageRepository;
use RKW\RkwAlerts\Domain\Repository\ProjectRepository;
use Madj2k\Postmaster\Domain\Repository\QueueMailRepository;
use Madj2k\Postmaster\Domain\Repository\QueueRecipientRepository;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\OptIn;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\OptInRepository;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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
 * AlertManagerTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertManagerTest extends FunctionalTestCase
{

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/core_extended',
        'typo3conf/ext/postmaster',
        'typo3conf/ext/fe_register',
        'typo3conf/ext/rkw_authors',
        'typo3conf/ext/rkw_projects',
        'typo3conf/ext/rkw_alerts',
    ];


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [];


    /**
     * @var \RKW\RkwAlerts\Alerts\AlertManager|null
     */
    private ?AlertManager $subject = null;


    /**
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository|null
     */
    private ?AlertRepository $alertRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository|null
     */
    private ?FrontendUserRepository $frontendUserRepository = null;


    /**
     * @var \RKW\RkwAlerts\Domain\Repository\PageRepository|null
     */
    private ?PageRepository $pageRepository = null;


    /**
     * @var \RKW\RkwAlerts\Domain\Repository\ProjectRepository|null
     */
    private ?ProjectRepository $projectRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\OptInRepository|null
     */
    private ?OptInRepository $optInRepository = null;


    /**
     * @var \Madj2k\Postmaster\Domain\Repository\QueueMailRepository|null
     */
    private ?QueueMailRepository $queueMailRepository = null;


    /**
     * @var \Madj2k\Postmaster\Domain\Repository\QueueRecipientRepository|null
     */
    private ?QueueRecipientRepository $queueRecipientRepository = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager|null
     */
    private ?PersistenceManager $persistenceManager = null;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    private ?ObjectManager $objectManager = null;


    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/AlertManagerTest/Fixtures';


    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // defaults for postmaster
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'service@rkw.de';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'RKW';

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Global.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:rkw_coreextended/Configuration/TypoScript/setup.txt',
                'EXT:postmaster/Configuration/TypoScript/setup.txt',
                'EXT:fe_register/Configuration/TypoScript/setup.txt',
                'EXT:rkw_authors/Configuration/TypoScript/setup.txt',
                'EXT:rkw_projects/Configuration/TypoScript/setup.txt',
                'EXT:rkw_alerts/Configuration/TypoScript/setup.txt',
                'EXT:rkw_alerts/Tests/Functional/Alerts/Fixtures/Frontend/Configuration/Rootpage.typoscript',
            ]
        );


        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->subject = $this->objectManager->get(AlertManager::class);

        $this->alertRepository = $this->objectManager->get(AlertRepository::class);
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);
        $this->pageRepository = $this->objectManager->get(PageRepository::class);
        $this->projectRepository = $this->objectManager->get(ProjectRepository::class);
        $this->optInRepository = $this->objectManager->get(OptInRepository::class);
        $this->queueMailRepository = $this->objectManager->get(QueueMailRepository::class);
        $this->queueRecipientRepository = $this->objectManager->get(QueueRecipientRepository::class);

    }

    //=============================================

    /**
     * @test
     */
    public function getSubscribableProjectByPageUidReturnsNullIfInvalidPageUidGiven ()
    {

        /**
        * Scenario:
        *
        * Given a non-existent page uid is set as parameter
        * When I call the method
        * Then null is returned
        */
        self::assertNull($this->subject->getSubscribableProjectByPageUid(99));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getSubscribableProjectByPageUidReturnsNullIfValidPageUidWithoutProjectGiven ()
    {

        /**
         * Scenario:
         *
         * Given an existent page uid is set as parameter
         * Given that page has no project set
         * When I call the method
         * Then null is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        self::assertNull($this->subject->getSubscribableProjectByPageUid(10));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getSubscribableProjectByPageUidReturnsNullIfDisabledProjectGiven ()
    {

        /**
         * Scenario:
         *
         * Given an existent page uid is set as parameter
         * Given that page has a project set
         * Given that project is not activated for alerts
         * When I call the method
         * Then null is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');


        self::assertNull($this->subject->getSubscribableProjectByPageUid(20));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getSubscribableProjectByPageUidReturnsProject ()
    {

        /**
         * Scenario:
         *
         * Given an existent page uid is set as parameter
         * Given that page has a project set
         * Given that project is activated for alerts
         * When I call the method
         * Then the project is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        self::assertInstanceOf(
            \RKW\RkwAlerts\Domain\Model\Project::class,
            $this->subject->getSubscribableProjectByPageUid(30)
        );
    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function hasFrontendUserSubscribedToProjectReturnsFalseIfFeUserNotPersisted ()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted FE-User
         * Given a persisted project
         * When I call the method
         * Then false returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check40.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $feUser */
        $feUser = $this->objectManager->get(\Madj2k\FeRegister\Domain\Model\FrontendUser::class);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(40);

        self::assertFalse( $this->subject->hasFrontendUserSubscribedToProject($feUser, $project));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function hasFrontendUserSubscribedToProjectReturnsFalseIfProjectNotPersisted ()
    {

        /**
         * Scenario:
         *
         * Given a persisted FE-User
         * Given a non-persisted project
         * When I call the method
         * Then false returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check50.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $feUser */
        $feUser = $this->frontendUserRepository->findByIdentifier(50);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->objectManager->get(\RKW\RkwAlerts\Domain\Model\Project::class);
        $project->setTxRkwalertsEnableAlerts(true);

        self::assertFalse( $this->subject->hasFrontendUserSubscribedToProject($feUser, $project));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function hasFrontendUserSubscribedToProjectReturnsTrueIfAlreadySubscribed ()
    {

        /**
         * Scenario:
         *
         * Given a persisted FE-User
         * Given a persisted project
         * Given the user has already subscribed the project
         * When I call the method
         * Then true returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $feUser */
        $feUser = $this->frontendUserRepository->findByIdentifier(60);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(60);

        self::assertTrue( $this->subject->hasFrontendUserSubscribedToProject($feUser, $project));
    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function hasEmailSubscribedToProjectReturnsFalseIfProjectNotPersisted ()
    {

        /**
         * Scenario:
         *
         * Given an email-address
         * Given that email-address belongs to a persisted frontend user
         * Given a non-persisted project
         * When I call the method
         * Then false returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check50.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->objectManager->get(\RKW\RkwAlerts\Domain\Model\Project::class);
        $project->setTxRkwalertsEnableAlerts(true);

        self::assertFalse( $this->subject->hasEmailSubscribedToProject('teste-email@test.de', $project));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function hasEmailSubscribedToProjectReturnsTrueIfAlreadySubscribed ()
    {

        /**
         * Scenario:
         *
         * Given an email-address
         * Given that email-address belongs to a persisted frontend user
         * Given a persisted project
         * Given the email-address has already subscribed the project
         * When I call the method
         * Then true returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(60);

        self::assertTrue( $this->subject->hasEmailSubscribedToProject('teste-email@test.de', $project));
    }

    //=============================================

    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     */
    public function createAlertChecksForValidEmail ()
    {

        /**
         * Scenario:
         *
         * Given I have used an invalid email
         * When I call the method
         * Then an emailInvalid-error is thrown
        */
        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.invalidEmail');

        $this->subject->createAlert($request, $alert, null, 'invalid-email');

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
    */
    public function createAlertChecksForValidEmailOfLoggedInUser ()
    {

        /**
         * Scenario:
         *
         * Given I'm logged in
         * Given the e-mail-address of my login-user is invalid
         * When I call the method
         * Then an emailInvalid-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check80.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(80);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.invalidEmail');

        $this->subject->createAlert($request, $alert, $frontendUser, 'valid@email.de');

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function createAlertChecksForSubscribableProject ()
    {

        /**
         * Scenario:
         *
         * Given I enter a valid e-mail-address
         * Given I subscribe a non-subscribable project
         * When I call the method
         * Then a projectInvalid-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check90.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(90);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.projectInvalid');

        $this->subject->createAlert($request,$alert, null, 'valid@email.de');
    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function createAlertChecksForExistingSubscriptionByEmail ()
    {

        /**
         * Scenario:
         *
         * Given I'm logged in
         * Given the e-mail-address of my login-user is valid
         * Given I already subscribed to the given project
         * When I call the method
         * Then an alreadySubscribed-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check100.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(100);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.alreadySubscribed');

        $this->subject->createAlert($request, $alert, null, 'teste-email@test.de');
    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function createAlertChecksForExistingSubscriptionOfLoggedInUser ()
    {

        /**
         * Scenario:
         *
         * Given I'm logged in
         * Given the e-mail-address of my login-user is valid
         * Given I already subscribed to the given project
         * When I call the method
         * Then an alreadySubscribed-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check100.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(100);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(100);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.alreadySubscribed');

        $this->subject->createAlert($request, $alert, $frontendUser, '');
    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function createAlertCreatesAlertForLoggedInUser ()
    {

        /**
         * Scenario:
         *
         * Given I'm logged in
         * Given the e-mail-address of my login-user is valid
         * When I call the method
         * Then the integer value 1 is returned
         * Then an alert-object for the given project and frontend-user is created
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check110.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(110);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(110);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        $result = $this->subject->createAlert($request, $alert, $frontendUser, '');
        self::assertEquals(1, $result);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        self::assertEquals($frontendUser->getUid(), $dbAlert->getFrontendUser()->getUid());
        self::assertEquals($project->getUid(), $dbAlert->getProject()->getUid());

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function createAlertCreatesAlertForNormalUser ()
    {

        /**
         * Scenario:
         *
         * Given I'm not logged in
         * Given the e-mail-address I entered is valid
         * When I call the method
         * Then integer value 2 is returned
         * Then an registration-object for the given alert is created
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check120.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(120);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        $result = $this->subject->createAlert($request, $alert, null, 'valid@email.de');
        self::assertEquals(2, $result);

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifier(1);
        self::assertInstanceOf(OptIn::class, $optIn);
        self::assertEquals($optIn->getCategory(), 'rkwAlerts');

    }

    //=============================================

    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function saveAlertChecksForFrontendUser ()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted alert
         * Given a no frontend user
         * When I call the method
         * Then a frontendUserMissing-error is thrown
         */

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.frontendUserMissing');

        $this->subject->saveAlert($alert, null);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function saveAlertChecksForNonPersistedFrontendUser ()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted alert
         * Given a non-persisted frontend user
         * When I call the method
         * Then a frontendUserNotPersisted-error is thrown
         */

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.frontendUserNotPersisted');

        $this->subject->saveAlert($alert, $frontendUser);
    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function saveAlertChecksForPersistedAlert ()
    {

        /**
         * Scenario:
         *
         * Given a persisted alert
         * Given a persisted frontend user
         * When I call the method
         * Then an alertAlreadyPersisted-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check130.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = $this->alertRepository->findByIdentifier(130);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(130);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.alertAlreadyPersisted');

        $this->subject->saveAlert($alert, $frontendUser);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function saveAlertChecksForNonSubscribableProject ()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted alert
         * Given a persisted frontend user
         * Given the alert has a non-subscribable project set
         * When I call the method
         * Then a projectInvalid-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check140.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(140);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(140);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.projectInvalid');

        $this->subject->saveAlert($alert, $frontendUser);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function saveAlertChecksForExistentSubscription()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted alert
         * Given a persisted frontend user
         * Given the alert has a subscribable project set
         * Given the user has already subscribed to the project
         * When I call the method
         * Then an alertManager.error.alreadySubscribed-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check150.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(150);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(150);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.alreadySubscribed');

        $this->subject->saveAlert($alert, $frontendUser);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function saveAlertPersistsAlert ()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted alert
         * Given a persisted frontend user
         * Given the alert has a subscribable project set
         * When I call the method
         * Then the alert is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check160.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(160);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(160);

        $result = $this->subject->saveAlert($alert, $frontendUser);
        self::assertTrue($result);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        self::assertEquals($frontendUser->getUid(), $dbAlert->getFrontendUser()->getUid());
        self::assertEquals($project->getUid(), $dbAlert->getProject()->getUid());

    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function saveAlertByRegistrationChecksForAlertKey ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given a registration object with alert-object in data-property, but no alert-key
         * Given the alert has a subscribable project set
         * When I call the method
         * Then no alert is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check210.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(210);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(210);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        $data = [
            'test' => $alert
        ];

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = GeneralUtility::makeInstance(OptIn::class);
        $optIn->setData($data);

        $this->subject->saveAlertByRegistration($frontendUser, $optIn);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        self::assertEmpty($dbAlert);

    }

    /**
     * @test
     * @throws \Exception
     */
    public function saveAlertByRegistrationChecksForAlertInstance ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given a registration object with non-alert-object in data-property, with alert-key
         * When I call the method
         * Then no alert is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check210.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(210);

        $data = [
            'alert' => $frontendUser
        ];

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = GeneralUtility::makeInstance(OptIn::class);
        $optIn->setData($data);

        $this->subject->saveAlertByRegistration($frontendUser, $optIn);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        self::assertEmpty($dbAlert);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function saveAlertByRegistrationPersistsAlert ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given a registration object with alert-object in data-property and alert-key
         * Given the alert has a subscribable project set
         * When I call the method
         * Then the alert is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check220.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(220);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(220);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        $data = [
            'alert' => $alert
        ];

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = GeneralUtility::makeInstance(OptIn::class);
        $optIn->setData($data);

        $this->subject->saveAlertByRegistration($frontendUser, $optIn);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        self::assertEquals($frontendUser->getUid(), $dbAlert->getFrontendUser()->getUid());
        self::assertEquals($project->getUid(), $dbAlert->getProject()->getUid());

    }

    //=============================================

    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function deleteAlertChecksForFrontendUser ()
    {

        /**
         * Scenario:
         *
         * Given a persisted alert
         * Given a no frontend user
         * When I call the method
         * Then a frontendUserNotLoggedIn-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check170.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = $this->alertRepository->findByIdentifier(170);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.frontendUserNotLoggedIn');

        $this->subject->deleteAlert($alert, null);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function deleteAlertChecksForNonPersistedFrontendUser ()
    {

        /**
         * Scenario:
         *
         * Given a persisted alert
         * Given a non-persisted frontend user
         * When I call the method
         * Then a frontendUserNotPersisted-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check170.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = $this->alertRepository->findByIdentifier(170);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.frontendUserNotPersisted');

        $this->subject->deleteAlert($alert, $frontendUser);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function deleteAlertChecksForNonPersistedAlert ()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted alert
         * Given a persisted frontend user
         * When I call the method
         * Then an alertAlreadyPersisted-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check180.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(180);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.alertNotPersisted');

        $this->subject->deleteAlert($alert, $frontendUser);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function deleteAlertChecksForValidFrontendUser ()
    {

        /**
         * Scenario:
         *
         * Given a persisted alert
         * Given a persisted frontend user
         * Given the alert does not belong to the given frontend user
         * When I call the method
         * Then a frontendUserInvalid-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check190.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = $this->alertRepository->findByIdentifier(190);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(190);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.frontendUserInvalid');

        $this->subject->deleteAlert($alert, $frontendUser);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function deleteAlertDeletesAlert ()
    {

        /**
         * Scenario:
         *
         * Given a persisted alert
         * Given a persisted frontend user
         * Given the alert belongs to the given frontend user
         * When I call the method
         * Then the alert is deleted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check200.xml');

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = $this->alertRepository->findByIdentifier(200);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(200);

        $result = $this->subject->deleteAlert($alert, $frontendUser);
        self::assertTrue($result);

        $alertDb = $this->alertRepository->findByIdentifier(200);
        self::assertEmpty($alertDb);

    }


    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
     */
    public function deleteAlertDeletesAlertOfValidDeletedFrontendUser ()
    {

        /**
         * Scenario:
         *
         * Given a persisted alert
         * Given a persisted frontend user
         * Given the alert belongs to the given frontend user
         * Given the frontend user has been deleted
         * When I call the method
         * Then the alert is deleted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check200.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(200);
        $this->frontendUserRepository->remove($frontendUser);
        $this->persistenceManager->persistAll();

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = $this->alertRepository->findByIdentifier(200);

        $result = $this->subject->deleteAlert($alert, $frontendUser);
        self::assertTrue($result);

        $alertDb = $this->alertRepository->findByIdentifier(200);
        self::assertEmpty($alertDb);
    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function deleteAlertsReturnsFalseOnError ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given a list of four alerts
         * Given the first of four alerts does not belong to the given frontend user
         * When I call the method
         * Then false is returned
         * Then the three alerts of the given frontend user are deleted
         * Then the one alert that does not belong to the given frontend user is not deleted
         * Then the counter is set to three
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check230.xml');


        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(230);

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
        $alerts = $this->alertRepository->findAll();

        $counter = 0;
        $result = $this->subject->deleteAlerts($alerts, $frontendUser, $counter);
        self::assertFalse($result);
        self::assertEquals(3, $counter);

        $alertDb = $this->alertRepository->findByIdentifier(230);
        self::assertInstanceOf(Alert::class, $alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(231);
        self::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(232);
        self::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(233);
        self::assertEmpty($alertDb);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function deleteAlertsReturnsFalseOnEmptyList ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given the frontend user has no alerts subscribed
         * When I call the method
         * Then false is returned
         * Then the counter is set to zero
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check240.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(240);

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
        $alerts = $this->alertRepository->findAll();

        $counter = 0;
        $result = $this->subject->deleteAlerts($alerts, $frontendUser, $counter);
        self::assertFalse($result);
        self::assertEquals(0, $counter);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function deleteAlertsReturnsTrue ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given a list of four alerts
         * Given all alerts do not belong to the given frontend user
         * When I call the method
         * Then true is returned
         * Then the three alerts of the given frontend user are deleted
         * Then the counter is set to three
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check250.xml');


        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(250);

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
        $alerts = $this->alertRepository->findAll();

        $counter = 0;
        $result = $this->subject->deleteAlerts($alerts, $frontendUser, $counter);
        self::assertTrue($result);
        self::assertEquals(4, $counter);

        $alertDb = $this->alertRepository->findByIdentifier(250);
        self::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(251);
        self::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(252);
        self::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(253);
        self::assertEmpty($alertDb);

    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function deleteAlertsByFrontendEndUserDeletesAlerts ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given the frontend user has three alerts subscribed
         * Given there is one alerts of another frontend-user
         * When I call the method
         * Then the three alerts of the given frontend user are deleted
         * Then the one alert that does not belong to the given frontend user is not deleted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check270.xml');


        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(270);

        $this->subject->deleteAlertsByFrontendEndUser($frontendUser);

        $alertDb = $this->alertRepository->findByIdentifier(270);
        self::assertInstanceOf(Alert::class, $alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(271);
        self::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(272);
        self::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(273);
        self::assertEmpty($alertDb);
    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function filterListBySubscribableProjectsReturnsEmptyArrayOnEmptyList ()
    {

        /**
         * Scenario:
         *
         * Given no alerts
         * When I call the method
         * Then an array is returned
         * Then the array is empty
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check280.xml');

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
        $alerts = $this->alertRepository->findAll();

        $result = $this->subject->filterListBySubscribableProjects($alerts);
        self::assertIsArray( $result);
        self::assertCount(0, $result);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function filterListBySubscribableProjectsReturnsActiveAlertsOnly ()
    {

        /**
         * Scenario:
         *
         * Given three alerts
         * Given one alert belongs to a project with alert-subscription enabled
         * Given one alert belongs to a project with alert-subscription disabled
         * Given one alert has no project set
         * When I call the method
         * Then an array is returned
         * Then the array contains one alert
         * Then the alert returned is the one that belongs to the project with alert-subscription enabled
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check290.xml');

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
        $alerts = $this->alertRepository->findAll();

        $result = $this->subject->filterListBySubscribableProjects($alerts);
        self::assertIsArray( $result);
        self::assertCount(1, $result);
        self::assertEquals(280, $result[0]->getUid());


    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function getPagesAndProjectsToNotifyReturnsValidDoktypesOnly ()
    {

        /**
         * Scenario:
         *
         * Given there are two pages with doktype 1 and one page with doktype 2
         * Given no alert has been sent for this pages, yet
         * Given the pages belong to the same project
         * Given that project is subscribable
         * Given the pages were created 2 days before
         * Given we search for pages that were created during the last 5 days
         * When I call the method
         * Then an array is returned
         * Then the array contains one key, which again contains an array with two keys
         * Then the first key has the name 'project'
         * Then the second key has the name 'pages'
         * Then the 'project'-sub-key contains an instance of Project
         * Then the 'pages'-sub-key is an instance of ObjectStorage
         * Then the 'pages'-sub-key contains two instances of Pages
         * Then the two page objects have the doktype 1
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check300.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->getPagesAndProjectsToNotify('crdate', 432000);
        self::assertIsArray( $result);
        self::assertCount(1, $result);

        $subArray = current($result);
        self::assertNotEmpty($subArray['project']);
        self::assertNotEmpty($subArray['pages']);

        self::assertInstanceOf(Project::class, $subArray['project']);

        /** @var ObjectStorage $pages */
        $pages = $subArray['pages'];
        self::assertInstanceOf(ObjectStorage::class, $pages);
        self::assertCount(2, $pages);

        self::assertInstanceOf(Page::class, $pages->current());
        $pages->next();
        self::assertInstanceOf(Page::class, $pages->current());
        $pages->rewind();

        /** @var \RKW\RkwAlerts\Domain\Model\Page $page */
        $page = $pages->current();
        self::assertEquals(1, $page->getDoktype());

        $pages->next();
        $page = $pages->current();
        self::assertEquals(1, $page->getDoktype());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getPagesAndProjectsToNotifyReturnsNonNotifiedPagesOnly ()
    {

        /**
         * Scenario:
         *
         * Given there are three pages with doktype 1
         * Given no alert has been sent for two of the pages, yet
         * Given an alert has been sent for one of the pages
         * Given the pages belong to the same project
         * Given that project is subscribable
         * Given the pages were created 2 days before
         * Given we search for pages that were created during the last 5 days
         * When I call the method
         * Then an array is returned
         * Then the array contains one key, which again contains an array with two keys
         * Then the first key has the name 'project'
         * Then the second key has the name 'pages'
         * Then the 'project'-sub-key contains an instance of Project
         * Then the 'pages'-sub-key is an instance of ObjectStorage
         * Then the 'pages'-sub-key contains two instances of Pages
         * Then the two Page-objects have not been used for alert-notification, yet
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check310.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->getPagesAndProjectsToNotify('crdate', 432000);
        self::assertIsArray( $result);
        self::assertCount(1, $result);

        $subArray = current($result);
        self::assertNotEmpty($subArray['project']);
        self::assertNotEmpty($subArray['pages']);

        self::assertInstanceOf(Project::class, $subArray['project']);

        /** @var ObjectStorage $pages */
        $pages = $subArray['pages'];
        self::assertInstanceOf(ObjectStorage::class, $pages);
        self::assertCount(2, $pages);

        self::assertInstanceOf(Page::class, $pages->current());
        $pages->next();
        self::assertInstanceOf(Page::class, $pages->current());
        $pages->rewind();

        /** @var \RKW\RkwAlerts\Domain\Model\Page $page */
        $page = $pages->current();
        self::assertEquals(0, $page->getTxRkwalertsSendStatus());

        $pages->next();
        $page = $pages->current();
        self::assertEquals(0, $page->getTxRkwalertsSendStatus());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getPagesAndProjectsToNotifyReturnsSubscribableProjectsOnly ()
    {

        /**
         * Scenario:
         *
         * Given there are three pages with doktype 1
         * Given no alert has been sent for the pages, yet
         * Given two pages belong to the same project
         * Given that project is subscribable
         * Given one page belongs to another project
         * Given that project is not subscribable
         * Given the pages were created 2 days before
         * Given we search for pages that were created during the last 5 days
         * When I call the method
         * Then an array is returned
         * Then the array contains one key, which again contains an array with two keys
         * Then the first key has the name 'project'
         * Then the second key has the name 'pages'
         * Then the 'project'-sub-key contains an instance of Project
         * Then the 'pages'-sub-key is an instance of ObjectStorage
         * Then the 'pages'-sub-key contains two instances of Pages
         * Then the two Page-objects belong to the subscribable project
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check320.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->getPagesAndProjectsToNotify('crdate', 432000);
        self::assertIsArray( $result);
        self::assertCount(1, $result);

        $subArray = current($result);
        self::assertNotEmpty($subArray['project']);
        self::assertNotEmpty($subArray['pages']);

        self::assertInstanceOf(Project::class, $subArray['project']);

        /** @var ObjectStorage $pages */
        $pages = $subArray['pages'];
        self::assertInstanceOf(ObjectStorage::class, $pages);
        self::assertCount(2, $pages);

        self::assertInstanceOf(Page::class, $pages->current());
        $pages->next();
        self::assertInstanceOf(Page::class, $pages->current());
        $pages->rewind();

        /** @var \RKW\RkwAlerts\Domain\Model\Page $page */
        $page = $pages->current();
        self::assertEquals(320, $page->getTxRkwprojectsProjectUid()->getUid());

        $pages->next();
        $page = $pages->current();
        self::assertEquals(320, $page->getTxRkwprojectsProjectUid()->getUid());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getPagesAndProjectsToNotifyReturnsPagesInGivenTimeframeOnly ()
    {

        /**
         * Scenario:
         *
         * Given there are three pages with doktype 1
         * Given no alert has been sent for the pages, yet
         * Given all pages belong to the same project
         * Given that project is subscribable
         * Given two of pages were created 2 days before
         * Given one of the pages was created 6 days before
         * Given we search for pages that were created during the last 5 days
         * When I call the method
         * Then an array is returned
         * Then the array contains one key, which again contains an array with two keys
         * Then the first key has the name 'project'
         * Then the second key has the name 'pages'
         * Then the 'project'-sub-key contains an instance of Project
         * Then the 'pages'-sub-key is an instance of ObjectStorage
         * Then the 'pages'-sub-key contains two instances of Pages
         * Then the two Page-objects have been created two days before
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check330.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $cnt = 0;
        $timeNow = time();
        foreach ($pages as $page) {
            if ($cnt == 0) {
                $page->setCrdate($timeNow - (6 * 60 * 60 * 24));
            } else {
                $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            }
            $this->pageRepository->update($page);
            $cnt++;
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->getPagesAndProjectsToNotify('crdate', 432000);
        self::assertIsArray( $result);
        self::assertCount(1, $result);

        $subArray = current($result);
        self::assertNotEmpty($subArray['project']);
        self::assertNotEmpty($subArray['pages']);

        self::assertInstanceOf(Project::class, $subArray['project']);

        /** @var ObjectStorage $pages */
        $pages = $subArray['pages'];
        self::assertInstanceOf(ObjectStorage::class, $pages);
        self::assertCount(2, $pages);

        self::assertInstanceOf(Page::class, $pages->current());
        $pages->next();
        self::assertInstanceOf(Page::class, $pages->current());
        $pages->rewind();

        /** @var \RKW\RkwAlerts\Domain\Model\Page $page */
        $page = $pages->current();
        self::assertEquals($timeNow - (2 * 60 * 60 * 24), $page->getCrdate());

        $pages->next();
        $page = $pages->current();
        self::assertEquals($timeNow - (2 * 60 * 60 * 24), $page->getCrdate());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getPagesAndProjectsToNotifyReturnsPagesInGivenTimeframeOnlyWithCustomField ()
    {

        /**
         * Scenario:
         *
         * Given there are three pages with doktype 1
         * Given no alert has been sent for the pages, yet
         * Given all pages belong to the same project
         * Given that project is subscribable
         * Given all of the pages were created 6 days before
         * Given two of pages have a date set to 2 days before in a custom field
         * Given one of pages have a date set to 2 days before in a custom field
         * Given we search for pages that were created during the last 5 days
         * When I call the method
         * Then an array is returned
         * Then the array contains one key, which again contains an array with two keys
         * Then the first key has the name 'project'
         * Then the second key has the name 'pages'
         * Then the 'project'-sub-key contains an instance of Project
         * Then the 'pages'-sub-key is an instance of ObjectStorage
         * Then the 'pages'-sub-key contains two instances of Pages
         * Then the two Page-Objects have been created two days before
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check340.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $cnt = 0;
        $timeNow = time();
        foreach ($pages as $page) {
            if ($cnt == 0) {
                $page->setLastUpdated($timeNow - (6 * 60 * 60 * 24));

            } else {
                $page->setLastUpdated($timeNow - (2 * 60 * 60 * 24));
            }
            $page->setCrdate($timeNow - (6 * 60 * 60 * 24));
            $this->pageRepository->update($page);
            $cnt++;
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->getPagesAndProjectsToNotify('lastUpdated', 432000);
        self::assertIsArray( $result);
        self::assertCount(1, $result);

        $subArray = current($result);
        self::assertNotEmpty($subArray['project']);
        self::assertNotEmpty($subArray['pages']);

        self::assertInstanceOf(Project::class, $subArray['project']);

        /** @var ObjectStorage $pages */
        $pages = $subArray['pages'];
        self::assertInstanceOf(ObjectStorage::class, $pages);
        self::assertCount(2, $pages);

        self::assertInstanceOf(Page::class, $pages->current());
        $pages->next();
        self::assertInstanceOf(Page::class, $pages->current());
        $pages->rewind();

        /** @var \RKW\RkwAlerts\Domain\Model\Page $page */
        $page = $pages->current();
        self::assertEquals($timeNow - (2 * 60 * 60 * 24), $page->getLastUpdated());

        $pages->next();
        $page = $pages->current();
        self::assertEquals($timeNow - (2 * 60 * 60 * 24), $page->getLastUpdated());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getPagesAndProjectsToNotifyReturnsPagesGroupedByProject ()
    {

        /**
         * Scenario:
         *
         * Given there are five pages with doktype 1
         * Given no alert has been sent for the pages, yet
         * Given two pages belong to the same project
         * Given that project is subscribable
         * Given three pages belongs to another project
         * Given that project is subscribable
         * Given all pages were created 2 days before
         * Given we search for pages that were created during the last 5 days
         * When I call the method
         * Then an array is returned
         * Then the array contains two keys, which again contains an array with two keys
         * Then each first key has the name 'project'
         * Then each second key has the name 'pages'
         * Then each 'project'-sub-key contains an instance of Project
         * Then each 'pages'-sub-key is an instance of ObjectStorage
         * Then the first 'pages'-sub-key contains two instances of Pages
         * Then the second 'pages'-sub-key contains three instances of Pages
         * Then the array contains two key with one array each, which again has two keys
         * Then the first key 'pages' is a sub-array
         * Then the second key 'project' contains one project-object
         * Then the first 'pages'-sub-array contains two page-objects
         * Then the second 'pages'-sub-array contains three page-objects
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check350.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->getPagesAndProjectsToNotify('crdate', 432000);
        self::assertIsArray( $result);
        self::assertCount(2, $result);

        $subArray = current($result);
        next($result);
        $subArrayTwo = current($result);

        self::assertNotEmpty($subArray['project']);
        self::assertNotEmpty($subArray['pages']);
        self::assertNotEmpty($subArrayTwo['project']);
        self::assertNotEmpty($subArrayTwo['pages']);

        self::assertInstanceOf(Project::class, $subArray['project']);
        self::assertInstanceOf(Project::class, $subArrayTwo['project']);

        /** @var ObjectStorage $pages */
        $pages = $subArray['pages'];
        self::assertInstanceOf(ObjectStorage::class, $pages);
        self::assertCount(2, $pages);

        self::assertInstanceOf(Page::class, $pages->current());
        $pages->next();
        self::assertInstanceOf(Page::class, $pages->current());

        $pages = $subArrayTwo['pages'];
        self::assertInstanceOf(ObjectStorage::class, $pages);
        self::assertCount(3, $pages);

        self::assertInstanceOf(Page::class, $pages->current());
        $pages->next();
        self::assertInstanceOf(Page::class, $pages->current());
        $pages->next();
        self::assertInstanceOf(Page::class, $pages->current());

    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function sendNotificationChecksForCurrentContents ()
    {

        /**
         * Scenario:
         *
         * Given there are no new pages to notify about
         * When I call the method
         * Then zero is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check360.xml');

        $result = $this->subject->sendNotification('crdate', 432000);
        self::assertEquals(0, $result);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function sendNotificationChecksForSubscriptionsAndMarksPagesAsDone ()
    {

        /**
         * Scenario:
         *
         * Given there are two pages to notify about
         * Given the pages belong to a notifiable project
         * Given there are no subscriptions for the given project
         * When I call the method
         * Then the relevant pages are marked as sent
         * Then zero is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check360.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->sendNotification('crdate', 432000);
        self::assertEquals(0, $result);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function sendNotificationSendsMailsToExistentUsersOnlyAndMarksPagesAsDone ()
    {

        /**
         * Scenario:
         *
         * Given there are two pages to notify about
         * Given the pages belong to a notifiable project
         * Given there are three subscriptions to this project
         * Given one of the subscriptions belongs to a deleted frontend-user
         * When I call the method
         * Then the relevant pages are marked as sent
         * Then two is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check370.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->sendNotification('crdate', 432000);
        self::assertEquals(2, $result);

        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        foreach ($pages as $page) {
            self::assertEquals(1, $page->getTxRkwalertsSendStatus());
        }
    }


    /**
     * @test
     * @throws \Exception
     */
    public function sendNotificationSendsMailsForMultipleProjectsAndMarksPagesAsDone ()
    {

        /**
         * Scenario:
         *
         * Given there are five pages to notify about
         * Given there are two notifiable projects
         * Given three pages belong to notifiable project A
         * Given two pages belong to notifiable project B
         * Given there are two subscriptions to project A
         * Given there are four subscriptions to the project B
         * When I call the method
         * Then the relevant pages are marked as sent
         * Then six is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check380.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->sendNotification('crdate', 432000);
        self::assertEquals(6, $result);

        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        foreach ($pages as $page) {
            self::assertEquals(1, $page->getTxRkwalertsSendStatus());
        }
    }


    /**
     * @test
     * @throws \Exception
     */
    public function sendNotificationSendsMailsForProjectsAndRecipientsWithDifferentStoragePids ()
    {

        /**
         * Scenario:
         *
         * Given there are five pages to notify about
         * Given there are two notifiable projects A and B
         * Given this project A is stored in storagePid X
         * Given this project B is stored in storagePid Y
         * Given three pages belong to notifiable project A
         * Given two pages belong to notifiable project B
         * Given there are two subscriptions to project A
         * Given one of this subscriptions is stored in storagePid X
         * Given one of this subscriptions is stored in storagePid Y
         * Given there are four subscriptions to the project B
         * Given two of this subscriptions are stored in storagePid X
         * Given two of this subscriptions are stored in storagePid Y
         * When I call the method
         * Then the relevant pages are marked as sent
         * Then six is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check390.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->sendNotification('crdate', 432000);
        self::assertEquals(6, $result);

        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        foreach ($pages as $page) {
            self::assertEquals(1, $page->getTxRkwalertsSendStatus());
        }
    }


    /**
     * @test
     * @throws \Exception
     */
    public function sendNotificationCreatesMailsInDebugMode ()
    {

        /**
         * Scenario:
         *
         * Given there are five pages to notify about
         * Given there are two notifiable project
         * Given three pages belong to notifiable project A
         * Given two pages belong to notifiable project B
         * Given there are two subscriptions to project A
         * Given there are four subscriptions to the project B
         * Given a debug-address is set
         * When I call the method
         * Then the relevant pages are not marked as sent
         * Then one email is prepared for project A
         * Then this email has one recipient
         * Then one email is prepared for project B
         * Then this email has one recipient
         * Then all emails are sent to the given debug-address
         * Then two is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check380.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->sendNotification('crdate', 432000, 'debugmode@rkw.de');
        self::assertEquals(2, $result);

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page */
        foreach ($pages as $page) {
            self::assertEquals(0, $page->getTxRkwalertsSendStatus());
        }

        $queueMails = $this->queueMailRepository->findAll()->toArray();
        self::assertCount(2, $queueMails);

        $queueRecipients = $this->queueRecipientRepository->findByQueueMail($queueMails[0]);
        self::assertCount(1, $queueRecipients);

        $queueRecipients = $this->queueRecipientRepository->findByQueueMail($queueMails[1]);
        self::assertCount(1, $queueRecipients);

        $queueRecipients = $this->queueRecipientRepository->findAll();
        /**  @var  \Madj2k\Postmaster\Domain\Model\QueueRecipient $queueRecipient */
        foreach ($queueRecipients as $queueRecipient) {
            self::assertEquals('debugmode@rkw.de', $queueRecipient->getEmail());
        }
    }


    /**
     * @test
     * @throws \Exception
     */
    public function sendNotificationCreatesMailsPerProjectWithLinkList ()
    {

        /**
         * Scenario:
         *
         * Given there are five pages to notify about
         * Given there are two notifiable project
         * Given three pages belong to notifiable project A
         * Given two pages belong to notifiable project B
         * Given there are two subscriptions to project A
         * Given there are four subscriptions to the project B
         * When I call the method
         * Then the relevant pages are marked as sent
         * Then one email is prepared for project A
         * Then this email has two recipients
         * Then one email is prepared for project B
         * Then this email has four recipients
         * Then six is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check380.xml');

        // set date accordingly for our check
        $pages = $this->pageRepository->findAll();

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        $timeNow = time();
        foreach ($pages as $page) {
            $page->setCrdate($timeNow - (2 * 60 * 60 * 24));
            $this->pageRepository->update($page);
        }

        $this->persistenceManager->persistAll();

        // now do the check
        $result = $this->subject->sendNotification('crdate', 432000);
        self::assertEquals(6, $result);

        /**  @var \RKW\RkwAlerts\Domain\Model\Page $page  */
        foreach ($pages as $page) {
            self::assertEquals(1, $page->getTxRkwalertsSendStatus());
        }

        $queueMails = $this->queueMailRepository->findAll()->toArray();
        self::assertCount(2, $queueMails);

        $queueRecipients = $this->queueRecipientRepository->findByQueueMail($queueMails[0]);
        self::assertCount(2, $queueRecipients);

        $queueRecipients = $this->queueRecipientRepository->findByQueueMail($queueMails[1]);
        self::assertCount(4, $queueRecipients);
    }

    //=============================================

    /**
     * TearDown
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }








}

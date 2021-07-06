<?php
namespace RKW\RkwAlerts\Tests\Integration\Alerts;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

use RKW\RkwAlerts\Alerts\AlertManager;
use RKW\RkwAlerts\Domain\Model\Alert;
use RKW\RkwAlerts\Domain\Repository\AlertRepository;
use RKW\RkwAlerts\Domain\Repository\PageRepository;
use RKW\RkwAlerts\Domain\Repository\ProjectRepository;
use RKW\RkwRegistration\Domain\Model\FrontendUser;
use RKW\RkwRegistration\Domain\Model\Registration;
use RKW\RkwRegistration\Domain\Repository\FrontendUserRepository;

use RKW\RkwRegistration\Domain\Repository\RegistrationRepository;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

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
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertManagerTest extends FunctionalTestCase
{

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/rkw_basics',
        'typo3conf/ext/rkw_registration',
        'typo3conf/ext/rkw_mailer',
        'typo3conf/ext/rkw_authors',
        'typo3conf/ext/rkw_projects',
        'typo3conf/ext/rkw_alerts',
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [];

    /**
     * @var \RKW\RkwAlerts\Alerts\AlertManager
     */
    private $subject = null;

    /**
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     */
    private $alertRepository;

    /**
     * @var \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository
     */
    private $frontendUserRepository;

    /**
     * @var \RKW\RkwAlerts\Domain\Repository\PageRepository
     */
    private $pageRepository;

    /**
     * @var \RKW\RkwAlerts\Domain\Repository\ProjectRepository
     */
    private $projectRepository;

    /**
     * @var \RKW\RkwRegistration\Domain\Repository\RegistrationRepository
     */
    private $registrationRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    private $persistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    private $objectManager;

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/AlertManagerTest/Fixtures';


    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        // defaults for rkw_mailer
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'service@rkw.de';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'RKW';

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Global.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:rkw_basics/Configuration/TypoScript/setup.txt',
                'EXT:rkw_mailer/Configuration/TypoScript/setup.txt',
                'EXT:rkw_registration/Configuration/TypoScript/setup.txt',
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
        $this->registrationRepository = $this->objectManager->get(RegistrationRepository::class);

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
    public function hasFrontendUserSubscribedProjectReturnsFalseIfFeUserNotPersisted ()
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $feUser */
        $feUser = $this->objectManager->get(\RKW\RkwRegistration\Domain\Model\FrontendUser::class);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(40);

        self::assertFalse( $this->subject->hasFrontendUserSubscribedProject($feUser, $project));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function hasFrontendUserSubscribedProjectReturnsFalseIfProjectNotPersisted ()
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $feUser */
        $feUser = $this->frontendUserRepository->findByIdentifier(50);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->objectManager->get(\RKW\RkwAlerts\Domain\Model\Project::class);
        $project->setTxRkwalertsEnableAlerts(true);

        self::assertFalse( $this->subject->hasFrontendUserSubscribedProject($feUser, $project));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function hasFrontendUserSubscribedProjectReturnsTrueIfAlreadySubscribed ()
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $feUser */
        $feUser = $this->frontendUserRepository->findByIdentifier(60);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(60);

        self::assertTrue( $this->subject->hasFrontendUserSubscribedProject($feUser, $project));
    }

    //=============================================

    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     */
    public function createAlertChecksForTermsIfNotLoggedIn ()
    {

        /**
         * Scenario:
         *
         * Given I'm not logged in
         * Given I do not accept the Terms & Conditions
         * When I call the method
         * Then an acceptTerms-error is thrown
         */
        /** @var \RKW\RkwAlerts\Domain\Model\alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.acceptTerms');

        $this->subject->createAlert($request, $alert, null, '', false, false);

    }

    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     */
    public function createAlertChecksForTermsIfUserNotRegistered ()
    {

        /**
         * Scenario:
         *
         * Given I'm not registered
         * Given I do not accept the Terms & Conditions
         * When I call the method
         * Then an acceptTerms-error is thrown
         */
        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);

        /** @var \RKW\RkwAlerts\Domain\Model\alert $alert */
        $alert = GeneralUtility::makeInstance(alert::class);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.acceptTerms');

        $this->subject->createAlert($request, $alert, $frontendUser, '', false, false);

    }

    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     */
    public function createAlertChecksForPrivacyIfNotLoggedIn ()
    {
        /**
         * Scenario:
         *
         * Given I'm not logged in
         * Given I accept the Terms and Conditions
         * Given I do not accept the privacy-terms
         * When I call the method
         * Then an acceptPrivacy error is thrown
         */
        /** @var \RKW\RkwAlerts\Domain\Model\alert $alert */
        $alert = GeneralUtility::makeInstance(alert::class);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.acceptPrivacy');

        $this->subject->createAlert($request, $alert, null, '', true, false);

    }

    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     * @throws \Exception
    */
    public function createAlertChecksOnlyForPrivacyIfUserIsLoggedIn ()
    {

        /**
         * Scenario:
         *
         * Given I'm logged in
         * Given I do not accept the terms and conditions
         * Given I do not accept the privacy-terms
         * When I call the method
         * Then an acceptPrivacy-error is thrown
        */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check70.xml');

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser  $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(70);

        /** @var \RKW\RkwAlerts\Domain\Model\alert $alert */
        $alert = GeneralUtility::makeInstance(alert::class);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.acceptPrivacy');

        $this->subject->createAlert($request, $alert, $frontendUser, '', false, false);

    }



    /**
     * @test
     * @throws \RKW\RkwAlerts\Exception
     */
    public function createAlertChecksForValidEmail ()
    {

        /**
         * Scenario:
         *
         * Given I accept the Terms & Conditions
         * Given I accept the Privacy-Terms
         * Given I have used an invalid email
         * When I call the method
         * Then an emailInvalid-error is thrown
        */
        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.emailInvalid');

        $this->subject->createAlert($request, $alert, null, 'invalid-email', true, true);

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
         * Given I accept the Terms & Conditions
         * Given I accept the Privacy-Terms
         * Given I'm logged in
         * Given the e-mail-address of my login-user is invalid
         * When I call the method
         * Then an emailInvalid-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check80.xml');

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(80);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        static::expectException(\RKW\RkwAlerts\Exception::class);
        static::expectExceptionMessage('alertManager.error.emailInvalid');

        $this->subject->createAlert($request, $alert, $frontendUser, 'valid@email.de', true, true);

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
         * Given I accept the Terms & Conditions
         * Given I accept the Privacy-Terms
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

        $this->subject->createAlert($request,$alert, null, 'valid@email.de', true, true);

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
         * Given I accept the Terms & Conditions
         * Given I accept the Privacy-Terms
         * Given I'm logged in
         * Given the e-mail-address of my login-user is valid
         * Given I already subscribed to the given project
         * When I call the method
         * Then an alreadySubscribed-error is thrown
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check100.xml');

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
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

        $this->subject->createAlert($request, $alert, $frontendUser, '', true, true);

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
         * Given I accept the Terms & Conditions
         * Given I accept the Privacy-Terms
         * Given I'm logged in
         * Given the e-mail-address of my login-user is valid
         * When I call the method
         * Then the integer value 1 is returned
         * Then an alert-object for the given project and frontend-user is created
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check110.xml');

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(110);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(110);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $request */
        $request = $this->objectManager->get(Request::class);

        $result = $this->subject->createAlert($request, $alert, $frontendUser, '', true, true);
        static::assertEquals(1, $result);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        static::assertEquals($frontendUser->getUid(), $dbAlert->getFrontendUser()->getUid());
        static::assertEquals($project->getUid(), $dbAlert->getProject()->getUid());

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
         * Given I accept the Terms & Conditions
         * Given I accept the Privacy-Terms
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

        $result = $this->subject->createAlert($request, $alert, null, 'valid@email.de', true, true);
        static::assertEquals(2, $result);

        /** @var \RKW\RkwRegistration\Domain\Model\Registration $registration */
        $registration = $this->registrationRepository->findByIdentifier(1);
        static::assertInstanceOf(Registration::class, $registration);
        static::assertEquals($registration->getCategory(), 'rkwAlerts');

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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
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
    public function saveAlertChecksForExistentSubscription ()
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(160);

        $result = $this->subject->saveAlert($alert, $frontendUser);
        static::assertTrue($result);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        static::assertEquals($frontendUser->getUid(), $dbAlert->getFrontendUser()->getUid());
        static::assertEquals($project->getUid(), $dbAlert->getProject()->getUid());

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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(210);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(210);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        $data = [
            'test' => $alert
        ];

        /** @var \RKW\RkwRegistration\Domain\Model\Registration $registration */
        $registration = GeneralUtility::makeInstance(Registration::class);
        $registration->setData($data);

        $result = $this->subject->saveAlertByRegistration($frontendUser, $registration);
        static::assertFalse($result);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        static::assertEmpty($dbAlert);

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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(210);

        $data = [
            'alert' => $frontendUser
        ];

        /** @var \RKW\RkwRegistration\Domain\Model\Registration $registration */
        $registration = GeneralUtility::makeInstance(Registration::class);
        $registration->setData($data);

        $result = $this->subject->saveAlertByRegistration($frontendUser, $registration);
        static::assertFalse($result);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        static::assertEmpty($dbAlert);

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
         * Then no alert is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check220.xml');

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(220);

        /** @var \RKW\RkwAlerts\Domain\Model\Project $project */
        $project = $this->projectRepository->findByIdentifier(220);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $alert */
        $alert = GeneralUtility::makeInstance(Alert::class);
        $alert->setProject($project);

        $data = [
            'alert' => $alert
        ];

        /** @var \RKW\RkwRegistration\Domain\Model\Registration $registration */
        $registration = GeneralUtility::makeInstance(Registration::class);
        $registration->setData($data);

        $result = $this->subject->saveAlertByRegistration($frontendUser, $registration);
        static::assertTrue($result);

        /** @var \RKW\RkwAlerts\Domain\Model\Alert $dbAlert */
        $dbAlert = $this->alertRepository->findByIdentifier(1);
        static::assertEquals($frontendUser->getUid(), $dbAlert->getFrontendUser()->getUid());
        static::assertEquals($project->getUid(), $dbAlert->getProject()->getUid());

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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(200);

        $result = $this->subject->deleteAlert($alert, $frontendUser);
        static::assertTrue($result);

        $alertDb = $this->alertRepository->findByIdentifier(200);
        static::assertEmpty($alertDb);


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


        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(230);

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
        $alerts = $this->alertRepository->findAll();

        $counter = 0;
        $result = $this->subject->deleteAlerts($alerts, $frontendUser, $counter);
        static::assertFalse($result);
        static::assertEquals(3, $counter);

        $alertDb = $this->alertRepository->findByIdentifier(230);
        static::assertInstanceOf(Alert::class, $alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(231);
        static::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(232);
        static::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(233);
        static::assertEmpty($alertDb);

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

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(240);

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
        $alerts = $this->alertRepository->findAll();

        $counter = 0;
        $result = $this->subject->deleteAlerts($alerts, $frontendUser, $counter);
        static::assertFalse($result);
        static::assertEquals(0, $counter);

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


        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(250);

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $alerts */
        $alerts = $this->alertRepository->findAll();

        $counter = 0;
        $result = $this->subject->deleteAlerts($alerts, $frontendUser, $counter);
        static::assertTrue($result);
        static::assertEquals(4, $counter);

        $alertDb = $this->alertRepository->findByIdentifier(250);
        static::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(251);
        static::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(252);
        static::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(253);
        static::assertEmpty($alertDb);

    }

    //=============================================
    /**
     * @test
     * @throws \Exception
     */
    public function deleteAlertsByFrontendEndUserReturnsFalseOnEmptyList ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given the frontend user has no alerts subscribed
         * When I call the method
         * Then false is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check260.xml');

        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(260);

        $result = $this->subject->deleteAlertsByFrontendEndUser($frontendUser);
        static::assertFalse($result);

    }

    /**
     * @test
     * @throws \Exception
     */
    public function deleteAlertsByFrontendEndUserReturnsTrue ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontend user
         * Given the frontend user has three alerts subscribed
         * Given there is one alerts of another frontend-user
         * When I call the method
         * Then true is returned
         * Then the three alerts of the given frontend user are deleted
         * Then the one alert that does not belong to the given frontend user is not deleted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check270.xml');


        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(270);

        $result = $this->subject->deleteAlertsByFrontendEndUser($frontendUser);
        static::assertTrue($result);

        $alertDb = $this->alertRepository->findByIdentifier(270);
        static::assertInstanceOf(Alert::class, $alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(271);
        static::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(272);
        static::assertEmpty($alertDb);

        $alertDb = $this->alertRepository->findByIdentifier(273);
        static::assertEmpty($alertDb);

    }

    //=============================================
    /**
     * @test
     * @throws \Exception
     */
    public function getActiveAlertsReturnsEmptyArrayOnEmptyList ()
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

        $result = $this->subject->getActiveAlerts($alerts);
        static::assertInternalType('array', $result);
        static::assertCount(0, $result);

    }

    /**
     * @test
     * @throws \Exception
     */
    public function getActiveAlertsReturnsActiveAlertsOnly ()
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

        $result = $this->subject->getActiveAlerts($alerts);
        static::assertInternalType('array', $result);
        static::assertCount(1, $result);
        static::assertEquals(280, $result[0]->getUid());


    }

    //=============================================

    /**
     * TearDown
     */
    protected function tearDown()
    {
        parent::tearDown();
    }








}
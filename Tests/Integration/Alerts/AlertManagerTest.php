<?php
namespace RKW\RkwAlerts\Tests\Integration\Alerts;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

use RKW\RkwAlerts\Alerts\AlertManager;
use RKW\RkwAlerts\Domain\Repository\AlertRepository;
use RKW\RkwAlerts\Domain\Repository\PageRepository;
use RKW\RkwAlerts\Domain\Repository\ProjectRepository;
use RKW\RkwRegistration\Domain\Repository\FrontendUserRepository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

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
    public function hasFeUserSubscribedToProjectReturnsFalseIfFeUserNotPersisted ()
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

        self::assertFalse( $this->subject->hasFeUserSubscribedToProject($feUser, $project));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function hasFeUserSubscribedToProjectReturnsFalseIfProjectNotPersisted ()
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

        self::assertFalse( $this->subject->hasFeUserSubscribedToProject($feUser, $project));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function hasFeUserSubscribedToProjectReturnsTrueIfAlreadySubscribed ()
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

        self::assertTrue( $this->subject->hasFeUserSubscribedToProject($feUser, $project));
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
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
use RKW\RkwAlerts\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
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
 * Class AlertNotify
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertsNotify extends AbstractManager
{

    /**
     * @var array
     */
    protected array $categoryContainer = [];

    /**
     * @var array
     */
    protected array $newsContainer = [];

    /**
     * Gets an associative array with the categories to notify
     * and the news to link to
     *
     * @param string $filterField
     * @param int $timeSinceCreation
     * @return void
     * @throws InvalidQueryException
     */
    public function getNewsAndCategoriesToNotify(
        string $filterField = 'datetime',
        int $timeSinceCreation = 432000
    ): void {

        $this->newsContainer = $this->newsRepository->findAllToNotify($filterField, $timeSinceCreation)->toArray();

        if (count($this->newsContainer)) {

            /**  @var \RKW\RkwAlerts\Domain\Model\News $news */
            foreach ($this->newsContainer as $news) {

                foreach ($news->getCategories() as $category) {

                    if (! isset($this->categoryContainer[$category->getUid()])) {
                        $this->categoryContainer[$category->getUid()] = $category;
                    }
                }
            }
        }
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
     * @throws InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function sendNotification(
        string $filterField,
        int $timeSinceCreation = 432000,
        string $debugMail = ''
    ): int {

        // fill categoryContainer and newsContainer
        $this->getNewsAndCategoriesToNotify($filterField, $timeSinceCreation);

        $frontendUserList = $this->frontendUserRepository->findByAlertCategories($this->categoryContainer);

        // load categories to notify
        $recipientCountGlobal = 0;
        if ($frontendUserList->count()) {

            DebuggerUtility::var_dump($frontendUserList); exit;

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

                    // convert News-Category to Alerts-Category
                    /** @var \RKW\RkwAlerts\Domain\Model\Category $alertsCategory */
                    $alertsCategory = $this->categoryRepository->findByIdentifier($category->getUid());

                    // find all alerts for category
                    if ($alerts = $this->alertRepository->findByCategory($alertsCategory)) {

                    //    try {


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
                                                'listPid'     => intval($settings['settings']['listPid']),
                                                'news'        => $newsList,
                                            ),
                                            'subject' => LocalizationUtility::translate(
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
                                LocalizationUtility::translate(
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
/*
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
*/
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

}

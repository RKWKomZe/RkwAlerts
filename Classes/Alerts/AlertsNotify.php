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
     * @var \RKW\RkwAlerts\Domain\Model\News
     */
    protected ?News $newsToNotify = null;

    /**
     * Gets an associative array with the categories to notify
     * and the news to link to
     *
     * @param string $filterField
     * @param int $timeSinceCreation
     * @return void
     * @throws InvalidQueryException
     */
    public function getSingleNewsAndCategoriesToNotify(
        string $filterField = 'datetime',
        int $timeSinceCreation = 432000
    ): void {

        $this->newsToNotify = $this->newsRepository->findOneToNotify($filterField, $timeSinceCreation);

        if ($this->newsToNotify instanceof News) {

            foreach ($this->newsToNotify->getCategories() as $category) {

                if (! isset($this->categoryContainer[$category->getUid()])) {
                    $this->categoryContainer[$category->getUid()] = $category;
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
        // 1. Get news to notify
        // 2. Get related categories of news
        $this->getSingleNewsAndCategoriesToNotify($filterField, $timeSinceCreation);

        // 3. Get userList filtered by alerts categories
        $frontendUserList = $this->frontendUserRepository->findByAlertCategories($this->categoryContainer);

        // load categories to notify
        $recipientCountGlobal = 0;



        if ($this->newsToNotify instanceof News) {

            // get configuration
            $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            //    try {

            // build e-mails per project
            /** @var \RKW\RkwAlerts\Domain\Model\FrontendUser $frontendUser */
            foreach ($frontendUserList as $frontendUser) {


                // 4. Collect FrontendUser alerts category list which are matching with the alerted news
                $frontendUserCategoryList = [];
                $frontendUserCategoryTitleArray = [];
                foreach ($frontendUser->getTxRkwalertsAlerts() as $frontendUserAlert) {
                    foreach ($this->newsToNotify->getCategories() as $category) {
                        if ($category->getUid() == $frontendUserAlert->getCategory()->getUid()) {
                            $frontendUserCategoryList[] = $frontendUserAlert->getCategory();
                            $frontendUserCategoryTitleArray[] = $frontendUserAlert->getCategory()->getTitle();
                        }
                    }
                }
                $frontendUserCategoryTitleString = implode(', ', $frontendUserCategoryTitleArray);

                // check if FE-User exists
                if ($frontendUser instanceof \Madj2k\FeRegister\Domain\Model\FrontendUser) {

                    $recipient = $frontendUser;
                    if ($debugMail) {
                        $recipient = ['email' => $debugMail];
                    }

                    $mailService->setTo(
                        $recipient,
                        array(
                            'marker'  => array(
                                'frontendUser'             => $frontendUser,
                                'categoryList'             => $frontendUserCategoryList,
                                'loginPid'                 => intval($settings['settings']['loginPid']),
                                'listPid'                  => intval($settings['settings']['listPid']),
                                'news'                     => $this->newsToNotify,
                                'categoryTitleListString'  => $frontendUserCategoryTitleString,
                            ),
                            'subject' => LocalizationUtility::translate(
                                'rkwMailService.sendAlert.subject',
                                'rkw_alerts',
                                [$frontendUserCategoryTitleString],
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
                    [],
                    $settings['settings']['defaultLanguageKey'] ?: 'default'
                )
            );

            // send mail
            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->addPartialPaths($settings['view']['partialRootPaths']);
            $mailService->getQueueMail()->setPlaintextTemplate('Email/Alert');
            $mailService->getQueueMail()->setHtmlTemplate('Email/Alert');
            $mailService->getQueueMail()->setType(2);

            //https://mein.ddev.site/weiterleitung/postmaster/redirect/14/7/?no_cache=1&tx_postmaster_tracking%5Burl%5D=https%3A%2F%2F
            //rkw-kompetenzzentrum.ddev.site%3A8443%2Fmein-rkw%2Fmeine-rkw-alerts%2F%3Ftx__%255B
            //action%255D%3D%26tx__%255B
            //controller%255D%3D%26cHash%3De161f975b843800f93eefce6239bd462&cHash=3ce246b6bb2c41b343604b479adab8be

            if ($recipientCount = count($mailService->getTo())) {

                $recipientCountGlobal += $recipientCount;
                $mailService->send();
                $this->getLogger()->log(
                    LogLevel::INFO,
                    sprintf(
                        'Successfully sent alert notification for news with id %s with %s recipients.',
                        $this->newsToNotify->getUid(),
                        $recipientCount
                    )
                );

            } else {
                $this->getLogger()->log(
                    LogLevel::DEBUG,
                    sprintf(
                        'No valid recipients found for alert notification for news with id %s.',
                        $this->newsToNotify->getUid()
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
                $this->newsToNotify->setTxRkwalertsSendStatus(1);
                $this->newsRepository->update($this->newsToNotify);

                // persist
                $this->persistenceManager->persistAll();
            }


            /*
            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Found %s categories for alert notifications.',
                    count($results)
                )
            );
            */

        } else {
            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf('No news found for alert notifications.')
            );
        }

        return $recipientCountGlobal;
    }

}

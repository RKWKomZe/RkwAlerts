<?php

namespace RKW\RkwAlerts\Domain\Repository;

use \RKW\RkwAlerts\Domain\Model\Category;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

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
 * Class NewsRepository
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NewsRepository extends \GeorgRinger\News\Domain\Repository\NewsRepository
{

    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * findOneToNotify
     *
     * find a news to notify
     *
     * @param string $filterField
     * @param int $timeSinceCreation
     * @return object
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findOneToNotify(string $filterField, int $timeSinceCreation = 432000): object
    {

        # Clean filter field and check it against TCA
        $filterField = preg_replace('/[^a-z0-9_\-]+/i', '', $filterField);
        if (
            (!$filterField)
            || (! $GLOBALS['TCA']['tx_news_domain_model_news']['columns'][$filterField])
        ) {
            $filterField = 'crdate';
        }

        $this->getLogger()->log(
            \TYPO3\CMS\Core\Log\LogLevel::DEBUG,
            sprintf(
                'Using database field %s for filtering.',
                $filterField
            )
        );

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('txRkwalertsSendStatus', 0),
                //$query->greaterThanOrEqual($filterField, (time() - intval($timeSinceCreation))),
                $query->greaterThan('categories', 0),
                $query->equals('categories.txRkwalertsEnableAlerts', 1)
            )
        );
        $query->setLimit(1);

        return $query->execute()->getFirst();
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

}

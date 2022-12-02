<?php

namespace RKW\RkwAlerts\Domain\Repository;

use RKW\RkwAlerts\Domain\Model\Page;
use Madj2k\CoreExtended\Utility\QueryUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

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
 * Class PageRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageRepository extends AbstractRepository
{
    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;

    /**
     * findByUid
     * finds page by uid
     *
     * @param int $uid
     * @return \RKW\RkwAlerts\Domain\Model\Page
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     * @deprecated since version 8.7, will be removed in version 9.5
     */
    public function findByUid($uid): ?Page
    {

        $result = $this->createQuery();
        $result->getQuerySettings()->setRespectStoragePage(false);
        $result->statement('SELECT * FROM pages
            WHERE uid = ' . intval($uid) .
            QueryTypo3::getWhereClauseVersioning('pages') .
            QueryTypo3::getWhereClauseEnabled('pages')
        );

        return $result->execute()->getFirst();
    }


    /**
     * findAllToNotify
     *
     * finds all pages to notify
     *
     * @param string $filterField
     * @param integer $timeSinceCreation
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findAllToNotify(string $filterField, int $timeSinceCreation = 432000): QueryResultInterface
    {

        # Clean filter field and check it against TCA
        $filterField = preg_replace('/[^a-z0-9_\-]+/i', '', $filterField);
        if (
            (!$filterField)
            || (! $GLOBALS['TCA']['pages']['columns'][$filterField])
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
                $query->equals('doktype', 1),
                $query->equals('txRkwalertsSendStatus', 0),
                $query->greaterThanOrEqual($filterField, (time() - intval($timeSinceCreation))),
                $query->greaterThan('txRkwprojectsProjectUid', 0),
                $query->equals('txRkwprojectsProjectUid.txRkwalertsEnableAlerts', 1)
            )
        );

        return $query->execute();
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

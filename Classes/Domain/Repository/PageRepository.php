<?php

namespace RKW\RkwAlerts\Domain\Repository;

use RKW\RkwBasics\Helper\QueryTypo3;

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
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{


    /**
     * findByUid
     * finds page by uid
     *
     * @return \RKW\RkwAlerts\Domain\Model\Pages
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function findByUid($uid)
    {

        $result = $this->createQuery();
        $result->getQuerySettings()->setRespectStoragePage(false);
        $result->statement('SELECT * FROM pages
            WHERE uid = ' . intval($uid) .
            QueryTypo3::getWhereClauseForVersioning('pages') .
            QueryTypo3::getWhereClauseForEnableFields('pages')
        );

        return $result->execute()->getFirst();
        //===
    }


    /**
     * findAllByTxRkwalertsSendStatus
     * finds all pages by alert-send status
     *
     * @param string $filterField
     * @param integer $timeSinceCreation
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function findByTxRkwalertsSendStatusAndProject($filterField = 'crdate', $timeSinceCreation = 432000)
    {

        $additionalWhere = '';
        if ($filterField) {
            $additionalWhere = ' AND ' . preg_replace('/[^a-z0-9_\-]+/i', '', $filterField) . ' >= ' . (time() - intval($timeSinceCreation));
        }

        $result = $this->createQuery();
        $result->getQuerySettings()->setRespectStoragePage(false);
        $result->statement('SELECT * FROM pages
            WHERE tx_rkwalerts_send_status = 0 AND tx_rkwbasics_department > 0' . $additionalWhere .
            QueryTypo3::getWhereClauseForVersioning('pages') .
            QueryTypo3::getWhereClauseForEnableFields('pages')
        );

        return $result->execute();

        //===
    }


}
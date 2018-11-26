<?php

namespace RKW\RkwAlerts\Domain\Repository;

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
 * Class AlertsRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertsRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{


    /**
     * findExistingAlert
     * finds alert by frontendUser and project
     */
    public function findExistingAlert(\RKW\RkwAlerts\Domain\Model\Alerts $alert)
    {

        $query = $this->createQuery();

        return $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('frontendUser', $alert->getFrontendUser()->getUid()),
                    $query->equals('project', $alert->getProject()->getUid())
                )
            )
            ->execute()->count();
        //===
    }


    /**
     * Find all alerts  that have been updated recently
     * Used by delete Signal-Slot
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByFrontendUser($frontendUser)
    {

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        $query->matching(
            $query->equals('frontendUser', $frontendUser)
        );

        return $query->execute();
        //===
    }
}
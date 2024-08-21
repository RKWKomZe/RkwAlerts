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

use RKW\RkwAlerts\Domain\Model\Alert;
use RKW\RkwAlerts\Domain\Model\Project;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Class AlertRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertRepository extends AbstractRepository
{

    /**
     * findOneByFrontendUserAndProject
     * find one alert by frontendUser and project
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \RKW\RkwAlerts\Domain\Model\Project $project
     * @return \RKW\RkwAlerts\Domain\Model\Alert|null
     */
    public function findOneByFrontendUserAndProject(FrontendUser $frontendUser, Project $project):? Alert
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching(
            $query->logicalAnd(
                $query->equals('frontendUser',$frontendUser->getUid()),
                $query->equals('project', $project->getUid())
            )
        );
        return $query->execute()->getFirst();
    }


    /**
     * Find all alerts by frontend-user
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByFrontendUser(FrontendUser $frontendUser): QueryResultInterface
    {

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching(
            $query->equals('frontendUser', $frontendUser)
        );

        return $query->execute();
    }


    /**
     * findByProject
     *
     * @param \RKW\RkwAlerts\Domain\Model\Project $project
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByProject(Project $project): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching(
            $query->equals('project', $project)
        );

        return $query->execute();
    }


    /**
     * Find alert by uid and return raw data
     *
     * @param int $uid
     * @return array
     */
    public function findByIdentifierRaw(int $uid): array
    {

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching(
            $query->equals('uid', $uid)
        );

        $result = $query->execute(true);
        return $result[0];
    }

}

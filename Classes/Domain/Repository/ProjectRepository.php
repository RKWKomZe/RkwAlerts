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

use RKW\RkwAlerts\Domain\Model\Project;
use RKW\RkwProjects\Domain\Repository\ProjectsRepository;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Class ProjectRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ProjectRepository extends ProjectsRepository
{

    /**
     * findByNameOrShortName
     *
     * @param string $string
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findByNameOrShortName(string $string):? QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching(
            $query->logicalOr(
                $query->like('name', '%' . $string . '%'),
                $query->like('shortName', '%' . $string . '%'),
            )
        );

        return $query->execute();
    }

}

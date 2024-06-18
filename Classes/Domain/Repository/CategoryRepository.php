<?php

namespace RKW\RkwAlerts\Domain\Repository;

use RKW\RkwAlerts\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
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
 * Class CategoryRepository
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CategoryRepository extends AbstractRepository
{

    /**
     * Some important things on init
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function initializeObject(): void
    {

        parent::initializeObject();

        /** @var $querySettings Typo3QuerySettings */
        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);

        // don't add the pid constraint
        $querySettings->setRespectStoragePage(false);

        $this->setDefaultQuerySettings($querySettings);
    }



    /**
     * @param mixed $identifier
     * @return object[]|QueryResultInterface
     */
    public function findEnabledByIdentifier($identifier)
    {

        $uid = $identifier instanceof AbstractEntity ? $identifier->getUid() : $identifier;

        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('uid', $uid),
                $query->equals('txRkwalertsEnableAlerts', true)
            )
        );

        return $query->execute();
    }



    /**
     * @param array $identifierList
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findEnabledByIdentifierMultiple(array $identifierList): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->in('uid', $identifierList),
                $query->equals('txRkwalertsEnableAlerts', true)
            )
        );

        return $query->execute();
    }



    /**
     * Returns all categories of a given parent category
     *
     * @param int $category
     * @param array $excludeCategories
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findChildrenByParent(int $category = 0, array $excludeCategories = []): QueryResultInterface
    {
        $constraints = array();
        $query = $this->createQuery();

        $constraints[] = $query->equals('txRkwalertsEnableAlerts', true);

        $constraints[] = $query->equals('parent', $category);
        if (count($excludeCategories) > 0) {
            $constraints[] = $query->logicalNot($query->in('uid', $excludeCategories));
        }
        $query->matching($query->logicalAnd($constraints));

        return $query->execute();
    }



    /**
     * findOneWithAllRecursiveChildren
     *
     * @todo: rework - too many return types!
     * @todo: rework - too many return types!@todo: rework - too many return types!
     * @todo: rework - too many return types!
     * @todo: rework - too many return types!
     * @todo: rework - too many return types!@todo: rework - too many return types!
     * @todo: rework - too many return types!@todo: rework - too many return types!@todo: rework - too many return types!
     * @todo: rework - too many return types!@todo: rework - too many return types!
     *
     *
     * @param \RKW\RkwBasics\Domain\Model\Category|null $sysCategory
     * @param boolean $returnUidArray
     * @param boolean $excludeEntriesWithoutParent
     * @param string $ordering
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array|object|void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     *  @todo: rework - too many return types!
     */
    public function findOneWithAllRecursiveChildren(
        Category $sysCategory = null,
        bool $returnUidArray = false,
        bool $excludeEntriesWithoutParent = false,
        string $ordering = 'ASC'
    ) {

        $query = $this->createQuery();
        $constraints = array();
        $sysCategoryUidArray = array();

        // if sysCategoryList is set, go recursive through the db
        if (!$sysCategory instanceof \RKW\RkwAlerts\Domain\Model\Category) {
            // important: Return void. No empty string, no empty array - simply nothing!
            return;

        } else {

            // 1. Set initial UID
            $sysCategoryUidArray[] = $sysCategory->getUid();

            // 2. Get children (of children, of children...)
            $goAhead = true;
            do {
                $query->matching(
                    $query->in('parent', $sysCategoryUidArray)
                );

                if ($furtherResults = $query->execute()) {
                    $itemCounter = 0;
                    // iterate results
                    foreach ($furtherResults as $category) {
                        // if not set yet, add to array
                        if (!in_array($category->getUid(), $sysCategoryUidArray)) {
                            $sysCategoryUidArray[] = $category->getUid();
                            $itemCounter++;
                        }
                    }
                    // check if something was added to the array. If not, we're at the end here!
                    if (!$itemCounter) {
                        $goAhead = false;
                    }
                } else {
                    $goAhead = false;
                }
            } while ($goAhead);

            // If wanted: Return the UID array and get out of here! :)
            if ($returnUidArray) {
                return $sysCategoryUidArray;

            }

            // 3. define final query with summary of sysCategoryUid's!
            $constraints[] = $query->in('uid', $sysCategoryUidArray);
        }

        // excluding parent
        if ($excludeEntriesWithoutParent) {
            $constraints[] = $query->logicalNot($query->equals('parent', 0));
        }

        // NOW: construct final query!
        if ($constraints) {
            $query->matching($query->logicalAnd($constraints));
        }

        // orderings
        if ($ordering == 'ASC') {
            $query->setOrderings(array('title' => QueryInterface::ORDER_ASCENDING));
        }
        if ($ordering == 'DESC') {
            $query->setOrderings(array('title' => QueryInterface::ORDER_DESCENDING));
        }

        // if there is no sysCategoryList defined, this execute is equal to a findAll()!
        // get single result
        if (count($sysCategoryUidArray) == 1) {
            return $query->execute()->getFirst();
        }

        // here we got a good old QueryResultInterface-Result
        return $query->execute();
    }


}

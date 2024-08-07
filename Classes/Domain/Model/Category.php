<?php

namespace RKW\RkwAlerts\Domain\Model;

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
 * Class Category
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Category extends \TYPO3\CMS\Extbase\Domain\Model\Category
{
    /**
     * ! Important ! -> Remove lazy from property through override (issue with serialize of FeRegister)
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\Category|null
     */
    protected $parent;

}

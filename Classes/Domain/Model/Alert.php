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

use Madj2k\CoreExtended\Domain\Model\AbstractCaptcha;
use Madj2k\FeRegister\Domain\Model\FrontendUser;

/**
 * Class Alert
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Alert extends AbstractCaptcha
{

    /**
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUser = null;

    /**
     * @var \RKW\RkwAlerts\Domain\Model\Category|null
     */
    protected ?Category $category = null;


    /**
     * Returns the frontendUser
     *
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     */
    public function getFrontendUser():? FrontendUser
    {
        return $this->frontendUser;
    }


    /**
     * Sets the frontendUser
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return void
     */
    public function setFrontendUser(\Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser): void
    {
        $this->frontendUser = $frontendUser;
    }


    /**
     * Returns the category
     *
     * @return \RKW\RkwAlerts\Domain\Model\Category $category
     */
    public function getCategory():? Category
    {
        return $this->category;
    }


    /**
     * Sets the category
     *
     * @param \RKW\RkwAlerts\Domain\Model\Category $category
     * @return void
     */
    public function setCategory(\RKW\RkwAlerts\Domain\Model\Category $category = null): void
    {
        $this->category = $category;
    }

}

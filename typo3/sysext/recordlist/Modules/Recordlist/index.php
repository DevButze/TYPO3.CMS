<?php
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
 * Module: Web>List
 *
 * Listing database records from the tables configured in $GLOBALS['TCA'] as they are related to the current page or root.
 *
 * Notice: This module and Web>Page module has a special status since they
 * are NOT located in their actual module directories (fx. mod/web/list/) but in the
 * backend root directory. This has some historical and practical causes.
 */
\TYPO3\CMS\Backend\Utility\BackendUtility::lockRecords();

$GLOBALS['SOBE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Recordlist\RecordList::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->clearCache();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();

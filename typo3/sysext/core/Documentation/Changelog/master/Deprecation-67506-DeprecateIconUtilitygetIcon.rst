====================================================
Deprecation: #67506 - Deprecate IconUtility::getIcon
====================================================

Description
===========

The method ``IconUtility::getIcon()`` which was used for generating overlaid icons for records has been marked for deprecation.


Impact
======

All calls to the PHP method will throw a deprecation warning.


Affected Installations
======================

Instances with third-party extensions modifying the TYPO3 Backend with a custom module or hook and calling ``IconUtility::getIcon()``.


Migration
=========

Use ``IconUtility::getSpriteIconForRecord()`` instead.

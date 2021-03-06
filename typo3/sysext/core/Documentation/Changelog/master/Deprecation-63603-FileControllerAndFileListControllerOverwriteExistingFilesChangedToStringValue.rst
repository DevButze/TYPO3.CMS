==========================================================================================================
Deprecation: #63603 - FileController and FileListController overwriteExistingFiles changed to string value
==========================================================================================================

Description
===========

The GET/POST param to tell the FileController and FileListController to override a file or not switched from a bool value to a string with the possibilities ``cancel``, ``replace`` and ``changeName``.


Impact
======

Extensions still using ``overwriteExistingFiles = 1`` will throw a deprecation warning.


Affected Installations
======================

All installations with extensions that use the BE upload functionality and supply the file override option.


Migration
=========

Change the ``<input name="overwriteExistingFiles" value="1">`` to ``<input name="overwriteExistingFiles" value="replace">``.
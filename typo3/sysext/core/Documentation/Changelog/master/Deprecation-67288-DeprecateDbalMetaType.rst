==========================================================================
Deprecation: #67288 - Deprecate Dbal\DatabaseConnection::MetaType() method
==========================================================================

Description
===========

The following public functions have been marked for deprecation as the bugfix requires a signature change:

* Dbal\DatabaseConnection->MetaType()


Impact
======

Using this function will throw a deprecation message. Due to missing information the field type cache will
be bypassed and the DBMS will be queried for the necessary information on each call.


Migration
=========

Switch to ``getMetadata()``and the field name for which you need the ADOdb MetaType information.

Xataface Comments Module
Created April 10, 2012 by Steve Hannah <steve@weblite.ca>

Synopsis:
=========

The comments module adds a comments section to the bottom of the view tab of a dataface
record.  Users (with appropriate permissions) can post comments on a record.

Features:
=========

- Allow users to post comments (with the "post comment" permission).
- Allow users to reply to comments.
- Manage comments through the control panel.
- Comments must be approved before visible (with post approved comment).

Installation:
==============

1. Copy the comments directory into your application's modules directory.
2. Add the following to the [_modules] section of the conf.ini file:

[_modules]
	modules_comments=modules/comments/comments.php
	
3. Set up permissions (see next section)


Setting Up Permissions:
=======================

If you want users to be able to post comments give them the "post comment" permission.
If you want them to be able to post comments without requiring approval, given them
the "post approved comment" permission.
If you want them to be able to view comments, give them the "view comments" permission.

Users will automatically have permission to edit and delete their own comments unless
you override this.
	


Disclaimer:
============

This module is still under development.  Many features are half finished and it is likely
still buggy.
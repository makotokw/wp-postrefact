Tool Export and Migrate WordPress posts
=====

## setup

* ``composer install``
* copy config.php.sample to config.php and edit it

## scripts

### export.php

usage: ``php export.php``

* export all posts to _export/post.html and _export/page.html

### migrate.php

**Warning: This script will update your database directly. Should backup database before executing.**

* update database from _migrate/post.html and _migrate/page.html

### wp2jekyll.php

usage: ``php wp2jekyll.php``

* export all posts to _export/_posts/*.html
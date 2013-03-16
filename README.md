URL Shortener

Just a silly little url shortener. made in a single function using anonymous functions to organize errthing.
Should be run in its own script, not embedded in another page, unless you tweak it.

*  Usage is as simple as calling `URLShortener();`

```php
/**
 * @param string $file - file to use for SQLite database
 * @param string $table - database table to use
 * @param boolean $init - Whether `CREATE TABLE IF NOT EXISTS` will be run.
 */
URLShortener($file = 'shorten.db', $table = 'shortened', $init = true);
```

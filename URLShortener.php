<?php

/**
 * URL Shortener
 *
 * just a silly little url shortener.
 * made in a single function using
 * anonymous functions to organize errthing.
 * Should be run in its own script,
 * not embedded in another page, unless you tweak it.
 *
 * Usage is as simple as calling `URLShortener();`
 *
 * @param string $file - file to use for SQLite database
 * @param string $table - database table to use
 * @param boolean $init - Whether `CREATE TABLE IF NOT EXISTS` will be run.
 */
function URLShortener($file = 'shorten.db', $table = 'shortened', $init = true)
{
    # Base HREF for links, e.g. http://example.com/go/{{id}}
    # {{id}} will be replacd with the ID of the URL so if for
    # some reason you want stuff after it, that's fine.
    # This default is a basic guess at the right URL to use,
    # it should produce something like:
    # http://localhost/scripts/urlshortener.php/{{id}}
    $href = ('on' === getenv('HTTPS') ? 'https://' : 'http://')
          . (getenv('HTTP_HOST') ?: getenv('SERVER_NAME'))
          . ('/'.trim(getenv('SCRIPT_NAME'), '/').'/{{id}}');
    # URL shortener form
    $entry_form = '<p><form method="post" action="" id="shortener">'
                . '<input type="url" name="url" id="long" autofocus>'
                . '<input type="submit" value="Shorten"></form></p>';
    /**
     * Function to get database connection
     */
    $getdb = function() use(&$getdb, $file, $table, $init)
    {
        static $dbc;
        if(is_a($dbc, '\PDO')) { return $dbc; }
        $db = new \PDO("sqlite:$file"); # sqlite::memory:
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if(!$init) { return $dbc = $db; } # Only `CREATE TABLE` if !!$init
        $c = "id INTEGER PRIMARY KEY, long TEXT UNIQUE, date INTEGER, by TEXT";
        $db->query("CREATE TABLE IF NOT EXISTS `$table` ($c)");
        return $dbc = $db;
    };
    /**
     * Function to shorten URL
     */
    $shorten = function($url) use(&$getdb, $table, $href, $entry_form)
    {
        if(false === filter_var($url, \FILTER_VALIDATE_URL)) {
            die('That does not appear to be a proper URL.'.$entry_form);
        }
        $db = $getdb();
        $t = $db->prepare("SELECT id FROM `$table` WHERE long = ? LIMIT 1");
        $t->execute([$url]);
        if($t = $t->fetchColumn()) {
            $url = strtolower(base_convert($t, 10, 36));
            $msg = 'That URL already existed. Here\'s a copy for you:';
        } else {
            $sql = "INSERT INTO `$table` (long, date, by) VALUES (?,?,?)";
            $st = $db->prepare($sql);
            $st->execute([$url, time(), getenv('REMOTE_ADDR')]);
            $url = strtolower(base_convert($db->lastInsertId(), 10, 36));
            $msg = 'Behold your shortened URL, Sir and/or Madam:';
        }
        $url = str_ireplace('{{id}}', $url, $href);
        exit("$msg<p><a href='$url'>$url</a></p>");
    };
    /**
     * function to redirect user to shortened URL
     */
    $redirect = function($id) use(&$getdb, $table, $entry_form)
    {
        if(preg_match('/[^a-z0-9]/', $id)) { die('Malformed ID.'); }
        $db = $getdb();
        $st = $db->prepare("SELECT long FROM `$table` WHERE id =? LIMIT 1");
        $st->execute([base_convert(strtolower($id), 36, 10)]);
        if(!($url = $st->fetchColumn())) {
            die('No URL exists for the specified ID. :('.$entry_form);
        }
        header("Location: $url", true, 301);
        exit("Redirecting to: <a href='$url'>$url</a>");
    };
    /*
     * the main show
     */
    if(!empty($_POST['url'])) {
        $shorten($_POST['url']);
    } elseif(preg_match('#^/(\w+)$#', getenv('PATH_INFO'), $m)) {
        $redirect($m[1]);
    } else {
        print $entry_form;
    }
}

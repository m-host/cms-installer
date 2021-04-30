<?php

error_reporting(E_ERROR);
ini_set('display_errors', 0);
ini_set('max_execution_time', 1800);

class installer
{
    private $cms_list = [
        'OpenCart' => [
            'opencart-3.0.3.6' => 'OpenCart 3.0.3.6',
            'opencart-2.3.0.2' => 'OpenCart 2.3.0.2',
        ],
        'ocStore' => [
            'ocstore-3.0.2.0' => 'ocStore 3.0.2.0 (rus)',
            'ocstore-2.3.0.2.3' => 'ocStore 2.3.0.2.3 (rus)',
        ],
        'WordPress' => [
            'wordpress-5.7.1-uk' => 'WordPress 5.7.1 (ukr)',
            'wordpress-5.5.1-uk' => 'WordPress 5.5.1 (ukr)',
            'wordpress-4.9.15-uk' => 'WordPress 4.9.15 (ukr)',
        ],
        'Joomla' => [
            'joomla-3.9.21' => 'Joomla! 3.9.21 (ukr)',
        ],
        'Drupal' => [
            'drupal-9.0.5' => 'Drupal 9.0.5 (ukr)',
        ],
        'PrestaShop' => [
            'prestashop-1.7.6.7' => 'PrestaShop 1.7.6.7 (ukr)',
        ],
        'phpBB' => [
            'phpbb-3.3.1' => 'phpBB 3.3.1',
        ],
        'MyBB' => [
            'mybb-1.8.24' => 'MyBB 1.8.24',
        ],
    ];

    private $cms_links = [
        'opencart-3.0.3.6' => 'http://m-host.net/data/cms/opencart-3.0.3.6.zip',
        'opencart-2.3.0.2' => 'http://m-host.net/data/cms/opencart-2.3.0.2.zip',
        'wordpress-5.7.1-uk' => 'http://m-host.net/data/cms/wordpress-5.7.1-uk.zip',
        'wordpress-5.5.1-uk' => 'http://m-host.net/data/cms/wordpress-5.5.1-uk.zip',
        'wordpress-4.9.15-uk' => 'http://m-host.net/data/cms/wordpress-4.9.15-uk.zip',
        'joomla-3.9.21' => 'http://m-host.net/data/cms/joomla-3.9.21.zip',
        'drupal-9.0.5' => 'http://m-host.net/data/cms/drupal-9.0.5.zip',
        'prestashop-1.7.6.7' => 'http://m-host.net/data/cms/prestashop-1.7.6.7.zip',
        'phpbb-3.3.1' => 'http://m-host.net/data/cms/phpbb-3.3.1.zip',
        'mybb-1.8.24' => 'http://m-host.net/data/cms/mybb-1.8.24.zip',
        'ocstore-3.0.2.0' => 'http://m-host.net/data/cms/ocstore-3.0.2.0.zip',
        'ocstore-2.3.0.2.3' => 'http://m-host.net/data/cms/ocstore-2.3.0.2.3.zip',
    ];

    private $cms_configs = [
        'opencart-3.0.3.6' => ['/config.php', '/admin/config.php'],
        'opencart-2.3.0.2' => ['/config.php', '/admin/config.php'],
        'ocstore-3.0.2.0' => ['/config.php', '/admin/config.php'],
        'ocstore-2.3.0.2.3' => ['/config.php', '/admin/config.php'],
        'wordpress-5.5.1-uk' => ['/wp-config.php'],
        'wordpress-4.9.15-uk' => ['/wp-config.php'],
        'joomla-3.9.21' => ['/configuration.php'],
        'drupal-9.0.5' => ['/sites/default/settings.php'],
        'prestashop-1.7.6.7' => ['/app/config/parameters.php','/mails/uk/order_conf.html','/.htaccess','/modules/ps_emailalerts/mails/uk/new_order.html','/modules/ps_emailalerts/mails/uk/return_slip.html',],
        'phpbb-3.3.1' => ['/config.php'],
        'mybb-1.8.24' => ['/inc/settings.php','/inc/config.php'],
    ];

    private $post;
    private $db;

    public static $cms_options;

    public function __construct()
    {
        $this->post = new stdClass();

        $fields = ['host','cms','mysql_db','mysql_user','mysql_password','admin_email','admin_login','admin_password'];

        foreach ($fields as $field) {
            $this->post->$field = empty($_POST[$field]) ? null : htmlspecialchars(addslashes(trim((string)$_POST[$field])));
        }

        if (!empty($this->post->host)) {
            return $this->process();
        }

        static::$cms_options = '';

        foreach ($this->cms_list as $cms_group => $cms_arr) {

            static::$cms_options .= '<optgroup label="'.$cms_group.'">';

            foreach ($cms_arr as $k => $name) {
                static::$cms_options .= '<option value="'.$k.'">'.$name.'</option>';
            }

            static::$cms_options .= '</optgroup>';
        }
    }

    private function process()
    {
        if (!($this->db = mysqli_connect(
            'localhost',
            $this->post->mysql_user,
            $this->post->mysql_password,
            $this->post->mysql_db
        ))) {
            throw new \Exception('Can\'t connect to DB with specified username and password');
        }

        mysqli_query($this->db, 'SET NAMES utf8');
        mysqli_query($this->db, 'SET character_set_client utf8');
        mysqli_query($this->db, 'SET character_set_connection utf8');
        mysqli_query($this->db, 'SET character_set_database utf8');
        mysqli_query($this->db, 'SET character_set_results utf8');
        mysqli_query($this->db, 'SET character_set_server utf8');

        $document_root = $_SERVER['DOCUMENT_ROOT'];

        chdir($document_root);

        if (empty($this->post->cms)) {
            return false;
        }

        foreach ($this->cms_list as $cms_group => $cms_arr) {
            foreach ($cms_arr as $k => $name) {
                if ($k == $this->post->cms && !empty($this->cms_links[$k])) {
                    $zip_name = $k . '.zip';
                    $cms_link = $this->cms_links[$k];
                    $cms_configs = $this->cms_configs[$k];
                    break;
                }
            }
        }

        if (empty($cms_link) || empty($zip_name)) {
            return false;
        }

        $cms_file_zip_path = $document_root . '/' . $zip_name;

        if (!is_file($cms_file_zip_path)) {

            $cms_zip = @file_get_contents($cms_link);

            if (empty($cms_zip)) {
                die('ERROR: we can\'t download CMS from m-host.net server. Ask support@m-host.net please.');
            }

            if (!file_put_contents($document_root . '/' . $zip_name, $cms_zip)) {
                die('ERROR: we can\'t store CMS in root directory. Check permissions please.');
            }
        }

        $zipArchive = new ZipArchive();
        $result = $zipArchive->open($document_root . '/' . $zip_name);
        if ($result === TRUE) {
            $zipArchive->extractTo($document_root);
            $zipArchive->close();
        }
        else {
            die('ERROR: we can\'t extract CMS archive to current project\'s dir');
        }

        if (!empty($cms_configs)) {
            foreach ($cms_configs as $cms_config_path) {

                if (!is_file($document_root . $cms_config_path)) {
                    continue;
                }

                $config_data = file_get_contents($document_root . $cms_config_path);

                foreach ($this->post as $post_k => $post_val) {
                    $config_data = str_replace('~' . $post_k . '~', $post_val, $config_data);
                }

                $config_data = str_replace('~document_root~', $document_root, $config_data);

                $config_data = preg_replace('/~.*?~/i', '', $config_data);

                file_put_contents($document_root . $cms_config_path, $config_data);
            }
        }

        if (is_file($document_root . '/dump.sql')) {
            $sql_dump = file_get_contents($document_root . '/dump.sql');

            $sql_dump = mb_convert_encoding($sql_dump, 'UTF-8');

            $sql_queries = explode(";\n", $sql_dump);

            if (!empty($sql_queries)) {
                foreach ($sql_queries as $sql_query) {

                    $sql_query = trim($sql_query);

                    if (empty($sql_query)) {
                        continue;
                    }

                    $sql_query = mb_convert_encoding($sql_query, 'UTF-8');

                    $sql_query = str_replace('~document_root~', $document_root, $sql_query);
                    $sql_query = str_replace('~host~', $_SERVER['HTTP_HOST'], $sql_query);
                    $sql_query = str_replace('~ip~', $_SERVER['REMOTE_ADDR'], $sql_query);
                    $sql_query = str_replace('~admin_login~', $this->post->admin_login, $sql_query);
                    $sql_query = str_replace('~admin_password~', $this->post->admin_password, $sql_query);
                    $sql_query = str_replace('~admin_email~', $this->post->admin_email, $sql_query);
                    $sql_query = preg_replace('/~.*?~/i', '', $sql_query);

                    mysqli_query($this->db, $sql_query);
                }
            }

            unlink($document_root . '/dump.sql');
        }

        unlink($cms_file_zip_path);


        $this->redirect('http://' . $_SERVER['HTTP_HOST'] . '/');

        return true;
    }

    private function redirect($url)
    {
        header("HTTP/1.1 301 Moved Permanently", true, 301);
        header("Location: " . $url);
        die;
    }
}

new installer;

?>
<!DOCTYPE html>
<html>
<head lang="uk">
    <title>Встановлювач CMS</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex, nofollow"/>
    <link href="https://m-framework.com/favicon.ico" rel="shortcut icon">
    <link href="https://cdn.m-framework.com/css/1.2.min/m.css" rel="stylesheet">
    <style>
        body:before{content:"";position:absolute;width:100%;top:0;left:0;right:0;bottom:0;display:block;background:#011924;-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=75)";filter:alpha(opacity=75);-moz-opacity:.75;-khtml-opacity:.75;opacity:.75;z-index:-1}body{background:url(https://m-host.net/templates/18/m-host/img/servers.jpg) no-repeat fixed;-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;background-position:center center;text-align:center;font-family:"Open Sans",sans-serif;font-weight:200;position:relative;margin:0;padding:0;color:#fff}.container .logo{position:relative;margin:100px 0}.logo .slogan{position:absolute;right:0;bottom:0;font-size:16px;color:#bbb}.container{display:block;max-width:1200px;margin:0 auto;padding:0;position:relative;min-height:100vh}.w50{width:50%;display:inline-block;padding:0;vertical-align:top;line-height:initial}.container img{margin:0 auto;display:block}h1{font-weight:300}a{text-decoration:none;color:#ddd;font-size:14px}a:hover{text-decoration:underline}a.gray{color:#ccc}@media (max-width:1024px){.logo .slogan{font-size:13px}}@media (max-width:820px){.w50{width:75%}}@media (max-width:560px){.container .logo{margin:50px 0}.logo .slogan{position:static;font-size:13px}}
    </style>
</head>
<body>
<div class="container">
    <a href="https://m-host.net/" class="w50 logo">
        <img src="https://m-host.net/templates/18/m-host/img/m-host_header_logo.svg" width="100%">
        <span class="slogan">Жодних котиків. Тільки якісний web-хостинг на SSD</span>
    </a>
    <h1>Встановлювач CMS від m-host.net</h1>
    <br>
    <br>
    <form method="post" action="" class="container w50" role="form">
        <div class="row">
            <label class="w33">Хост сайту:</label>
            <input class="w66" type="text" name="host" value="<?=$_SERVER['HTTP_HOST']?>" readonly required>
        </div>
        <div class="row">
            <label class="w33">Виберіть CMS:</label>
            <select name="cms" class="w66" required>
                <option></option>
                <?=installer::$cms_options?>
            </select>
        </div>
        <hr>
        <div class="row">
            <label class="w33">MySQL база даних:</label>
            <input class="w66" type="text" name="mysql_db" value="" required>
        </div>
        <div class="row">
            <label class="w33">MySQL користувач:</label>
            <input class="w66" type="text" name="mysql_user" value="" required>
        </div>
        <div class="row">
            <label class="w33">MySQL пароль:</label>
            <input class="w66" type="password" name="mysql_password" required>
        </div>
        <hr>
        <div class="row">
            <label class="w33">Email адміністратора:</label>
            <input class="w66" type="email" name="admin_email" value="" required>
        </div>
        <div class="row">
            <label class="w33">Логін адміністратора:</label>
            <input class="w66" type="text" name="admin_login" value="admin" required>
        </div>
        <div class="row">
            <label class="w33">Пароль адміністратора:</label>
            <input class="w66" type="text" name="admin_password" value="admin" readonly required>
        </div>
        <br>
        <button type="submit" class="btn btn-big">Встановити</button>
    </form>
</div>
</body>
</html>
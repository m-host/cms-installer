<?php

error_reporting(E_ERROR);
ini_set('display_errors', 0);
ini_set('max_execution_time', 1800);

class cms_installer
{
    public static $cms_list = [
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
        'wordpress-5.7.1-uk' => ['/wp-config.php'],
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

        foreach (static::$cms_list as $cms_group => $cms_arr) {
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

new cms_installer();

?>
<!DOCTYPE html>
<html>
<head lang="en">
    <title>CMS installer from m-host.net</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex, nofollow"/>
    <link href="https://m-host.net/favicon.ico" rel="shortcut icon">
    <link href="https://cdn.m-framework.com/css/1.2.min/m.css" rel="stylesheet" type="text/css" media="all">
    <link href="https://cdn.m-framework.com/css/1.2.min/fonts/lato-web.css" rel="stylesheet" type="text/css" media="all">
    <style>
        body {
            margin: 0;
            padding: 20px 0;
            background-color: #fff;
            font-family: 'LatoWeb', sans-serif;;
            font-size: 14px;
        }
        .container {
            display: block;
            max-width: 1200px;
            margin: 0 auto;
        }
        .cms-row input[type="radio"] {
            visibility: hidden;
            position: absolute;
            opacity: 0;
            top: 0;
            left: 0;
            width: 1px;
            height: 1px;
        }
        .cms-row input[type="radio"] + label {
            display: block;
            padding: 15px 15px;
            text-align: center;
            border: solid 1px #ccc;
            border-radius: 5px;
            cursor: pointer;
        }
        .cms-row input[type="radio"] + label:hover {
            border: solid 1px #ddd;
            background-color: #eee;
        }
        .cms-row input[type="radio"]:checked + label {
            border: solid 1px #2578a6;
            background-color: #2a8abf;
            color: #ddf4fc;
        }
        div.alert,
        div.notice
        {
            padding: 20px;
            display: block;
            width: 100%;
        }
    </style>
</head>
<body>
<div class="container txt-c">
    <a href="https://m-host.net/" class="logo">
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 413.3 70.6" xml:space="preserve" height="34">
        <g>
            <g>
                <path fill="#9EB5BB" d="M66.8,69.4V21.5h7.1c1.5,0,2.5,0.7,2.9,2.1l0.7,3.5c0.8-0.9,1.7-1.8,2.6-2.6c0.9-0.8,1.9-1.4,2.9-2
                    c1-0.6,2.2-1,3.4-1.3c1.2-0.3,2.5-0.5,3.9-0.5c3,0,5.5,0.8,7.4,2.5c1.9,1.6,3.4,3.8,4.4,6.5c0.7-1.6,1.7-2.9,2.8-4.1
                    c1.1-1.1,2.3-2.1,3.7-2.8c1.3-0.7,2.8-1.2,4.3-1.6c1.5-0.3,3-0.5,4.6-0.5c2.6,0,5,0.4,7.1,1.2c2.1,0.8,3.8,2,5.2,3.5
                    c1.4,1.6,2.5,3.5,3.2,5.7c0.7,2.2,1.1,4.8,1.1,7.7v30.5h-11.5V38.9c0-3.1-0.7-5.3-2-6.9c-1.3-1.5-3.3-2.3-5.9-2.3
                    c-1.2,0-2.3,0.2-3.3,0.6c-1,0.4-1.9,1-2.7,1.8c-0.8,0.8-1.4,1.7-1.8,2.9c-0.4,1.2-0.7,2.5-0.7,4v30.5H94.6V38.9
                    c0-3.2-0.6-5.5-1.9-7c-1.3-1.5-3.2-2.2-5.7-2.2c-1.7,0-3.2,0.4-4.6,1.2c-1.4,0.8-2.8,2-4,3.4v35.1H66.8z"/>
                <path fill="#9EB5BB" d="M135.9,36.4h24.2V46h-24.2V36.4z"/>
                <path fill="#9EB5BB" d="M163,69.4V0h11.5v26.7c1.9-1.8,3.9-3.2,6.2-4.3c2.2-1.1,4.9-1.6,7.9-1.6c2.6,0,4.9,0.4,7,1.3
                    c2,0.9,3.7,2.1,5.1,3.7c1.4,1.6,2.4,3.5,3.1,5.7c0.7,2.2,1.1,4.7,1.1,7.4v30.5h-11.5V38.9c0-2.9-0.7-5.2-2-6.8
                    c-1.4-1.6-3.4-2.4-6.1-2.4c-2,0-3.9,0.5-5.6,1.4c-1.7,0.9-3.4,2.1-4.9,3.7v34.6H163z"/>
                <path fill="#9EB5BB" d="M228.6,20.7c3.6,0,6.8,0.6,9.7,1.7c2.9,1.2,5.4,2.8,7.4,4.9c2.1,2.1,3.6,4.7,4.8,7.8
                    c1.1,3.1,1.7,6.5,1.7,10.2c0,3.8-0.6,7.2-1.7,10.3c-1.1,3.1-2.7,5.6-4.8,7.8c-2.1,2.1-4.5,3.8-7.4,4.9s-6.2,1.7-9.7,1.7
                    c-3.6,0-6.8-0.6-9.8-1.7s-5.4-2.8-7.5-4.9c-2.1-2.1-3.7-4.7-4.8-7.8c-1.1-3-1.7-6.5-1.7-10.3c0-3.8,0.6-7.2,1.7-10.2
                    c1.1-3,2.7-5.6,4.8-7.8c2.1-2.1,4.6-3.8,7.5-4.9C221.8,21.3,225,20.7,228.6,20.7z M228.6,61.2c4,0,6.9-1.3,8.8-4
                    c1.9-2.7,2.9-6.6,2.9-11.8c0-5.2-1-9.1-2.9-11.8s-4.9-4.1-8.8-4.1c-4,0-7,1.4-9,4.1c-1.9,2.7-2.9,6.7-2.9,11.8s1,9.1,2.9,11.7
                    C221.6,59.9,224.6,61.2,228.6,61.2z"/>
                <path fill="#9EB5BB" d="M281.8,30.8c-0.3,0.5-0.6,0.8-1,1.1c-0.3,0.2-0.8,0.3-1.3,0.3c-0.6,0-1.2-0.2-1.8-0.5
                    c-0.6-0.3-1.4-0.7-2.2-1.1c-0.8-0.4-1.8-0.7-2.9-1.1c-1.1-0.3-2.3-0.5-3.8-0.5c-2.3,0-4.1,0.5-5.4,1.4c-1.3,1-2,2.2-2,3.8
                    c0,1,0.3,1.9,1,2.6c0.7,0.7,1.6,1.3,2.7,1.8s2.4,1,3.8,1.4c1.4,0.4,2.8,0.9,4.3,1.4c1.5,0.5,2.9,1.1,4.3,1.7
                    c1.4,0.6,2.7,1.4,3.8,2.4c1.1,1,2,2.2,2.7,3.5c0.7,1.4,1,3,1,4.9c0,2.3-0.4,4.4-1.3,6.4c-0.8,1.9-2.1,3.6-3.7,5
                    c-1.6,1.4-3.6,2.5-6,3.3c-2.4,0.8-5.1,1.2-8.2,1.2c-1.7,0-3.3-0.1-4.8-0.4c-1.6-0.3-3.1-0.7-4.5-1.2s-2.8-1.2-4-1.9
                    c-1.2-0.7-2.3-1.5-3.2-2.3l2.7-4.4c0.3-0.5,0.7-0.9,1.2-1.2c0.5-0.3,1.1-0.4,1.8-0.4s1.4,0.2,2,0.6c0.6,0.4,1.4,0.8,2.2,1.3
                    c0.8,0.5,1.8,0.9,3,1.3c1.1,0.4,2.6,0.6,4.3,0.6c1.4,0,2.5-0.2,3.5-0.5c1-0.3,1.8-0.8,2.4-1.3s1.1-1.1,1.4-1.8
                    c0.3-0.7,0.4-1.4,0.4-2.2c0-1.1-0.3-2-1-2.8c-0.7-0.7-1.6-1.3-2.7-1.9c-1.1-0.5-2.4-1-3.8-1.4c-1.4-0.4-2.9-0.9-4.3-1.4
                    c-1.5-0.5-2.9-1.1-4.3-1.8c-1.4-0.7-2.7-1.5-3.8-2.5c-1.1-1-2-2.3-2.7-3.8c-0.7-1.5-1-3.3-1-5.4c0-2,0.4-3.8,1.2-5.6
                    c0.8-1.8,1.9-3.3,3.4-4.6s3.4-2.4,5.6-3.2c2.3-0.8,4.9-1.2,7.8-1.2c3.3,0,6.3,0.5,9,1.6s5,2.5,6.8,4.3L281.8,30.8z"/>
                <path fill="#9EB5BB" d="M301.9,70.1c-4.1,0-7.3-1.2-9.6-3.5c-2.2-2.3-3.4-5.6-3.4-9.7V30.1h-4.9c-0.6,0-1.2-0.2-1.6-0.6
                    s-0.7-1-0.7-1.8v-4.6l7.7-1.3L292,8.8c0.1-0.6,0.4-1.1,0.9-1.4s1-0.5,1.7-0.5h6v15.1h12.6v8.2h-12.6v26c0,1.5,0.4,2.7,1.1,3.5
                    c0.7,0.8,1.7,1.3,3,1.3c0.7,0,1.3-0.1,1.8-0.3s0.9-0.4,1.3-0.5c0.4-0.2,0.7-0.4,1-0.5c0.3-0.2,0.6-0.3,0.8-0.3
                    c0.3,0,0.6,0.1,0.8,0.3c0.2,0.2,0.5,0.4,0.7,0.8l3.5,5.6c-1.7,1.4-3.6,2.5-5.8,3.2S304.3,70.1,301.9,70.1z"/>
            </g>
            <g>
                <path fill="#C4C4C4" d="M317.4,65.5c0-0.7,0.1-1.3,0.4-2s0.6-1.2,1.1-1.6c0.5-0.4,1-0.8,1.6-1.1c0.6-0.3,1.3-0.4,2-0.4
                    c0.7,0,1.3,0.1,2,0.4c0.6,0.3,1.2,0.6,1.6,1.1c0.4,0.4,0.8,1,1.1,1.6c0.3,0.6,0.4,1.3,0.4,2c0,0.7-0.1,1.4-0.4,2
                    c-0.3,0.6-0.6,1.1-1.1,1.6c-0.4,0.4-1,0.8-1.6,1c-0.6,0.3-1.3,0.4-2,0.4c-0.7,0-1.4-0.1-2-0.4c-0.6-0.3-1.2-0.6-1.6-1
                    c-0.5-0.4-0.8-1-1.1-1.6C317.5,66.9,317.4,66.2,317.4,65.5z"/>
                <path fill="#C4C4C4" d="M329.4,70V35.8h5c1.1,0,1.8,0.5,2.1,1.5l0.6,2.7c0.7-0.7,1.4-1.4,2.2-1.9c0.8-0.6,1.6-1.1,2.4-1.5
                    c0.9-0.4,1.8-0.7,2.8-1c1-0.2,2-0.3,3.2-0.3c1.9,0,3.5,0.3,5,1c1.4,0.6,2.7,1.5,3.6,2.7c1,1.1,1.7,2.5,2.2,4.1
                    c0.5,1.6,0.8,3.3,0.8,5.3V70H351V48.3c0-2.1-0.5-3.7-1.5-4.9c-1-1.1-2.4-1.7-4.4-1.7c-1.4,0-2.8,0.3-4,1c-1.2,0.6-2.4,1.5-3.5,2.6
                    V70H329.4z"/>
                <path fill="#C4C4C4" d="M376.5,35.3c2.2,0,4.1,0.3,6,1c1.8,0.7,3.4,1.7,4.7,3c1.3,1.3,2.3,2.9,3.1,4.9c0.7,1.9,1.1,4.1,1.1,6.6
                    c0,0.6,0,1.1-0.1,1.6c-0.1,0.4-0.2,0.7-0.3,1s-0.3,0.4-0.6,0.5c-0.2,0.1-0.6,0.2-0.9,0.2h-21.1c0.2,3.5,1.2,6.1,2.8,7.7
                    c1.6,1.6,3.8,2.5,6.5,2.5c1.3,0,2.5-0.2,3.5-0.5c1-0.3,1.8-0.7,2.5-1s1.4-0.7,1.9-1c0.5-0.3,1.1-0.5,1.6-0.5
                    c0.3,0,0.6,0.1,0.9,0.2c0.2,0.1,0.5,0.3,0.6,0.6l2.4,3c-0.9,1.1-1.9,2-3.1,2.7c-1.1,0.7-2.3,1.3-3.6,1.7c-1.2,0.4-2.5,0.7-3.8,0.9
                    s-2.5,0.3-3.7,0.3c-2.4,0-4.6-0.4-6.6-1.2c-2-0.8-3.8-2-5.3-3.5c-1.5-1.5-2.7-3.5-3.6-5.7c-0.9-2.3-1.3-4.9-1.3-7.9
                    c0-2.3,0.4-4.5,1.1-6.6s1.8-3.8,3.3-5.4c1.4-1.5,3.1-2.7,5.2-3.6C371.7,35.7,374,35.3,376.5,35.3z M376.7,41.2
                    c-2.4,0-4.3,0.7-5.6,2c-1.4,1.4-2.2,3.3-2.6,5.8h15.5c0-1.1-0.1-2.1-0.4-3c-0.3-0.9-0.7-1.8-1.3-2.5c-0.6-0.7-1.4-1.3-2.3-1.7
                    C379,41.4,377.9,41.2,376.7,41.2z"/>
                <path fill="#C4C4C4" d="M404.3,70.6c-3,0-5.2-0.8-6.8-2.5c-1.6-1.7-2.4-4-2.4-7V42h-3.5c-0.4,0-0.8-0.1-1.2-0.4
                    c-0.3-0.3-0.5-0.7-0.5-1.3V37l5.5-0.9l1.7-9.3c0.1-0.4,0.3-0.8,0.6-1c0.3-0.2,0.7-0.4,1.2-0.4h4.3v10.8h9V42h-9v18.5
                    c0,1.1,0.3,1.9,0.8,2.5c0.5,0.6,1.2,0.9,2.1,0.9c0.5,0,0.9-0.1,1.3-0.2s0.6-0.2,0.9-0.4c0.3-0.1,0.5-0.3,0.7-0.4
                    c0.2-0.1,0.4-0.2,0.6-0.2c0.2,0,0.4,0.1,0.6,0.2c0.2,0.1,0.3,0.3,0.5,0.6l2.5,4c-1.2,1-2.6,1.8-4.1,2.3
                    C407.6,70.3,406,70.6,404.3,70.6z"/>
            </g>
            <path fill="#9EB5BB" d="M31.6,57.2c-1.3,0-2.4,1.1-2.4,2.4s1.1,2.4,2.4,2.4s2.4-1.1,2.4-2.4S33,57.2,31.6,57.2L31.6,57.2z M17.1,57.2
                H9.8c-1.3,0-2.4,1.1-2.4,2.4S8.5,62,9.8,62h7.3c1.3,0,2.4-1.1,2.4-2.4S18.4,57.2,17.1,57.2z M38.9,57.2c-1.3,0-2.4,1.1-2.4,2.4
                s1.1,2.4,2.4,2.4c1.3,0,2.4-1.1,2.4-2.4S40.2,57.2,38.9,57.2L38.9,57.2z M31.6,42.7c-1.3,0-2.4,1.1-2.4,2.4c0,1.3,1.1,2.4,2.4,2.4
                s2.4-1.1,2.4-2.4C34.1,43.8,33,42.7,31.6,42.7L31.6,42.7z M17.1,42.7H9.8c-1.3,0-2.4,1.1-2.4,2.4c0,1.3,1.1,2.4,2.4,2.4h7.3
                c1.3,0,2.4-1.1,2.4-2.4S18.4,42.7,17.1,42.7z M38.9,28.1c-1.3,0-2.4,1.1-2.4,2.4c0,1.3,1.1,2.4,2.4,2.4c1.3,0,2.4-1.1,2.4-2.4
                S40.2,28.1,38.9,28.1L38.9,28.1z M38.9,42.7c-1.3,0-2.4,1.1-2.4,2.4c0,1.3,1.1,2.4,2.4,2.4c1.3,0,2.4-1.1,2.4-2.4
                C41.3,43.8,40.2,42.7,38.9,42.7L38.9,42.7z M48.6,28.1c0-4-3.3-7.3-7.3-7.3h-34c-4,0-7.3,3.3-7.3,7.3V33c0,1.8,0.7,3.5,1.9,4.9
                c-1.1,1.3-1.8,3-1.8,4.8v4.9c0,1.8,0.7,3.5,1.9,4.9c-1.2,1.2-1.9,2.9-1.9,4.7v4.9c0,4,3.3,7.3,7.3,7.3l0,0h34c4,0,7.3-3.3,7.3-7.3
                v-4.9c0-1.8-0.7-3.5-1.9-4.9c1.2-1.3,1.9-3.1,1.9-4.9v-4.9c0-1.8-0.7-3.5-1.9-4.9c1.2-1.3,1.9-3.1,1.9-4.9L48.6,28.1L48.6,28.1z
                 M43.8,62.1c0,1.3-1.1,2.4-2.4,2.4l0,0h-34c-1.3,0-2.4-1.1-2.4-2.4l0,0v-4.9c0-1.3,1.1-2.4,2.4-2.4l0,0h34c1.3,0,2.4,1.1,2.4,2.4
                l0,0V62.1z M43.8,47.5c0,1.3-1.1,2.4-2.4,2.4l0,0h-34c-1.3,0-2.4-1.1-2.4-2.4l0,0v-4.9c0-1.3,1.1-2.4,2.4-2.4h34
                c1.3,0,2.4,1.1,2.4,2.4l0,0V47.5z M43.8,33c0,1.3-1.1,2.4-2.4,2.4l0,0h-34C6.1,35.4,5,34.3,5,33v-4.9c0-1.3,1.1-2.4,2.4-2.4h34
                c1.3,0,2.4,1.1,2.4,2.4l0,0V33z M31.6,28.1c-1.3,0-2.4,1.1-2.4,2.4c0,1.3,1.1,2.4,2.4,2.4s2.4-1.1,2.4-2.4S33,28.1,31.6,28.1
                L31.6,28.1z M17.1,28.1H9.8c-1.3,0-2.4,1.1-2.4,2.4c0,1.3,1.1,2.4,2.4,2.4h7.3c1.3,0,2.4-1.1,2.4-2.4S18.4,28.1,17.1,28.1z"/>
        </g>
    </svg>
    </a>
</div>
<br>
<hr>
<div class="container">
    <form method="post" action="" class="container w75 md-w100" role="form">
        <br>
        <h1 class="txt-c">CMS Installer</h1>
        <br>
        <h2>Choose a CMS</h2>
        <br>

        <?php
        foreach (cms_installer::$cms_list as $group_name => $cms_arr) {
            ?>
            <h3><?=$group_name?></h3>
            <div class="row cms-row">
            <?php
            foreach ($cms_arr as $cms_key => $cms_name) {
                ?>
                <div class="w20 md-w25 sm-50 xs-w50">
                    <input type="radio" name="cms" value="<?=$cms_key?>" id="cms-<?=$cms_key?>" autocomplete="off" required>
                    <label for="cms-<?=$cms_key?>"><?=$cms_name?></label>
                </div>
                <?php
            }
            ?>
            </div>
            <?php
        }
        ?>
        <br>
        <hr>
        <br>
        <h2>Basic CMS settings</h2>
        <br>
        <div class="row">
            <div class="w33">
                <label>Website host address:</label>
                <div class="row">
                    <input type="text" name="host" value="<?=$_SERVER['HTTP_HOST']?>" readonly required>
                </div>
            </div>
            <div class="w33"></div>
            <div class="w33"></div>
        </div>
        <hr>
        <div class="row">
            <div class="w33">
                <label>MySQL/PgSQL DB:</label>
                <div class="row">
                    <input type="text" name="mysql_db" value="" required>
                </div>
            </div>
            <div class="w33">
                <label>MySQL user:</label>
                <div class="row">
                    <input class="inner-block" type="text" name="mysql_user" value="" required>
                </div>
            </div>
            <div class="w33">
                <label>MySQL password:</label>
                <div class="row">
                    <input type="password" name="mysql_password" required>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="w33">
                <label>Email of Administrator:</label>
                <div class="row">
                    <input type="email" name="admin_email" value="admin@<?=$_SERVER['HTTP_HOST']?>" required>
                </div>
            </div>
            <div class="w33">
                <label>Administrator's login:</label>
                <div class="row">
                    <input type="text" name="admin_login" value="admin" required>
                </div>
            </div>
            <div class="w33">
                <label>Administrator's password:</label>
                <div class="row">
                    <input type="text" name="admin_password" value="admin" readonly required>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="alert notice">After installation you should immediately change an Admin username and password in Admin panel of CMS from defaults to own !</div>
        </div>
        <br>
        <hr>
        <br>
        <div class="row">
            <button type="submit" class="btn btn-big">Start installation</button>
        </div>
    </form>
    <br>
    <br>
    <br>
</div>
</body>
</html>
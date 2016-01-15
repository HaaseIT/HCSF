<?php

namespace HaaseIT\HCSF\Controller\Admin;

class Index extends Base
{
    public function __construct($C, $DB, $sLang)
    {
        parent::__construct($C, $DB, $sLang);
        $this->P->cb_customcontenttemplate = 'adminhome';
        $this->P->cb_customdata = array(
            'filter_enabled' => extension_loaded('filter'),
            'path_templatecache' => realpath(PATH_TEMPLATECACHE),
            'path_templatecache_exists' => file_exists(PATH_TEMPLATECACHE),
            'path_templatecache_writable' => is_writable(PATH_TEMPLATECACHE),
            'path_purifiercache' => realpath(PATH_PURIFIERCACHE),
            'path_purifiercache_exists' => file_exists(PATH_PURIFIERCACHE),
            'path_purifiercache_writable' => is_writable(PATH_PURIFIERCACHE),
            'enable_module_shop' => $C["enable_module_shop"],
            'path_logs' => realpath(PATH_LOGS),
            'path_logs_exists' => file_exists(PATH_LOGS),
            'path_logs_writable' => is_writable(PATH_LOGS),
        );
        if (function_exists('apache_get_modules')) {
            $aApacheModules = apache_get_modules();
            $this->P->cb_customdata['check_mod_rewrite'] = true;
            $this->P->cb_customdata['mod_rewrite_available'] = (array_search('mod_rewrite', $aApacheModules) !== false);
            unset($aApacheModules);
        }
        if (isset($_POST['string']) && trim($_POST['string']) != '') {
            $this->P->cb_customdata['encrypted_string'] = crypt($_POST["string"], $C["blowfish_salt"]);
        }
    }

}
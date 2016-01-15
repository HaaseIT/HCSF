<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 14.01.16
 * Time: 23:04
 */

namespace HaaseIT\HCSF;


class Router
{
    private $P;

    public function __construct($C, $DB, $sLang, $request, $twig)
    {
        $map = [
            '/_admin/index.html' => 'Admin\\Index',
            '/_admin/' => 'Admin\\Index',
            '/_admin' => 'Admin\\Index',
            '/_admin/cleartemplatecache.html' => 'Admin\\ClearTemplateCache',
            //'/_admin/clearimagecache.html' => 'Admin\\ClearImageCache',
            '/_admin/phpinfo.html' => 'Admin\\Phpinfo',
            '/_admin/pageadmin.html' => 'Admin\\Pageadmin',
            '/_admin/textcatadmin.html' => 'Admin\\Textcatadmin',
            '/_admin/customeradmin.html' => 'Admin\\Customer\\Customeradmin',
            '/_admin/itemadmin.html' => 'Admin\\Shop\\Itemadmin',
        ];
        $this->P = 404;
        $aURL = parse_url($request->getRequestTarget());
        $sPath = $aURL["path"];

        if (!empty($map[$sPath])) {
            $class = '\\HaaseIT\\HCSF\\Controller\\'.$map[$sPath];

            try {
                $controller = new $class($C, $DB, $sLang, $twig);
                $this->P = $controller->getPage();
            } catch (\Exception $e) {
                $this->P = $e;
            }

        }

        // todo: 404 handling
    }

    public function getPage()
    {
        return $this->P;
    }
}
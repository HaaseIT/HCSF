<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 14.01.16
 * Time: 23:04
 */

namespace HaaseIT\HCSF;


use Zend\ServiceManager\ServiceManager;

class Router
{
    private $P;

    /**
     * @var string
     */
    private $sPath;

    /**
     * @var ServiceManager
     */
    private $serviceManager;

    /**
     * @var \HaaseIT\HCSF\HelperConfig
     */
    protected $config;

    /**
     * @var \HaaseIT\HCSF\Helper
     */
    protected $helper;

    /**
     * Router constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        $this->config = $serviceManager->get('config');
        $this->helper = $serviceManager->get('helper');
    }

    public function getPage()
    {
        // Maintenance page
        if ($this->config->getCore('maintenancemode')) {
            try {
                $controller = new \HaaseIT\HCSF\Controller\Maintenance($this->serviceManager);
                $this->P = $controller->getPage();
            } catch (\Exception $e) {
                $this->P = $e->getMessage();
            }
        } else {
            $routes = require __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'routes.php';
            if ($this->config->getCore('enable_sandbox')) {
                $routes['/_misc/sandbox.html'] = 'Sandbox'; // dev sandbox for testing new functionality
            }
            $aURL = parse_url($this->serviceManager->get('request')->getRequestTarget());
            $this->sPath = $aURL['path'];

            $aPath = explode('/', $this->sPath);
            if (!empty($routes[$this->sPath])) {
                $class = '\\HaaseIT\\HCSF\\Controller\\'.$routes[$this->sPath];
            } else {
                if ($aPath[1] === $this->config->getCore('directory_images')) {
                    $class = Controller\Glide::class;
                }
            }

            if (!empty($class)) {
                // Core Page
                try {
                    /** @var Controller\Base $controller */
                    $controller = new $class($this->serviceManager, $aPath);
                    $this->P = $controller->getPage();
                } catch (\Exception $e) {
                    $this->P = new Page();
                    $this->P->setStatus(500);
                    // todo: write error message
                    //echo $e->getMessage();
                }
            } else {
                if ($this->config->getCore('enable_module_shop')) {
                    $aRoutingoverride = $this->getRoutingoverride($aPath);
                }

                $this->P = new UserPage($this->serviceManager, $this->sPath);

                // go and look if the page can be loaded yet
                if ($this->P->cb_id === NULL) {
                    /*
                    If the last part of the path doesn't include a dot (.) and is not empty, apend a slash.
                    If there is already a slash at the end, the last part of the path array will be empty.
                     */
                    if (mb_strpos($aPath[count($aPath) - 1], '.') === false && $aPath[count($aPath) - 1] !== '') {
                        $this->sPath .= '/';
                    }

                    if ($this->sPath[strlen($this->sPath) - 1] === '/') {
                        $this->sPath .= 'index.html';
                    }

                    $this->P = new UserPage($this->serviceManager, $this->sPath);
                }

                if ($this->P->cb_id === NULL) { // if the page is still not found, unset the page object
                    $this->P->setStatus(404);
                } else { // if it is found, go on
                    // Support for shorturls
                    if ($this->P->cb_pagetype === 'shorturl') {
                        $this->P->setStatus(302);
                        $this->P->addHeader('Location: '.$this->P->cb_pageconfig);
                    }

                    if (isset($this->P, $aRoutingoverride) && count($aRoutingoverride)) {
                        $this->P->cb_pagetype = $aRoutingoverride['cb_pagetype'];
                        $this->P->cb_pageconfig->itemno = $aRoutingoverride['itemno'];
                    }
                }
            }

            $serverserverprotocol = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            if ($this->P->getStatus() === 404) {
                $this->P = new CorePage($this->serviceManager);
                $this->P->cb_pagetype = 'error';
                $this->P->setStatus(404);
                $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('misc_page_not_found');
            } elseif ($this->P->getStatus() === 500) {
                $this->P = new CorePage($this->serviceManager);
                $this->P->cb_pagetype = 'error';
                $this->P->setStatus(500);
                $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('misc_server_error');
            } elseif (is_object($this->P) && $this->P->oPayload === null) {// elseif the page has been found but contains no payload...
                if (
                    !( // no payload is fine if page is one of these
                        $this->P->cb_pagetype === 'itemoverview'
                        || $this->P->cb_pagetype === 'itemoverviewgrpd'
                        || $this->P->cb_pagetype === 'itemdetail'
                    )
                ) {
                    $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('misc_content_not_found');
                    $this->P->setStatus(404);
                }
            } elseif ($this->P->oPayload->cl_lang !== null && $this->P->oPayload->cl_lang !== $this->config->getLang()) { // if the page is available but not in the current language, display info
                $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('misc_page_not_available_lang').'<br><br>'.$this->P->oPayload->cl_html;
            }
        }
        return $this->P;
    }

    private function getRoutingoverride($aPath)
    {
        $aRoutingoverride = [];
        // /xxxx/item/0010.html
        $aTMP['parts_in_path'] = count($aPath);
        // if the last dir in path is 'item' and the last part of the path is not empty
        if ($aPath[$aTMP['parts_in_path'] - 2] === 'item' && $aPath[$aTMP['parts_in_path'] - 1] !== '') {

            // explode the filename by .
            $aTMP['exploded_request_file'] = explode('.', $aPath[$aTMP['parts_in_path'] - 1]);

            // if the filename ends in '.html', get the requested itemno
            if ($aTMP['exploded_request_file'][count($aTMP['exploded_request_file']) - 1] === 'html') {
                // to allow dots in the filename, we have to iterate through all parts of the filename
                $aRoutingoverride['itemno'] = '';
                for ($i = 0; $i < count($aTMP['exploded_request_file']) - 1; $i++) {
                    $aRoutingoverride['itemno'] .= $aTMP['exploded_request_file'][$i].'.';
                }
                // remove the trailing dot
                $aRoutingoverride['itemno'] = \HaaseIT\Toolbox\Tools::cutStringend($aRoutingoverride['itemno'], 1);

                $aRoutingoverride['cb_pagetype'] = 'itemdetail';

                // rebuild the path string without the trailing '/item/itemno.html'
                $this->sPath = '';
                for ($i = 0; $i < $aTMP['parts_in_path'] - 2; $i++) {
                    $this->sPath .= $aPath[$i].'/';
                }
            }
        }

        return $aRoutingoverride;
    }
}

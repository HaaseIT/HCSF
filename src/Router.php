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

    public function __construct($C, $DB, $sLang, $request, $twig, $oItem)
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
            '/_admin/shopadmin.html' => 'Admin\\Shop\\Shopadmin',
            '/_admin/itemgroupadmin.html' => 'Admin\\Shop\\Itemgroupadmin',
            '/_misc/login.html' => 'Customer\\Login',
            '/_misc/logout.html' => 'Customer\\Logout',
            '/_misc/userhome.html' => 'Customer\\Userhome',
            '/_misc/register.html' => 'Customer\\Register',
            '/_misc/forgotpassword.html' => 'Customer\\Forgotpassword',
            '/_misc/rp.html' => 'Customer\\Resetpassword',
            '/_misc/verifyemail.html' => 'Customer\\Verifyemail',
            '/_misc/resendverificationmail.html' => 'Customer\\Resendverificationmail',
            '/_misc/myorders.html' => 'Shop\\Myorders',
            '/_misc/itemsearch.html' => 'Shop\\Itemsearch',
            '/_misc/checkedout.html' => 'Shop\\Checkedout',
            '/_misc/updateshippingcost.html' => 'Shop\\Updateshippingcost',
            '/_misc/shoppingcart.html' => 'Shop\\Shoppingcart',
            '/_misc/update-cart.html' => 'Shop\\Updatecart',
            '/_misc/sofortueberweisung.html' => 'Shop\\Sofortueberweisung',
            '/_misc/paypal.html' => 'Shop\\Paypal',
            '/_misc/paypal_notify.html' => 'Shop\\Paypalnotify',
        ];
        $this->P = 404;
        $aURL = parse_url($request->getRequestTarget());
        $sPath = $aURL["path"];

        if (!empty($map[$sPath])) {
            $class = '\\HaaseIT\\HCSF\\Controller\\'.$map[$sPath];
            try {
                $controller = new $class($C, $DB, $sLang, $twig, $oItem);
                $this->P = $controller->getPage();
            } catch (\Exception $e) {
                $this->P = $e->getMessage();
            }
        } else {
            $aPath = explode('/', $sPath);

            // /xxxx/item/0010.html
            if ($C["enable_module_shop"]) {
                $aTMP["parts_in_path"] = count($aPath);
                // if the last dir in path is 'item' and the last part of the path is not empty
                if ($aPath[$aTMP["parts_in_path"] - 2] == 'item' && $aPath[$aTMP["parts_in_path"] - 1] != '') {

                    // explode the filename by .
                    $aTMP["exploded_request_file"] = explode('.', $aPath[$aTMP["parts_in_path"] - 1]);
                    //\HaaseIT\Tools::debug($aTMP["exploded_request_file"]);

                    // if the filename ends in '.html', get the requested itemno
                    if ($aTMP["exploded_request_file"][count($aTMP["exploded_request_file"]) - 1] == 'html') {
                        // to allow dots in the filename, we have to iterate through all parts of the filename
                        $aRoutingoverride["itemno"] = '';
                        for ($i = 0; $i < count($aTMP["exploded_request_file"]) - 1; $i++) {
                            $aRoutingoverride["itemno"] .= $aTMP["exploded_request_file"][$i].'.';
                        }
                        // remove the trailing dot
                        $aRoutingoverride["itemno"] = \HaaseIT\Tools::cutStringEnd($aRoutingoverride["itemno"], 1);

                        //\HaaseIT\Tools::debug($aRoutingoverride["itemno"]);
                        $aRoutingoverride["cb_pagetype"] = 'itemdetail';

                        // rebuild the path string without the trailing '/item/itemno.html'
                        $sPath = '';
                        for ($i = 0; $i < $aTMP["parts_in_path"] - 2; $i++) {
                            $sPath .= $aPath[$i] . '/';
                        }
                    }
                }
                //HaaseIT\Tools::debug($sPath);
                //HaaseIT\Tools::debug($aTMP);
                //HaaseIT\Tools::debug($aRoutingoverride);
                unset($aTMP);
            }

            $this->P = new \HaaseIT\HCSF\UserPage($C, $sLang, $DB, $sPath);

            // go and look if the page can be loaded yet
            if ($this->P->cb_id == NULL) {
                /*
                If the last part of the path doesn't include a dot (.) and is not empty, apend a slash.
                If there is already a slash at the end, the last part of the path array will be empty.
                 */
                if (mb_strpos($aPath[count($aPath) - 1], '.') === false && $aPath[count($aPath) - 1] != '') $sPath .= '/';

                if ($sPath[strlen($sPath) - 1] == '/') $sPath .= 'index.html';

                $this->P = new \HaaseIT\HCSF\UserPage($C, $sLang, $DB, $sPath);
            }
            unset($aPath); // no longer needed
            //die(var_dump($P));

            if ($this->P->cb_id == NULL) { // if the page is still not found, unset the page object
                $this->P = 404;
            } else { // if it is found, go on
                // Support for shorturls
                if ($this->P->cb_pagetype == 'shorturl') {
                    header('Location: '.$this->P->cb_pageconfig, true, 302);
                    exit();
                }

                if (isset($this->P) && isset($aRoutingoverride) && count($aRoutingoverride)) {
                    $this->P->cb_pagetype = $aRoutingoverride["cb_pagetype"];
                    $this->P->cb_pageconfig->itemno = $aRoutingoverride["itemno"];
                }
            }

            if (!is_object($this->P) && $this->P == 404) {
                $P = new \HaaseIT\HCSF\CorePage($C, $sLang);
                $P->cb_pagetype = 'error';

                $P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_page_not_found");
                header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            } elseif (is_object($this->P) && $this->P->oPayload == NULL) {// elseif the page has been found but contains no payload...
                if (
                    !(
                        $this->P->cb_pagetype == 'itemoverview'
                        || $this->P->cb_pagetype == 'itemoverviewgrpd'
                        || $this->P->cb_pagetype == 'itemdetail'
                    )
                ) { // no payload is fine if page is one of these
                    $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_content_not_found");
                    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
                }
            } elseif($this->P->oPayload->cl_lang != NULL && $this->P->oPayload->cl_lang != $sLang) { // if the page is available but not in the current language, display info
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_page_not_available_lang").'<br><br>'.$this->P->oPayload->cl_html;
            }
        }
    }

    public function getPage()
    {
        return $this->P;
    }
}
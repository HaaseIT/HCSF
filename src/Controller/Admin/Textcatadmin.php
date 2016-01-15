<?php

namespace HaaseIT\HCSF\Controller\Admin;

class Textcatadmin extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->P->cb_customcontenttemplate = 'textcatadmin';

        $sH = '';

        if (!isset($_REQUEST["action"]) || $_REQUEST["action"] == '') {
            $aData = \HaaseIT\Textcat::getCompleteTextcatForCurrentLang();
            //HaaseIT\Tools::debug($aData);

            $aListSetting = array(
                array('title' => 'TC Key', 'key' => 'tc_key', 'width' => '20%', 'linked' => false,),
                array('title' => 'TC Text', 'key' => 'tcl_text', 'width' => '80%', 'linked' => false, 'escapehtmlspecialchars' => true,),
                array(
                    'title' => 'Edit',
                    'key' => 'tc_id',
                    'width' => 35,
                    'linked' => true,
                    'ltarget' => '/_admin/textcatadmin.html',
                    'lkeyname' => 'id',
                    'lgetvars' => array(
                        'action' => 'edit',
                    ),
                ),
            );
            $sH .= \HaaseIT\Tools::makeListtable($aListSetting, $aData, $twig);
        } elseif ($_GET["action"] == 'edit' || $_GET["action"] == 'delete') {
            if ($_GET["action"] == 'delete' && isset($_POST["delete"]) && $_POST["delete"] == 'do') {
                \HaaseIT\Textcat::deleteText($_GET["id"]);
                $this->P->cb_customdata["deleted"] = true;
            } else {
                $this->P->cb_customdata["edit"] = true;
                //\HaaseIT\Tools::debug($_REQUEST);

                \HaaseIT\Textcat::initTextIfVoid($_GET["id"]);

                // if post:edit is set, update
                if (isset($_POST["edit"]) && $_POST["edit"] == 'do') {
                    $purifier_config = \HTMLPurifier_Config::createDefault();
                    $purifier_config->set('Core.Encoding', 'UTF-8');
                    $purifier_config->set('Cache.SerializerPath', PATH_PURIFIERCACHE);
                    $purifier_config->set('HTML.Doctype', $C['purifier_doctype']);
                    if (isset($C['textcat_unsafe_html_whitelist']) && trim($C['textcat_unsafe_html_whitelist']) != '') {
                        $purifier_config->set('HTML.Allowed', $C['textcat_unsafe_html_whitelist']);
                    }
                    if (isset($C['textcat_loose_filtering']) && $C['textcat_loose_filtering']) {
                        $purifier_config->set('HTML.Trusted', true);
                        $purifier_config->set('Attr.EnableID', true);
                    }
                    $purifier = new \HTMLPurifier($purifier_config);
                    \HaaseIT\Textcat::$purifier = $purifier;

                    \HaaseIT\Textcat::saveText($_POST["lid"], $_POST["text"]);
                    $this->P->cb_customdata["updated"] = true;
                }

                $aData = \HaaseIT\Textcat::getSingleTextByID($_GET["id"]);
                //HaaseIT\Tools::debug($aData);
                $this->P->cb_customdata["editform"] = array(
                    'id' => $aData["tc_id"],
                    'lid' => $aData["tcl_id"],
                    'key' => $aData["tc_key"],
                    'lang' => $aData["tcl_lang"],
                    'text' => $aData["tcl_text"],
                );
            }
        } elseif ($_GET["action"] == 'add') {
            $this->P->cb_customdata["add"] = true;
            if (isset($_POST["add"]) && $_POST["add"] == 'do') {
                $this->P->cb_customdata["err"] = \HaaseIT\Textcat::verifyAddTextKey($_POST["key"]);

                if (count($this->P->cb_customdata["err"]) == 0) {
                    $this->P->cb_customdata["addform"] = array(
                        'key' => $_POST["key"],
                        'id' => \HaaseIT\Textcat::addTextKey($_POST["key"]),
                    );
                }
            }
        }

        $this->P->oPayload->cl_html = $sH;
    }

}
<?php

namespace HaaseIT\HCSF\Controller\Admin;

class ClearTemplateCache extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->P->oPayload->cl_html = 'The template cache has been cleared.';

        // todo: the following methods are deprecated. clear cache folder manually!!
        $twig->clearTemplateCache();
        $twig->clearCacheFiles();
    }
}
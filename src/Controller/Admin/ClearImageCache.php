<?php

namespace HaaseIT\HCSF\Controller\Admin;

class ClearImageCache extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->P->oPayload->cl_html = 'The image cache has been cleared. - No, wait, this is not implemented yet!';

        // todo: implement this! the following does not work
        $glideserver = \League\Glide\ServerFactory::create([
            'source' => PATH_DOCROOT.$C['directory_images'].'/master',
            'cache' => PATH_GLIDECACHE,
        ]);
        $glideserver->deleteCache('/');
    }
}
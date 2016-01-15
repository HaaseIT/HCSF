<?php

namespace HaaseIT\HCSF\Controller\Admin;

class Phpinfo extends Base
{
    public function __construct($C, $DB, $sLang)
    {
        parent::__construct($C, $DB, $sLang);
        ob_start();
        phpinfo();
        preg_match ('%<style type="text/css">(.*?)</style>.*?(<body>.*</body>)%s', ob_get_clean(), $matches);
        $html = '<div class=\'phpinfodisplay\'><style type=\'text/css\'>';

        $html .= join( "\n",
            array_map(
                create_function(
                    '$i',
                    'return ".phpinfodisplay " . preg_replace( "/,/", ",.phpinfodisplay ", $i );'
                ),
                preg_split( '/\n/', $matches[1] )
            )
        );
        $html .= '</style>'.$matches[2];
        $html .= '</div>';

        $this->P->oPayload->cl_html = $html;
    }
}
<?php

/*
    HCSF - A multilingual CMS and Shopsystem
    Copyright (C) 2014  Marcus Haase - mail@marcus.haase.name

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace HaaseIT\HCSF\Controller\Admin;

/**
 * Class Phpinfo
 * @package HaaseIT\HCSF\Controller\Admin
 */
class Phpinfo extends Base
{
    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        ob_start();
        phpinfo();
        preg_match('%<style type="text/css">(.*?)</style>.*?(<body>.*</body>)%s', ob_get_clean(), $matches);
        $html = '<div class=\'phpinfodisplay\'><style type=\'text/css\'>';

        $html .= implode("\n",
            array_map(
                function($i) {
                    return ".phpinfodisplay " . preg_replace( "/,/", ",.phpinfodisplay ", $i );
                },
                preg_split('/\n/', $matches[1])
            )
        );
        $html .= '</style>'.$matches[2];
        $html .= '</div>';

        $this->P->oPayload->cl_html = $html;
    }
}
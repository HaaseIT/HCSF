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

namespace HaaseIT\HCSF\Controller;


use Zend\ServiceManager\ServiceManager;

class Glide extends Base
{
    private $aPath;

    public function __construct(ServiceManager $serviceManager, $aPath)
    {
        parent::__construct($serviceManager);
        $this->aPath = $aPath;
    }

    public function preparePage()
    {
        $sPath = implode('/', $this->aPath);
        $sImageroot = PATH_BASEDIR.$this->config->getCore('directory_glide_master');

        if (
            is_file($sImageroot.substr($sPath, strlen($this->config->getCore('directory_images')) + 1))
            && getimagesize($sImageroot.substr($sPath, strlen($this->config->getCore('directory_images')) + 1))
        ) {
            $glideserver = \League\Glide\ServerFactory::create([
                'source' => $sImageroot,
                'cache' => PATH_GLIDECACHE,
                'max_image_size' => $this->config->getCore('glide_max_imagesize'),
            ]);
            $glideserver->setBaseUrl('/'.$this->config->getCore('directory_images').'/');
            // Generate a URL

            try {
                // Validate HTTP signature
                \League\Glide\Signatures\SignatureFactory::create($this->config->getSecret('glide_signkey'))->validateRequest($sPath, $_GET);
                $glideserver->outputImage($sPath, $_GET);
                $this->helper->terminateScript();

            } catch (\League\Glide\Signatures\SignatureException $e) {
                $this->P->setStatus(404);
            }
        } else {
            $this->P->setStatus(404);
        }

        return $this->P;
    }
}

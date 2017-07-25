<?php

namespace HaaseIT\HCSF\Controller\Api\Shop\Item;


use HaaseIT\HCSF\CorePage;

class Index extends Base
{
    public function preparePage()
    {
        $headers = [
            'Content-type' => 'application/json',
        ];

        $this->P = new CorePage($this->serviceManager, $headers);
        $this->P->cb_pagetype = 'itemoverviewjson';
    }
}

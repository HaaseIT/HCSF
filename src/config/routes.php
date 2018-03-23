<?php

return [
    'literal' => [
        '/_admin/index.html' => 'Admin\\Index',
        '/_admin/' => 'Admin\\Index',
        '/_admin' => 'Admin\\Index',
        '/_admin/cleartemplatecache.html' => 'Admin\\ClearTemplateCache',
        '/_admin/clearimagecache.html' => 'Admin\\ClearImageCache',
        '/_admin/phpinfo.html' => 'Admin\\Phpinfo',
        '/_admin/pageadmin.html' => 'Admin\\Pageadmin',
        '/_admin/textcatadmin.html' => 'Admin\\Textcatadmin',
        '/_admin/customeradmin.html' => 'Admin\\Customer\\Customeradmin',
        '/_admin/itemadmin.html' => 'Admin\\Shop\\Itemadmin',
        '/_admin/shopadmin.html' => 'Admin\\Shop\\Shopadmin',
        '/_admin/shopadmin_export.csv' => 'Admin\\Shop\\ShopadminExportCSV',
        '/_admin/itemgroupadmin.html' => 'Admin\\Shop\\Itemgroupadmin',
        '/_admin/dbstatus.html' => 'Admin\\DBStatus',
        '/_admin/screen.css' => 'Admin\\Stylesheet',
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
    ],
    'regex' => [
        [
            'regex' => '/_api/shop/item/index/(?<index>[a-zA-Z0-9_-]+)',
            'controller' => 'Api\\Shop\\Item\\Index',
        ]
    ],
];
<?php

//error_reporting(E_ALL);

/* Druckansicht für Acrylx
$P = array(
'head_scripts' => '<script type="text/javascript">
hs.Expander.prototype.printHtml = function ()
{
var pw = window.open("about:blank", "_new");
pw.document.open();
pw.document.write(this.getHtmlPrintPage());
pw.document.close();
return false;
};
hs.Expander.prototype.getHtmlPrintPage = function()
{
// We break the closing script tag in half to prevent
// the HTML parser from seeing it as a part of
// the *main* page.
var body = hs.getElementByClass(this.innerContent, \'DIV\', \'highslide-body\')
|| this.innerContent;

return "<html>\n" +
"<head>\n" +
"<title>Shop Bestellung</title>\n" +
"<link rel=\'stylesheet\' type=\'text/css\' href=\'/screen-global.css\'>\n" +
"<style type=\'text/css\'>html,body{margin-bottom:0;}body{min-height:100%;}</style>" +
"<script>\n" +"function step1() {\n" +
"  setTimeout(\'step2()\', 10);\n" +
"}\n" +
"function step2() {\n" +
"  window.print();\n" +
"  window.close();\n" +
"}\n" +
"</scr" + "ipt>\n" +
"</head>\n" +
"<body onLoad=\'step1()\'>\n" +
body.innerHTML +
"</body>\n" +
"</html>\n";
};
</script>',
);
*/

include_once('base.inc.php');
include_once('shop/functions.admin.shop.inc.php');
include_once('shop/functions.shoppingcart.inc.php');

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
        'cb_customcontenttemplate' => 'shop/shopadmin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
    ),
);

$sH = '';

if (isset($_POST["change"])) {
    $aData = array(
        'o_lastedit_timestamp' => time(),
        'o_remarks_internal' => $_POST["remarks_internal"],
        'o_transaction_no' => $_POST["transaction_no"],
        'o_paymentcompleted' => $_POST["order_paymentcompleted"],
        'o_ordercompleted' => $_POST["order_completed"],
        'o_lastedit_user' => ((isset($_SERVER["REMOTE_USER"])) ? $_SERVER["REMOTE_USER"] : ''),
        'o_shipping_service' => $_POST["order_shipping_service"],
        'o_shipping_trackingno' => $_POST["order_shipping_trackingno"],
        'o_id' => $_POST["id"],
    );

    $sQ = \HaaseIT\Tools::buildPSUpdateQuery($aData, DB_ORDERTABLE, 'o_id');
    //echo debug($sQ, true);
    $hResult = $DB->prepare($sQ);
    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
    $hResult->execute();
    header('Location: '.$_SERVER["PHP_SELF"].'?action=edit&id='.$_POST["id"]);
    die();
}

$aPData = [
    'searchform_type' => \HaaseIT\Tools::getFormfield('type', 'openinwork'),
    'searchform_fromday' => \HaaseIT\Tools::getFormfield('fromday', '01'),
    'searchform_frommonth' => \HaaseIT\Tools::getFormfield('frommonth', '01'),
    'searchform_fromyear' => \HaaseIT\Tools::getFormfield('fromyear', '2014'),
    'searchform_today' => \HaaseIT\Tools::getFormfield('today', date("d")),
    'searchform_tomonth' => \HaaseIT\Tools::getFormfield('tomonth', date("m")),
    'searchform_toyear' => \HaaseIT\Tools::getFormfield('toyear', date("Y")),
];

$aShopadmin = handleShopAdmin($CSA);

$P["base"]["cb_customdata"] = array_merge($aPData, $aShopadmin);

/* Druckansicht für Acrylx
$sH .= '<div>
	<a href="#" onclick="return hs.htmlExpand(this, {
			width: 736,
			headingText: \'Acrylx Bestellung\', wrapperClassName: \'titlebar\' })">Druckansicht</a>
	<div class="highslide-maincontent">
		<a class="control" onclick="return hs.getExpander(this).printHtml()" href="#">Drucken</a>
		'.$sShopadmin.'
	</div>
</div>';
*/

$sH .= $aShopadmin["html"];

$P["lang"]["cl_html"] = $sH;

$aP = generatePage($C, $P, $sLang);

echo $twig->render($C["template_base"], $aP);

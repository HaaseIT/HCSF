SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS content_base;
CREATE TABLE IF NOT EXISTS content_base (
cb_id int(11) NOT NULL,
  cb_key varchar(80) NOT NULL,
  cb_group varchar(80) NOT NULL,
  cb_pagetype varchar(16) NOT NULL DEFAULT 'content',
  cb_pageconfig text NOT NULL,
  cb_subnav varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS content_lang;
CREATE TABLE IF NOT EXISTS content_lang (
cl_id int(11) NOT NULL,
  cl_cb int(11) NOT NULL,
  cl_lang varchar(2) NOT NULL,
  cl_html text NOT NULL,
  cl_keywords text NOT NULL,
  cl_description text NOT NULL,
  cl_title varchar(255) NOT NULL,
  cl_background varchar(80) NOT NULL,
  cl_pdf text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TRIGGER IF EXISTS content_lang_update;
DELIMITER //
CREATE TRIGGER content_lang_update AFTER UPDATE ON content_lang
 FOR EACH ROW INSERT INTO content_lang_archive (cl_id, cl_cb, cl_lang, cl_html, cl_keywords, cl_description, cl_title, cl_background, cl_pdf, cla_timestamp)
	  VALUES(OLD.cl_id, OLD.cl_cb, OLD.cl_lang, OLD.cl_html, OLD.cl_keywords, OLD.cl_description, OLD.cl_title, OLD.cl_background, OLD.cl_pdf, NOW())
//
DELIMITER ;

DROP TABLE IF EXISTS content_lang_archive;
CREATE TABLE IF NOT EXISTS content_lang_archive (
cla_id int(11) NOT NULL,
  cl_id int(11) NOT NULL,
  cl_cb int(11) NOT NULL,
  cl_lang varchar(2) NOT NULL,
  cl_html text NOT NULL,
  cl_keywords text NOT NULL,
  cl_description text NOT NULL,
  cl_title varchar(255) NOT NULL,
  cl_background varchar(80) NOT NULL,
  cl_pdf text NOT NULL,
  cla_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS customer;
CREATE TABLE IF NOT EXISTS customer (
cust_id int(11) NOT NULL,
  cust_no varchar(10) NOT NULL,
  cust_email varchar(128) NOT NULL,
  cust_corp varchar(128) NOT NULL,
  cust_name varchar(128) NOT NULL,
  cust_street varchar(256) NOT NULL,
  cust_zip varchar(10) NOT NULL,
  cust_town varchar(128) NOT NULL,
  cust_phone varchar(32) NOT NULL,
  cust_cellphone varchar(32) NOT NULL,
  cust_fax varchar(32) NOT NULL,
  cust_country varchar(32) NOT NULL,
  cust_group varchar(16) NOT NULL,
  cust_password varchar(128) NOT NULL,
  cust_active enum('y','n') NOT NULL DEFAULT 'n',
  cust_emailverified enum('y','n') NOT NULL DEFAULT 'n',
  cust_emailverificationcode varchar(32) NOT NULL,
  cust_tosaccepted enum('y','n') NOT NULL DEFAULT 'n',
  cust_cancellationdisclaimeraccepted enum('y','n') NOT NULL DEFAULT 'n',
  cust_registrationtimestamp int(11) NOT NULL,
  cust_pwresetcode varchar(32) NOT NULL,
  cust_pwresettimestamp int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS itemgroups_base;
CREATE TABLE IF NOT EXISTS itemgroups_base (
itmg_id int(11) NOT NULL,
  itmg_no varchar(12) NOT NULL,
  itmg_name varchar(128) NOT NULL,
  itmg_img varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS itemgroups_text;
CREATE TABLE IF NOT EXISTS itemgroups_text (
itmgt_id int(11) NOT NULL,
  itmgt_pid int(11) NOT NULL,
  itmgt_lang char(2) NOT NULL,
  itmgt_shorttext text NOT NULL,
  itmgt_details text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS item_base;
CREATE TABLE IF NOT EXISTS item_base (
itm_id int(11) NOT NULL,
  itm_no varchar(12) NOT NULL,
  itm_group int(11) NOT NULL,
  itm_img varchar(256) NOT NULL,
  itm_name varchar(128) NOT NULL,
  itm_index varchar(256) NOT NULL,
  itm_price decimal(10,2) NOT NULL,
  itm_vatid varchar(16) NOT NULL,
  itm_rg varchar(2) NOT NULL,
  itm_order int(11) NOT NULL,
  itm_data text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS item_lang;
CREATE TABLE IF NOT EXISTS item_lang (
itml_id int(11) NOT NULL,
  itml_pid int(11) NOT NULL,
  itml_lang char(2) NOT NULL,
  itml_name_override varchar(128) NOT NULL,
  itml_text1 text NOT NULL,
  itml_text2 text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS orders;
CREATE TABLE IF NOT EXISTS orders (
o_id int(11) NOT NULL,
  o_custno int(11) NOT NULL,
  o_email varchar(128) NOT NULL,
  o_corpname varchar(128) NOT NULL,
  o_name varchar(128) NOT NULL,
  o_street varchar(256) NOT NULL,
  o_zip varchar(16) NOT NULL,
  o_town varchar(128) NOT NULL,
  o_phone varchar(128) NOT NULL,
  o_cellphone varchar(128) NOT NULL,
  o_fax varchar(128) NOT NULL,
  o_country varchar(32) NOT NULL,
  o_group varchar(16) NOT NULL,
  o_remarks text NOT NULL,
  o_tos enum('y','n','') NOT NULL,
  o_cancellationdisclaimer enum('y','n') NOT NULL DEFAULT 'n',
  o_paymentmethod varchar(32) NOT NULL,
  o_sumvoll varchar(16) NOT NULL,
  o_sumerm varchar(16) NOT NULL,
  o_sumnettoall varchar(16) NOT NULL,
  o_taxvoll varchar(16) NOT NULL,
  o_taxerm varchar(16) NOT NULL,
  o_sumbruttoall varchar(16) NOT NULL,
  o_mindermenge varchar(16) NOT NULL,
  o_shippingcost varchar(16) NOT NULL,
  o_orderdate varchar(16) NOT NULL,
  o_ordertimestamp varchar(12) NOT NULL,
  o_authed enum('y','n') NOT NULL DEFAULT 'n',
  o_sessiondata text NOT NULL,
  o_postdata text NOT NULL,
  o_remote_address varchar(15) NOT NULL,
  o_ordercompleted enum('y','n','i','s','d') NOT NULL DEFAULT 'n',
  o_paymentcompleted varchar(16) NOT NULL,
  o_srv_hostname varchar(128) NOT NULL,
  o_lastedit_timestamp varchar(12) NOT NULL,
  o_remarks_internal text NOT NULL,
  o_transaction_no varchar(16) NOT NULL,
  o_lastedit_user varchar(16) NOT NULL,
  o_paypal_tx varchar(128) NOT NULL,
  o_shipping_service varchar(16) NOT NULL,
  o_shipping_trackingno varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS orders_items;
CREATE TABLE IF NOT EXISTS orders_items (
oi_id int(11) NOT NULL,
  oi_o_id int(11) NOT NULL,
  oi_cartkey varchar(64) NOT NULL,
  oi_amount int(11) NOT NULL,
  oi_vat_id varchar(16) NOT NULL,
  oi_rg char(2) NOT NULL,
  oi_rg_rebate int(11) NOT NULL,
  oi_itemname varchar(128) NOT NULL,
  oi_price_netto_list varchar(12) DEFAULT NULL,
  oi_price_brutto_list varchar(12) DEFAULT NULL,
  oi_price_netto_sale varchar(12) DEFAULT NULL,
  oi_price_brutto_sale varchar(12) DEFAULT NULL,
  oi_price_netto_rebated varchar(12) NOT NULL,
  oi_price_brutto_rebated varchar(12) NOT NULL,
  oi_price_netto_use varchar(12) NOT NULL,
  oi_price_brutto_use varchar(12) NOT NULL,
  oi_img mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS textcat_base;
CREATE TABLE IF NOT EXISTS textcat_base (
tc_id int(11) NOT NULL,
  tc_key varchar(64) NOT NULL,
  tcl_group varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS textcat_lang;
CREATE TABLE IF NOT EXISTS textcat_lang (
tcl_id int(11) NOT NULL,
  tcl_tcid int(11) NOT NULL,
  tcl_lang varchar(2) NOT NULL,
  tcl_text text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TRIGGER IF EXISTS textcat_lang_update;
DELIMITER //
CREATE TRIGGER textcat_lang_update AFTER UPDATE ON textcat_lang
 FOR EACH ROW INSERT INTO textcat_lang_archive (tcl_id, tcl_tcid, tcl_lang, tcl_text, tcla_timestamp)
	  VALUES(OLD.tcl_id, OLD.tcl_tcid, OLD.tcl_lang, OLD.tcl_text, NOW())
//
DELIMITER ;

DROP TABLE IF EXISTS textcat_lang_archive;
CREATE TABLE IF NOT EXISTS textcat_lang_archive (
tcla_id int(11) NOT NULL,
  tcl_id int(11) NOT NULL,
  tcl_tcid int(11) NOT NULL,
  tcl_lang varchar(2) NOT NULL,
  tcl_text text NOT NULL,
  tcla_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE content_base
 ADD PRIMARY KEY (cb_id), ADD UNIQUE KEY cm_key (cb_key), ADD KEY cm_pagetype (cb_pagetype);

ALTER TABLE content_lang
 ADD PRIMARY KEY (cl_id), ADD KEY co_cm (cl_cb,cl_lang);

ALTER TABLE content_lang_archive
 ADD PRIMARY KEY (cla_id), ADD KEY co_id (cl_id), ADD KEY co_cm (cl_cb,cl_lang);

ALTER TABLE customer
 ADD PRIMARY KEY (cust_id), ADD UNIQUE KEY cust_no (cust_no,cust_email), ADD KEY cust_group (cust_group);

ALTER TABLE itemgroups_base
 ADD PRIMARY KEY (itmg_id), ADD UNIQUE KEY itmg_no (itmg_no);

ALTER TABLE itemgroups_text
 ADD PRIMARY KEY (itmgt_id), ADD KEY itmgt_pid (itmgt_pid,itmgt_lang);

ALTER TABLE item_base
 ADD PRIMARY KEY (itm_id), ADD UNIQUE KEY itm_no (itm_no), ADD KEY itm_name (itm_name), ADD KEY itm_index (itm_index), ADD KEY itm_order (itm_order);

ALTER TABLE item_lang
 ADD PRIMARY KEY (itml_id), ADD KEY itml_lang (itml_lang), ADD KEY itml_pid (itml_pid);

ALTER TABLE orders
 ADD PRIMARY KEY (o_id), ADD KEY io_order_completed (o_ordercompleted), ADD KEY io_orderdate (o_orderdate), ADD KEY o_custno (o_custno);

ALTER TABLE orders_items
 ADD PRIMARY KEY (oi_id), ADD KEY oi_o_id (oi_o_id);

ALTER TABLE textcat_base
 ADD PRIMARY KEY (tc_id), ADD UNIQUE KEY tc_key (tc_key);

ALTER TABLE textcat_lang
 ADD PRIMARY KEY (tcl_id), ADD KEY tcl_tcid (tcl_tcid,tcl_lang);

ALTER TABLE textcat_lang_archive
 ADD PRIMARY KEY (tcla_id), ADD KEY tcl_tcid (tcl_tcid,tcl_lang), ADD KEY tcl_id (tcl_id);


ALTER TABLE content_base
MODIFY cb_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE content_lang
MODIFY cl_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE content_lang_archive
MODIFY cla_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE customer
MODIFY cust_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE itemgroups_base
MODIFY itmg_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE itemgroups_text
MODIFY itmgt_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE item_base
MODIFY itm_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE item_lang
MODIFY itml_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE orders
MODIFY o_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE orders_items
MODIFY oi_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE textcat_base
MODIFY tc_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE textcat_lang
MODIFY tcl_id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE textcat_lang_archive
MODIFY tcla_id int(11) NOT NULL AUTO_INCREMENT;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Daten für Tabelle content_base
--

INSERT INTO content_base (cb_id, cb_key, cb_group, cb_pagetype, cb_pageconfig, cb_subnav) VALUES
(1, '/index.html', 'verschiedenes', 'content', '', '');

--
-- Daten für Tabelle content_lang
--

INSERT INTO content_lang (cl_id, cl_cb, cl_lang, cl_html, cl_keywords, cl_description, cl_title, cl_background, cl_pdf) VALUES
(1, 1, 'en', 'Willkommen bei HCSF by Haase IT', '', '', '', '', '');

--
-- Daten für Tabelle textcat_base
--

INSERT INTO textcat_base (tc_id, tc_key, tcl_group) VALUES
(1, 'misc_add_new_value', ''),
(2, 'misc_page_not_found', ''),
(3, 'misc_page_not_available_lang', '');

--
-- Daten für Tabelle textcat_lang
--

INSERT INTO textcat_lang (tcl_id, tcl_tcid, tcl_lang, tcl_text) VALUES
(1, 1, 'de', 'Neuen Wert hinzufügen'),
(2, 1, 'en', 'Add new value'),
(3, 1, 'es', 'Añadir nuevo valor'),
(4, 2, 'en', 'The page you requested was not found.'),
(5, 2, 'de', 'Die gewünschte Seite konnte nicht gefunden werden.'),
(6, 2, 'es', 'La página que estás buscando no existe.'),
(7, 3, 'en', 'We are sorry, but at this time there is no english version of this page available. Instead, we are displaying this page in its original language.'),
(8, 3, 'es', 'Lo sentimos, pero de momento no tenemos una versión de esta página disponible en español, como reemplazo se le mostrará la página en el idioma predeterminado.'),
(9, 3, 'de', 'Es tut uns leid, aber zur Zeit ist keine deutsche Version dieser Seite verfügbar, als Ersatz zeigen wir Ihnen die Seite in der Standardsprache.');

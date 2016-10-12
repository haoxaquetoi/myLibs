<?php

ini_set('date.timezone', 'Asia/Ho_Chi_Minh');
ini_set('memory_limit', '-1');
define('DS', DIRECTORY_SEPARATOR);
define('SERVER_ROOT', __DIR__ . DS);

require_once('const.php');
require('libs/convert_number_to_words.php');
require ('libs/downloadAttachment.php');















$url = "http://mdm.bacgiang.gov.vn/ContentFolder/HoSoFileDinhKem/source_files/2016/10/04/17235373_QĐ_GQ_16-10-04.docx";
$arrExt = array('xlsx', 'pdf', 'png', 'Docx');

$instance = new downloadAttachment();
$instance->init($arrExt, null, null);

$instance->download($url, __DIR__ . DS . 'uploads');



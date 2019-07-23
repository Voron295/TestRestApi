<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'BLOCK_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('RESTAPI_COMPONENT_BLOCK_ID_PARAM'),
			'TYPE' => 'TEXT'
		),
		'SEF_FOLDER_URL' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('RESTAPI_COMPONENT_SEF_URL_PARAM'),
			'TYPE' => 'TEXT'
		)
	),
);
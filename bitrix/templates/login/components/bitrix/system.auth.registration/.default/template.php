<?
	/**
	 * Bitrix Framework
	 * @package bitrix
	 * @subpackage main
	 * @copyright 2001-2014 Bitrix
	 */
	
	/**
	 * Bitrix vars
	 * @global CMain $APPLICATION
	 * @param array $arParams
	 * @param array $arResult
	 * @param CBitrixComponentTemplate $this
	 */
	
	if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?#='<pre>'; print_r($arResult["AUTH_URL"]); '</pre>';?>
    <style>
        input {
            padding: 5px;
            border-radius: 3px;
            border: 1px solid #ccc;
        }
        select {
            padding: 5px;
            border-radius: 3px;
            border: 1px solid #ccc;
        }
    </style>

<?$APPLICATION->IncludeComponent(
	"bitrix:main.register",
	"ans_register",
	array(
		"COMPONENT_TEMPLATE" => "ans_register",
		"SHOW_FIELDS" => array(
			0 => "EMAIL",
			1 => "NAME",
			2 => "SECOND_NAME",
			3 => "LAST_NAME",
			4 => "PERSONAL_PHONE",
		),
		"REQUIRED_FIELDS" => array(
			0 => "EMAIL",
			1 => "NAME",
			2 => "SECOND_NAME",
			3 => "LAST_NAME",
		),
		"AUTH" => "N",
		"USE_BACKURL" => "N",
		"SUCCESS_PAGE" => "/reg/",
		"SET_TITLE" => "Y",
		"USER_PROPERTY" => array(
			0 => "UF_EXTRGROUP",
		),
		"USER_PROPERTY_NAME" => ""
	),
	false
);?>

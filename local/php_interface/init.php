<?php
#require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once ($_SERVER["DOCUMENT_ROOT"].'/local/class/Core.php');

// Все скрипты вынесены в local/class/all.php
	require_once ($_SERVER["DOCUMENT_ROOT"].'/local/class/all.php');

	
	
// Разобрать потом
/*
AddEventHandler("crm", "OnAfterCrmLeadAdd", array("crmLead", "postAfterAddLead"));
AddEventHandler("crm", "OnBeforeCrmLeadUpdate", array("crmLead", "postAfterCloseLead"));
*/
AddEventHandler("tasks", "OnTaskAdd", array("remindigtaskInit", "Add"));
AddEventHandler("tasks", "OnTaskAdd", array("dealCalendar", "addEvent"));
AddEventHandler("tasks", "OnTaskUpdate", array("dealCalendar", "EventUpdateTask"));
//AddEventHandler("main", "OnProlog", array("dealCalendar", "deadEvent"));

AddEventHandler("support", "OnAfterTicketAdd", array("supportTack", "get"));
?>
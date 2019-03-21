<?php
	require_once ($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/sopdu.remindingtask/class.php');
	class remindigtaskInit{
		function Add(&$arFields){
			$taskID = $arFields;
			if(CModule::IncludeModule("tasks")) {
				$zapros = CTasks::GetByID($taskID);
				$taskRow = $zapros->GetNext();
				Core::Dump($taskRow);
				remindigtask::taskAdd(
					$taskID,
					$taskRow["RESPONSIBLE_ID"],
					$taskRow["CREATED_BY"]
				);
			}
			return;
		}
	}
	class crmLead {
		function leadOutPost($email, $lead_id) {
			$arEventFields = array(
				"LEAD_ID" => $lead_id,
				"EMAIL_TO" => $email
			);
			CEvent::Send("OUT_HOURS_LEAD", "s1", $arEventFields);
			return "";
		}
	}
	class dealCalendar {
		function addEvent($arFields, $dead){
			$taskID = $arFields;
			if(CModule::IncludeModule("tasks")) {
				$zapros = CTasks::GetByID($taskID);
				$taskRow = $zapros->GetNext();
				global $DB;
				$date_today = date('Y-m-d  H:i:s');
				$cal_type = 34;
				if($dead){
					$cal_type = 36;
				}
				if($taskRow['CLOSED_DATE']){
					$cal_type = 35;
				}
				if($taskRow['UF_TASK_SUMM_TYPE'] == 114) {
					$typeDeal = "+";
				} elseif($taskRow['UF_TASK_SUMM_TYPE'] == 115) {
					$typeDeal = "-";
				} else {
					$err = 1;
				}
				if(!$taskRow['DEADLINE'] or !$taskRow['UF_AUTO_386531497848']) {
					$err = 1;
				}
				if($err!=1){
					$taskRow['UF_AUTO_386531497848'] = str_replace("|", " ", $taskRow['UF_AUTO_386531497848']);
					$name_task = $typeDeal.' '.$taskRow['UF_AUTO_386531497848'].' '.$taskRow['TITLE'];
					$startline = date('Y-m-d', strtotime($taskRow['DEADLINE_ORIG']))." 00:00:00";
					$deadline = date('Y-m-d', strtotime($taskRow['DEADLINE_ORIG']))." 23:59:59";
					$unixStartline = strtotime($startline);
					$unixDeadline = strtotime($deadline);
					$secondDiff = round(abs($unixDeadline - $unixStartline),2);
					$description = "[URL=/company/personal/user/".$taskRow['CREATED_BY']."/tasks/task/view/".$taskRow['ID']."/]".$taskRow['TITLE']."[/URL]";
					$strSql = "INSERT INTO `b_calendar_event` (`ACTIVE`, `DELETED`, `CAL_TYPE`, `OWNER_ID`, `NAME`, `DATE_FROM`, `DATE_TO`, `TZ_FROM`, `TZ_TO`, `TZ_OFFSET_FROM`, `TZ_OFFSET_TO`, `DATE_FROM_TS_UTC`, `DATE_TO_TS_UTC`, `DT_SKIP_TIME`, `DT_LENGTH`, `EVENT_TYPE`, `CREATED_BY`, `DATE_CREATE`, `TIMESTAMP_X`, `DESCRIPTION`, `DT_FROM`, `DT_TO`, `PRIVATE_EVENT`, `ACCESSIBILITY`, `IMPORTANCE`, `IS_MEETING`, `MEETING_STATUS`, `MEETING_HOST`, `MEETING`, `LOCATION`, `REMIND`, `COLOR`, `TEXT_COLOR`, `RRULE`, `EXDATE`, `DAV_XML_ID`, `DAV_EXCH_LABEL`, `CAL_DAV_LABEL`, `VERSION`, `ATTENDEES_CODES`, `RECURRENCE_ID`, `RELATIONS`, `SEARCHABLE_CONTENT`, `SECTION_ID`) VALUES ('Y', 'N', 'company_calendar', '0', '".$name_task."', '".$startline."', '".$deadline."', 'Europe/Moscow', 'Europe/Moscow', '10800', '10800', '".$unixStartline."', '".$unixDeadline."', 'N', '".$secondDiff."', NULL, '18', '".$date_today."', '".$date_today."', '".$description."', NULL, NULL, NULL, 'busy', 'normal', NULL, NULL, '18', 'a:5:{s:9:\"HOST_NAME\";s:23:\"Ярослав Зуев\";s:6:\"NOTIFY\";b:1;s:12:\"ALLOW_INVITE\";b:1;s:15:\"MEETING_CREATOR\";s:1:\"1\";s:8:\"REINVITE\";b:0;}', '', 'a:1:{i:0;a:2:{s:4:\"type\";s:3:\"min\";s:5:\"count\";d:15;}}', '', NULL, '', '', '', NULL, NULL, '1', NULL, NULL, NULL, ' ', NULL);";
					$res = $DB->Query($strSql, false, $err_mess);
					$strSql = "select last_insert_id();";
					$res = $DB->Query($strSql, false, $err_mess);
					while ($row = $res->Fetch())
					{
						$id_event = $row['last_insert_id()'];
						$strSql = "INSERT INTO `b_calendar_event_sect` (`EVENT_ID`, `SECT_ID`, `REL`) VALUES ('".$id_event."', '".$cal_type."', NULL);";
						$res2 = $DB->Query($strSql, false, $err_mess);
						$strSql = "UPDATE `b_calendar_event` SET `PARENT_ID` = '".$id_event."' WHERE `b_calendar_event`.`ID` = ".$id_event.";";
						$res2 = $DB->Query($strSql, false, $err_mess);
					}
					dealCalendar::addEventIB($taskID, $taskRow['DEADLINE'], $taskRow['UF_TASK_SUMM_TYPE'], $taskRow['UF_AUTO_386531497848'], $id_event);
				}
			}
		}
		function EventUpdateTask(&$arFields){
			$taskID = $arFields;
			global $DB;
			if(CModule::IncludeModule("tasks")) {
				$zapros = CTasks::GetByID($taskID);
				$taskRow = $zapros->GetNext();
				$arSelect = Array("ID", "PROPERTY_TASK_ID", "PROPERTY_DEADLINE", "PROPERTY_TYPE_DEAL", "PROPERTY_SUMM", "PROPERTY_ID_EVENT");
				$arFilter = Array("IBLOCK_ID"=>32, "PROPERTY_TASK_ID"=>$taskRow['ID']);
				$res = CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
				if($ob = $res->GetNextElement())
				{
					$arFields = $ob->GetFields();
					if($taskRow['DEADLINE']!=$arFields['PROPERTY_DEADLINE_VALUE']){
						CIBlockElement::SetPropertyValues(
							$arFields['ID'],
							32,
							$taskRow['DEADLINE'],
							'DEADLINE'
						);
					}
					if($taskRow['UF_TASK_SUMM_TYPE']!=$arFields['PROPERTY_TYPE_DEAL_VALUE']){
						CIBlockElement::SetPropertyValues(
							$arFields['ID'],
							32,
							$taskRow['UF_TASK_SUMM_TYPE'],
							'TYPE_DEAL'
						);
					}
					if($taskRow['UF_AUTO_386531497848']!=$arFields['PROPERTY_SUMM_VALUE']){
						CIBlockElement::SetPropertyValues(
							$arFields['ID'],
							32,
							$taskRow['UF_AUTO_386531497848'],
							'SUMM'
						);
					}
					$strSql = "DELETE FROM `b_calendar_event` WHERE `b_calendar_event`.`ID` = ".$arFields['PROPERTY_ID_EVENT_VALUE'];
					$res = $DB->Query($strSql, false, $err_mess);
					$strSql = "DELETE FROM `b_calendar_event_sect` WHERE `b_calendar_event_sect`.`EVENT_ID` = ".$arFields['PROPERTY_ID_EVENT_VALUE'];
					$res = $DB->Query($strSql, false, $err_mess);
					dealCalendar::addEvent($taskRow['ID'], 0);
				} else {
					dealCalendar::addEvent($taskRow['ID'], 0);
				}
			}
		}
		function addEventIB($taskID, $deadline, $typeDeal, $summ, $id_event) {
			$arSelect = Array("ID", "PROPERTY_TASK_ID", "PROPERTY_DEADLINE", "PROPERTY_TYPE_DEAL", "PROPERTY_SUMM", "PROPERTY_ID_EVENT");
			$arFilter = Array("IBLOCK_ID"=>32, "PROPERTY_TASK_ID"=>$taskID);
			$res = CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
			if($ob = $res->GetNextElement()){
				$arFields = $ob->GetFields();
				if($id_event!=$arFields['PROPERTY_ID_EVENT_VALUE']){
					CIBlockElement::SetPropertyValues(
						$arFields['ID'],
						32,
						$id_event,
						'ID_EVENT'
					);
				}
			} else {
				$arElFields = array(
					"ACTIVE" => "Y",
					"IBLOCK_ID" => 32,
					"NAME" => "Оплата к задаче №".$taskID,
					"PROPERTY_VALUES" => array(
						"TASK_ID" =>$taskID,
						"DEADLINE" =>$deadline,
						"TYPE_DEAL" =>$typeDeal,
						"SUMM" =>$summ,
						"ID_EVENT" =>$id_event
					)
				);
				$oElement = new CIBlockElement();
				$idElement = $oElement->Add($arElFields, false, false, true);
			}
		}
		function deadEvent () {
			require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
			global $DB;
			$arSelect = Array("ID", "PROPERTY_DEADLINE", "PROPERTY_ID_EVENT", "PROPERTY_TASK_ID", "CLOSED_DATE");
			$arFilter = Array("IBLOCK_ID"=>32);
			$result = CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
			while($ob = $result->GetNextElement())
			{
				$arFields = $ob->GetFields();
				if(strtotime($arFields['PROPERTY_DEADLINE_VALUE']) < strtotime(date('d.m.Y H:i:s'))){
					$strSql = "DELETE FROM `b_calendar_event` WHERE `b_calendar_event`.`ID` = ".$arFields['PROPERTY_ID_EVENT_VALUE'];
					$res = $DB->Query($strSql, false, $err_mess);
					$strSql = "DELETE FROM `b_calendar_event_sect` WHERE `b_calendar_event_sect`.`EVENT_ID` = ".$arFields['PROPERTY_ID_EVENT_VALUE'];
					$res = $DB->Query($strSql, false, $err_mess);
					dealCalendar::addEvent($arFields['PROPERTY_TASK_ID_VALUE'], 1);
				}
			}
			//return "dealCalendar::deadEvent();";
		}
	}
	
	class supportTack{
		function get(&$arFields){
			Core::Dump($arFields);
			return;
		}
	}
	
	/*class crmLead {
		function postAfterAddLead($arFields) {
			$arEventFields = array(
				"LEAD_ID" => $arFields['ID'],
				"NAME" => $arFields['NAME'],
				"SECOND_NAME" => $arFields['SECOND_NAME'],
				"LAST_NAME" => $arFields['LAST_NAME'],
				"EMAIL_TO" => $arFields['FM']['EMAIL']['n0']['VALUE'],
			);
			CEvent::Send("ADD_LEAD", "s1", $arEventFields);
		}
		function postAfterCloseLead($arFields) {
			if($arFields['STATUS_ID'] == "CONVERTED") {
				$res = CCrmContact::GetByID($arFields['CONTACT_ID']);
				while($ob = $res->GetNext()){
				  $arContacts = $ob;
				}
				$arEventFields = array(
					"ARFIELDS" => print_r($arContacts, true),
					"FULL_NAME" => $arFields['FULL_NAME'],
					"LEAD_ID" => $arFields['ID']
				);
				CEvent::Send("CLOSE_LEAD", "s1", $arEventFields);
			}
		}
	}
	*/
	?>
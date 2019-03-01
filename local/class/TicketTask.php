<?php
	/** Подключаем необходимые битриксовские модули */
	CModule::IncludeModule("tasks"); // модуль задачи
	CModule::IncludeModule("support"); // модель техническая поддержка
	CModule::IncludeModule("forum"); // модуль форум
	CModule::IncludeModule("socialnetwork"); // модуль социальная сеть
	CModule::IncludeModule("blog"); // модуль блог
	CModule::IncludeModule("im"); // модуль мессенджера (бизнес-чат)
	CModule::IncludeModule("main"); // главный модуль
	CModule::IncludeModule("iblock"); // модуль информационных блоков
	CModule::IncludeModule("crm"); // модуль CRM
	use Bitrix\Main\Mail\Event; // модуль почтовых событий
	
	/** Техническая функция для отладки */
	class Dump {
		public function main($value){
			$filePath = $_SERVER["DOCUMENT_ROOT"].'/MyDump.txt';
			$file = fopen($filePath, "w");
			fwrite($file, print_r($value, 1));
			fclose();
			return;
		}
	}
	
	/**
	Регистрируем пользователя
	Создаем лид
	Добавляем в группу
	 */
	class regUser {
		
		/** Проверяем есть-ли пользователь в контактах CRM */
		private function getContact($name, $lastName){ // Входные параметры имя и фамилия
			global $DB;
			$zapros = $DB->Query("
            	select ID from b_crm_contact where NAME = '".$name."' and LAST_NAME = '".$lastName."'
            ");
			if($zapros->Fetch()){
				return 'Y'; // такой человек есть в контактах CRM
			} else {
				return 'N'; // такого человека нет в контактах CRM
			}
		}
		
		/** Проверяем есть-ли пользователь в лидах CRM */
		private function getLead($name, $lastName){ // Входные параметры имя и фамилия
			global $DB;
			$zapros = $DB->Query("
            	select ID from b_crm_lead where NAME = '".$name."' and LAST_NAME = '".$lastName."'
            ");
			if($zapros->Fetch()){
				return 'Y'; // такой человек есть в контактах CRM
			} else {
				return 'N'; // такого человека нет в контактах CRM
			}
		}
		
		/** Получение ответственного за лид */
		private function getAudutors($extrgroup){
			$zapros = CIBlockElement::GetPropertyValues(33, array());
			while ($row = $zapros->Fetch()){
				$result[$row["IBLOCK_ELEMENT_ID"]] = $row;
			}
			return $result[$extrgroup];
		}
		
		/** Создаем лид */
		private function addLead($name, $lastName, $group) { // Входнгые параметры имя, фамилия и группа (группа не обязательная)
			if(
				(self::getContact($name, $lastName) == 'N' || self::getLead($name, $lastName) == 'N') and
				$group == 7
			){
				global $DB;
				$DB->Query("
	                insert into b_crm_lead
	                (
	                  DATE_CREATE,
	                  DATE_MODIFY,
	                  CREATED_BY_ID,
	                  MODIFY_BY_ID,
	                  ASSIGNED_BY_ID,
	                  OPENED,
	                  STATUS_ID,
	                  SOURCE_DESCRIPTION,
	                  TITLE,
	                  FULL_NAME,
	                  NAME,
	                  LAST_NAME,
	                  COMMENTS
	                ) value (
	                  'NOW()',
	                  'NOW()',
	                  '1',
	                  '1',
	                  '1',
	                  'Y',
	                  'NEW',
	                  'Форма регистрации ЛК ТП',
	                  '".$lastName." ".$name.": Заяка на регистрацию ЛК ТП',
	                  '".$lastName." ".$name."',
	                  '".$name."',
	                  '".$lastName."',
	                  'Подана заявка на регистрацию личного кабинета Технической Поддержки от: ".$lastName." ".$name."'
	                )
	            ");
				return;
			} else {
				return;
			}
		}
		
		private function getMaxIdLead(){
			global $DB;
			$zapros = $DB->Query("
            SELECT * FROM b_crm_lead WHERE ID=(SELECT MAX(ID) FROM b_crm_lead);
        ");
			return $zapros->Fetch();
		}
		
		public function main(&$arFields){
			Dump::main(self::getAudutors($arFields["UF_EXTRGROUP"]));
			#Dump::main($arFields);
		}
	}
?>
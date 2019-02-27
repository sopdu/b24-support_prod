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
	}
?>
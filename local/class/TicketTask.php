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
	
	/** Функция собирает настройки из инфоблока "Технический" */
	class setting {
		public function main ($extranetGroup = '', $extranetSettingId = ''){
			if(!empty($extranetGroup) || !empty($extranetSettingId)){
				if(empty($extranetGroup) and !empty($extranetSettingId)){
					$zapros = CIBlockElement::GetPropertyValues(33, array());
					while ($row = $zapros->Fetch()){
						$result[$row["IBLOCK_ELEMENT_ID"]] = $row;
					}
					return $result[$extranetSettingId];
				}
				if(!empty($extranetGroup) and empty($extranetSettingId)){
					
					return;
				}
			} else {
				return;
			}
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
		
		/** Создаем лид */
		private function addLead($name, $lastName, $group, $extranetSettingId) { // Входнгые параметры имя, фамилия и группа (группа не обязательная)
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
	                  '".setting::main('', $extranetSettingId)[154][0]."',
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
		
		/** Ставим задачу ответственном, что создан лид и надо его проверить */
		private function addTask($name, $lastName, $group, $extranetSettingId){
			if($group == 7){
				$obTask = new CTasks;
				$obTask->Add(
					array(
						"TITLE"                 =>  'В тех поддержке зарегистрировался новый пользователь',
						"DESCRIPTION"           =>  'Зарегистрировался новый поьзователь в тех поддержке: <strong>'.$name.' '.$lastName.'</strong>. На основании его создал лид',
						"AUDITORS"              =>  setting::main('', $extranetSettingId)[154],
						"ACCOMPLICES"           =>  setting::main('', $extranetSettingId)[154],
						"ALLOW_TIME_TRACKING"   =>  'Y',
						"TAGS"                  =>  'Регистрация в тех поддержке',
						"ALLOW_CHANGE_DEADLINE" =>  'Y',
						"TASK_CONTROL"          =>  'Y',
						"RESPONSIBLE_ID"        =>  setting::main('', $extranetSettingId)[154][0]
					)
				);
			}
			return;
		}
		
		private function getMaxIdLead(){
			global $DB;
			$zapros = $DB->Query("
            SELECT * FROM b_crm_lead WHERE ID=(SELECT MAX(ID) FROM b_crm_lead);
        ");
			return $zapros->Fetch();
		}
		
		/** Уведомления о том, что зарегистрировался новый пользователь (лид) */
		private function addNotify($extranetSettingId){
			CAdminNotify::Add( // уведомления в админ панель
				array(
					"MESSAGE"   =>  'Зарегистрировался новый пользователь технической поддержки: '.self::getMaxIdLead()["FULL_NAME"].'. <a target="_blank" href="/crm/lead/details/'.self::getMaxIdLead()["ID"].'">Перейти к списку</a>'
				)
			);
			foreach (setting::main('', $extranetSettingId)[154] as $userNotify){
				CIMNotify::Add( // уведомления в "колокольчик"
					array(
						"FROM_USER_ID" => 1,
						"TO_USER_ID" => $userNotify,
						"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => "im",
						"NOTIFY_TAG"    => 'support',
						"NOTIFY_MESSAGE" => 'Зарегистрировался новый пользователь технической поддержки: '.self::getMaxIdLead()["FULL_NAME"].'. <a target="_blank" href="/crm/lead/details/'.self::getMaxIdLead()["ID"].'/">Перейти</a>'
					)
				);
			}
			return;
		}
		
		/** Сообщение в живую ленту о том, что зарегистрировался новый пользователь */
		private function addSocNet($extranetSettingId){
			// создаем пост в блок который потом будет отправлен в живую ленту
			$group = explode('.', setting::main('', $extranetSettingId)[153]);
			$group = $group[0];
			$postID = CBlogPost::Add(
				array(
					"TITLE" => 'Зарегистрировался новый пользователь технической поддержки',
					"DETAIL_TEXT" => self::getMaxIdLead()["FULL_NAME"].' [URL=/crm/lead/details/'.self::getMaxIdLead()["ID"].'/]Перейти в лид[/URL]',
					"BLOG_ID" => 1,
					"AUTHOR_ID" => 1, //ID блога, в котором будет запись
					"DATE_PUBLISH" => date('d.m.Y H:i'),
					"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
					"ENABLE_TRACKBACK" => 'N',
					"ENABLE_COMMENTS" => 'Y'
				)
			);
			$eventID = CSocNetLog::Add(
				array(
					'EVENT_ID'     => 'blog_post',
					'=LOG_DATE'    => 'now()',
					'TITLE_TEMPLATE' => '#USER_NAME# добавил(а) сообщение "#TITLE#" в блог',
					'TITLE'    => "Зарегистрировался новый пользователь технической поддержки",
					'MESSAGE'  => 'Зарегистрировался пользователь '.self::getMaxIdLead()["FULL_NAME"].' [URL=/crm/lead/details/'.self::getMaxIdLead()["ID"].'/]Перейти в лид[/URL]',
					'TEXT_MESSAGE'  => 'Зарегистрировался пользователь '.self::getMaxIdLead()["FULL_NAME"].' [URL=/crm/lead/details/'.self::getMaxIdLead()["ID"].'/]Перейти в лид[/URL]',
					'MODULE_ID'     => 'blog',
					'CALLBACK_FUNC' => false,
					'SOURCE_ID'     => $postID,
					'ENABLE_COMMENTS'  => 'Y',
					'RATING_TYPE_ID'   => 'BLOG_POST',
					'ENTITY_TYPE' => 'U',
					#'ENTITY_ID'   => '1',
					'ENTITY_ID'   => $group,
					'USER_ID'     => '1',
					'URL' => '/company/personal/user/1/blog/'.$postID.'/',
					"GROUP_ID"  => $group,
					"CATEGORY_ID" => $group,
				)
			);
			CSocNetLogRights::Add ( $eventID, array ("G3") );
			return;
		}
		
		/** Добавляем пользователя в группу экстранета */
		private function extranetGroup($userId, $extranetSettingId){
			$group = explode('.', setting::main('', $extranetSettingId)[153]);
			$group = $group[0];
			CSocNetUserToGroup::Add(
				array(
					"USER_ID"               =>  $userId,
					"GROUP_ID"              =>  $group,
					"ROLE"                  =>  SONET_ROLES_USER,
					"=DATE_CREATE"          =>  $GLOBALS["DB"]->CurrentTimeFunction(),
					"=DATE_UPDATE"          =>  $GLOBALS["DB"]->CurrentTimeFunction(),
					"INITIATED_BY_TYPE"     =>  SONET_INITIATED_BY_GROUP,
					"INITIATED_BY_USER_ID"  =>  1,
					"MESSAGE"               =>  'Новый пользователь'
				)
			);
			return;
		}
		
		public function main(&$arFields){
			self::addLead(
				$arFields["NAME"],
				$arFields["LAST_NAME"],
				$arFields["GROUP_ID"][0],
				$arFields["UF_EXTRGROUP"]
			);
			self::addNotify($arFields["UF_EXTRGROUP"]);
			self::addSocNet($arFields["UF_EXTRGROUP"]);
			self::extranetGroup($arFields["ID"], $arFields["UF_EXTRGROUP"]);
			
			/* Не создаем задачу о том, что создан новый лид
	        self::addTask(
		        $arFields["NAME"],
		        $arFields["LAST_NAME"],
		        $arFields["GROUP_ID"][0]
	        );
	        */
			return;
		}
	}
	
	/** Опперации при создании тикета */
	class newTicket {
		
		/** Проверяем наличие задачи */
		private function getTask($ticketID){
			$zapros = CTasks::GetList(
				array(),
				array(),
				array(),
				array()
			);
			while ($row = $zapros->Fetch()){
				$exp = explode(': ', $row["NAME"]);
				$exp = explode('_', $exp[0]);
				if($exp[1] == $ticketID){
					$resultZapros[] = $row;
				}
			}
			if(empty($resultZapros)) {
				$result = 0;
			} else {
				$result = 1;
			}
			return $result;
		}
		
		/** получение тикета */
		private function getTicket($ticketID){
			$zapros = CTicket::GetByID($ticketID, "ru", "N")->Fetch();
			return $zapros;
		}
		
		/** Получаем id группы */
		private function getGroup($author){
			global $DB;
			$groupGroupName = CIBlockElement::GetByID(CUser::GetByID($author)->Fetch()["UF_EXTRGROUP"])->Fetch()["NAME"];
			$zapros = $DB->Query("
            select ID from b_sonet_group where NAME = '".$groupGroupName."'
        ");
			return $zapros->Fetch()["ID"];
		}
		
		/** добавляем задачу */
		// Требуеться тестирование
		private function addTask($ticketID, $ticketMessage, $author){
			if(self::getTask($ticketID) == 0){
				$getTicket = self::getTicket($ticketID);
				if($getTicket["CRITICALITY_ID"] == 4){
					$critical = 0;
					$criticalText = '<b>Критичность:</b> Низкая';
				} elseif($getTicket["CRITICALITY_ID"] == 5){
					$critical = 1;
					$criticalText = '<b>Критичность:</b> Средняя';
				} elseif($getTicket["CRITICALITY_ID"] == 6){
					$critical = 3;
					$criticalText = '<b>Критичность:</b> Высокая';
				} else {
					$critical = '';
				}
				$addToMessage = '
                	<br /><br />'.$criticalText.'
                	<br /><br /><br />
                	_______________________________________________________
                	<br /><br />
                	Что бы ответить пользователю начните комментарий с символов:<br />
                	~|toUser|~
                	<br />
                	<strong>Например: </strong>~|toUser|~ Услуги по технической поддержки оказаны.<br />
                
            	';
				$obTask = new CTasks;
				$obTask->Add(
					array(
						"TITLE"                 =>  'Ticket_'.$ticketID.': '.$getTicket["TITLE"],
						"DESCRIPTION"           =>  $ticketMessage.$addToMessage,
						"PRIORITY"              =>  $critical,
						#"ACCOMPLICES"           =>  array(8),
						"AUDITORS"              =>  array(8),
						"ALLOW_TIME_TRACKING"   =>  'Y',
						"TAGS"                  =>  'Тикет тех поддержки',
						"ALLOW_CHANGE_DEADLINE" =>  'Y',
						"TASK_CONTROL"          =>  'Y',
						"RESPONSIBLE_ID"        =>  $getTicket["RESPONSIBLE_USER_ID"],
						"GROUP_ID"              =>  self::getGroup($author)
					)
				);
			}
			return;
		}
	
		public function main(&$arFields) {
			// getGroup
			// MESSAGE_AUTHOR_USER_ID
			Dump::main(self::getGroup($arFields["MESSAGE_AUTHOR_USER_ID"]));
			
			
			return;
		}
		
		
		/*
		 * Array
(
    [SITE_ID] => co
    [CLOSE] =>
    [TITLE] => dsad
    [CRITICALITY_ID] =>
    [CATEGORY_ID] =>
    [MARK_ID] =>
    [MESSAGE] => dsad
    [HIDDEN] => N
    [FILES] => Array
        (
        )

    [COUPON] =>
    [PUBLIC_EDIT_URL] => /extranet/
    [AUTO_CLOSE_DAYS] => 7
    [MESSAGE_AUTHOR_SID] =>
    [MESSAGE_AUTHOR_USER_ID] => 102
    [MESSAGE_CREATED_MODULE_NAME] => support
    [MESSAGE_SOURCE_ID] => 0
    [LOG] => N
    [IS_LOG] => N
    [ID] => 8
    [MID] => 15
)
		 *
		 *
		public function main($arFields) {
			if(CModule::IncludeModule("support")){
				if($arFields != null){
					if(empty($arFields)){
						Dump::main('is not array');
					} else {
						Dump::main($arFields);
						#Dump::main('function is working');
					}
				} else {
					Dump::main('this null');
				}
			} else {
				Dump::main('no module');
			}
			
			return;
		}
		*/
	}
	
	
?>
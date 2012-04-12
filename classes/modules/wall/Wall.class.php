<?php
/*-------------------------------------------------------
*
*   LiveStreet Engine Social Networking
*   Copyright © 2008 Mzhelskiy Maxim
*
*--------------------------------------------------------
*
*   Official site: www.livestreet.ru
*   Contact e-mail: rus.engine@gmail.com
*
*   GNU General Public License, version 2:
*   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
---------------------------------------------------------
*/

/**
 * Модуль Wall - записи на стене профиля пользователя
 *
 */
class ModuleWall extends Module {
			
	protected $oMapper;
	protected $oUserCurrent;

			
	/**
	 * Инициализация
	 *
	 */
	public function Init() {		
		$this->oMapper=Engine::GetMapper(__CLASS__);
		$this->oUserCurrent=$this->User_GetUserCurrent();
	}

	/**
	 * Добавление записи на стену
	 *
	 * @param ModuleWall_EntityWall $oWall
	 *
	 * @return bool | ModuleWall_EntityWall
	 */
	public function AddWall($oWall) {
		if (!$oWall->getDateAdd()) {
			$oWall->setDateAdd(date("Y-m-d H:i:s"));
		}
		if (!$oWall->getIp()) {
			$oWall->setIp(func_getIp());
		}
		if ($iId=$this->oMapper->AddWall($oWall)) {
			$oWall->setId($iId);
			/**
			 * Обновляем данные у родительской записи
			 */
			if ($oPidWall=$oWall->GetPidWall()) {
				$this->UpdatePidWall($oPidWall);
			}
			return $oWall;
		}
		return false;
	}

	/**
	 * Обновление записи
	 *
	 * @param ModuleWall_EntityWall $oWall
	 *
	 * @return bool
	 */
	public function UpdateWall($oWall) {
		return $this->oMapper->UpdateWall($oWall);
	}

	/**
	 * Получение списка записей по фильтру
	 *
	 * @param $aFilter
	 * @param $aOrder
	 * @param int $iCurrPage
	 * @param int $iPerPage
	 * @param array $aAllowData
	 *
	 * @return array('collection'=>array,'count'=>int)
	 */
	public function GetWall($aFilter,$aOrder,$iCurrPage=1,$iPerPage=10,$aAllowData=null) {
		$aResult=array(
			'collection'=>$this->oMapper->GetWall($aFilter,$aOrder,$iCount,$iCurrPage,$iPerPage),
			'count'=>$iCount
		);
		$aResult['collection']=$this->GetWallAdditionalData($aResult['collection'],$aAllowData);
		return $aResult;
	}
	/**
	 * Возвращает число сообщений на стене по фильтру
	 *
	 * @param $aFilter
	 *
	 * @return int
	 */
	public function GetCountWall($aFilter) {
		return $this->oMapper->GetCountWall($aFilter);
	}

	/**
	 * Получение записей по ID, без дополнительных данных
	 *
	 * @param array $aWallId
	 *
	 * @return array
	 */
	public function GetWallsByArrayId($aWallId) {
		if (!is_array($aWallId)) {
			$aWallId=array($aWallId);
		}
		$aWallId=array_unique($aWallId);
		$aWalls=array();
		$aResult = $this->oMapper->GetWallsByArrayId($aWallId);
		foreach ($aResult as $oWall) {
			$aWalls[$oWall->getId()]=$oWall;
		}
		return $aWalls;
	}

	/**
	 * Получение записей по ID с дополнительные связаными данными
	 *
	 * @param $aWallId
	 * @param array $aAllowData
	 *
	 * @return array
	 */
	public function GetWallAdditionalData($aWallId,$aAllowData=null) {
		if (is_null($aAllowData)) {
			$aAllowData=array('user'=>array(),'reply');
		}
		func_array_simpleflip($aAllowData);
		if (!is_array($aWallId)) {
			$aWallId=array($aWallId);
		}

		$aWalls=$this->GetWallsByArrayId($aWallId);
		/**
		 * Формируем ID дополнительных данных, которые нужно получить
		 */
		$aUserId=array();
		$aWallReplyId=array();
		foreach ($aWalls as $oWall) {
			if (isset($aAllowData['user'])) {
				$aUserId[]=$oWall->getUserId();
			}
			/**
			 * Список последних записей хранится в строке через запятую
			 */
			if (isset($aAllowData['reply']) and is_null($oWall->getPid()) and $oWall->getLastReply()) {
				$aReply=explode(',',trim($oWall->getLastReply()));
				$aWallReplyId=array_merge($aWallReplyId,$aReply);
			}
		}
		/**
		 * Получаем дополнительные данные
		 */
		$aUsers=isset($aAllowData['user']) && is_array($aAllowData['user']) ? $this->User_GetUsersAdditionalData($aUserId,$aAllowData['user']) : $this->User_GetUsersAdditionalData($aUserId);
		$aWallReply=array();
		if (isset($aAllowData['reply']) and count($aWallReplyId)) {
			$aWallReply=$this->GetWallAdditionalData($aWallReplyId,array('user'=>array()));
		}
		/**
		 * Добавляем данные к результату
		 */
		foreach ($aWalls as $oWall) {
			if (isset($aUsers[$oWall->getUserId()])) {
				$oWall->setUser($aUsers[$oWall->getUserId()]);
			} else {
				$oWall->setUser(null); // или $oWall->setUser(new ModuleUser_EntityUser());
			}
			$aReply=array();
			if ($oWall->getLastReply()) {
				$aReplyId=explode(',',trim($oWall->getLastReply()));
				foreach($aReplyId as $iReplyId) {
					if (isset($aWallReply[$iReplyId])) {
						$aReply[]=$aWallReply[$iReplyId];
					}
				}
			}
			$oWall->setLastReplyWall($aReply);
		}
		return $aWalls;
	}

	/**
	 * Получение записи по ID
	 *
	 * @param int $iId
	 *
	 * @return ModuleWall_EntityWall
	 */
	public function GetWallById($iId) {
		$aResult=$this->GetWallAdditionalData($iId);
		if (isset($aResult[$iId])) {
			return $aResult[$iId];
		}
		return null;
	}

	/**
	 * Обновляет родительские данные у записи - количество ответов и ID последних ответов
	 *
	 * @param ModuleWall_EntityWall $oWall
	 *
	 * @param null|int $iLimit
	 */
	public function UpdatePidWall($oWall,$iLimit=null) {
		if (is_null($iLimit)) {
			$iLimit=Config::Get('module.wall.count_last_reply');
		}

		$aResult=$this->GetWall(array('pid'=>$oWall->getId()),array('id'=>'desc'),1,$iLimit,array());
		if ($aResult['count']) {
			$oWall->setCountReply($aResult['count']);
			$aKeys=array_keys($aResult['collection']);
			sort($aKeys,SORT_NUMERIC);
			$oWall->setLastReply(join(',',$aKeys));
			$this->UpdateWall($oWall);
		}
	}

}
?>
<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('highloadblock'))
{
	ShowError(GetMessage("F_NO_MODULE"));
	return 0;
}

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

$hlblock_id = $arParams['BLOCK_ID'];
if (empty($hlblock_id))
{
	ShowError(GetMessage('RESTAPI_NO_ID'));
	return 0;
}
$hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();
if (empty($hlblock))
{
	ShowError(GetMessage('RESTAPI_404'));
	return 0;
}

$entity = HL\HighloadBlockTable::compileEntity($hlblock);
$HLClass = $entity->getDataClass();

$arDefaultUrlTemplates404 = array(
	"method" => "#method#",
	"method1" => "#method#/"
);

$arDefaultVariableAliases404 = array();
$arDefaultVariableAliases = array();

$arComponentVariables = array(
	"method"
);

$arVariables = array();

$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

$componentPage = CComponentEngine::ParseComponentPath(
	$arParams["SEF_FOLDER_URL"],
	$arUrlTemplates,
	$arVariables
);
CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

if(!class_exists('RestApiMethods')) {
	class RestApiMethods {
		private $_HLClass;
		
		public function __construct($hlclass) {
			$this->_HLClass = $hlclass;
		}
		
		public function list() {
			$result = array();
			$hlData = $this->_HLClass::getList(array(
			  'select' => array('ID', 'UF_NAME', 'UF_ADDRESS', 'UF_UPDATED_AT', 'UF_CREATED_AT'),
			  'order' => array('ID' => 'ASC'),
			  'filter' => array()
			));
			while ($arItem = $hlData->Fetch()) {
				$result[] = array(
					'id' => $arItem['ID'],
					'name' => $arItem['UF_NAME'],
					'address' => $arItem['UF_ADDRESS'],
					'updated_at' => $arItem['UF_UPDATED_AT']->toString(),
					'created_at' => $arItem['UF_CREATED_AT']->toString()
				);
			}
			return $result;
		}
		
		public function add($jsonData) {
			$dateNow = new Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s');
			$arAddedIds = array();
			foreach($jsonData as $el) {
				$arElementFields = array(
					'UF_NAME' => $el['name'],
					'UF_ADDRESS' => $el['address'],
					'UF_UPDATED_AT' => $dateNow,
					'UF_CREATED_AT' => $dateNow
				);
				$obResult = $this->_HLClass::add($arElementFields);
				if($obResult->isSuccess()) {
					$arAddedIds[] = $obResult->getID();
				} else {
					$arAddedIds[] = 0;
				}
			}
			return $arAddedIds;
		}
		
		public function update($jsonData) {
			$dateNow = new Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s');
			$result = array();
			$arUpdatedIds = array();
			foreach($jsonData as $el) {
				$arElementFields = array(
					'UF_NAME' => $el['name'],
					'UF_ADDRESS' => $el['address'],
					'UF_UPDATED_AT' => $dateNow
				);
				$obResult = $this->_HLClass::update($el['id'], $arElementFields);
				if($obResult->isSuccess()) {
					$arUpdatedIds[] = $el['id'];
				}
			}
			$hlData = $this->_HLClass::getList(array(
			  'select' => array('ID', 'UF_NAME', 'UF_ADDRESS', 'UF_UPDATED_AT', 'UF_CREATED_AT'),
			  'order' => array('ID' => 'ASC'),
			  'filter' => array('ID' => $arUpdatedIds)
			));
			while ($arItem = $hlData->Fetch()) {
				$result[] = array(
					'id' => $arItem['ID'],
					'name' => $arItem['UF_NAME'],
					'address' => $arItem['UF_ADDRESS'],
					'updated_at' => $arItem['UF_UPDATED_AT']->toString(),
					'created_at' => $arItem['UF_CREATED_AT']->toString()
				);
			}
			return $result;
		}
		
		public function delete($jsonData) {
			$result = array('deleted' => 0);
			foreach($jsonData as $el) {
				$obResult = $this->_HLClass::delete($el);
				if($obResult->isSuccess()) {
					$result['deleted']++;
				}
			}
			return $result;
		}
	}
}

$method = ToLower($arVariables['method']);
$resultData = array();
$jsonData = array();
$inputData = file_get_contents('php://input');
if(!empty($inputData)) {
	$jsonData = json_decode($inputData, true);
}
$apiMethods = new RestApiMethods($HLClass);

try{
	$reflection = new ReflectionMethod($apiMethods, $method);
} catch (\Exception $e){
	ShowError(GetMessage('RESTAPI_NO_METHOD'));
	return 0;
}

if($method != '__construct' && $reflection->isPublic()) {
	$resultData = $apiMethods->{$method}($jsonData);
} else {
	ShowError(GetMessage('RESTAPI_NO_METHOD'));
	return 0;
}

$APPLICATION->RestartBuffer();
header('Content-type: application/json; charset=utf-8');

echo json_encode($resultData, JSON_UNESCAPED_UNICODE );
CMain::FinalActions();
die();
?>
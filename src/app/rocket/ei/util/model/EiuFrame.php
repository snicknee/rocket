<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the n2n module ROCKET.
 *
 * ROCKET is free software: you can redistribute it and/or modify it under the terms of the
 * GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * ROCKET is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg...........:	Architect, Lead Developer, Concept
 * Bert Hofmänner.............: Idea, Frontend UI, Design, Marketing, Concept
 * Thomas Günther.............: Developer, Frontend UI, Rocket Capability for Hangar
 */
namespace rocket\ei\util\model;

use rocket\ei\manage\mapping\EiEntry;
use n2n\persistence\orm\criteria\compare\CriteriaComparator;
use n2n\persistence\orm\criteria\item\CrIt;
use n2n\persistence\orm\store\EntityInfo;
use rocket\ei\manage\EiFrame;
use rocket\ei\manage\EiObject;
use rocket\ei\EiType;
use rocket\ei\manage\EiEntityObj;
use rocket\core\model\Rocket;
use n2n\persistence\orm\EntityManager;
use n2n\l10n\N2nLocale;
use n2n\reflection\ArgUtils;
use rocket\ei\manage\preview\model\PreviewModel;
use n2n\util\ex\NotYetImplementedException;
use n2n\l10n\Lstr;
use n2n\core\container\N2nContext;
use rocket\ei\EiCommandPath;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\map\PropertyPathPart;
use rocket\ei\component\command\EiCommand;
use rocket\ei\manage\gui\EiGui;
use rocket\ei\manage\gui\ViewMode;
use rocket\ei\manage\LiveEiObject;
use n2n\reflection\CastUtils;
use rocket\ei\manage\DraftEiObject;
use rocket\user\model\LoginContext;
use rocket\ei\manage\draft\DraftValueMap;
use n2n\reflection\ReflectionUtils;
use rocket\ei\manage\draft\Draft;
use rocket\ei\manage\gui\GuiIdPath;
use rocket\ei\mask\EiMask;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\util\NestedSetUtils;

class EiuFrame {
	private $eiFrame;
	private $eiuFactory;
	
	public function __construct(EiFrame $eiFrame, EiuFactory $eiuFactory = null) {
		$this->eiFrame = $eiFrame;
		$this->eiuFactory = $eiuFactory;
	}
	
	/**
	 * @return \rocket\ei\manage\EiFrame
	 */
	public function getEiFrame() {
		return $this->eiFrame;
	}
	
	/**
	 * @throws IllegalStateException;
	 * @return \n2n\web\http\HttpContext
	 */
	public function getHttpContext() {
		return $this->eiFrame->getN2nContext()->getHttpContext();
	}
	
	/**
	 * @return N2nContext
	 */
	public function getN2nContext() {
		return $this->eiFrame->getN2nContext();
	}
	
	/**
	 * @return N2nLocale
	 */
	public function getN2nLocale() {
		return $this->eiFrame->getN2nContext()->getN2nLocale();
	}

	/**
	 * @return EntityManager
	 */
	public function em() {
		return $this->eiFrame->getManageState()->getEntityManager();
	}

	
	private $eiuEngine;
	
	/**
	 * @return \rocket\ei\util\model\EiuEngine
	 */
	public function getContextEiuEngine() {
		if (null !== $this->eiuEngine) {
			return $this->eiuEngine;		
		}
		
		return $this->eiuEngine = new EiuEngine($this->eiFrame->getContextEiEngine(), null, $this->eiuFactory);
	}
	
	/**
	 * @param mixed $eiObjectObj {@see EiuFactory::buildEiObjectFromEiArg()}
	 * @return \rocket\ei\util\model\EiuEngine
	 */
	public function mask($eiObjectObj = null) {
		if ($eiObjectObj === null) {
			return $this->getContextEiuEngine()->getEiuMask();
		}
		
		$contextEiType = $this->getContextEiType();
		$eiObject = EiuFactory::buildEiObjectFromEiArg($eiObjectObj, 'eiObjectArg', $contextEiType);
		$eiType = $eiObject->getEiEntityObj()->getEiType();
		
		if ($contextEiType->equals($eiType)) {
			return $this->getContextEiuEngine()->getEiuMask();
		}
		
		
		return new EiuMask($this->eiFrame->determineEiMask($eiType), null, $this->eiuFactory);
	}
	
	
	/**
	 * @param mixed $eiObjectObj {@see EiuFactory::buildEiObjectFromEiArg()}
	 * @return \rocket\ei\util\model\EiuEngine
	 */
	public function engine($eiObjectObj = null) {
		if ($eiObjectObj === null) {
			return $this->getContextEiuEngine();
		}
		
		$contextEiType = $this->getContextEiType();
		$eiObject = EiuFactory::buildEiObjectFromEiArg($eiObjectObj, 'eiObjectArg', $contextEiType);
		$eiType = $eiObject->getEiEntityObj()->getEiType();
		
		if ($contextEiType->equals($eiType)) {
			return $this->getContextEiuEngine();
		}
		
		
		return new EiuEngine($this->eiFrame->determineEiMask($eiType)->getEiEngine(), null, $this->eiuFactory);
	}
	
	
	public function getContextClass() {
		return $this->eiFrame->getContextEiEngine()->getEiMask()->getEiType()->getEntityModel()->getClass();
	}
	
	/**
	 * @param mixed $eiObjectObj
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\util\model\EiuEntry
	 */
	public function entry($eiObjectObj) {
		$eiEntry = null;
		$eiObject = EiuFactory::determineEiObject($eiObjectObj, $eiEntry);
		return new EiuEntry($eiObject, $eiEntry, $this, $this->eiuFactory);
	}
	
	/**
	 * @param bool $draft
	 * @param mixed $eiTypeArg
	 * @return \rocket\ei\util\model\EiuEntry
	 */
	public function newEntry(bool $draft = false, $eiTypeArg = null) {
		return new EiuEntry($this->createNewEiObject($draft, 
				EiuFactory::buildEiTypeFromEiArg($eiTypeArg, 'eiTypeArg', false)), null, $this);
	}
	
	public function containsId($id, int $ignoreConstraintTypes = 0): bool {
		$criteria = $this->eiFrame->createCriteria('e', $ignoreConstraintTypes);
		$criteria->select(CrIt::c('1'));
		$this->applyIdComparison($criteria->where(), $id);
		
		return null !== $criteria->toQuery()->fetchSingle();
	}
	
	/**
	 * 
	 * @param mixed $id
	 * @param int $ignoreConstraintTypes
	 * @return \rocket\ei\util\model\EiuEntry
	 */
	public function lookupEntry($id, int $ignoreConstraintTypes = 0) {
		return $this->entry($this->lookupEiEntityObj($id, $ignoreConstraintTypes));
	}
	
	/**
	 * @param int $ignoreConstraintTypes
	 * @return int
	 */
	public function countEntries(int $ignoreConstraintTypes = 0) {
		return (int) $this->createCountCriteria('e', $ignoreConstraintTypes)->toQuery()->fetchSingle();
	}
	
	/**
	 * @param string $entityAlias
	 * @param int $ignoreConstraintTypes
	 * @return \n2n\persistence\orm\criteria\Criteria
	 */
	public function createCountCriteria(string $entityAlias, int $ignoreConstraintTypes = 0) {
		return $this->eiFrame->createCriteria($entityAlias, $ignoreConstraintTypes)
				->select('COUNT(e)');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\ei\util\model\EiuFrame::lookupEiEntityObj($id, $ignoreConstraints)
	 */
	private function lookupEiEntityObj($id, int $ignoreConstraintTypes = 0): EiEntityObj {
		$criteria = $this->eiFrame->createCriteria('e', $ignoreConstraintTypes);
		$criteria->select('e');
		$this->applyIdComparison($criteria->where(), $id);
		
		if (null !== ($entityObj = $criteria->toQuery()->fetchSingle())) {
			return EiEntityObj::createFrom($this->eiFrame->getContextEiEngine()->getEiMask()->getEiType(), $entityObj);
		}
		
		throw new UnknownEntryException('Entity not found: ' . EntityInfo::buildEntityString(
				$this->getContextEiType()->getEntityModel(), $id));
		
	}
	
	private function applyIdComparison(CriteriaComparator $criteriaComparator, $id) {
		$criteriaComparator->match(CrIt::p('e', $this->getEiFrame()->getContextEiEngine()->getEiMask()->getEiType()
				->getEntityModel()->getIdDef()->getEntityProperty()), CriteriaComparator::OPERATOR_EQUAL, $id);
	}
	
	public function getDraftManager() {
		return $this->eiFrame->getManageState()->getDraftManager();
	}
	
	/**
	 * @param EiObject $eiObject
	 * @return \rocket\ei\manage\mapping\EiEntry
	 * @throws \rocket\ei\security\InaccessibleEntryException
	 */
	private function createEiEntry(EiObject $eiObject) {
		return $this->determineEiMask($eiObject)->getEiEngine()->createEiEntry($this->eiFrame, $eiObject);
	}
	
	/**
	 * @param mixed $fromEiObjectArg
	 * @return EiuEntry
	 */
	public function copyEntryTo($fromEiObjectArg, $toEiObjectArg = null) {
		return $this->createEiEntryCopy($fromEiObjectArg, EiuFactory::buildEiObjectFromEiArg($toEiObjectArg, 'toEiObjectArg'));
	}
	
	public function copyEntry($fromEiObjectArg, bool $draft = null, $eiTypeArg = null) {
		$fromEiuEntry = EiuFactory::buildEiuEntryFromEiArg($fromEiObjectArg, $this, 'fromEiObjectArg');
		$draft = $draft ?? $fromEiuEntry->isDraft();
		
		if ($eiTypeArg !== null) {
			$eiType = EiuFactory::buildEiTypeFromEiArg($eiTypeArg, 'eiTypeArg', false);
		} else {
			$eiType = $fromEiuEntry->getEiType();
		}
		
		$eiObject = $this->createNewEiObject($draft, $eiType);
		return new EiuEntry($eiObject, $this->createEiEntryCopy($fromEiuEntry, $eiObject), $this, $this->eiuFactory);
	}
	
	public function copyEntryValuesTo($fromEiEntryArg, $toEiEntryArg, array $eiPropPaths = null) {
		$fromEiuEntry = EiuFactory::buildEiuEntryFromEiArg($fromEiEntryArg, $this, 'fromEiEntryArg');
		$toEiuEntry = EiuFactory::buildEiuEntryFromEiArg($toEiEntryArg, $this, 'toEiEntryArg');
		
		$this->determineEiMask($toEiEntryArg)->getEiEngine()
				->copyValues($this->eiFrame, $fromEiuEntry->getEiEntry(), $toEiuEntry->getEiEntry(), $eiPropPaths);
	}
	
	/**
	 * @param mixed $fromEiObjectObj
	 * @param EiObject $to
	 * @return \rocket\ei\manage\mapping\EiEntry
	 */
	private function createEiEntryCopy($fromEiObjectObj, EiObject $to = null, array $eiPropPaths = null) {
		$fromEiuEntry = EiuFactory::buildEiuEntryFromEiArg($fromEiObjectObj, $this, 'fromEiObjectObj');
		
		if ($to === null) {
			$to = $this->createNewEiObject($fromEiuEntry->isDraft(), $fromEiuEntry->getEiType());
		}
		
		return $this->determineEiMask($to)->getEiEngine()
				->createEiEntryCopy($this->eiFrame, $to, $fromEiuEntry->getEiEntry());
	}
	
	/**
	 * 
	 * @param bool $draft
	 * @param mixed $copyFromEiObjectObj
	 * @param PropertyPath $contextPropertyPath
	 * @param array $allowedEiTypeIds
	 * @throws EntryManageException
	 * @return EiuEntryForm
	 */
	public function newEiuEntryForm(bool $draft = false, $copyFromEiObjectObj = null, 
			PropertyPath $contextPropertyPath = null, array $allowedEiTypeIds = null,
			array $eiEntries = array()) {
		$eiuEntryTypeForms = array();
		$labels = array();
		
		$contextEiType = $this->eiFrame->getContextEiEngine()->getEiMask()->getEiType();
		$contextEiMask = $this->eiFrame->getContextEiEngine()->getEiMask();
		
		$eiGui = new EiGui($this->eiFrame, ViewMode::BULKY_ADD);
		$eiGui->init($contextEiMask->createEiGuiViewFactory($eiGui));
		
		ArgUtils::valArray($eiEntries, EiEntry::class);
		foreach ($eiEntries as $eiEntry) {
			$eiEntries[$eiEntry->getEiType()->getId()] = $eiEntry;
		}
		
		$eiTypes = array_merge(array($contextEiType->getId() => $contextEiType), $contextEiType->getAllSubEiTypes());
		if ($allowedEiTypeIds !== null) {
			foreach (array_keys($eiTypes) as $eiTypeId) {
				if (in_array($eiTypeId, $allowedEiTypeIds)) continue;
					
				unset($eiTypes[$eiTypeId]);
			}
		}
		
		if (empty($eiTypes)) {
			throw new \InvalidArgumentException('Param allowedEiTypeIds caused an empty EiuEntryForm.');
		}
		
		$chosenId = null;
		foreach ($eiTypes as $subEiTypeId => $subEiType) {
			if ($subEiType->getEntityModel()->getClass()->isAbstract()) {
				continue;
			}
				
			$subEiEntry = null;
			if (isset($eiEntries[$subEiType->getId()])) {
				$subEiEntry = $eiEntries[$subEiType->getId()];
				$chosenId = $subEiType->getId();
			} else {
				$eiObject = $this->createNewEiObject($draft, $subEiType);
				
				if ($copyFromEiObjectObj !== null) {
					$subEiEntry = $this->createEiEntryCopy($copyFromEiObjectObj, $eiObject);
				} else {
					$subEiEntry = $this->createEiEntry($eiObject);
				}
				
			}
						
			$eiuEntryTypeForms[$subEiTypeId] = $this->createEiuEntryTypeForm($subEiType, $subEiEntry, $contextPropertyPath);
			$labels[$subEiTypeId] = $this->eiFrame->determineEiMask($subEiType)->getLabelLstr()
					->t($this->eiFrame->getN2nContext()->getN2nLocale());
		}
		
		$eiuEntryForm = new EiuEntryForm($this);
		$eiuEntryForm->setEiuEntryTypeForms($eiuEntryTypeForms);
		$eiuEntryForm->setChoicesMap($labels);
		$eiuEntryForm->setChosenId($chosenId ?? key($eiuEntryTypeForms));
		$eiuEntryForm->setContextPropertyPath($contextPropertyPath);
		$eiuEntryForm->setChoosable(count($eiuEntryTypeForms) > 1);
		
		if (empty($eiuEntryTypeForms)) {
			throw new EntryManageException('Can not create EiuEntryForm of ' . $contextEiType
					. ' because its class is abstract an has no s of non-abstract subtypes.');
		}
		
		return $eiuEntryForm;
	}
	
	/**
	 * @param EiEntry $eiEntry
	 * @param PropertyPath $contextPropertyPath
	 * @return \rocket\ei\util\model\EiuEntryForm
	 */
	public function eiuEntryForm($eiEntryArg, PropertyPath $contextPropertyPath = null) {
		$eiEntry = EiuFactory::buildEiEntryFromEiArg($eiEntryArg);
		$contextEiMask = $this->eiFrame->getContextEiEngine()->getEiMask();
		$eiuEntryForm = new EiuEntryForm($this);
		$eiType = $eiEntry->getEiType();

		$eiuEntryForm->setEiuEntryTypeForms(array($eiType->getId() => $this->createEiuEntryTypeForm($eiType, $eiEntry, $contextPropertyPath)));
		$eiuEntryForm->setChosenId($eiType->getId());
		// @todo remove hack when ContentItemEiProp gets updated.
		$eiuEntryForm->setChoicesMap(array($eiType->getId() => $this->eiFrame->determineEiMask($eiType)->getLabelLstr()
				->t($this->eiFrame->getN2nContext()->getN2nLocale())));
		return $eiuEntryForm;
	}
	
	private function createEiuEntryTypeForm(EiType $eiType, EiEntry $eiEntry, PropertyPath $contextPropertyPath = null) {
		$eiMask = $this->getEiFrame()->determineEiMask($eiType);
		$eiGui = new EiGui($this->eiFrame, $eiEntry->isNew() ? ViewMode::BULKY_ADD : ViewMode::BULKY_EDIT);
		$eiGui->init($eiMask->createEiGuiViewFactory($eiGui));
		$eiEntryGui = $eiGui->createEiEntryGui($eiEntry);
		
		if ($contextPropertyPath === null) {
			$contextPropertyPath = new PropertyPath(array());
		}
		
		$eiEntryGui->setContextPropertyPath($contextPropertyPath->ext(
				new PropertyPathPart('eiuEntryTypeForms', true, $eiType->getId()))->ext('dispatchable'));
		
		return new EiuEntryTypeForm(new EiuEntryGui($eiEntryGui));
	}
	
	public function remove(EiObject $eiObject) {
		if ($eiObject->isDraft()) {
			throw new NotYetImplementedException();
		}
			
		$eiType = $eiObject->getEiEntityObj()->getEiType();
		$nss = $eiType->getNestedSetStrategy();
		if (null === $nss) {
			$this->em()->remove($eiObject->getEiEntityObj()->getEntityObj());
		} else {
			$nsu = new NestedSetUtils($this->em(), $eiType->getEntityModel()->getClass(), $nss);
			$nsu->remove($eiObject->getLiveObject());
		}
	}
	
	/**
	 * @return \rocket\core\model\launch\TransactionApproveAttempt
	 */
	public function flush() {
		return $this->eiFrame->getManageState()->getEiLifecycleMonitor()
				->approve($this->eiFrame->getN2nContext());
	}

	public function lookupPreviewController(string $previewType, $eiObjectArg) {
		$eiObject = EiuFactory::buildEiObjectFromEiArg($eiObjectArg, 'eiObjectArg');
		
		$entityObj = null;
		if (!$eiObject->isDraft()) {
			$entityObj = $eiObject->getLiveObject();
		} else {
			$eiEntry = $this->createEiEntry($eiObject);
			$previewEiEntry = $this->createEiEntryCopy($eiEntry, 
					$this->createNewEiObject(false, $eiObject->getEiEntityObj()->getEiType()));
			$previewEiEntry->write();
			$entityObj = $previewEiEntry->getEiObject()->getLiveObject();
		}
		
		$previewModel = new PreviewModel($previewType, $eiObject, $entityObj);
		
		return $this->getContextEiMask()->lookupPreviewController($this->eiFrame, $previewModel);
	}

	public function getPreviewType(EiObject $eiObject) {
		$previewTypeOptions = $this->getPreviewTypeOptions($eiObject);
		
		if (empty($previewTypeOptions)) return null;
			
		return key($previewTypeOptions);
	}
	
	/**
	 * @return boolean
	 */
	public function isPreviewSupported() {
		return $this->getContextEiMask()->isPreviewSupported();
	}
	
	public function getPreviewTypeOptions(EiObject $eiObject) {
		$eiMask = $this->getContextEiMask();
		if (!$eiMask->isPreviewSupported()) {
			return array();
		}
		
		$previewController = $eiMask->lookupPreviewController($this->eiFrame);
		$previewTypeOptions = $previewController->getPreviewTypeOptions(new Eiu($this, $eiObject));
		ArgUtils::valArrayReturn($previewTypeOptions, $previewController, 'getPreviewTypeOptions', 
				array('string', Lstr::class));
		
		return $previewTypeOptions;
	}
	
	public function isExecutedBy($eiCommandPath) {
		return $this->eiFrame->getEiExecution()->getEiCommandPath()->startsWith(EiCommandPath::create($eiCommandPath));
	}
	
	public function isExecutedByType($eiCommandType) {
// 		ArgUtils::valType($eiCommandType, array('string', 'object'));
		return $this->eiFrame->getEiExecution()->getEiCommand() instanceof $eiCommandType;
	}
	
	/**
	 * 
	 * @return \rocket\ei\manage\generic\ScalarEiProperty[]
	 */
	public function getScalarEiProperties() {
		return $this->getContextEiMask()->getEiEngine()->getScalarEiDefinition()->getMap()->getValues();
	}
	
	/**
	 * @param EiCommand $eiCommand
	 * @return \rocket\ei\util\model\EiuControlFactory
	 */
	public function controlFactory(EiCommand $eiCommand) {
		return new EiuControlFactory($this, $eiCommand);
	}
	
	/**
	 * @return \n2n\util\uri\Url
	 */
	public function getCurrentUrl() {
		return $this->eiFrame->getCurrentUrl($this->getN2nContext()->getHttpContext());
	}
	
	/**
	 * @return \n2n\util\uri\Url
	 */
	public function getUrlToCommand(EiCommand $eiCommand) {
		return $this->getHttpContext()->getControllerContextPath($this->getEiFrame()->getControllerContext())
				->ext($eiCommand->getId())->toUrl();
	}
	
	/**
	 * @return \n2n\util\uri\Url
	 */
	public function getContextUrl() {
		return $this->getHttpContext()->getControllerContextPath($this->getEiFrame()->getControllerContext())->toUrl();
	}
	
	/**
	 * @return EiuGui
	 */
	public function newGui(int $viewMode) {
		$eiGui = new EiGui($this->eiFrame, $viewMode);
		
		$eiGui->init($this->eiFrame->getContextEiEngine()->getEiMask()->createEiGuiViewFactory($eiGui));
		
		return new EiuGui($eiGui, $this);
	}
	
	/**
	 * @param int $viewMode
	 * @param \Closure $uiFactory
	 * @param array $guiIdPaths
	 * @return \rocket\ei\util\model\EiuGui
	 */
	public function newCustomGui(int $viewMode, \Closure $uiFactory, array $guiIdPaths) {
		$eiGui = new EiGui($this->eiFrame, $viewMode);
		$eiuGui = new EiuGui($eiGui, $this);
		$eiuGui->initWithUiCallback($uiFactory, $guiIdPaths);
		return $eiuGui;
	}
	
	
	
	
	
	
	
	
	//////////////////////////
	
	
	/**
	 * @return \rocket\ei\mask\EiMask
	 */
	public function getContextEiMask() {
		return $this->eiFrame->getContextEiEngine()->getEiMask();
	}
	
	/**
	 * @return \rocket\ei\EiType
	 */
	public function getContextEiType() {
		return $this->getContextEiMask()->getEiType();
	}

	/**
	 * @return \n2n\persistence\orm\util\NestedSetStrategy
	 */
	public function getNestedSetStrategy() {
		return $this->getContextEiType()->getNestedSetStrategy();
	}

	/**
	 * @param mixed $id
	 * @return string
	 */
	public function idToPid($id): string {
		return $this->getContextEiType()->idToPid($id);
	}

	/**
	 * @param string $pid
	 * @return mixed
	 */
	public function pidToId(string $pid) {
		return $this->getContextEiType()->pidToId($pid);
	}

	/**
	 * @param mixed $eiObjectObj
	 * @param N2nLocale $n2nLocale
	 * @return string
	 */
	public function getGenericLabel($eiObjectObj = null, N2nLocale $n2nLocale = null): string {
		return $this->determineEiMask($eiObjectObj)->getLabelLstr()->t($n2nLocale ?? $this->getN2nLocale());
	}

	/**
	 * @param mixed $eiObjectObj
	 * @param N2nLocale $n2nLocale
	 * @return string
	 */
	public function getGenericPluralLabel($eiObjectObj = null, N2nLocale $n2nLocale = null): string {
		return $this->determineEiMask($eiObjectObj)->getPluralLabelLstr()->t($n2nLocale ?? $this->getN2nLocale());
	}

	/**
	 * @param mixed $eiObjectObj
	 * @return string
	 */
	public function getGenericIconType($eiObjectObj = null) {
		return $this->determineEiMask($eiObjectObj)->getIconType();
	}

	/**
	 * @param EiObject $eiObject
	 * @param bool $determineEiMask
	 * @param N2nLocale $n2nLocale
	 * @return string
	 */
	public function createIdentityString(EiObject $eiObject, bool $determineEiMask = true,
			N2nLocale $n2nLocale = null): string {
		$eiMask = null;
		if ($determineEiMask) {
			$eiMask = $this->determineEiMask($eiObject);
		} else {
			$eiMask = $this->getContextEiMask();
		}

		return $eiMask->createIdentityString($eiObject, $n2nLocale ?? $this->getN2nLocale());
	}

	/**
	 * @param mixed $eiObjectObj
	 * @return EiType
	 */
	private function determineEiType($eiObjectObj): EiType {
		if ($eiObjectObj === null) {
			return $this->getContextEiType();
		}

		ArgUtils::valType($eiObjectObj, array(EiObject::class, EiEntry::class, EiEntityObj::class, EiuEntry::class, 'object'), true);

		if ($eiObjectObj instanceof EiEntry) {
			return $eiObjectObj->getEiObject()->getEiEntityObj()->getEiType();
		}

		if ($eiObjectObj instanceof EiObject) {
			return $eiObjectObj->getEiEntityObj()->getEiType();
		}

		if ($eiObjectObj instanceof EiEntityObj) {
			return $eiObjectObj->getEiType();
		}

		if ($eiObjectObj instanceof Draft) {
			return $eiObjectObj->getEiEntityObj()->getEiType();
		}

		if ($eiObjectObj instanceof EiuEntry) {
			return $eiObjectObj->getEiEntityObj()->getEiType();
		}

		return $this->getContextEiType()->determineAdequateEiType(new \ReflectionClass($eiObjectObj));
	}

	/**
	 * @param mixed $eiObjectObj
	 * @return EiMask
	 */
	private function determineEiMask($eiObjectObj): EiMask {
		if ($eiObjectObj === null) {
			return $this->getContextEiMask();
		}

		return $this->determineEiType($eiObjectObj)->getEiMask();
	}

	/**
	 * @param mixed $eiObjectObj
	 * @return \rocket\ei\EiEngine
	 */
	private function determineEiEngine($eiObjectObj) {
		return $this->determineEiMask($eiObjectObj)->getEiEngine();
	}

	/**
	 * @param mixed $guiIdPath
	 * @param mixed $eiTypeObj
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public function containsGuiProp($guiIdPath, $eiTypeObj = null) {
		return $this->determineEiEngine($eiTypeObj)->getGuiDefinition()->containsGuiProp(
				GuiIdPath::create($guiIdPath));
	}

	public function guiIdPathToEiPropPath($guiIdPath, $eiTypeObj = null) {
		try {
			return $this->determineEiEngine($eiTypeObj)->getGuiDefinition()->guiIdPathToEiPropPath(
					GuiIdPath::create($guiIdPath));
		} catch (\rocket\ei\manage\gui\GuiException $e) {
			return null;
		}
	}

	/**
	 * @param mixed $id
	 * @param int $ignoreConstraintTypes
	 * @return EiObject
	 */
	public function lookupEiObjectById($id, int $ignoreConstraintTypes = 0): EiObject {
		return new LiveEiObject($this->lookupEiEntityObj($id, $ignoreConstraintTypes));
	}


	/**
	 * @return bool
	 */
	public function isDraftingEnabled(): bool {
		return $this->getContextEiMask()->isDraftingEnabled();
	}


	/**
	 * @param int $id
	 * @throws UnknownEntryException
	 * @return Draft
	 */
	public function lookupDraftById(int $id): Draft {
		$draft = $this->getDraftManager()->find($this->getClass(), $id,
				$this->getContextEiMask()->getEiEngine()->getDraftDefinition());

		if ($draft !== null) return $draft;

		throw new UnknownEntryException('Unknown draft with id: ' . $id);
	}


	/**
	 * @param int $id
	 * @return EiObject
	 */
	public function lookupEiObjectByDraftId(int $id): EiObject {
		return new DraftEiObject($this->lookupDraftById($id));
	}


	/**
	 * @param mixed $entityObjId
	 * @param int $limit
	 * @param int $num
	 * @return array
	 */
	public function lookupDraftsByEntityObjId($entityObjId, int $limit = null, int $num = null): array {
		return $this->getDraftManager()->findByEntityObjId($this->getClass(), $entityObjId, $limit, $num,
				$this->getContextEiMask()->getEiEngine()->getDraftDefinition());
	}


	/**
	 * @return object
	 */
	public function createEntityObj() {
		return ReflectionUtils::createObject($this->getClass());
	}


	/**
	 * @param mixed $eiEntityObj
	 * @return EiObject
	 */
	public function createEiObjectFromEiEntityObj($eiEntityObj): EiObject {
		if ($eiEntityObj instanceof EiEntityObj) {
			return new LiveEiObject($eiEntityObj);
		}

		if ($eiEntityObj !== null) {
			return LiveEiObject::create($this->getContextEiType(), $eiEntityObj);
		}

		return new LiveEiObject(EiEntityObj::createNew($this->getContextEiType()));
	}

	/**
	 * @param Draft $draft
	 * @return EiObject
	 */
	public function createEiObjectFromDraft(Draft $draft): EiObject {
		return new DraftEiObject($draft);
	}

	/**
	 * @param bool $draft
	 * @param EiType $eiType
	 * @return EiObject
	 */
	public function createNewEiObject(bool $draft = false, EiType $eiType = null): EiObject {
		if ($eiType === null) {
			$eiType = $this->getContextEiType();
		}

		if (!$draft) {
			return new LiveEiObject(EiEntityObj::createNew($eiType));
		}

		$loginContext = $this->getN2nContext()->lookup(LoginContext::class);
		CastUtils::assertTrue($loginContext instanceof LoginContext);

		return new DraftEiObject($this->createNewDraftFromEiEntityObj(EiEntityObj::createNew($eiType)));
	}

	/**
	 * @param EiEntityObj $eiEntityObj
	 * @return \rocket\ei\manage\draft\Draft
	 */
	public function createNewDraftFromEiEntityObj(EiEntityObj $eiEntityObj) {
		$loginContext = $this->getN2nContext()->lookup(LoginContext::class);
		CastUtils::assertTrue($loginContext instanceof LoginContext);

		return new Draft(null, $eiEntityObj, new \DateTime(),
				$loginContext->getCurrentUser()->getId(), new DraftValueMap());
	}

	/**
	 * @param mixed $eiObjectObj
	 * @param bool $flush
	 */
	public function persist($eiObjectObj, bool $flush = true) {
		if ($eiObjectObj instanceof Draft) {
			$this->persistDraft($eiObjectObj, $flush);
			return;
		}

		if ($eiObjectObj instanceof EiEntityObj) {
			$this->persistEiEntityObj($eiObjectObj, $flush);
			return;
		}

		$eiObject = EiuFactory::buildEiObjectFromEiArg($eiObjectObj, 'eiObjectObj', $this->getContextEiType());

		if ($eiObject->isDraft()) {
			$this->persistDraft($eiObject->getDraft(), $flush);
			return;
		}

		$this->persistEiEntityObj($eiObject->getEiEntityObj(), $flush);
	}

	private function persistDraft(Draft $draft, bool $flush) {
		$draftManager = $this->getDraftManager();

		if (!$draft->isNew()) {
			$draftManager->persist($draft);
		} else {
			$draftManager->persist($draft, $this->getContextEiMask()->determineEiMask(
					$draft->getEiEntityObj()->getEiType())->getEiEngine()->getDraftDefinition());
		}

		if ($flush) {
			$draftManager->flush();
		}
	}

	private function persistEiEntityObj(EiEntityObj $eiEntityObj, bool $flush) {
		$em = $this->em();
		$nss = $this->getNestedSetStrategy();
		if ($nss === null || $eiEntityObj->isPersistent()) {
			$em->persist($eiEntityObj->getEntityObj());
			if (!$flush) return;
			$em->flush();
		} else {
			if (!$flush) {
				throw new IllegalStateException(
						'Flushing is mandatory because EiEntityObj is new and has a NestedSetStrategy.');
			}

			$nsu = new NestedSetUtils($em, $this->getClass(), $nss);
			$nsu->insertRoot($eiEntityObj->getEntityObj());
		}

		if (!$eiEntityObj->isPersistent()) {
			$eiEntityObj->refreshId();
			$eiEntityObj->setPersistent(true);
		}
	}
}

// class EiCascadeOperation implements CascadeOperation {
// 	private $cascader;
// 	private $entityModelManager;
// 	private $spec;
// 	private $entityObjs = array();
// 	private $eiTypes = array();
// 	private $liveEntries = array();

// 	public function __construct(EntityModelManager $entityModelManager, Spec $spec, int $cascadeType) { 
// 		$this->entityModelManager = $entityModelManager;
// 		$this->spec = $spec;
// 		$this->cascader = new OperationCascader($cascadeType, $this);
// 	}

// 	public function cascade($entityObj) {
// 		if (!$this->cascader->markAsCascaded($entityObj)) return;

// 		$entityModel = $this->entityModelManager->getEntityModelByEntityObj($entityObj);
		
// 		$this->liveEntries[] = EiEntityObj::createFrom($this->spec
// 				->getEiTypeByClass($entityModel->getClass()), $entityObj);
		
// 		$this->cascader->cascadeProperties($entityModel, $entityObj);
// 	}
	
// 	public function getLiveEntries(): array {
// 		return $this->liveEntries;
// 	}
// }
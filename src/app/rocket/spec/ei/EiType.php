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
namespace rocket\spec\ei;

use n2n\core\container\PdoPool;
use n2n\persistence\orm\model\EntityModel;
use n2n\core\module\Module;
use n2n\reflection\ArgUtils;
use n2n\core\container\N2nContext;
use rocket\spec\ei\component\command\PrivilegedEiCommand;
use n2n\reflection\ReflectionUtils;
use rocket\spec\ei\manage\EiFrame;
use n2n\util\ex\UnsupportedOperationException;
use rocket\spec\ei\manage\security\PrivilegeBuilder;
use rocket\spec\ei\mask\EiMaskCollection;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\util\NestedSetStrategy;
use rocket\spec\ei\manage\veto\VetoableActionListener;
use rocket\spec\ei\manage\veto\VetoableRemoveAction;
use rocket\spec\config\Spec;
use n2n\l10n\Lstr;

class EiType extends Spec implements EiThing {
	private $entityModel;
	private $eiDef;
	private $eiEngine;
	
	private $superEiType;
	protected $subEiTypes = array();
	
	private $defaultEiMask;
	private $eiMaskCollection;
	
	private $dataSourceName = null;
	private $nestedSetStrategy;
	
	private $vetoListeners = array();
	
	/**
	 * @param string $id
	 * @param Module $moduleNamespace
	 * @param EntityModel $entityModel
	 */
	public function __construct($id, $moduleNamespace) {
		parent::__construct($id, $moduleNamespace);
		
		$this->eiDef = new EiDef();
		$this->eiEngine = new EiEngine($this);
		$this->eiMaskCollection = new EiMaskCollection($this);
	}

	public function getEiThingPath(): EiThingPath {
		return new EiThingPath(array($this->getId()));
	}

	public function setEntityModel(EntityModel $entityModel) {
		IllegalStateException::assertTrue($this->entityModel === null);
		$this->entityModel = $entityModel;
	}
	
	/**
	 * @return \n2n\persistence\orm\model\EntityModel
	 */
	public function getEntityModel(): EntityModel {
		IllegalStateException::assertTrue($this->entityModel !== null);
		return $this->entityModel;
	}
	
	/**
	 * @param EiType $superEiType
	 */
	public function setSuperEiType(EiType $superEiType) {
		$this->superEiType = $superEiType;
		$superEiType->subEiTypes[$this->getId()] = $this;
		
		$superEiEngine = $superEiType->getEiEngine();
		$this->eiEngine->getEiPropCollection()->setInheritedCollection($superEiEngine->getEiPropCollection());
		$this->eiEngine->getEiCommandCollection()->setInheritedCollection($superEiEngine->getEiCommandCollection());
		$this->eiEngine->getEiModificatorCollection()->setInheritedCollection(
				$superEiEngine->getEiModificatorCollection());
	}
	
	public function getLabelLstr(): Lstr {
		return new Lstr($this->eiDef->getLabel(), $this->moduleNamespace);
	}
		
	public function getPluralLabelLstr(): Lstr {
		return new Lstr($this->eiDef->getPluralLabel(), $this->moduleNamespace);
	}
	
	public function getIconType(): string {
		return $this->eiDef->getIconType();
	}
	
	/**
	 * @return \rocket\spec\ei\EiDef
	 */
	public function getDefaultEiDef(): EiDef {
		return $this->eiDef;
	}
	
	public function getEiEngine(): EiEngine {
		return $this->eiEngine;
	}
	
	public function getMaskedEiThing() {
		return null;
	}
	
	/**
	 * @return \rocket\spec\ei\EiType
	 */
	public function getSuperEiType(): EiType {
		if ($this->superEiType !== null) {
			return $this->superEiType;
		}
		
		throw new IllegalStateException('EiType has not SuperEiType: ' . (string) $this);
	}
	
	/**
	 * @return boolean
	 */
	public function hasSuperEiType(): bool {
		return $this->superEiType !== null;
	}
	
	/**
	 * @return \rocket\spec\ei\EiType
	 */
	public function getSupremeEiType(): EiType {
		$topEiType = $this;
		while ($topEiType->hasSuperEiType()) {
			$topEiType = $topEiType->getSuperEiType();
		}
		return $topEiType;
	}
	
	public function getAllSuperEiTypes($includeSelf = false) {
		$superEiTypes = array();
		
		if ($includeSelf) {
			$superEiTypes[$this->getId()] = $this;
		}
		
		$eiType = $this;
		while (null != ($eiType = $eiType->getSuperEiType())) {
			$superEiTypes[$eiType->getId()] = $eiType;
		}
		return $superEiTypes;
	}
	
	/**
	 * @return boolean
	 */
	public function hasSubEiTypes() {
		return (bool) sizeof($this->subEiTypes);
	}
	
	/**
	 * @return \rocket\spec\ei\EiType[]
	 */
	public function getSubEiTypes() {
		return $this->subEiTypes;
	}
	
	public function containsSubEiTypeId($eiTypeId, $deepCheck = false) {
		if (isset($this->subEiTypes[$eiTypeId])) return true;
		
		if ($deepCheck) {
			foreach ($this->subEiTypes as $subEiType) {
				if ($subEiType->containsSubEiTypeId($eiTypeId, $deepCheck)) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * @return \rocket\spec\ei\EiType[]
	 */
	public function getAllSubEiTypes() {
		return $this->lookupAllSubEiTypes($this); 
	}
	
	/**
	 * @param EiType $eiType
	 * @return \rocket\spec\ei\EiType[]
	 */
	private function lookupAllSubEiTypes(EiType $eiType) {
		$subEiTypes = $eiType->getSubEiTypes();
		foreach ($subEiTypes as $subEiType) {
			$subEiTypes = array_merge($subEiTypes, 
					$this->lookupAllSubEiTypes($subEiType));
		}
		
		return $subEiTypes;
	}
	
	public function findEiTypeByEntityModel(EntityModel $entityModel) {
		if ($this->entityModel->equals($entityModel)) {
			return $this;
		}
		
		foreach ($this->getAllSuperEiTypes() as $superEiType) {
			if ($superEiType->getEntityModel()->equals($entityModel)) {
				return $superEiType;
			}
		}
		
		foreach ($this->getAllSubEiTypes() as $subEiType) {
			if ($subEiType->getEntityModel()->equals($entityModel)) {
				return $subEiType;
			}
		}
	}
	/**
	 * @param EntityModel $entityModel
	 * @throws \InvalidArgumentException
	 * @return \rocket\spec\ei\EiType
	 */
	public function determineEiType(EntityModel $entityModel): EiType {
		if ($this->entityModel->equals($entityModel)) {
			return $this;
		}
		
		foreach ($this->getAllSubEiTypes() as $subEiType) {
			if ($subEiType->getEntityModel()->equals($entityModel)) {
				return $subEiType;
			}
		}
				
		// @todo make better exception
		throw new \InvalidArgumentException('No EiType for Entity \'' 
				. $entityModel->getClass()->getName() . '\' defined.');
	}
		
	public function determineAdequateEiType(\ReflectionClass $class): EiType {
		if (!ReflectionUtils::isClassA($class, $this->entityModel->getClass())) {
			throw new \InvalidArgumentException('Class \'' . $class->getName()
					. '\' is not instance of \'' . $this->getEntityModel()->getClass()->getName() . '\'.');
		} 
		
		$eiType = $this;
		
		foreach ($this->getAllSubEiTypes() as $subEiType) {
			if (ReflectionUtils::isClassA($class, $subEiType->getEntityModel()->getClass())) {
				$eiType = $subEiType;
			}
		}
		
		return $eiType;
	}
	
// 	public function createDraftModel(DraftManager $draftManager) {
// 		$draftModel = new DraftModel($draftManager, $this->entityModel, $this->getDraftables(false),
// 				$this->getDraftables(true));
		
// 		return $draftModel;
// 	}

	
	public function setupEiFrame(EiFrame $eiFrame) {
		foreach ($this->getEiEngine()->getEiModificatorCollection() as $eiModificator) {
			$eiModificator->setupEiFrame($eiFrame);
		}
	}
	
	public function hasSecurityOptions() {
		return $this->superEiType === null;
	}
	
	public function getPrivilegeOptions(N2nContext $n2nContext) {
		if ($this->superEiType !== null) return null;
		
		return $this->buildPrivilegeOptions($this, $n2nContext, array());
	}
	
	private function buildPrivilegeOptions(EiType $eiType, N2nContext $n2nContext, array $options) {
		$n2nLocale = $n2nContext->getN2nLocale();
		foreach ($eiType->getEiCommandCollection()->filterLevel() as $eiCommand) {
			if ($eiCommand instanceof PrivilegedEiCommand) {
				$options[PrivilegeBuilder::buildPrivilege($eiCommand)]
						= $eiCommand->getPrivilegeLabel($n2nLocale);
			}
				
// 			if ($eiCommand instanceof PrivilegeExtendableEiCommand) {
// 				$privilegeOptions = $eiCommand->getPrivilegeExtOptions($n2nLocale);
					
// 				ArgUtils::valArrayReturnType($privilegeOptions, 'scalar', $eiCommand, 'getPrivilegeOptions');
					
// 				foreach ($privilegeOptions as $privilegeExt => $label) {
// 					if ($eiType->hasSuperEiType()) {
// 						$label . ' (' . $eiType->getLabel() . ')';
// 					}
					
// 					$options[PrivilegeBuilder::buildPrivilege($eiCommand, $privilegeExt)] = $label;
// 				}
// 			}
		}
		
		foreach ($eiType->getSubEiTypes() as $subEiType) {
			$options = $this->buildPrivilegeOptions($subEiType, $n2nContext, $options);
		}
		
		return $options;
	}
	
	private function ensureIsTop() {
		if ($this->superEiType !== null) {
			throw new UnsupportedOperationException('EiType has super EiType');
		}
	}
	
// 	public function createRestrictionSelectorItems(N2nContext $n2nContext) {
// 		$this->ensureIsTop();
		
// 		$restrictionSelectorItems = array();
// 		foreach ($this->eiPropCollection as $eiProp) {
// 			if (!($eiProp instanceof RestrictionEiProp)) continue;
			
// 			$restrictionSelectorItem = $eiProp->createRestrictionSelectorItem($n2nContext);
			
// 			ArgUtils::valTypeReturn($restrictionSelectorItem, 'rocket\spec\ei\manage\critmod\filter\impl\field\SelectorItem', 
// 					$eiProp, 'createRestrictionSelectorItem');
			
// 			$restrictionSelectorItems[$eiProp->getId()] = $restrictionSelectorItem;
// 		}
		
// 		return $restrictionSelectorItems;
// 	}
	
	public function isObjectValid($object) {
		return is_object($object) && ReflectionUtils::isObjectA($object, $this->getEntityModel()->getClass());
	}
// 	/**
// 	 * @param unknown $propertyName
// 	 * @return \rocket\spec\ei\component\field\ObjectPropertyEiProp
// 	 */
// 	public function containsEiPropPropertyName($propertyName) {
// 		foreach ($this->eiPropCollection as $eiProp) {
// 			if ($eiProp instanceof ObjectPropertyEiProp
// 					&& $eiProp->getPropertyName() == $propertyName) {
// 				return true;
// 			}
// 		}
		
// 		return false;
// 	}
	/**
	 * @param string $dataSourceName
	 */
	public function setDataSourceName($dataSourceName) {
		$this->dataSourceName = $dataSourceName;
	}
	/**
	 * @return string
	 */
	public function getDataSourceName() {
		return $this->dataSourceName;
	}
	
	/**
	 * @return \n2n\persistence\orm\util\NestedSetStrategy
	 */
	public function getNestedSetStrategy() {
		return $this->nestedSetStrategy;
	}
	
	/**
	 * @param NestedSetStrategy $nestedSetStrategy
	 */
	public function setNestedSetStrategy(NestedSetStrategy $nestedSetStrategy = null) {
		$this->nestedSetStrategy = $nestedSetStrategy;
	}
	

// 	private $mainTranslationN2nLocale;
// 	private $translationN2nLocales;
	
// 	public function getMainTranslationN2nLocale() {
// 		if ($this->superEiType !== null) {
// 			return $this->superEiType->getMainTranslationN2nLocale();	
// 		} 
		
// 		if ($this->mainTranslationN2nLocale === null) {
// 			return N2nLocale::getDefault();
// 		}
		
// 		return $this->mainTranslationN2nLocale;
// 	}
	
// 	public function setMainTranslationN2nLocale(N2nLocale $mainTranslationN2nLocale = null) {
// 		if ($this->superEiType !== null) {
// 			return $this->superEiType->setMainTranslationN2nLocale($mainTranslationN2nLocale);	
// 		}
		
// 		$this->mainTranslationN2nLocale = $mainTranslationN2nLocale;
// 	}
	
// 	public function getTranslationN2nLocales() {
// 		if ($this->superEiType !== null) {
// 			return $this->superEiType->getTranslationN2nLocales();	
// 		}
		
// 		if ($this->translationN2nLocales === null) {
// 			$n2nLocales = N2N::getN2nLocales();
// 			unset($n2nLocales[$this->getMainTranslationN2nLocale()->getId()]);
// 			return $n2nLocales;
// 		}
		
// 		return $this->translationN2nLocales;
// 	}
	
// 	public function setTranslationN2nLocales(array $translationN2nLocales = null) {
// 		if ($this->superEiType === null) {
// 			$this->translationN2nLocales = $translationN2nLocales;
// 		}
		
// 		$this->superEiType->setTranslationN2nLocales($translationN2nLocales);
// 	}
	
	/**
	 * @param \n2n\core\container\PdoPool $dbhPool
	 * @return \n2n\persistence\orm\EntityManager
	 */
	public function lookupEntityManager(PdoPool $dbhPool, $transactional = false): EntityManager {
		$emf = $this->lookupEntityManagerFactory($dbhPool);
		if ($transactional) {
			return $emf->getTransactional();
		} else {
			return $emf->getExtended();
		}
	}
	/**
	 * @param PdoPool $dbhPool
	 * @return \n2n\persistence\orm\EntityManagerFactory
	 */
	public function lookupEntityManagerFactory(PdoPool $dbhPool) {
		return $dbhPool->getEntityManagerFactory($this->dataSourceName);
	}
	/**
	 * @param $entity
	 * @return mixed
	 */
	public function extractId($entity) {
		return $this->entityModel->getIdDef()->getEntityProperty()->readValue($entity);
	}
	
	/**
	 * @param mixed $id
	 * @return string
	 * @throws \InvalidArgumentException if null is passed as id.
	 */
	public function idToIdRep($id): string {
		return $this->entityModel->getIdDef()->getEntityProperty()->valueToRep($id);
	}
	
	/**
	 * @param string $idRep
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function idRepToId(string $idRep) {
		return $this->entityModel->getIdDef()->getEntityProperty()->repToValue($idRep);
	}
	
	/**
	 * @return EiMaskCollection
	 */
	public function getEiMaskCollection(): EiMaskCollection {
		return $this->eiMaskCollection;
	}
	
	public function __toString(): string {
		return 'EiType [id: ' . $this->getId() . ']';
	}
	
	public function isAbstract(): bool {
		return $this->entityModel->getClass()->isAbstract();
	}
	
	public function registerVetoableActionListener(VetoableActionListener $vetoListener) {
		$this->vetoListeners[spl_object_hash($vetoListener)] = $vetoListener;
	}
	
	public function unregisterVetoableActionListener(VetoableActionListener $vetoListener) {
		unset($this->vetoListeners[spl_object_hash($vetoListener)]);
	}
	
	public function onRemove(VetoableRemoveAction $vetoableRemoveAction, N2nContext $n2nContext) {
		foreach ($this->vetoListeners as $vetoListener) {
			$vetoListener->onRemove($vetoableRemoveAction, $n2nContext);
		}
	}
}
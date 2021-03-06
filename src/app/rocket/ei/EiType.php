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
namespace rocket\ei;

use n2n\core\container\PdoPool;
use n2n\persistence\orm\model\EntityModel;
use n2n\core\module\Module;
use n2n\core\container\N2nContext;
use rocket\ei\component\command\PrivilegedEiCommand;
use n2n\reflection\ReflectionUtils;
use n2n\util\ex\UnsupportedOperationException;
use rocket\ei\manage\security\PrivilegeBuilder;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\util\NestedSetStrategy;
use rocket\spec\Type;
use rocket\ei\mask\EiMask;
use rocket\ei\mask\model\DisplayScheme;
use rocket\ei\manage\veto\VetoableLifecycleAction;

class EiType extends Type {
	private $entityModel;
	private $superEiType;
	protected $subEiTypes = array();
	
	private $eiMask;
	private $eiTypeExtensionCollection;
	
	private $dataSourceName = null;
	private $nestedSetStrategy;
	
	/**
	 * @var EiLifecycleListener[]
	 */
	private $eiLifecycleListeners = array();
	
	/**
	 * @param string $id
	 * @param Module $moduleNamespace
	 * @param EntityModel $entityModel
	 */
	public function __construct($id, $moduleNamespace) {
		parent::__construct($id, $moduleNamespace);
		
		$this->eiMask = new EiMask($this, new DisplayScheme());
		$this->eiTypeExtensionCollection = new EiTypeExtensionCollection($this);
	}

// 	public function getEiThingPath(): EiThingPath {
// 		return new EiThingPath(array($this->getId()));
// 	}

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
		
		$superEiMask = $superEiType->getEiMask();
		$this->eiMask->getEiPropCollection()->setInheritedCollection($superEiMask->getEiPropCollection());
		$this->eiMask->getEiCommandCollection()->setInheritedCollection($superEiMask->getEiCommandCollection());
		$this->eiMask->getEiModificatorCollection()->setInheritedCollection(
				$superEiMask->getEiModificatorCollection());
	}
	
	/**
	 * @return \rocket\ei\EiType
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
	 * @return \rocket\ei\EiType
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
	 * @return \rocket\ei\EiType[]
	 */
	public function getSubEiTypes() {
		return $this->subEiTypes;
	}
	
	public function containsSubEiTypeId(string $eiTypeId, bool $deepCheck = false) {
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
	 * @return \rocket\ei\EiType[]
	 */
	public function getAllSubEiTypes() {
		return $this->lookupAllSubEiTypes($this); 
	}
	
	/**
	 * @param EiType $eiType
	 * @return \rocket\ei\EiType[]
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
	 * @return \rocket\ei\EiType
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
	
// 	public function setupEiFrame(EiFrame $eiFrame) {
// 		foreach ($this->getEiEngine()->getEiModificatorCollection() as $eiModificator) {
// 			$eiModificator->setupEiFrame($eiFrame);
// 		}
// 	}
	
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
			
// 			ArgUtils::valTypeReturn($restrictionSelectorItem, 'rocket\ei\manage\critmod\filter\impl\field\SelectorItem', 
// 					$eiProp, 'createRestrictionSelectorItem');
			
// 			$restrictionSelectorItems[$eiProp->getId()] = $restrictionSelectorItem;
// 		}
		
// 		return $restrictionSelectorItems;
// 	}
	
	/**
	 * @param object $object
	 * @return boolean
	 */
	public function isObjectValid($object) {
		return is_object($object) && ReflectionUtils::isObjectA($object, $this->getEntityModel()->getClass());
	}

	/**
	 * 
	 * @return EiMask
	 */
	public function getEiMask() {
		return $this->eiMask;
	}
	
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
	 * @param NestedSetStrategy|null $nestedSetStrategy
	 */
	public function setNestedSetStrategy(?NestedSetStrategy $nestedSetStrategy) {
		$this->nestedSetStrategy = $nestedSetStrategy;
	}
	
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
	 * @param object $entityObj
	 * @return mixed
	 */
	public function extractId($entityObj) {
		return $this->entityModel->getIdDef()->getEntityProperty()->readValue($entityObj);
	}
	
	/**
	 * <p>Converts the id of an entity object of this {@see EiType} into a pid. In rocket pid stands for 
	 * <strong>Practical Identifier</strong>. Ids of entities can have diffrent types which isn&apos;t practical.</p>
	 * 
	 * <p>Pids are always strings which can&apos;t contain slashes or backslashes. This allowes a pid to be used 
	 * in a url path (most servers can&apos;t handle urlencoded slashes in paths).</p>
	 * 
	 * <p><strong>Note:</strong> This method currently uses <code>urlencode()</code> to mask slashes and backslahes 
	 * which could change in further versions. If you want to embed a pid in a url you stil have to encode it even if 
	 * this means that a pid gets urlencoded a second time.</p>
	 * 
	 * @param mixed $id
	 * @return string
	 * @throws \InvalidArgumentException if null is passed as id.
	 */
	public function idToPid($id): string {
		return urlencode($this->entityModel->getIdDef()->getEntityProperty()->valueToRep($id));
	}
	
	/**
	 * <p>Converts a pid back to an id. {@see self::idToPid()} for further informations.</p>
	 * 
	 * @param string $pid
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function pidToId(string $pid) {
		return $this->entityModel->getIdDef()->getEntityProperty()->repToValue(urldecode($pid));
	}
	
	/**
	 * @return EiTypeExtensionCollection
	 */
	public function getEiTypeExtensionCollection(): EiTypeExtensionCollection {
		return $this->eiTypeExtensionCollection;
	}
	
	public function __toString(): string {
		return 'EiType [id: ' . $this->getId() . ']';
	}
	
	public function isAbstract(): bool {
		return $this->entityModel->getClass()->isAbstract();
	}
	
	public function registerVetoableActionListener(EiLifecycleListener $eiLifecycleListener) {
		$this->eiLifecycleListeners[spl_object_hash($eiLifecycleListener)] = $eiLifecycleListener;
	}
	
	public function unregisterVetoableActionListener(EiLifecycleListener $eiLifecycleListener) {
		unset($this->eiLifecycleListeners[spl_object_hash($eiLifecycleListener)]);
	}
	
	public function validateLifecycleAction(VetoableLifecycleAction $vetoableLifecycleAction, N2nContext $n2nContext) {
		foreach ($this->eiLifecycleListeners as $eiLifecycleListener) {
			if ($vetoableLifecycleAction->isPersist()) {
				$eiLifecycleListener->onPersist($vetoableLifecycleAction, $n2nContext);
			} else if ($vetoableLifecycleAction->isUpdate()) {
				$eiLifecycleListener->onUpdate($vetoableLifecycleAction, $n2nContext);
			} else {
				$eiLifecycleListener->onRemove($vetoableLifecycleAction, $n2nContext);
			}
		}
	}
}

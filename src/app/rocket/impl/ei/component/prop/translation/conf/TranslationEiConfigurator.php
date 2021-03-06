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
namespace rocket\impl\ei\component\prop\translation\conf;

use rocket\impl\ei\component\prop\adapter\AdaptableEiPropConfigurator;
use rocket\ei\component\EiSetup;
use n2n\l10n\N2nLocale;
use rocket\spec\UnknownTypeException;
use rocket\ei\UnknownEiTypeExtensionException;
use rocket\ei\component\UnknownEiComponentException;
use rocket\impl\ei\component\prop\translation\TranslationEiProp;
use rocket\ei\component\prop\indepenent\CompatibilityLevel;
use rocket\ei\component\prop\indepenent\PropertyAssignation;
use rocket\ei\component\InvalidEiComponentConfigurationException;
use n2n\persistence\orm\CascadeType;
use n2n\reflection\ReflectionUtils;
use n2n\core\container\N2nContext;
use n2n\impl\web\dispatch\mag\model\BoolMag;
use n2n\util\config\LenientAttributeReader;
use n2n\impl\web\dispatch\mag\model\MagCollectionArrayMag;
use n2n\web\dispatch\mag\MagCollection;
use n2n\impl\web\dispatch\mag\model\StringMag;
use n2n\impl\web\dispatch\mag\model\MagForm;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\l10n\IllegalN2nLocaleFormatException;
use n2n\reflection\property\TypeConstraint;
use n2n\core\config\WebConfig;
use rocket\impl\ei\component\prop\translation\command\TranslationCopyCommand;
use n2n\impl\web\dispatch\mag\model\NumericMag;

class TranslationEiConfigurator extends AdaptableEiPropConfigurator {
	const ATTR_USE_SYSTEM_LOCALES_KEY = 'useSystemN2nLocales';
	const ATTR_SYSTEM_LOCALE_DEFS_KEY = 'systenN2nLocaleDefs';
	const ATTR_CUSTOM_LOCALE_DEFS_KEY = 'customN2nLocaleDefs';
	const ATTR_LOCALE_ID_KEY = 'id';
	const ATTR_LOCALE_LABEL_KEY = 'label';
	const ATTR_LOCALE_MANDATORY_KEY = 'mandatory';
	const ATTR_MIN_NUM_TRANSLATIONS_KEY = 'min';
	
	private $translationEiProp;
	
	public function __construct(TranslationEiProp $translationEiProp) {
		parent::__construct($translationEiProp);
				
		$this->autoRegister($translationEiProp);
		
		$this->translationEiProp = $translationEiProp;
	}
	
	public function testCompatibility(PropertyAssignation $propertyAssignation): int {
		$level = parent::testCompatibility($propertyAssignation);
		if (CompatibilityLevel::NOT_COMPATIBLE == $level) {
			return CompatibilityLevel::NOT_COMPATIBLE;
		}
		
		return CompatibilityLevel::EXTREMELY_COMMON;
	}
	
	public function createMagDispatchable(N2nContext $n2nContext): MagDispatchable {
		$magDispatchable = parent::createMagDispatchable($n2nContext);
		$lar = new LenientAttributeReader($this->attributes);
		
		$magCollection = $magDispatchable->getMagCollection();
		
		$magCollection->addMag(self::ATTR_USE_SYSTEM_LOCALES_KEY, new BoolMag('Use system locales',
				$lar->getBool(self::ATTR_USE_SYSTEM_LOCALES_KEY, true)));
		
		$systemN2nLocaleDefsMag = new MagCollectionArrayMag('System locales',
				$this->createN2nLocaleDefMagClosure());
		$systemN2nLocaleDefsMag->setValue($this->n2nLocaleDefsToMagValue($this->readModN2nLocaleDefs(
				self::ATTR_SYSTEM_LOCALE_DEFS_KEY, $lar, $n2nContext->lookup(WebConfig::class)->getSupersystem()->getN2nLocales())));
		$magCollection->addMag(self::ATTR_SYSTEM_LOCALE_DEFS_KEY, $systemN2nLocaleDefsMag);
		
		$customN2nLocaleDefsMag = new MagCollectionArrayMag('Custom locales',
				$this->createN2nLocaleDefMagClosure());
		$customN2nLocaleDefsMag->setValue($this->n2nLocaleDefsToMagValue(
				$this->readN2nLocaleDefs(self::ATTR_CUSTOM_LOCALE_DEFS_KEY, $lar)));
		$magCollection->addMag(self::ATTR_CUSTOM_LOCALE_DEFS_KEY, $customN2nLocaleDefsMag);
		
		$magCollection->addMag(self::ATTR_MIN_NUM_TRANSLATIONS_KEY, new NumericMag('Min translations number',
				$lar->getNumeric(self::ATTR_MIN_NUM_TRANSLATIONS_KEY, 0)));
		
		return $magDispatchable;
	}
	
	private function createN2nLocaleDefMagClosure() {
		return function () {
			$magCollection = new MagCollection();
			$magCollection->addMag(self::ATTR_LOCALE_ID_KEY, new StringMag('N2nLocale', null, true));
			$magCollection->addMag(self::ATTR_LOCALE_MANDATORY_KEY, new BoolMag('Mandatory'));
			$magCollection->addMag(self::ATTR_LOCALE_LABEL_KEY, new StringMag('Label', null, false));
			return new MagForm($magCollection);
		};
	}
	
	public function saveMagDispatchable(MagDispatchable $magDispatchable, N2nContext $n2nContext) {
		parent::saveMagDispatchable($magDispatchable, $n2nContext);
		
		$this->attributes->appendAll($magDispatchable->getMagCollection()->readValues(
				array(self::ATTR_USE_SYSTEM_LOCALES_KEY, self::ATTR_SYSTEM_LOCALE_DEFS_KEY, 
						self::ATTR_CUSTOM_LOCALE_DEFS_KEY, self::ATTR_MIN_NUM_TRANSLATIONS_KEY)), true);
	}
	
	private function n2nLocaleDefsToMagValue(array $n2nLocaleDefs) {
		$magValue = array();
		foreach ($n2nLocaleDefs as $n2nLocaleDef) {
			$magValue[] = array(
					self::ATTR_LOCALE_ID_KEY => $n2nLocaleDef->getN2nLocale()->getId(),
					self::ATTR_LOCALE_MANDATORY_KEY => $n2nLocaleDef->isMandatory(),
					self::ATTR_LOCALE_LABEL_KEY => $n2nLocaleDef->getLabel());
		}
		return $magValue;
	}
	
	private function readModN2nLocaleDefs($key, LenientAttributeReader $lar, array $n2nLocales): array {
		$modN2nLocaleDefs = $this->readN2nLocaleDefs($key, $lar);
		
		$n2nLocaleDefs = array();
		foreach ($n2nLocales as $n2nLocale) {			
			$n2nLocaleId = $n2nLocale->getId();
			
			if (isset($modN2nLocaleDefs[$n2nLocaleId])) {
				$n2nLocaleDefs[$n2nLocaleId] = $modN2nLocaleDefs[$n2nLocaleId];
				continue;
			}
			
			$n2nLocaleDefs[$n2nLocaleId] = new N2nLocaleDef($n2nLocale, false, null);
		}

		return $n2nLocaleDefs;
	}
	
	private function readN2nLocaleDefs($key, LenientAttributeReader $lar): array {
		$n2nLocaleDefs = array();
		foreach ($lar->getArray($key, array(), TypeConstraint::createArrayLike('array', false, 
				TypeConstraint::createSimple('scalar'))) as $n2nLocaleDefAttr) {
			if (!isset($n2nLocaleDefAttr[self::ATTR_LOCALE_ID_KEY])) continue;
			$n2nLocale = null;
			try {
				$n2nLocale = N2nLocale::create($n2nLocaleDefAttr[self::ATTR_LOCALE_ID_KEY]);
			} catch (IllegalN2nLocaleFormatException $e) {
				continue;
			}
			
			$n2nLocaleDefs[$n2nLocale->getId()] = new N2nLocaleDef($n2nLocale,
					(isset($n2nLocaleDefAttr[self::ATTR_LOCALE_MANDATORY_KEY]) 
							? (bool) $n2nLocaleDefAttr[self::ATTR_LOCALE_MANDATORY_KEY] : false),
					(isset($n2nLocaleDefAttr[self::ATTR_LOCALE_LABEL_KEY]) 
							? $n2nLocaleDefAttr[self::ATTR_LOCALE_LABEL_KEY] : null));
		}
		return $n2nLocaleDefs;
	}
	
	private function writeN2nLocaleDefs($key, array $n2nLocaleDefs, bool $modOnly) {
		$n2nLocaleDefsAttrs = array();
		
		foreach ($n2nLocaleDefs as $n2nLocaleDef) {
			$attrs = array(self::ATTR_LOCALE_ID_KEY => $n2nLocaleDef->getN2nLocale()->getId());
			if ($n2nLocaleDef->isMandatory()) {
				$attrs[self::ATTR_LOCALE_MANDATORY_KEY] = $n2nLocaleDef->isMandatory();
			}
			if (null !== ($label = $n2nLocaleDef->getLabel())) {
				$attrs[self::ATTR_LOCALE_LABEL_KEY] = $n2nLocaleDef->getLabel();
			}
			$n2nLocaleDefsAttrs[] = $attrs;
		}
		
		$this->attributes->set($key, $n2nLocaleDefsAttrs);
	}
	
	public function setup(EiSetup $eiSetupProcess) {
		$eiu = $eiSetupProcess->eiu();
		
		$lar = new LenientAttributeReader($this->attributes);
		
		$n2nLocaleDefs = array();
		if ($this->attributes->getBool(self::ATTR_USE_SYSTEM_LOCALES_KEY, false, true)) {
			$n2nLocaleDefs = $this->readModN2nLocaleDefs(self::ATTR_SYSTEM_LOCALE_DEFS_KEY, $lar, 
					$eiu->lookup(WebConfig::class)->getAllN2nLocales());
		} 
		
		$n2nLocaleDefs = array_merge($n2nLocaleDefs, $this->readN2nLocaleDefs(self::ATTR_CUSTOM_LOCALE_DEFS_KEY, $lar));
		if (empty($n2nLocaleDefs)) {
			$n2nLocaleDefs = array(N2nLocale::getDefault()->getId() => new N2nLocaleDef(N2nLocale::getDefault(), false));
		}
		$this->translationEiProp->setN2nLocaleDefs($n2nLocaleDefs);
		
		$this->translationEiProp->setMinNumTranslations($this->attributes->getInt(self::ATTR_MIN_NUM_TRANSLATIONS_KEY, false, 0));
		
		$this->addMandatory = true;
		
		// @todo combine with relation eifields
		$eiPropRelation = $this->translationEiProp->getEiPropRelation();
		$relationProperty = $eiPropRelation->getRelationEntityProperty();
		$targetEntityClass = $relationProperty->getRelation()->getTargetEntityModel()->getClass();
		try {
			$targetEiType = $eiSetupProcess->eiu()->context()->mask($targetEntityClass)->getEiType();
				
			$targetEiMask = null;
// 			if (null !== ($eiMaskId = $this->attributes->get(self::OPTION_TARGET_MASK_KEY))) {
// 				$targetEiMask = $target->getEiTypeExtensionCollection()->getById($eiMaskId);
// 			} else {
				$targetEiMask = $targetEiType->getEiMask();
// 			}

			$entityProperty = $this->requireEntityProperty();
			if (CascadeType::ALL !== $entityProperty->getRelation()->getCascadeType()) {
				throw $eiSetupProcess->createException('EiProp requires an EntityProperty which cascades all: ' 
						. ReflectionUtils::prettyPropName($entityProperty->getEntityModel()->getClass(),
								$entityProperty->getName()));
			}
			
			if (!$entityProperty->getRelation()->isOrphanRemoval()) {
				throw $eiSetupProcess->createException('EiProp requires an EntityProperty which removes orphans: '
						. ReflectionUtils::prettyPropName($entityProperty->getEntityModel()->getClass(),
								$entityProperty->getName()));
			}

			$eiPropRelation->init($eiSetupProcess->eiu(), $targetEiType, $targetEiMask, []);
		} catch (UnknownTypeException $e) {
			throw $eiSetupProcess->createException(null, $e);
		} catch (UnknownEiTypeExtensionException $e) {
			throw $eiSetupProcess->createException(null, $e);
		} catch (UnknownEiComponentException $e) {
			throw $eiSetupProcess->createException('EiProp for Mapped Property required', $e);
		} catch (InvalidEiComponentConfigurationException $e) {
			throw $eiSetupProcess->createException(null, $e);
		}
		
		$copyCommand = new TranslationCopyCommand();
		$targetEiMask->getEiCommandCollection()->add($copyCommand);
		$this->translationEiProp->setCopyCommand($copyCommand);
	}
}

class N2nLocaleDef {
	private $n2nLocale;
	private $mandatory;
	private $label;
	
	public function __construct(N2nLocale $n2nLocale, bool $mandatory, string $label = null) {
		$this->n2nLocale = $n2nLocale;
		$this->mandatory = $mandatory;
		$this->label = $label;
	}
	
	public function getN2nLocaleId() {
		return $this->n2nLocale->getId();
	}
	
	public function getN2nLocale() {
		return $this->n2nLocale;
	}
	
	public function isMandatory(): bool {
		return $this->mandatory;
	}
	
	public function getLabel() {
		return $this->label;
	}
	
	public function buildLabel(N2nLocale $n2nLocale) {
		if ($this->label !== null) {
			return $this->label;
		}
		
		return $this->n2nLocale->getName($n2nLocale);
	}
}

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
namespace rocket\ei\component;

use n2n\reflection\ArgUtils;
use n2n\util\ex\IllegalStateException;
use rocket\ei\IdPath;
use rocket\ei\mask\EiMask;

abstract class EiComponentCollection implements \IteratorAggregate, \Countable {
	private $elementName;
	private $genericType;
	
	protected $eiMask;
	private $elements = array();
	private $independentElements = array();
	private $inheritedCollection;
	private $disabledInheritIds = array();
	
	public function __construct($elementName, $genericType) {
		$this->elementName = $elementName;
		$this->genericType = $genericType;
	}
	
	protected function setEiMask(EiMask $eiMask) {
		$this->eiMask = $eiMask;
	}
	
	public function getEiMask() {
		if ($this->eiMask !== null) {
			return $this->eiMask;
		}
		
		throw new IllegalStateException('No EiMask assigend to EiComponentCollection: ' . $this->elementName);
	}
	
	/**
	 * @param EiComponentCollection $inheritedCollection
	 */
	public function setInheritedCollection(EiComponentCollection $inheritedCollection = null) {
		$this->ensureNoEiEngine();
		
		$this->inheritedCollection = $inheritedCollection;
	}
	
	/**
	 * @return EiComponentCollection
	 */
	public function getInheritedCollection() {
		return $this->inheritedCollection;
	}
	
	private function ensureNoEiEngine() {
		if (!$this->eiMask->hasEiEngine()) return;
		
		throw new IllegalStateException('Can not add EiComponent because EiEngine for EiMask ' . $this->eiMask 
				. ' is already initialized.');
	}
	
	/**
	 * @param EiComponent $eiComponent
	 */
	protected function addEiComponent(EiComponent $eiComponent, bool $prepend = false) {
		$this->ensureNoEiEngine();
		
		ArgUtils::valType($eiComponent, $this->genericType);
		if (0 == mb_strlen($eiComponent->getId())) {
			$eiComponent->setId($this->makeUniqueId($eiComponent->getIdBase()));
		} else if (IdPath::constainsSpecialIdChars($eiComponent->getId())) {
			throw new InvalidEiComponentConfigurationException($this->elementName . ' contains invalid id: ' 
					. $eiComponent->getId());
		}
		
		$eiComponent->setEiMask($this->eiMask);
		if (!$prepend) {
			$this->elements[$eiComponent->getId()] = $eiComponent;
		} else {
			$this->elements = array($eiComponent->getId() => $eiComponent) + $this->elements;
		}
	}
	
	public function addIndependent(IndependentEiComponent $independentEiComponent) {
		$this->addEiComponent($independentEiComponent);
		$this->independentElements[$independentEiComponent->getId()] = $independentEiComponent;
	}
	
	/**
	 * @param string $id
	 * @return EiComponent
	 * @throws UnknownEiComponentException
	 */
	protected function getEiComponentById($id) {
		if (isset($this->elements[$id])) {
			return $this->elements[$id];
		}
		
		if ($this->inheritedCollection !== null) {
			return $this->inheritedCollection->getById($id);
		}
		
		throw new UnknownEiComponentException('No ' . $this->elementName . ' with id \'' . (string) $id 
				. '\' found in ' . $this->eiMask . '.');
	}
	
	/**
	 * @param bool $checkInherited
	 * @return boolean
	 */
	public function isEmpty(bool $checkInherited = true): bool {
		if (!$checkInherited) {
			return empty($this->elements);
		}
		
		return empty($this->elements) && ($this->inheritedCollection === null 
				|| $this->inheritedCollection->isEmpty());
	}
	
	/**
	 * @param string $idBase
	 * @return string
	 */
	private function makeUniqueId($idBase) {
		$idBase = IdPath::stripSpecialIdChars($idBase);
		if (0 < mb_strlen($idBase) && !$this->containsId($idBase, true, true)) {
			return $idBase;			
		}
		
		for ($ext = 1; true; $ext++) {
			$id = $idBase . $ext;
			if (!$this->containsId($id, true, true)) {
				return $id;
			}
		}
	}
	
	/**
	 * @param string $id
	 * @return boolean
	 */
	public function containsId($id, $checkInherited = true): bool {
		if (isset($this->elements[$id])) return true;
		
		if ($this->inheritedCollection !== null && $checkInherited && 
				$this->inheritedCollection->containsId($id, true, false)) {
			return true;
		}
		
		return false;
	}
	
	/* (non-PHPdoc)
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator() {
		return new \ArrayIterator($this->toArray());
	}
	
	/**
	 * @param string $independentOnly
	 * @return \rocket\ei\component\EiComponent[] 
	 */
	public function toArray(bool $includeInherited = true): array {
		if ($this->inheritedCollection === null || !$includeInherited) return $this->elements;
		
		$superElements = $this->filterEnableds($this->inheritedCollection->toArray(true));

		return $superElements + $this->elements;
	}
	
	/**
	 * @param string $levelOnly
	 * @param string $independentOnly
	 * @return \rocket\ei\component\IndependentEiComponent[] 
	 */
	public function filter($levelOnly = false, $independentOnly = false) {
		if ($levelOnly) {
			return $this->filterLevel($independentOnly);
		}
		
		return $this->toArray($independentOnly);
	}
	
	/**
	 * @param string $independentOnly
	 * @return \rocket\ei\component\IndependentEiComponent[]  
	 */
	private function filterInherited($independentOnly = false) {
		if ($this->inheritedCollection === null) return array();
		
		return $this->inheritedCollection->toArray($independentOnly);
	}
	/**
	 * @param bool $independentOnly
	 * @return \rocket\ei\component\EiComponent[] 
	 */
	public function filterLevel($independentOnly = false, $includeMasked = true, $includeDisabledInherits = false) {
		$elements = null;
		if (!$independentOnly) {
			$elements = $this->elements;
		} else {
			$elements = $this->independentElements;
			if (!$includeDisabledInherits) {
				$elements = $this->filterEnableds($elements);
			}
		}
		
		if ($this->inheritedCollection !== null && $this->eiMask !== null && $includeMasked) {
			$elements = $this->inheritedCollection->filterLevel($independentOnly, $includeMasked, $includeDisabledInherits) + $elements;
		}
		
		return $elements;
	}
	
	/**
	 * @param array $elements
	 * @return \rocket\ei\component\IndependentEiComponent[]
	 */
	private function filterEnableds(array $elements) {
		foreach ($elements as $id => $element) {
			if ($this->containsDisabledInheritId($id)) {
				unset($elements[$id]);
			}
		}
		
		return $elements;
	}
	
	/**
	 * @param array $elements
	 * @return \rocket\ei\component\IndependentEiComponent[] 
	 */
	private function filterIndependents(array $elements) {
		$independentElements = array();
		foreach ($elements as $key => $element) {
			if ($element instanceof IndependentEiComponent) {
				$independentElements[$key] = $element;
			}
		}
		
		return $independentElements;
	}
	
	/* (non-PHPdoc)
	 * @see Countable::count()
	 */
	public function count() {
		$num = count($this->elements);
		if ($this->inheritedCollection !== null) {
			$num += $this->inheritedCollection->count();
		}	
		return $num;
	}
	
	/**
	 * @return number
	 */
	public function countLevel(): int {
		$num = count($this->elements);
		if ($this->hasMasked()) {
			$num += $this->inheritedCollection->countLevel();
		}
		return $num;
	}
	
	/**
	 * @param bool $independentOnly
	 */
	public function clear($independentOnly) {
		$this->clearLevel($independentOnly);
		
		if ($this->inheritedCollection !== null) {
			$this->inheritedCollection->clear($independentOnly);
		}
	}
	
	/**
	 * @param bool $independentOnly
	 */
	public function clearLevel($independentOnly = false) {
		if (!$independentOnly) {
			$this->elements = array();
			return;
		}
		
		foreach ($this->filterIndependents($this->elements) as $id => $element) {
			unset($this->elements[$id]);
		}
	}
	
	/**
	 * @param string $id
	 */
	public function removeById($id) {
		if (isset($this->elements[$id])) {
			unset($this->elements[$id]);
		}
		
		if ($this->inheritedCollection !== null) {
			$this->inheritedCollection->removeById($id);
		}
	}
	
	/**
	 * @param EiComponent $eiComponent
	 */
	public function remove(EiComponent $eiComponent) {
		$this->removeById($eiComponent->getId());
	}
	
	/**
	 * @param string $independentOnly
	 * @return number
	 */
	public function combineAll($independentOnly = false) {
		return $this->filterInherited($independentOnly) 
				+ $this->combineLevelAndSub($independentOnly);
	}
	
	/**
	 * @param string $independentOnly
	 * @return \rocket\ei\component\EiComponent[] 
	 */
	protected function combineLevelAndSub($independentOnly = false) {
		$elements = $this->filterLevel($independentOnly);
		foreach ($this->subCollections as $subCollection) {
			$elements += $subCollection->combineLevelAndSub($independentOnly);
		}
		return $elements;
	}
	
	/**
	 * @param string $id
	 */
	public function disableInheritById(string $id) {
		$this->disabledInheritIds[$id] = $id;
	}
	
	/**
	 * @param string $id
	 * @return boolean
	 */
	public function containsDisabledInheritId($id) {
		return isset($this->disabledInheritIds[$id]);
	}
}

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

use rocket\ei\component\prop\SortableEiProp;
use rocket\ei\component\prop\EiPropCollection;
use rocket\ei\component\modificator\EiModificatorCollection;
use rocket\ei\manage\EiFrame;
use rocket\ei\manage\critmod\filter\FilterDefinition;
use rocket\ei\component\prop\FilterableEiProp;
use n2n\core\container\N2nContext;
use rocket\ei\EiPropPath;
use rocket\ei\manage\critmod\filter\EiEntryFilterDefinition;
use rocket\ei\manage\critmod\sort\SortDefinition;
use n2n\reflection\ArgUtils;
use rocket\ei\manage\critmod\sort\SortField;
use rocket\ei\component\prop\SortableEiPropFork;
use rocket\ei\manage\critmod\sort\SortFieldFork;
use rocket\ei\manage\critmod\filter\EiEntryFilterField;
use rocket\ei\manage\critmod\filter\FilterField;
use rocket\ei\component\prop\QuickSearchableEiProp;
use rocket\ei\manage\critmod\quick\QuickSearchDefinition;

class CritmodFactory {
	private $eiPropCollection;
	private $eiModificatorCollection;
	
	public function __construct(EiPropCollection $eiPropCollection, EiModificatorCollection $eiModificatorCollection) {
		$this->eiPropCollection = $eiPropCollection;
		$this->eiModificatorCollection = $eiModificatorCollection;
	}
	
// 	public static function createFilterModel(EiType $eiType, N2nContext $n2nContext) {
// 		return self::createFilterModelInstance($eiType, $n2nContext);
// 	}
	
// 	public static function createFilterModelFromEiFrame(EiFrame $eiFrame) {
// 		return self::createFilterModelInstance($eiFrame->getContextEiEngine()->getEiMask()->getEiType(), 
// 				$eiFrame->getN2nContext(), $eiFrame);
// 	}
	
	public function createManagedFilterDefinition(EiFrame $eiFrame): FilterDefinition {
		$filterDefinition = new FilterDefinition();
		foreach ($this->eiPropCollection as $id => $eiProp) {
			if (!($eiProp instanceof FilterableEiProp)) continue;
			
			$filterField = $eiProp->buildManagedFilterField($eiFrame);
			ArgUtils::valTypeReturn($filterField, FilterField::class, $eiProp, 'buildManagedFilterField', true);
			
			if ($filterField !== null) {
				$filterDefinition->putFilterField($eiProp->getId(), $filterField);
			}
		}		
		return $filterDefinition;
	}
	
	public function createFilterDefinition(N2nContext $n2nContext): FilterDefinition {
		$filterDefinition = new FilterDefinition();
		foreach ($this->eiPropCollection as $id => $eiProp) {
			if (!($eiProp instanceof FilterableEiProp)) continue;
			
			$filterField = $eiProp->buildFilterField($n2nContext);
			ArgUtils::valTypeReturn($filterField, FilterField::class, $eiProp, 'buildFilterField', true);
			
			if ($filterField !== null) {
				$filterDefinition->putFilterField($eiProp->getId(), $filterField);
			}
		}
		return $filterDefinition;
	}
	
	public function createEiEntryFilterDefinition(N2nContext $n2nContext): EiEntryFilterDefinition {
		$eiFieldFilterDefinition = new EiEntryFilterDefinition();
		
		foreach ($this->eiPropCollection as $id => $eiProp) {
			if (!($eiProp instanceof FilterableEiProp)) continue;
			
			$eiEntryFilterField = $eiProp->buildEiEntryFilterField($n2nContext);
			ArgUtils::valTypeReturn($eiEntryFilterField, EiEntryFilterField::class, $eiProp, 
					'buildEiEntryFilterField', true);

			if ($eiEntryFilterField !== null) {
				$eiFieldFilterDefinition->putEiEntryFilterField(EiPropPath::from($eiProp), $eiEntryFilterField);
			}
		}
		
		return $eiFieldFilterDefinition;
	}
	
	public function createManagedSortDefinition(EiFrame $eiFrame): SortDefinition {
		$sortDefinition = new SortDefinition();
		
		foreach ($this->eiPropCollection as $id => $eiProp) {
			if ($eiProp instanceof SortableEiProp) {
				if (null !== ($sortField = $eiProp->buildManagedSortField($eiFrame))) {
					ArgUtils::valTypeReturn($sortField, SortField::class, $eiProp, 'buildManagedSortField', true);
					$sortDefinition->putSortField($id, $sortField);
				}
			}
			
			if ($eiProp instanceof SortableEiPropFork) {
				if (null !== ($sortFieldFork = $eiProp->buildManagedSortFieldFork($eiFrame))) {
					ArgUtils::valTypeReturn($sortFieldFork, SortFieldFork::class, $eiProp, 'buildManagedSortFieldFork', true);
					$sortDefinition->putSortFieldFork($id, $sortFieldFork);
				}
			}
		}
		
		return $sortDefinition;
	}
	
	public function createSortDefinition(N2nContext $n2nContext): SortDefinition {
		$sortDefinition = new SortDefinition();
		
		foreach ($this->eiPropCollection as $id => $eiProp) {
			if (!($eiProp instanceof SortableEiProp)) continue;
			
			$sortDefinition->putSortField(EiPropPath::from($eiProp), $eiProp->buildSortField($n2nContext));
		}
		
		return $sortDefinition;
	}
	
	public function createQuickSearchDefinition(EiFrame $eiFrame) {
		$quickSearchDefinition = new QuickSearchDefinition();
	
		foreach ($this->eiPropCollection as $id => $eiProp) {
			if (!($eiProp instanceof QuickSearchableEiProp)) continue;
				
			if (null !== ($quickSearchField = $eiProp->buildQuickSearchField($eiFrame))) {
				$quickSearchDefinition->putQuickSearchField(EiPropPath::from($eiProp), $quickSearchField);
			}
		}
	
		return $quickSearchDefinition;
	}
	
// 	public static function createSortModelFromEiFrame(EiFrame $eiFrame) {
// 		return self::createSortModelInstance($eiFrame->getContextEiEngine()->getEiMask()->getEiType(), $eiFrame->getN2nContext());
// 	}
	
// 	public static function createSortModel() {
// 		$sortModel = new SortModel();
// 		foreach ($this->eiPropCollection as $id => $eiProp) {
// 			if (!($eiProp instanceof SortableEiProp)) continue;
			
// 			if (null !== ($sortItem = $eiProp->getSortItem())) {
// 				$sortModel->putSortItem($id, $eiProp->getSortItem());
// 			}
			
// 			if (null !== ($sortItemFork = $eiProp->getSortItemFork())) {
// 				$sortModel->putSortItemFork($id, $eiProp->getSortItemFork());
// 			}
// 		}
// 		return $sortModel;
// 	}
		
// 	public static function createQuickSearchableModel(EiFrame $eiFrame) {
// 		$quickSerachModel = new QuickSearchModel();
// 		foreach ($eiFrame->getContextEiEngine()->getEiMask()->getEiType()->getEiPropCollection() as $field) {
// 			if ($field instanceof QuickSearchableEiProp) {
// 				$quickSerachModel->addQuickSearchable($field);
// 			}
// 		}
// 		return $quickSerachModel;
// 	}
}

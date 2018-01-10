<?php 
namespace rocket\impl\ei\component\prop\translation\command;

use n2n\web\http\controller\ControllerAdapter;
use rocket\spec\ei\manage\util\model\EiuCtrl;
use n2n\web\http\controller\ParamQuery;
use rocket\spec\ei\manage\gui\GuiIdPath;
use n2n\web\http\BadRequestException;
use n2n\impl\web\ui\view\jhtml\JhtmlResponse;
use n2n\util\ex\UnsupportedOperationException;
use n2n\web\dispatch\map\PropertyPath;
use n2n\l10n\N2nLocale;
use n2n\l10n\IllegalN2nLocaleFormatException;

class TranslationCopyController extends ControllerAdapter {
	public function doLive(EiuCtrl $eiuCtrl, ParamQuery $guiIdPath, ParamQuery $propertyPath, ParamQuery $bulky,
			ParamQuery $toN2nLocale, ParamQuery $fromIdRep, ParamQuery $toIdRep = null) {
				$fromEiuEntry = $eiuCtrl->lookupEntry((string) $fromIdRep)->copy();
		$toEiuEntry = null;
		if ($toIdRep !== null) {
			$toEiuEntry = $eiuCtrl->lookupEntry((string) $toIdRep);
		} else {
			$toEiuEntry = $eiuCtrl->frame()->newEntry(false, $fromEiuEntry);
		}
		
		try {
			$guiIdPath = GuiIdPath::createFromExpression((string) $guiIdPath);
			$propertyPath = PropertyPath::createFromPropertyExpression($propertyPath);
			$toN2nLocale = N2nLocale::create($toN2nLocale);
		} catch (\InvalidArgumentException $e) {
			throw new BadRequestException(null, null, $e);
		} catch (IllegalN2nLocaleFormatException $e) {
			throw new BadRequestException(null, null, $e);
		}
		
		if (!$fromEiuEntry->containsGuiProp($guiIdPath)) {
			throw new BadRequestException('Unknown guiIdPath: ' . $guiIdPath);
		}
		
		$eiPropPath = $fromEiuEntry->guiIdPathToEiPropPath($guiIdPath);
		$fromEiuEntry->copyValuesTo($toEiuEntry, [$eiPropPath]);
		
		$eiuEntryGui = $toEiuEntry->newCustomEntryGui(function () {
			throw new UnsupportedOperationException();
		}, array($guiIdPath), $bulky->toBool(), true);
		
		$this->send(new JhtmlResponse($this->createView('translationCopy.html', 
				array('eiuEntryGui' => $eiuEntryGui, 'propertyPath' => $propertyPath,
						'n2nLocale' => $toN2nLocale, 'guiIdPath' => $guiIdPath))));
	}
}
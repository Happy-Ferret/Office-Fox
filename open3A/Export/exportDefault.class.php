<?php
/*
 *  This file is part of open3A.

 *  open3A is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  open3A is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  2007 - 2014, Rainer Furtmeier - Rainer@Furtmeier.IT
 */
abstract class exportDefault implements iGUIHTML2 {
	protected $filename;
	protected $lineEnding = "\n";
	
	public function getHTML($id){
		$T = new HTMLTable(1, $this->getLabel());

		$BCSV = new Button("CSV","export");
		$BCSV->windowRme(str_replace("GUI", "",get_class($this)), "", "getExportData", "CSVExport");
		$BCSV->style("float:right;");

		$BXML = new Button("XML","export");
		$BXML->windowRme(str_replace("GUI", "",get_class($this)), "", "getExportData", "XML");

		$T->addRow($BCSV.$BXML);


		$BHTML = new Button("HTML","export");
		$BHTML->windowRme(str_replace("GUI", "",get_class($this)), "", "getExportData", "HTMLTable");

		$BXLS = new Button("Excel","./open3A/Export/excelExport.png");
		$BXLS->windowRme(str_replace("GUI", "",get_class($this)), "", "getExportData", "ExcelExport");
		$BXLS->style("float:right;");

		$T->addRow($BXLS.$BHTML);

		return $T;
	}

	public abstract function getExportCollection();

	protected abstract function entryParser(PersistentObject $entry);

	public function getExportData($type){
		$C = $this->getExportCollection();

		while($t = $C->getNextEntry())
			$this->entryParser($t);
		
		$C->resetPointer();

		$className = null;

		switch($type){
			case "XML":
				$XML = new XML();
				$XML->setCollection($C);

				echo Util::getBasicHTMLText(htmlentities(utf8_decode($XML->getXML())),"XML-Export");
			break;

			case "HTMLTable":
			case "CSVExport":
			case "ExcelExport":
				$UT = null;

				while($t = $C->getNextEntry()){
					$A = $t->getA();

					if($UT == null){
						$fields = PMReflector::getAttributesArrayAnyObject($A);
						$ID = get_class($t)."ID";
						if(isset($A->$ID)) {
							unset($A->$ID);
							unset($fields[array_search($ID, $fields)]);
						}

						$UT = new UnifiedTable(count($fields));

						foreach($fields AS $k => $v)
							$fields[$k] = ucfirst($fields[$k]);

						$UT->addHeaderRow($fields);

						$className = get_class($t);
					}


					$UT->addRow($A);
				}

				if($type == "HTMLTable")
					echo Util::getBasicHTML($UT->getAs($type), "HTML-Export");

				if($type == "CSVExport"){
					$UT->setCSVNewline($this->lineEnding);
					$UT->getAs($type, $this->filename == null ? "CSV-Export_".$className."_".date("Ymd").".csv" : $this->filename);
				}
				
				if($type == "ExcelExport")
					$UT->getAs($type, "Excel-Export_".$className."_".date("Ymd").".xls");
			break;
		}

	}
}
?>

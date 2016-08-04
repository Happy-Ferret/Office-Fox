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
 *  2007 - 2016, Rainer Furtmeier - Rainer@Furtmeier.IT
 */
class exportAdressenGUI extends exportDefault implements iExport, iGUIHTML2 {

	private $Kategorien = array();

	public function getLabel(){
		return "Adressen";
	}
	
	public function getApps(){
		return array("open3A", "lightCRM");
	}

	public function getHTML($id){
		$p = parent::getHTML($id);
		
		$text = "";
		$fields = array();
		$AC = anyC::get("Adresse", "AuftragID", "-1");
		$num = $AC->getTotalNum();
		if($num > 2000){
			$fields[] = "anzahl";
			$fields[] = "start";
			
			$text = "<p>Ihre Datenbank enthält $num Datensätze. Bitte geben Sie den Startdatensatz sowie eine Anzahl ein, um eine Auswahl zu exportieren.</p>";
		}
		
		if(Session::isPluginLoaded("mAdressBuch"))
			$fields[] = "CK1";
		
		$T = new HTMLForm("exportSubset", $fields, "Auswahl");
		$T->getTable()->setColWidth(1, 120);
		
		if(Session::isPluginLoaded("mAdressBuch")){
			$T->setLabel("CK1", "Adressbuch");
			$T->setType("CK1", "select", null, mAdressBuchGUI::getABs(true));
		}
		
		$T->setDescriptionField("start", "Zum Beispiel bei Anzahl 1000: 1, 1001, 2001, 3001, ...");
		
		return $text.$p.$T;
	}

	public function getExportCollection($start = null, $count = null, $Adressbuch = 0){
		$ac = new anyC();
		$ac->setCollectionOf("Kategorie");
		$ac->addAssocV3("type", "=", "1");

		while($a = $ac->getNextEntry())
			$this->Kategorien[$a->getID()] = $a->A("name");


		$ac = new anyC();
		$ac->setCollectionOf("Adresse");
		$ac->addJoinV3("Kappendix", "AdresseID", "=", "AdresseID");
		$ac->addAssocV3("AuftragID", "=", "-1");
		if(Session::isPluginLoaded("mAdressBuch") AND $Adressbuch)
			$ac->addAssocV3("type", "=", $Adressbuch);
		
		$ac->setFieldsV3(array(
			"anrede",
			"nachname",
			"vorname",
			"anrede AS Anrede",
			"firma AS Firma",
			"vorname AS Vorname",
			"nachname AS Nachname",
			"strasse AS Strasse",
			"nr AS Hausnummer",
			"plz AS PLZ",
			"ort AS Ort",
			"land AS Land",
			"tel AS Telefon",
			"fax AS Fax",
			"email AS EMail",
			"homepage AS Homepage",
			"mobil AS Mobil",
			"KategorieID AS Kategorie",
			"t1.bemerkung AS Bemerkung",
			"kundennummer AS Kundennummer",
			"t2.UStIdNr AS UStIdNr",
			"anrede AS Anrede2"));

		if($start AND $count)
			$ac->setLimitV3 ("$start, $count");
		
		return $ac;
	}

	protected function entryParser(PersistentObject $entry){

		$entry->changeA("Anrede", Util::formatAnrede("de_DE", $entry, true));
		$entry->changeA("Anrede2", Util::formatAnrede("de_DE", $entry, false));

		if(isset($this->Kategorien[$entry->A("Kategorie")]))
			$entry->changeA("Kategorie", $this->Kategorien[$entry->A("Kategorie")]);
		else $entry->changeA("Kategorie", "");

		$A = $entry->getA();
		unset($A->AdresseID);
		unset($A->anrede);
		unset($A->nachname);
		unset($A->vorname);
	}
}
?>

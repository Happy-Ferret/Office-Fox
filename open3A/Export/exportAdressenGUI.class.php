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
class exportAdressenGUI extends exportDefault implements iExport, iGUIHTML2 {

	private $Kategorien = array();

	public function getLabel(){
		return "Adressen";
	}
	
	public function getApps(){
		return array("open3A", "lightCRM");
	}

	public function getExportCollection(){
		$ac = new anyC();
		$ac->setCollectionOf("Kategorie");
		$ac->addAssocV3("type", "=", "1");

		while($a = $ac->getNextEntry())
			$this->Kategorien[$a->getID()] = $a->A("name");


		$ac = new anyC();
		$ac->setCollectionOf("Adresse");
		$ac->addJoinV3("Kappendix", "AdresseID", "=", "AdresseID");
		$ac->addAssocV3("AuftragID", "=", "-1");
		$ac->setFieldsV3(array(
			"anrede",
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
			"t2.UStIdNr AS UStIdNr"));

		return $ac;
	}

	protected function entryParser(PersistentObject $entry){

		$entry->changeA("Anrede", Util::formatAnrede("de_DE", $entry, true));

		if(isset($this->Kategorien[$entry->A("Kategorie")]))
			$entry->changeA("Kategorie", $this->Kategorien[$entry->A("Kategorie")]);
		else $entry->changeA("Kategorie", "");

		$A = $entry->getA();
		unset($A->AdresseID);
		unset($A->anrede);
	}
}
?>

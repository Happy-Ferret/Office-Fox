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
class Adressen extends anyC implements iPluginSpecificRestrictions {
	function __construct() {
		$this->setCollectionOf("Adresse");
		$this->addAssocV3("AuftragID","=","-1");
		#$this->addOrderV3("nachname","ASC");
	}
	
	function getPluginSpecificRestrictions(){
		return array("pluginSpecificCanUse1xAdresse" => "kann NUR vorhandene und 1x-Adressen verwenden", "pluginSpecificCanUseProvision" => "kann NUR Adressen mit Provision verwenden");
	}
	
	public function getEtiketten(){
		$KID = BPS::getProperty("AdressenGUI", "etikettenKID", 0);
		
		#$Auftrag = new Auftrag($this->A("AuftragID"));
		#$Adresse = new Adresse($Auftrag->A("AdresseID"));

		$Stammdaten = Stammdaten::getActiveStammdaten();

		$AC = anyC::get("Adresse", "type", "default");
		$AC->addAssocV3("AuftragID", "=", "-1");
		$AC->addAssocV3("KategorieID", "=", $KID);
		
		$a = array();
		while($Ad = $AC->n())
			$a[] = array($Stammdaten->A("firmaLang").", ".$Stammdaten->A("strasse")." ".$Stammdaten->A("nr").", ".$Stammdaten->A("plz")." ".$Stammdaten->A("ort").(ISO3166::getCountryToCode($Stammdaten->A("land")) != $Ad->A("land") ? ", ".ISO3166::getCountryToCode($Stammdaten->A("land")) : ""), $Ad->getFormattedAddress());
		
		return $a;
	}

}
?>

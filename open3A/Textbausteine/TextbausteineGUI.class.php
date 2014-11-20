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
class TextbausteineGUI extends Textbausteine implements iGUIHTML2 {
	function getHTML($id){
		
		foreach($_SESSION["TBKategorien"] as $key => $value)
			$this->addAssocV3("KategorieID","=","$key","OR", "1");
		
		while($R = Registry::callNext("Textbausteine"))
			$this->addAssocV3("KategorieID","=",$R[0],"OR", "1");
		
		Registry::reset("Textbausteine");
		
		#$Belegarten = Auftrag::getBelegArten(null, true);
		#if(Applications::activeApplication() == "openWaWi")
		#	$Belegarten = array_merge ($Belegarten, LBestellungGUI::getBelegArten(null, true));
		
		#foreach($Belegarten AS $k => $v)
		#	$this->addAssocV3("is{$v}Standard", "=", "1", $k == 0 ? "AND" : "OR", "2");
		
		$this->setOrderV3("KategorieID","ASC");
		if($this->A == null) $this->lCV3($id);
		
		$gui = new HTMLGUIX($this);
		$gui->version("Textbausteine");
		$gui->tip();
		$gui->name("Textbaustein");
		$gui->screenHeight();
		#if($this->collector != null) $gui->setAttributes($this->collector);
		
		#$gui->setCollectionOf($this->collectionOf);
		
		$gui->parser("isRStandard","TextbausteineGUI::isRStandardParser");
		#$gui->setParser("isLStandard","TextbausteineGUI::isLStandardParser");
		#$gui->setParser("isGStandard","TextbausteineGUI::isGStandardParser");
		#$gui->setParser("isAStandard","TextbausteineGUI::isAStandardParser");
		
		$gui->attributes(array("isRStandard", "label"));
		
		$gui->colWidth("isRStandard","100px");
		#$gui->setColWidth("isLStandard","40px");
		#$gui->setColWidth("isGStandard","40px");
		#$gui->setColWidth("isAStandard","40px");
		
		/*$kat = new Kategorien();
		$kat->setAssociation("type","3");
		$kv = $kat->getArrayWithKeysAndValues();*/
		#$gui->setDisplayGroup("KategorieID",$_SESSION["TBKategorien"]);
		$gui->displayGroup("KategorieID", "TextbausteineGUI::parserDG");
		
		/*$gui->setShowAttributes(array("aktiv","firmaKurz"));
		$gui->setParser("aktiv","mStammdatenGUI::aktivParser");
		$gui->setColWidth("aktiv","20px");
		
		$gui->setParser("firma","mStammdatenGUI::firmaParser",array("\$sc->vorname","\$sc->nachname")); # only works because \$sc->vorname is eval'd while $sc is set in HTMLGUI.class.php
		*/
		
		$ST = new HTMLSideTable("left");
		$ST->addRow("R: Rechnung");
		$ST->addRow("L: Lieferschein");
		$ST->addRow("G: Gutschrift");
		$ST->addRow("A: Angebot");
		$ST->addRow("O: Bestellung");
		$ST->addRow("P: Preisanfrage");
		$ST->addRow("B: Bestätigung");
		$ST->addRow("M: Rechnung");
		$ST->addRow("Std: Standard für diese Kategorie");
		
		/*$html = "";
		if($id == -1) $html = "
		<div class=\"backgroundColor1 Tab\"><p>Erklärung</p></div>
		<table>
			<colgroup>
				<col style=\"width:30px;\" class=\"backgroundColor3\" />
				<col class=\"backgroundColor2\" />
			</colgroup>
			<tr>
				<td style=\"text-align:right;\">R:</td><td>Standard-Textbaustein für Rechnungen</td>
			</tr>
			<tr>
				<td style=\"text-align:right;\">L:</td>
				<td>Standard-Textbaustein für Lieferscheine</td>
			</tr>
			<tr>
				<td style=\"text-align:right;\">G:</td>
				<td>Standard-Textbaustein für Gutschriften</td>
			</tr>
			<tr>
				<td style=\"text-align:right;\">A:</td>
				<td>Standard-Textbaustein für Angebote</td>
			</tr>
			<tr>
				<td style=\"text-align:right;\">O:</td>
				<td>Standard-Textbaustein für Bestellungen</td>
			</tr>
			<tr>
				<td style=\"text-align:right;\">P:</td>
				<td>Standard-Textbaustein für Preisanfragen</td>
			</tr>
			<!--<tr>
				<td style=\"text-align:right;\">B:</td>
				<td>Standard-Textbaustein für Bestätigungen</td>
			</tr>
			<tr>
				<td style=\"text-align:right;\">M:</td>
				<td>Standard-Textbaustein für Mahnungen</td>
			</tr>-->
			<tr>
				<td style=\"text-align:right;\">Std:</td>
				<td>Standard-Textbaustein für diese Kategorie</td>
			</tr>
		</table>";*/
		$gui->prepend($ST);
		try {
			return $gui->getBrowserHTML($id);#.$html;
		} catch (Exception $e){ }
	}		
	
	public static function parserDG($id){
		if(isset($_SESSION["TBKategorien"][$id]))
			return $_SESSION["TBKategorien"][$id];
		
		while($R = Registry::callNext("Textbausteine"))
			if($R[0] == $id)
				return $R[1];
		
		Registry::reset("Textbausteine");
	}
	
	public function getTBVariables($KategorieID){
		while($R = Registry::callNext("Textbausteine"))
			if($R[0] == $KategorieID)
				die($this->formatVariables($R[2]));
		
		Registry::reset("Textbausteine");
		
		$TBs = isset($_SESSION["TBVariables"][$KategorieID]) ? $_SESSION["TBVariables"][$KategorieID] : array();
		
		$TBs = Aspect::joinPoint("append", $this, __METHOD__, array($TBs, $KategorieID), $TBs);
		
		die($this->formatVariables($TBs, isset($_SESSION["TBVariablesConditions"][$KategorieID]) ? $_SESSION["TBVariablesConditions"][$KategorieID] : array()));
		#
		#echo count($TBs) === 0 ? "nil" : implode(";", $TBs);
	}
	
	private function formatVariables($V, $C = array()){
		$T = "";
		foreach($V AS $k => $v){
			$T .= "<div style=\"cursor:pointer;\" onclick=\"tinyMCE.activeEditor.execCommand('mceInsertContent', false, '{".$v."}');\" id=\"TBVal$k\">{".$v."}</div>";
			if(isset($C[$v]))
				$T .= OnEvent::script("if(!".$C[$v].") \$j('#TBVal$k').hide();");
		}
	
		return $T.OnEvent::script("\$j('#tinyMCEVarsDescription').html('Sie können folgende Variablen in Ihrem Text verwenden (bitte beachen Sie Groß- und Kleinschreibung):');");
	}
	
	public static function isRStandardParser($w, $E){
		#"\$sc->isLStandard", "\$sc->isGStandard", "\$sc->isAStandard", "\$sc->isKatDefault", "\$sc->isMStandard", "\$sc->isBStandard"
		$html = $E->A("isRStandard") == 1 ? " R " : "";
		$html .= $E->A("isLStandard") == 1 ? " L " : "";
		$html .= $E->A("isGStandard") == 1 ? " G " : "";
		$html .= $E->A("isAStandard") == 1 ? " A " : "";
		$html .= $E->A("isKatDefault") == 1 ? " Std " : "";
		$html .= $E->A("isSStandard") == 1 ? " S " : "";
		$html .= $E->A("isMStandard") == 1 ? " M " : "";
		$html .= $E->A("isBStandard") == 1 ? " B " : "";
		$html .= $E->A("isOStandard") == 1 ? " O " : "";
		$html .= $E->A("isPStandard") == 1 ? " P " : "";
		
		return $html;
	}

	public static function doSomethingElse(){
		$k = new Textbausteine();
		$k->addTBKategorie("Zahlungsbedingungen", "1");
		$k->addTBKategorie("Textbaustein oben", "2");
		$k->addTBKategorie("Textbaustein unten", "3");
		#$k->addTBKategorie("E-Mail Belege Betreff", "41");
		$k->addTBKategorie("E-Mail Belege", "42");

		$k->addTBVariables("1",array("Anrede","+1Woche","+2Wochen","+3Wochen","+1Monat","+3Monate", "+#Tage", "Gesamtsumme","Rabatt:#%", "Benutzername"));
		$k->addTBVariables("2",array("Anrede","+1Woche","+2Wochen","+3Wochen","+1Monat","+3Monate", "Benutzername"));
		$k->addTBVariables("3",array("Anrede","+1Woche","+2Wochen","+3Wochen","+1Monat","+3Monate", "Gesamtsumme","Rabatt:#%","Benutzername"));
		
		#$k->addTBVariables("41",array("Firmenname","Belegnummer","Belegdatum"));
		$k->addTBVariables("42",array("Anrede","Firmenname","Benutzername","Belegnummer","Belegdatum", "Rechnungsnummer", "Gesamtsumme", "+1Woche","+2Wochen","+3Wochen","+1Monat", "+#Tage"));
		$k->addTBVariablesCondition("42", "Rechnungsnummer", "\$j('input[name=isMStandard]').prop('checked')");
	}

}
?>

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
class KundenpreiseGUI extends Kundenpreise implements iGUIHTML2 {
	private $kundennummer;
	
	public function getHTML($id){
		$this->setFieldsV3(array("name","kundenPreis","isBrutto", "artikelnummer"));

		$gui = new HTMLGUIX($this);
		$gui->name("Kundenpreise");
		$gui->screenHeight();
		
		$_SESSION["BPS"]->registerClass("mArtikelGUI");
		
		$gui->options(true, false, false, false);
		$gui->displayMode("BrowserLeft");

		$gui->colWidth("kundenPreis","70px");
		
		$gui->colStyle("kundenPreis","text-align:right;");
		$gui->colStyle("artikelnummer","text-align:right;");
		
		
		$gui->attributes(array("name", "artikelnummer","kundenPreis"));
		
		$gui->parser("name","KundenpreiseGUI::nameParser");
		$gui->parser("kundenPreis","KundenpreiseGUI::preisParser");
		
			
		$B = $gui->addSideButton("Kundenpreis\nhinzufügen", "package");
		$B->select(true, "mArtikel", "Kundenpreis", "-2", "makeKundenpreis");

		
		$Adresse = new Adresse(BPS::getProperty("KundeGUI", "AdresseID", -1));
		$gui->prepend("<div class=\"browserContainerSubHeight\"><p>{$Adresse->getHTMLFormattedAddress()}</p></div>");
		
		
		$FK = "";
		if(Session::isPluginLoaded("mFahrtkosten")){
			$FKG = new mFahrtkostenGUI();
			$FK = $FKG->getUserpriceForm($this->kundennummer);
			
			$gui->prepend("<div class=\"browserContainerSubHeight\">".$FK."</div>");
		}
		
		
		return $gui->getBrowserHTML($id);

	}	
	
	public function setKundennummer($kundennummer){
		$this->kundennummer = $kundennummer;
	}
	
	public static function preisParser($w, $E){
		$I = new HTMLInput("kundenPreis", "text", $w);
		$I->style("text-align:right;");
		$I->activateMultiEdit("Kundenpreis", $E->getID());
		
		return $I;
	}
	
	public static function nameParser($w, $E){
		return $w.($E->A("isBrutto") == "1" ? "<br /><small style=\"color:grey;\">Brutto-Artikel</small>" : "");
	}
	/*
	public static function addPostenButton($w,$t,$p){
		$s = HTMLGUI::getArrayFromParametersString($p);
		return "
		<input type=\"button\" value=\"Artikel\nhinzufügen\" class=\"bigButton backgroundColor2\" style=\"background-image:url(./images/navi/package.png);\" onclick=\"contentManager.backupFrame('contentRight','selectionOverlay');contentManager.loadFrame('contentRight','mArtikel', -1, 0,'mArtikelGUI;selectionMode:multiSelection,Kundenpreis,-2,makeKundenpreis,Adressen,contentLeft,Kunde,-2');\" />";
	}*/
}
?>

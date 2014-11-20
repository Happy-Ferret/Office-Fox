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
class PostenGUI extends Posten implements iGUIHTML2 {
	
	function __construct($ID){
		parent::__construct($ID);
		$this->setParser("mwst","Util::CLNumberParserZ");
	}
	
	function getHTML($id){
		#if($id != -1) $this->recalcNetto = false;
		$userLabels = mUserdata::getRelabels("Artikel");
		$userHiddenFields = mUserdata::getHides("Artikel");

		$mode = $_SESSION["BPS"]->getProperty("mPostenGUI","loadGRLBMID");
		$this->loadMeOrEmpty();

		if($this->A->isBrutto == "1")
			$this->A->preis = Util::CLNumberParserZ($this->A->bruttopreis,"load");
	
		$message = "";
		$gui = new HTMLGUIX($this);
		if($id == -1) {
			$this->A = $this->newAttributes();
			$this->A->GRLBMID = $mode;
			$G = new GRLBM($this->A->GRLBMID);
		} else {
			$G = new GRLBM($this->A->GRLBMID);
			if($G->A("isPayed") == "1" AND ($G->A("isR") == "1" OR $G->A("isA") == "1")){
				$message = "<p class=\"highlight\">Dieser Posten kann nicht mehr bearbeitet werden, der Beleg wurde gesperrt!</p>";
				$gui->optionsEdit (false, false);
			}
		}

		#$gui->setObject($this);
		$gui->name("Posten");
		$gui->formID("1xPostenForm");
		$gui->displayMode("popupN");
		
		$gui->attributes(array(
			"name",
			"gebinde",
			"GRLBMID",
			"preis",
			"menge",
			"menge2",
			"mwst",
			"artikelnummer",
			#"rabatt",
			"EK1",
			"EK2",
			"beschreibung",
			"isBrutto",
			"createArtikel",
			"erloeskonto"));
		
		$gui->label("gebinde","Einheit");
		$gui->label("artikelnummer","Artikelnummer");
		$gui->label("mwst","MwSt");
		$gui->label("name","Name");
		$gui->label("menge","Menge");
		$gui->label("preis","Preis");
		$gui->label("beschreibung","Beschreibung");
		$gui->label("createArtikel", "Artikel?");
		$gui->label("erloeskonto", "Erlöskonto");
		
		$gui->type("beschreibung","textarea");
		#$gui->type("bemerkung","hidden");
		$gui->type("GRLBMID","hidden");
		$gui->type("menge2","hidden");
		#$gui->type("oldArtikelID","hidden");
		$gui->type("createArtikel", "checkbox");

		
		$Stammdaten = mStammdaten::getActiveStammdaten();
		try {
			$CurrentVorlage = $Stammdaten->A("ownTemplate");
			$Vorlage = new $CurrentVorlage($Stammdaten);
		} catch (ClassNotFoundException $e){
			$Vorlage = new Vorlage_de_DE_leer($Stammdaten);
		}
		
		if($this->A("PostenUsedSerials") != "[]" AND $this->A("PostenUsedSerials") != ""){
			$gui->type("menge", "readonly");
			$gui->descriptionField("menge", "Die Menge kann nicht bearbeitet werden, da für diesen Posten Seriennummern eingetragen wurden.");
		}
		#$gui->parser("preis","PostenGUI::preisInputParser");
		
		$BC = new Button("Brutto- in Nettopreis umrechnen", "./images/i2/calc.png", "icon");
		$BC->onclick("rme('Artikel','-1','calcBruttoPreis', Array(\$j('#1xPostenForm input[name=preis]').val(), \$j('#1xPostenForm input[name=mwst]').val()),'\$j(\'#1xPostenForm input[name=preis]\').val(transport.responseText);');");
		
		$gui->addFieldButton("preis", $BC);
		
		$gui->space("menge");
		$gui->space("beschreibung");
		

		$gui->descriptionField("createArtikel","Soll dieser Posten als Artikel angelegt werden?");
		

		if($this->A("oldArtikelID") != "0" AND $this->A("oldArtikelID") != "")
			$gui->type("createArtikel", "hidden");
		else
			$gui->space("createArtikel");
		

		if(Session::isPluginLoaded("mBrutto")){
			$gui->type("isBrutto","checkbox");
			$gui->label("isBrutto","Bruttopreis?");
			$gui->descriptionField("isBrutto","Ist der angegebene Preis ein Bruttopreis?");
			$gui->label("preis","Preis");
		} else 
			$gui->type("isBrutto", "hidden");

		
		
		if(Session::isPluginLoaded("mexportDatev") AND mUserdata::getGlobalSettingValue("DVKappendixKundenKonto", "0"))
			$gui->space("sachkonto");
		else
			$gui->type("sachkonto", "hidden");
		
		
		if(!Session::isPluginLoaded("Provisionen"))
			$gui->type("EK2", "hidden");
		
		#$gui->removeAttribute("bruttopreis");
		#$gui->removeAttribute("PostenIsAlternative");
		#$gui->removeAttribute("PostenSortOrder");
		#$gui->removeAttribute("rabatt");
		#$gui->removeAttribute("PostenAddLine");
		#$gui->removeAttribute("PostenNewPage");
		#$gui->removeAttribute("menge2");
		
		foreach($userHiddenFields as $key => $value)
			$gui->type($key, "hidden");
		
		foreach($userLabels as $key => $value)
			$gui->label($key,$value);
			
		#$kat = new Kategorien();
		#$kat->addAssocV3("type","=","mwst");
		#$mwst = array("0" => "bitte auswählen");
		#$values = $kat->getArrayWithValues();
		#for($i=0;$i<count($values);$i++){
			#$keys[] = Util::parseFloat("de_DE", str_replace("%","",$values[$i]));
			#$values[$i] = Util::CLNumberParserZ(Util::parseFloat("de_DE",str_replace("%","",$values[$i])),"load")."%";
		#	$mwst[Util::parseFloat("de_DE",str_replace("%","",$values[$i])).""] = Util::CLNumberParserZ(Util::parseFloat("de_DE",str_replace("%","",$values[$i])),"load")."%";
		#}
		
		#$keys[] = "0";
		#$values[] = "bitte auswählen";
		
		#$gui->setOptions("mwst",$keys,$values);
		#$gui->type("mwst", "select", $mwst);
		
		$gui->addToEvent("onSave", "var scrolled = \$j('#PostenSortableContainer').scrollTop(); contentManager.loadFrame('subframe','GRLBM','".$this->A("GRLBMID")."', '', '', function(){ \$j('#PostenSortableContainer').scrollTop(scrolled); });");
		
		/*$gui->setJSEvent("onSave","function() { 
					Popup.close('Posten', 'edit');
					contentManager.loadFrame('subframe','GRLBM','".$this->A("GRLBMID")."');"."
				}");*/

		
		$gui->customize($this->customizer);

		/*if(in_array($G->getMyPrefix(), $Vorlage->sumHideOn)){
			$gui->type("preis", "hidden");
			$gui->type("mwst", "hidden");
			$gui->type("EK1", "hidden");
			$gui->type("EK2", "hidden");
			$gui->type("isBrutto", "hidden");
			$gui->type("rabatt", "hidden");
		} else*/
			$gui->space("EK1");
		
		#$gui->setStandardSaveButton($this);
		
		return $message.$gui->getEditHTML();
	}
	
	/*public static function preisInputParser($w, $l, $p){
		return "<img src=\"./images/i2/calc.png\" onclick=\"rme('Artikel','-1','calcBruttoPreis',Array(\$j('#1xPostenForm input[name=preis]').val(), \$j('#1xPostenForm select[name=mwst]').val()),'\$j(\'#1xPostenForm input[name=preis]\').val(transport.responseText);');\" style=\"float:right;\" class=\"mouseoverFade\" title=\"Brutto- in Nettopreis umrechnen\" />
			<input type=\"text\" value=\"$w\" id=\"preis\" style=\"width: 85%;\" name=\"preis\" onblur=\"blurMe(this);\" onfocus=\"focusMe(this);\"/>";
	}*/
	
	public function saveMultiEditField($field,$value){
		parent::saveMultiEditField($field,$value);
		#print_r($this->getA());
		Red::messageD("Änderung gespeichert");
	}
}
?>
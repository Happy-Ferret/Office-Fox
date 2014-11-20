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

class Bericht_KundenpreislisteGUI extends Bericht_default implements iBerichtDescriptor {
	
 	function __construct() {
 		parent::__construct();
 		
 		if(!$_SESSION["S"]->checkForPlugin("mArtikel")) return;
 		
		$Artikel = new anyC();
		$Artikel->setCollectionOf("Artikel");
		
 		#$Artikel = new mArtikel();
 		$Artikel->addJoinV3("Kategorie","KategorieID","=","KategorieID");
 		$Artikel->addJoinV3("Userdata"," CONCAT('Bericht_KundenpreislisteGUIKategorieID',t1.KategorieID)","=","name");
 		$Artikel->setFieldsV3(array("t2.name AS katName","t1.name","preis", "mwst", "aufschlagGesamt", "aufschlagListenpreis", "LohngruppeID", "Lohnminuten"));
 		$Artikel->addAssocV3("wert",">","0");
 		$Artikel->addAssocV3("hideInReport","=","0");
 		$Artikel->addAssocV3("UserID","=",Session::currentUser()->getID());
 		$Artikel->addOrderV3("ABS(wert)");
 		$Artikel->addOrderV3("name");
 		
 		#foreach($this->userdata AS $key => $value)
 		#	if($value == "1")
 		#		$Artikel->addAssocV3("t1.KategorieID","=",str_replace(get_class($this)."KategorieID","",$key),"OR");
 			
 		$this->collection = $Artikel;
 		
 	}
 	
 	public function getLabel(){
 		if($_SESSION["S"]->checkForPlugin("mArtikel")) return "Kundenpreisliste";
 		else return null;
 	}
 	
	public function loadMe(){
		parent::loadMe();
		$table = $this->getClearClass();
		$Ks = new anyC();
		$Ks->setCollectionOf("Kategorie");
		$Ks->setFieldsV3(array("t1.name"));
		$Ks->addAssocV3("type","=","2");
		
		$n = $table."GUIKategorieID0";
		$this->A->$n = -1;
		
		while($t = $Ks->getNextEntry()){
			$n = $table."GUIKategorieID".$t->getID();
			if(!isset($this->A->$n)) $this->A->$n = -1;
		}
	}
 	
 	public function getHTML($id){
 		$this->loadMe();
 		
 		$phtml = parent::getHTML($id);
 		
		$Ks = new anyC();
		$Ks->setCollectionOf("Kategorie");
		
 		#$Ks = new Kategorien();
 		$Ks->setFieldsV3(array("t1.name"));
 		$Ks->addJoinV3("Userdata"," CONCAT('".get_class($this)."KategorieID',t1.KategorieID)","=","name");
 		$Ks->addJoinV3("Userdata","UserID","=",Session::currentUser()->getID());
 		$Ks->addAssocV3("type","=","2");
 		#$Ks->addAssocV3("UserID","=",$_SESSION["S"]->getCurrentUser()->getID());
 		$Ks->addOrderV3("ABS(wert)");
 		 		
		$L = new HTMLList();
		$L->addListStyle("list-style-type: none;");
		$L->setListID("sortMe");
		
 		$h = "
 				<tr>
 					<td colspan=\"2\">Sie können die Einträge verschieben, indem Sie auf <img src=\"./images/i2/topdown.png\" /> klicken und ziehen.";
		
		$L->addItem("<img class=\"handle\" src=\"./images/i2/topdown.png\" style=\"cursor:move;float:left;margin-right:5px;\" />
				<img
					id=\"image".get_class($this)."KategorieID0\"
					class=\"mouseoverFade\"
					style=\"float:left;margin-right:5px;\"
					src=\"./images/i2/".((isset($this->userdata[get_class($this)."KategorieID0"]) AND $this->userdata[get_class($this)."KategorieID0"] >= 1) ? "" : "not")."ok.gif\" 
					onclick=\"checkVirtualBox(this, '".get_class($this)."KategorieID0');\"
				/>
				<input
					type=\"hidden\"
					name=\"".get_class($this)."KategorieID0"."\" 
					id=\"".get_class($this)."KategorieID0\"
					value=\"".(isset($this->userdata[get_class($this)."KategorieID0"]) ? $this->userdata[get_class($this)."KategorieID0"] : 0)."\"
				/>
				Ohne Kategorie");
 		while(($t = $Ks->getNextEntry())){
			$L->addItem("<img class=\"handle\" src=\"./images/i2/topdown.png\" style=\"cursor:move;float:left;margin-right:5px;\" />
				<img
					id=\"image".get_class($this)."KategorieID".$t->getID()."\"
					class=\"mouseoverFade\"
					style=\"float:left;margin-right:5px;\"
					src=\"./images/i2/".((isset($this->userdata[get_class($this)."KategorieID".$t->getID()]) AND $this->userdata[get_class($this)."KategorieID".$t->getID()] >= 1) ? "" : "not")."ok.gif\" 
					onclick=\"checkVirtualBox(this, '".get_class($this)."KategorieID".$t->getID()."');\"
				/>
				<input
					type=\"hidden\"
					name=\"".get_class($this)."KategorieID".$t->getID()."\" 
					id=\"".get_class($this)."KategorieID".$t->getID()."\"
					value=\"".(isset($this->userdata[get_class($this)."KategorieID".$t->getID()]) ? $this->userdata[get_class($this)."KategorieID".$t->getID()]  : 0)."\"
				/>
				".$t->A("name"));
 			
 		}
 		$h .= "$L
 					</td>
 				</tr>";
 		
 		$phtml .= "
 		<form id=\"Bericht\">
			<div class=\"backgroundColor1 Tab\"><p>Artikel-Kategorien:</p></div>
	 		<table>
	 			<colgroup>
	 				<col class=\"backgroundColor3\" />
	 			</colgroup>$h
	 			<tr>
	 				<td>
	 					<input type=\"button\" style=\"float:right;width:150px;\" onclick=\"markAllBerichtK();\" value=\"alle markieren\" />
	 					<input type=\"button\" style=\"width:150px;\" onclick=\"unmarkAllBerichtK();\" value=\"keine markieren\" />
	 				</td>
	 			</tr>
	 			<tr>
	 				<td><input type=\"button\" style=\"background-image: url(./images/i2/save.gif);\" value=\"Einstellungen speichern\" onclick=\"saveBericht('".get_class($this)."');\" /></td>
	 			</tr>
	 		</table>
 		</form>
		<script type=\"text/javascript\">
			// <![CDATA[
			Sortable.create(\"sortMe\", {dropOnEmpty:true,handle:'handle',constraint:'vertical'});
			// ]]>
		</script>";
 		
 		return $phtml;
 	}

 	public function getPDF($save = false){
 		$userLabels = mUserdata::getRelabels("Artikel");
		$userHiddenFields = mUserdata::getHides("Artikel");

		foreach($userLabels AS $key => $value)
 			$this->setLabel($key, $value);
 		
 		$nameWidth = 120;
 		
 		$this->fieldsToShow = array("name");

 		
 		$this->fieldsToShow[] = "preis";

 		$this->groupBy = "katName";

 		$this->setHeader("Preisliste vom ".date("d.m.Y"));


 		$this->setAlignment("preis","R");
		$this->setDefaultFont("Arial","",7);
		$this->setDefaultCellHeight(3);
 		$this->setColWidth("name",$nameWidth);
 		$this->setColWidth("preis",0);
 		
 		$this->setFieldParser("preis","Bericht_KundenpreislisteGUI::parserPreis");
 		return parent::getPDF($save);
 	}
 	
 	public static function parserPreis($w, $p, $A, $E){
		$E->resetParsers();
 		return Util::conv_euro(Util::CLFormatCurrency($E->getGesamtBruttoVK(true) * 1, true));#number_format(str_replace(",",".",$w),2,",",".").chr(128);
 	}
	
 	#public static function preisParser($w){
 	#	return number_format(str_replace(",",".",$w),2,",",".").chr(128);
 	#}
} 
?>
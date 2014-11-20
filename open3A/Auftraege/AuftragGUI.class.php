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
class AuftragGUI extends Auftrag implements iGUIHTML2 {
	public $newDisplayedFields = array();
	protected $showRowKundennummer = true;
	protected $showRowLieferantennummer = false;
	protected $showRowUStIdNr = true;
	protected $showButtonEditAdresse = true;
	protected $showButtonsBeleg = true;
	
	public function newDisplayedFields(array $fields){
		$this->newDisplayedFields = array_merge($this->newDisplayedFields, $fields);
	}

	function getHTML($id){
		$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
		$bps = $this->getMyBPSData();

		$gui = new HTMLGUI();
		$gui->setObject($this);
		
		if($this->A ==  null AND $id != -1) $this->loadMe();
		
		if($id == -1)
			$id = $this->newWithDefaultValues();
		
		$this->checkUserRestrictions($pSpecData);

		$_SESSION["BPS"]->registerClass("mGRLBMGUI");
		$_SESSION["BPS"]->setACProperty("AuftragID", $this->ID);
		
		$d = null;
		#$BelegeTab = new HTMLSideTable("right");
		$L = new HTMLList();
		$L->addListStyle("list-style-type:none;padding-top:0px;margin-left:10px;");
		$i = 0;
		foreach($this->getBelegArten() AS $B){
			if(isset($pSpecData["pluginSpecificCanOnlySeeKalk"]) AND $B != "Kalk") continue;

			$_SESSION["BPS"]->setACProperty("type", $B);
			$tests = new mGRLBMGUI();
			$tests->addAssocV3("AuftragID", "=", $this->ID);

			$tests->lCV3();
			$test = $tests->getNextEntry();

			if($this->showButtonsBeleg){
				if($tests->numLoaded() <= 1)
					$label = Stammdaten::getLongType($B);
				else
					$label = Stammdaten::getPluralType($B);
				
				$Bu = new Button($label, Stammdaten::getIconType($B));
				$Bu->onclick("Auftrag.createGRLBM('$this->ID','Auftrag','$B');");
				$Bu->name($test == null ? "1" : "2");
				$Bu->id($B."Button");
						
				if($test == null)
					$Bu->className("backgroundColor0");

				$L->addItem($Bu);
				$L->addItemStyle("display:inline-block;margin-top:10px;margin-left:0px;margin-right:15px;");#.($i > 0 ? "margin-left:15px;" : "margin-left:10px;"));
			}
			
			if($test != null AND ($B != "Kalk" OR $d == null))
				$d = $tests;

			$i++;
		}


		if($bps != -1 AND isset($bps["GRLBMID"])){ //Load specific GRLBM
			$d = new GRLBMGUI($bps["GRLBMID"]);
			BPS::unsetProperty("AuftragGUI", "GRLBMID");
		}
		

		$html = "";

		$ADTab = new HTMLTable(2);#
		$ADTab->setTableStyle("width:100%;border:0px;");
		$ADTab->setColWidth(1, 120);
		$ADTab->weight("light");
		
		$ADTab2 = new HTMLTable(2);#$gui->getOperationsHTML(get_parent_class($this),$this->ID)
		$ADTab2->setTableStyle("width:100%;border:0px;");
		$ADTab2->setColWidth(1, 120);
		$ADTab2->weight("light");

		$anyC = anyC::get("Auftrag", "AdresseID", $this->A("AdresseID"));
		$AdresseUsed = $anyC->getTotalNum();
		
		$confirmMessage = "if(confirm('Achtung: Beim Bearbeiten der Adresse über diesen Knopf geht die Verbindung zur Kundennummer und die USt-IdNr verloren! Die Adressänderung wirkt sich auf alle Belege in diesem Auftrag aus. Fortfahren?'))";
		$confirmChange = Aspect::joinPoint("changeAdresseConfirm", $this, __METHOD__, array($confirmMessage), $confirmMessage);
		
		$ButtonEditAdresse = new Button("Adresse bearbeiten","./images/i2/edit.png", "icon");
		$ButtonEditAdresse->style("float:right;");
		if($AdresseUsed == 1) $ButtonEditAdresse->onclick("$confirmChange contentManager.loadFrame('contentRight','Adresse',{$this->A->AdresseID},0,'AdresseGUI;displayMode:auftragsAdresse;AuftragID:$this->ID')");
		else $ButtonEditAdresse->onclick("alert('Diese Adresse kann nicht bearbeitet werden, da sie in ".($AdresseUsed - 1)." anderen ".($AdresseUsed == 2 ? "Auftrag" : "Aufträgen")." verwendet wird.');");

		if(!$this->showButtonEditAdresse)
			$ButtonEditAdresse = "";
		
		$Adresse = new Adresse($this->A("AdresseID"));

		$AspectAdresse = Aspect::joinPoint("adresse", $this, __METHOD__, $Adresse);
		if($AspectAdresse != null) $Adresse = $AspectAdresse;

		$BKlickTel = "";
		if(Session::isPluginLoaded("mklickTel")){
			$BKlickTel = klickTel::getButtonSmall($this->A("AdresseID"));
			$BKlickTel->style("float:right;margin-right:10px;");
		}
		
		$ADTab->addRow(
			($this->A("AdresseID") != "0" ? "<div style=\"float:right;\">".$this->alterAdresseButtons()."<br />".$ButtonEditAdresse."</div>$BKlickTel".($this->A("AdresseID") != "0" ? $Adresse : "") : "<div style=\"float:right;\">".$this->alterAdresseButtons()."</div>").
			($this->showRowKundennummer ? "<div style=\"clear:both;\"><span style=\"color:grey;\">Kundennummer: ". ($this->A->kundennummer > 0 ? AdressenGUI::getContactButton($this->A("kundennummer")).$this->A("kundennummer") : "keine")."</span></div>" : ""));
		$ADTab->addRowColspan(1, 2);
		$ADTab->addCellStyle(1, "padding-left:0px;");
		
		if($this->showRowLieferantennummer)
			$ADTab2->addLV("LieferantenNr.:", $this->A->lieferantennummer != -2 ? $this->A("lieferantennummer") : "keine");
		
		if($this->showRowUStIdNr)
			$ADTab2->addLV("USt-IdNr/St.Nr.:", "<input type=\"text\" value=\"".$this->A->UStIdNr."\" ".($this->A->kundennummer == -2 ? " class=\"multiEditInput2\" onfocus=\"oldValue = this.value;\" onblur=\"if(oldValue != this.value) saveMultiEditInput('Auftrag','".$this->getID()."','UStIdNr');\" id=\"UStIdNrID".$this->getID()."\" type=\"text\" onkeydown=\"if(event.keyCode == 13) saveMultiEditInput('Auftrag','".$this->getID()."','UStIdNr');\" style=\"width:95%;text-align:left;\"" : "readonly=\"readonly\"")." />");

		if(Session::isPluginLoaded("mProjekt") AND $this->A("kundennummer") > 0){
			#$ADTab->addRow(array());
			#$ADTab->addRowClass("backgroundColor0");
			
			$O = array("kein Projekt");
			$AC = anyC::get("Projekt", "ProjektKunde", Kappendix::getAdresseIDToKundennummer($this->A("kundennummer")));
			while($P = $AC->getNextEntry())
				$O[$P->getID()] = $P->A("ProjektName");
			
			$PI = new HTMLInput("ProjektID", "select", $this->A("ProjektID"), $O);
			$PI->activateMultiEdit("Auftrag", $this->getID());
			
			$BNew = new Button("Projekt erstellen", "./images/i2/new.png", "icon");
			$BNew->style("float:right;margin-top:4px;");
			#$BNew->rmePCR("Bestellung", "-1", "createFromAuftrag", $this->getID(), "function(transport){ contentManager.loadPlugin('contentRight', 'mBestellung', '', transport.responseText); }");
			#$BNew->editInPopup("Projekt", -1, "Projekt erstellen", "ProjektGUI;edit:true");
			$BNew->popup("", "Projekt erstellen", "Projekt", "-1", "newForAuftragPopup", array($this->getID()));
				
			$ADTab2->addLV("Projekt:", $BNew.$PI);
		}
		
		if(Session::isPluginLoaded("mBestellung")){
			if(!Session::isPluginLoaded("mProjekt")){
				$ADTab2->addRow(array());
				$ADTab2->addRowClass("backgroundColor0");
			}
			
			$Order = anyC::getFirst("Bestellung", "BestellungAuftragID", $this->getID());
			if($Order != null){
				$BOrder = new Button("Bestellung anzeigen", "./ubiquitous/Bestellungen/Bestellung18.png", "icon");
				$BOrder->style("float:right;");
				$BOrder->loadPlugin("contentRight", "mBestellung", "", $Order->getID());
				
				$O = $BOrder."Bestellnummer ".($Order->getNummer());
			} else {
				$BOrder = new Button("Bestellung erstellen", "./ubiquitous/Bestellungen/Bestellung18.png", "icon");
				$BOrder->style("float:right;");
				$BOrder->rmePCR("Bestellung", "-1", "createFromAuftrag", $this->getID(), "function(transport){ contentManager.loadPlugin('contentRight', 'mBestellung', '', transport.responseText); }");
				
				$O = $BOrder."keine Bestellung zugeordnet";
			}
			
			$ADTab2->addLV("Bestellung:", $O);
		}
		
		$gui->customize($this->customizer);
		
		foreach($this->newDisplayedFields AS $label => $value)
			$ADTab2->addLV($label, $value);

		$html .= "
	<div style=\"min-height:50px;\" id=\"subframe\">".($d != null ? $d->getHTML(-1,0) : "")."</div>";

		$BOP = $gui->getOperationsHTML(get_parent_class($this),$this->ID);
		$BOP->type("iconicG");
		$BOP->style("margin-top:13px;margin-left:5px;");
		
		return "<div class=\"prettyTitle\">Auftrag".$BOP."</div>
			<div class=\"AuftragBelegContent\">
				<div style=\"width:270px;display:inline-block;vertical-align:top;\">
					<div style=\"padding-left:10px;padding-right:10px;\">".$ADTab."</div>
				</div>
				<div style=\"width:-webkit-calc(100% - 274px); width:calc(100% - 274px);display:inline-block;vertical-align:top;min-width:390px;\">
					<div style=\"padding-left:10px;padding-right:10px;border-left:1px solid #DDD;\">".$ADTab2."</div>
				</div>
			</div>
			".($this->showButtonsBeleg ? "<div style=\"padding-right:10px;\" class=\"AuftragBelegContent backgroundColor4\"><div class=\"prettySubtitle\" style=\"margin-top:40px;\">Belege des Auftrags</div><div class=\"AuftragBelegContent backgroundColor3\" id=\"AuftragBelegeOperations\" style=\"padding-right:10px;\">".$L."</div></div>" : "").$html.OnEvent::script("Auftrag.reWidth();");
	}
	
	public function alterAdresseButtons(){
		$BAdresse = new Button("Adresse\n".($this->A->AdresseID != 0 ? "ändern" : "hinzufügen"), "./open3A/Auftraege/lieferAdresse.png", "icon");
		$BAdresse->loadFrame("contentRight", "Adressen", -1, 0, "AdressenGUI;selectionMode:singleSelection,Auftrag,$this->ID,getAdresseCopy,Auftraege,contentLeft,Auftrag,$this->ID");

		#$B1xAdresse = new Button("1x-Adresse\nerstellen", "1xAddress");
		#$B1xAdresse->style("float:right;");
		#$B1xAdresse->loadFrame('contentRight', 'Adresse', -1, 0, "AdresseGUI;AuftragID:$this->ID;displayMode:auftragsAdresse");
		#$B1xAdresse->id("Button1xAdresse");
		
		return $BAdresse;
	}
/*
	public static function renrParser($w,$l,$p){
		$s = HTMLGUI::getArrayFromParametersString($p);
		if($w != "") return "<input type=\"text\" value=\"$w\" id=\"rechnungNummer\" name=\"rechnungNummer\" readonly=\"readonly\" />";
		else return "<input type=\"button\" value=\"Rechnung erstellen\" onclick=\"createRechnung('$s[0]','".$s[1]."');\" />";
	}*/

	public function getAdresseCopy($AdresseID){
		parent::getAdresseCopy($AdresseID);
		echo "Adresse übernommen";
	}

	public function createGRLBM($type, $returnID = false, $belegNummer = false, $referenz = "", $datum = null){
		echo parent::createGRLBM($type, $returnID, $belegNummer, $referenz, $datum);
	}

	function getGRLBMPDF($copy, $pdf = null, $GRLBMID = null, $printed = false){
		if($GRLBMID == null)
			throw new Exception("No GRLBM ID given in ".__FILE__." in method ".__METHOD__);

		if($pdf == "") $pdf = "";

		if($printed){
			$G = new GRLBM($GRLBMID, false);
			$G->changeA("isPrinted".($copy == "true" ? "Copy" : ""), "1");
			$G->changeA("isPrinted".($copy == "true" ? "Copy" : "")."Time", time());
			$G->saveMe();
		}
		
		$brief = $this->getLetter("", $copy == "true" ? true : false, $GRLBMID);
		if(Util::usePDFViewer() AND $pdf == null){
			$filename = $brief->generate(true, $pdf);
			Util::PDFViewer($filename);
		} else $brief->generate(false, $pdf);
	}

	function getGRLBMPDFPreview($copy, $pdf = null, $GRLBMID = null){
		if($GRLBMID == null)
			throw new Exception("No GRLBM ID given in ".__FILE__." in method ".__METHOD__);

		if($pdf == "") $pdf = "";

		$brief = $this->getLetter("", $copy == "true" ? true : false, $GRLBMID);
		$brief->generate(false, $pdf);
	}

	public static function getEMailTBs(Adresse $A, Stammdaten $Stammdaten, GRLBM $GRLBM, $die = true){
		$alteredA = Aspect::joinPoint("alterAdresse", __CLASS__, __METHOD__, array($A, $GRLBM), $A);
		#if($alteredA != null) $A = $alteredA;
		
		//removed in 1.6 but still required for automatic recovery [06.11.2011]
		$T = new Textbausteine();
		list($k, $v) = $T->getTBs("emailBetreff", $GRLBM->getMyPrefix(), true);
		$betreff = isset($k[0]) ? $k[0] : null;
		
		
		$T = new Textbausteine();
		list($k, $v) = $T->getTBs("emailText", $GRLBM->getMyPrefix(), true);
		$text = isset($k[0]) ? $k[0] : null;

		
		if($text == null) {
			if($die) die("error:AuftraegeMessages.E015");
			else throw new Exception("E-Mail: Textbaustein not found!");
		}
		
	   /* $T = new Textbaustein($betreff);
	    #$T->loadMe();
	    $Subject = $T->A("text");
	    $Subject = str_replace("{Firmenname}", $Stammdaten->getA()->firmaLang, $Subject);
	    if($GRLBM->getMyPrefix() != "M")
			$Subject = str_replace(array("{Rechnungsnummer}","{Belegnummer}"), $GRLBM->A("prefix").$GRLBM->A("nummer"), $Subject);
		else {
			$GRLBMOrig = new GRLBM($GRLBM->A("AuftragID"));
			$Subject = str_replace(array("{Rechnungsnummer}","{Belegnummer}"), $GRLBM->A("prefix").$GRLBMOrig->A("nummer")."/".$GRLBM->A("nummer"), $Subject);
		}
	    $Subject = str_replace(array("{Rechnungsdatum}","{Belegdatum}"), $GRLBM->A("datum"), $Subject);
*/
	    $replace = array(
			"{Firmenname}",
			"{Benutzername}",
			"{Anrede}",
			"{Rechnungsdatum}",
			"{Belegdatum}",
			"{Gesamtsumme}",
			"{+1Woche}",
			"{+2Wochen}",
			"{+3Wochen}",
			"{+1Monat}");
		
		$date = Util::CLDateParser($GRLBM->A("datum"), "store");
		if($date == -1) $date = $GRLBM->A("datum");
		
		$D = new Datum($date);
		$D->addMonth();
		
		
		$replaceWith = array(
			$Stammdaten->A("firmaLang"),
			Session::currentUser()->A("name"),
			Util::formatAnrede("de_DE", $A),
			$GRLBM->A("datum"),
			$GRLBM->A("datum"),
			Util::CLFormatCurrency($GRLBM->A("bruttobetrag")*1, true),
			Util::CLDateParser($date + 7 * 3600 * 24),
			Util::CLDateParser($date + 14 * 3600 * 24),
			Util::CLDateParser($date + 21 * 3600 * 24),
			Util::CLDateParser($D->time()));
		
		
	    $T = new Textbaustein($text);
	    
		if($betreff != null AND $T->A("betreff") == ""){//fix removal of TB type "E-Mail Betreff"
			$TBetreff = new Textbaustein($betreff);
			$T->changeA("betreff", $TBetreff->A("text"));
			$T->saveMe(true, false);
			
			$TBetreff->deleteMe();
		}
		
		$Subject = $T->A("betreff");
		$Body    = $T->A("text");
		
		if(preg_match_all("/{\+([0-9]+)Tage}/", $Body, $regs))
			foreach($regs[1] AS $mv)
				$Body = str_replace("{+{$mv}Tage}", Util::CLDateParser($date + $mv * 3600 * 24), $Body);
			
		
		
		$Subject = str_ireplace($replace, $replaceWith, $Subject);		
	    $Body    = str_ireplace($replace, $replaceWith, $Body);
		
		#$Body = str_replace("{Benutzername}",Session::currentUser()->A("name"), $Body);
	    #$Body = str_replace("{Anrede}", Util::formatAnrede("de_DE", $A), $Body);
	    if($GRLBM->getMyPrefix() != "M"){
			$Subject = str_ireplace(array("{Rechnungsnummer}", "{Belegnummer}"), $GRLBM->A("prefix").$GRLBM->A("nummer"), $Subject);
			$Body    = str_ireplace(array("{Rechnungsnummer}", "{Belegnummer}"), $GRLBM->A("prefix").$GRLBM->A("nummer"), $Body);
		} else {
			$GRLBMOrig = new GRLBM($GRLBM->A("AuftragID"));
			
			$Subject = str_ireplace("{Rechnungsnummer}", $GRLBMOrig->A("prefix").$GRLBMOrig->A("nummer"), $Subject);
			$Body    = str_ireplace("{Rechnungsnummer}", $GRLBMOrig->A("prefix").$GRLBMOrig->A("nummer"), $Body);
			
			$Subject = str_ireplace("{Belegnummer}", $GRLBM->A("prefix").$GRLBMOrig->A("nummer")."/".$GRLBM->A("nummer"), $Subject);
			$Body    = str_ireplace("{Belegnummer}", $GRLBM->A("prefix").$GRLBMOrig->A("nummer")."/".$GRLBM->A("nummer"), $Body);
		}
		
		#$Subject = str_replace(array("{Rechnungsdatum}","{Belegdatum}"), $GRLBM->A("datum"), $Subject);
		#$Body    = str_replace(array("{Rechnungsdatum}","{Belegdatum}"), $GRLBM->A("datum"), $Body);
	    
		
		$Subject = Aspect::joinPoint("alterSubject", null, __METHOD__, array($A, $Subject), $Subject);
		
		return array($Subject, $Body);
	}

	public static function getEMailSender($Stammdaten = null, $die = true){
		if($Stammdaten == null) $Stammdaten = mStammdaten::getActiveStammdaten();

		$ud = new mUserdata();
		$sender = $ud->getUDValue("sendBelegViaEmailSender", "firm");
		if($sender == "firm") {
			$from = $Stammdaten->getA()->email;
			$fromName = $Stammdaten->getA()->firmaLang;
		}
		if($sender == "user") {
			$from = $_SESSION["S"]->getCurrentUser()->getA()->UserEmail;
			$fromName = $_SESSION["S"]->getCurrentUser()->getA()->name;
		}

		$fromName = Aspect::joinPoint("senderName", __CLASS__, __METHOD__, array($fromName), $fromName);
		$from = Aspect::joinPoint("senderAddress", __CLASS__, __METHOD__, array($from), $from);
		
		if($Stammdaten->getA()->email == "" AND $sender == "firm"){
			if($die) die("error:AuftraegeMessages.E014");
			else throw new Exception("E-Mail: Please check the firms e-mail address in the Stammdaten");
		}
		if($_SESSION["S"]->getCurrentUser()->getA()->UserEmail == "" AND $sender == "user"){
			if($die) die("error:AuftraegeMessages.E014");
			else throw new Exception("E-Mail: Please check the users e-mail address");
		}

		return array($fromName, $from);
	}

	function getViaEMailWindow($GRLBMID, $sendVia = "E-Mail", $AnsprechpartnerID = "0"){
		$G = new GRLBM($GRLBMID);
		$AnAdresse = new Adresse($this->A("AdresseID"));


		$Recipients = array();
		$Recipients[0] = array(
			$AnAdresse->A("firma") != "" ? $AnAdresse->A("firma") : $AnAdresse->A("vorname")." ".$AnAdresse->A("nachname"),
			$AnAdresse->A("email"),
			"Firmenadresse");
		
		if(Session::isPluginLoaded("mAnsprechpartner")){
			$ASPs = Ansprechpartner::getAllAnsprechpartner($this->A("kundennummer"));
			while($ASP = $ASPs->getNextEntry())
				$Recipients[$ASP->getID()] = array(
					trim($ASP->A("AnsprechpartnerVorname")." ".$ASP->A("AnsprechpartnerNachname")),
					$ASP->A("AnsprechpartnerEmail"),
					"Ansprechpartner für ".($ASP->A("AnsprechpartnerGetsR") ? "Rechnungen" : "")." ".($ASP->A("AnsprechpartnerGetsR") ? " und " : "").($ASP->A("AnsprechpartnerGetsA") ? "Angebote" : ""));
			
			if($AnsprechpartnerID != "0"){
				$ARecipient = new Ansprechpartner($AnsprechpartnerID);

				$AnAdresse->changeA("vorname", $ARecipient->A("AnsprechpartnerVorname"));
				$AnAdresse->changeA("nachname", $ARecipient->A("AnsprechpartnerNachname"));
				$AnAdresse->changeA("anrede", $ARecipient->A("AnsprechpartnerAnrede"));
				$AnAdresse->changeA("email", $ARecipient->A("AnsprechpartnerEmail"));
			}
		}
		
		
		
		$Empfaenger = array();
		foreach($Recipients AS $k => $R)
			$Empfaenger[$k] = $R[0]." <$R[1]>";
		
		$IR = new HTMLInput("EMailRecipientSelection", "select", $AnsprechpartnerID, $Empfaenger);
		$IR->onchange("Auftrag.windowMail(".$this->getID().", '$GRLBMID', '$sendVia', this.value);");
		if(count($Empfaenger) == 1)
			$IR = $Empfaenger[0];
		
		$Stammdaten = mStammdaten::getActiveStammdaten();
		list($Subject, $Body) = AuftragGUI::getEMailTBs($AnAdresse, $Stammdaten, $G);
		list($fromName, $from) = AuftragGUI::getEMailSender($Stammdaten);
		$recipient = $Recipients[$AnsprechpartnerID][1];
		
		$tab = new HTMLTable(2);
		$tab->setColWidth(1, "120px;");
		$tab->addLV("Absender:", "$fromName &lt;$from&gt;");
		$tab->addLV("Empfänger:", $IR."<br /><small style=\"color:grey;\">$recipient<br />".$Recipients[$AnsprechpartnerID][2]."</small>");
		$ud = new mUserdata();
		if($_SESSION["S"]->getCurrentUser()->A("UserEmail") != "" AND $ud->getUDValue("BCCToUser", "false") == "true")
			$tab->addLV("BCC:",$_SESSION["S"]->getCurrentUser()->A("UserEmail"));

		$tab->addLV("Betreff:", "<input type=\"text\" id=\"EMailSubject$this->ID\" value=\"$Subject\" />");
		
		$tab->addRow(array("<textarea id=\"EMailBody$this->ID\" style=\"width:100%;height:300px;font-size:10px;\">$Body</textarea>"));
		$tab->addRowColspan(1, 2);
		$tab->addRowClass("backgroundColor0");

		$BAbort = new Button("Abbrechen","stop");
		$BAbort->onclick("Popup.close('$this->ID', 'EMailPreview');");

		$BGo = new Button("E-Mail\nsenden","okCatch");
		$BGo->style("float:right;");
		if($sendVia == "E-Mail")
			$BGo->onclick((strpos($Body, "<p") !== false ? "nicEditors.findEditor('EMailBody$this->ID').saveContent();" : "")." Auftrag.directMail('$this->ID', $GRLBMID, '$recipient', $('EMailSubject$this->ID').value, $('EMailBody$this->ID').value); Popup.close('$this->ID', 'EMailPreview');");
		if($sendVia == "sign")
			$BGo->onclick((strpos($Body, "<p") !== false ? "nicEditors.findEditor('EMailBody$this->ID').saveContent();" : "")." Auftrag.plSign('$this->ID', $GRLBMID, '$recipient', $('EMailSubject$this->ID').value, $('EMailBody$this->ID').value); Popup.close('$this->ID', 'EMailPreview');");

		$tab->addRow(array($BGo.$BAbort));
		$tab->addRowColspan(1, 2);

		echo $tab;
		
		if(strpos($Body, "<p") !== false)
			echo OnEvent::script("
				setTimeout(function(){
			new nicEditor({
				iconsPath : './libraries/nicEdit/nicEditorIconsTiny.gif',
				buttonList : ['bold','italic','underline'],
				maxHeight : 400

			}).panelInstance('EMailBody$this->ID');}, 100);");
	}
	
	static function getNextNumber($t){
		return parent::getNextNumber($t);
	}
	
	public function getMyStammdaten(){
		return parent::getMyStammdaten();
	}

	public function saveMultiEditField($field,$value){
		parent::saveMultiEditField($field,$value);
		Red::messageD("Änderung gespeichert");
	}
}
?>
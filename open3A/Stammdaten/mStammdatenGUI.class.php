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
class mStammdatenGUI extends mStammdaten implements iGUIHTML2 {
	public function getHTML($id){
		$gui = new HTMLGUI();
		$gui->VersionCheck("mStammdaten");

		#if($query != "") $this->makeSearch($query);
		if($this->A == null) $this->lCV3($id);
		
		$gui->setName("Stammdaten");
		if($this->collector != null) $gui->setAttributes($this->collector);
		
		$gui->setCollectionOf($this->collectionOf);
		$gui->setShowAttributes(array("aktiv","firmaKurz"));
		$gui->setParser("aktiv","mStammdatenGUI::aktivParser",array("\$aid"));
		$gui->setColWidth("aktiv","20px");
		
		$gui->setParser("firmaKurz","mStammdatenGUI::firmaParser",array("\$sc->vorname","\$sc->nachname","\$aid")); # only works because \$sc->vorname is eval'd while $sc is set in HTMLGUI.class.php
		
		if(Session::isPluginLoaded("Auftraege") AND $this->numLoaded() > 1){
			$AC = anyC::get("Auftrag", "AuftragStammdatenID", "0");
			$AC->setLimitV3(1);
			$AC->lCV3();

			$ST = new HTMLSideTable("left");
			if($AC->numLoaded() > 0){
				$B = $ST->addButton("Stammdaten-\nzuweisung", "./images/navi/edit.png");
				$B->popup("", "Stammdatenzuweisung", "mStammdaten", "-1", "stammdantenzuweisungPopup", "", "", "{width:800, top:20}");
			}
		}
		
		try {
			return ($id == -1 ? $ST : "").$gui->getBrowserHTML($id);
		} catch (Exception $e){ }
	}
	
	private static $start;
	private static $ende;
	public function stammdantenzuweisungPopup($page = 0){
		self::$start = mUserdata::getUDValueS("stammAuftragStart", 0);
		self::$ende = mUserdata::getUDValueS("stammAuftragEnde", 0);
		$done = mUserdata::getUDValueS("stammAuftragDone", 0);
		
		echo "<div style=\"display:inline-block;width:400px;vertical-align:top;\">";
		echo "<p>In Ihrem System befinden sich Aufträge ohne zugewiesene Stammdaten. Dies ist in der Regel kein Problem, da automatisch die aktiven Stammdaten verwendet werden.</p>";
		echo "<p>Sie benötigen diese Zuweisung, wenn sich die Stammdaten ändern und Aufträge bis zu einem bestimmten Datum weiterhin die bisherigen Stammdaten verwenden sollen.</p>";
		
		if($done > 0)
			echo "<p style=\"color:green;\">$done ".($done == 1 ? "Auftrag" : "Aufträge")." erfolgreich zugewiesen.</p>";
		
		if(self::$start == 0 AND self::$ende == 0)
			echo "<p style=\"margin-top:30px;\">Um die Zuweisung zu starten, wählen Sie bitte rechts den Auftrag, bis zu welchem Datum die Stammdaten zugewiesen werden sollen.</p>";
		
		if(self::$start != 0 AND self::$ende == 0)
			echo "<p style=\"margin-top:30px;\">Bitte wählen Sie einen zweiten Auftrag, ab welchem Datum die Stammdaten zugewiesen werden sollen.</p>";
			
		if(self::$start != 0 AND self::$ende != 0)
			echo "<p style=\"margin-top:30px;\">Bitte wählen Sie nun auf dieser Seite die zuzuweisenden Stammdaten.</p>";
		
		if(self::$start != 0){
			$F = new HTMLForm("stammdatenZuordnung", array("StammdatenID"), "Stammdatenzuweisung");
			
			$options = array();
			while($S = $this->n())
				$options[$S->getID()] = $S->A("firmaKurz")." ".($S->A("aktiv") ? "(aktiv)" : "(inaktiv)");
				
			
			$F->getTable()->setColWidth(1, 120);
			$F->setLabel("StammdatenID", "Stammdaten");
			
			$F->setType("StammdatenID", "select", 0, $options);
			$F->setSaveRMEPCR("Zuweisen", "", "mStammdaten", "-1", "stammdatenzuweisungAction", OnEvent::reloadPopup("mStammdaten"));
			$F->useRecentlyChanged();
			
			echo $F;
		}
		
		
		echo "</div>";
		
		
		$AC = anyC::get("Auftrag", "AuftragStammdatenID", "0");
		$AC->addOrderV3("auftragDatum");
		$AC->addOrderV3("t1.AuftragID");
		$AC->addJoinV3("Adresse", "AdresseID", "=", "AdresseID");
		
		$AC->loadMultiPageMode(-1, $page, 10);

		
		$GUI = new HTMLGUIX($AC, "mStammdaten");
		
		$GUI->attributes(array("auftragDatum"));
		$GUI->options(false, false, false, false, true);
		$GUI->displayMode("popup");
		
		$GUI->parser("auftragDatum", "mStammdatenGUI::parserAuftragDatum");
		
		echo "<div style=\"display:inline-block;width:400px;vertical-align:top;\">".$GUI->getBrowserHTML()."</div>";
		if(self::$start != 0)
			echo OnEvent::script("\$j('#editDetailsmStammdaten #BrowsermStammdaten".self::$start."').addClass('highlight')");
		
		if(self::$ende != 0)
			echo OnEvent::script("\$j('#editDetailsmStammdaten #BrowsermStammdaten".self::$ende."').addClass('highlight')");
	}
	
	function stammdatenzuweisungAction($StammdatenID){
		$start = mUserdata::getUDValueS("stammAuftragStart", 0);
		$ende = mUserdata::getUDValueS("stammAuftragEnde", 0);
		
		if($ende == 0){
			$Auftrag = new Auftrag($start);
			$Auftrag->changeA("AuftragStammdatenID", $StammdatenID);
			$Auftrag->saveMe();
			
			mUserdata::setUserdataS("stammAuftragStart", 0);
			mUserdata::setUserdataS("stammAuftragDone", 1);
			
			return;
		}
		
		if($ende != 0 AND $start != 0){
			$AC = anyC::get("Auftrag", "AuftragStammdatenID", "0");
			$AC->addOrderV3("auftragDatum");
			$AC->addOrderV3("t1.AuftragID");
			
			$started = false;
			$i = 0;
			while($A = $AC->n()){
				if($A->getID() == $start)
					$started = true;
				
				if($started){
					$A->changeA("AuftragStammdatenID", $StammdatenID);
					$A->saveMe();
					$i++;
				}
				
				if($A->getID() == $ende)
					break;
			}
			
			mUserdata::setUserdataS("stammAuftragStart", 0);
			mUserdata::setUserdataS("stammAuftragEnde", 0);
			mUserdata::setUserdataS("stammAuftragDone", $i);
		}
		
	}
	
	public static function parserAuftragDatum($w, $l, $E){
		$Adresse = new Adresse(-1);
		$Adresse->setA($E->getA());
		$I = new HTMLInput("AuftragID", "checkbox", (self::$start == $E->getID() OR self::$ende == $E->getID()) ? 1 : 0);
		$I->style("float:left;");
		$I->onchange(OnEvent::rme("mStammdaten", "saveDate", $E->getID(), OnEvent::reloadPopup("mStammdaten")));
		
		return "<span style=\"float:right;\">".Util::CLDateParser($w).(self::$start == $E->getID() ? "<br /><strong>START</strong>" : "").(self::$ende == $E->getID() ? "<br /><strong>ENDE</strong>" : "")."</span>$I<small style=\"display:inline-block;margin-left:5px;color:grey;\">".$Adresse->getHTMLFormattedAddress()."</small>";
	}
	
	function saveDate($AuftragID){
		$startID = mUserdata::getUDValueS("stammAuftragStart", 0);
		$endID = mUserdata::getUDValueS("stammAuftragEnde", 0);
		
		if($AuftragID == $startID){
			mUserdata::setUserdataS("stammAuftragStart", 0);
			return;
		}
		
		if($AuftragID == $endID){
			mUserdata::setUserdataS("stammAuftragEnde", 0);
			return;
		}
		
		if($startID == 0)
			mUserdata::setUserdataS("stammAuftragStart", $AuftragID);
		else
			mUserdata::setUserdataS("stammAuftragEnde", $AuftragID);
	}
	
	public static function firmaParser($w, $a, $p){
		$s = HTMLGUI::getArrayFromParametersString($p);
		return ($w != "" ? "<img style=\"float:right;\" title=\"leeren Brief anzeigen\" src=\"./images/i2/pdf.gif\" onclick=\"document.open('./interface/rme.php?bps=mGRLBMGUI;type:R&class=Stammdaten&amp;constructor=$s[2]&amp;method=getLetter&amp;parameters=','Druckansicht','height=650,width=875,left=20,top=20');\" />".$w : "<img style=\"float:right;\" title=\"leeren Brief anzeigen\" src=\"./images/i2/pdf.gif\" onclick=\"document.open('./interface/rme.php?class=Stammdaten&amp;constructor=$s[2]&amp;method=getLetter&amp;parameters=','Druckansicht','height=650,width=875,left=20,top=20');\" />".$s[0]." ".$s[1]);
	}
	
	public static function aktivParser($w, $a, $p){
		return $w == 1 ? "<img src=\"./images/i2/ok.gif\" title=\"Stammdaten aktiv\" />" : "<img src=\"./images/i2/notok.gif\" title=\"Stammdaten aktivieren\" onclick=\"rme('mStammdaten','','activate','$p','contentManager.reloadFrameRight();');\" class=\"mouseoverFade\" />";
	}
}
?>

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
class Kundenpreis extends PersistentObject {
	
	function __construct($ID){
		parent::__construct($ID);
		$this->setParser("kundenPreis","Util::CLNumberParserZ");
	}
	
	protected function saveMultiEditField($field,$value){
		if($this->A == null) $this->loadMe();
		$this->A->$field = $value;
		$this->saveMe();
	}
	
	protected function makeKundenpreis($ArtikelID){
		$bps = $this->getMyBPSData();
		$Artikel = new Artikel($ArtikelID);
		#$Artikel->setParser("preis","Util::nothingParser");
		$Artikel->setParser("EK1","Util::nothingParser");
		$Artikel->setParser("EK2","Util::nothingParser");
		
		$Ks = new anyC();
		$Ks->setCollectionOf("Kundenpreis");
		$Ks->addAssocV3("ArtikelID","=",$ArtikelID);
		$Ks->addAssocV3("kundennummer","=",$bps["kundennummer"]);
		$Ks = $Ks->getNextEntry();
		if($Ks != null) return -1;
		

		$K = new Kundenpreis(-1);
		$KA = $K->newAttributes();
		$KA->ArtikelID = $ArtikelID;
		$KA->kundennummer = $bps["kundennummer"];
		$KA->kundenPreis = $Artikel->getA()->preis;
		
		$K->setA($KA);
		$K->newMe();
		
		return $Artikel->getA()->name;
	}
}
?>

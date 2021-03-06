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
class GRLBMAdapter extends Adapter {
	function saveSingle2($forWhat, $A){
		parent::saveSingle2($forWhat, $A);
		
		if(isset($A->isPayed) AND $A->isPayed == 1) return;
		if(isset($A->isM) AND $A->isM == 1) return;
		
		if(!isset($A->AuftragID))
			return;
		
		$Auftrag = new Auftrag($A->AuftragID);
		$Auftrag->updateDatum($A->datum);
	}
}
?>
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
class PDFExport extends UnifiedTable {

	private $fontSize = 10;
	function setDefaultFontSize($size){
		$this->fontSize = $size;
	}
	
	function setCellStyles($styles){
		$this->cellStyles = $styles;
	}
	
	public function getExport($filename = null, $output = "I"){
		$pdf = new FPDF();
		
		$pdf->AddPage();
		$pdf->SetFont('Arial', '', $this->fontSize);
		
		if($this->caption){
			$pdf->SetFont('Arial', 'B', 15);
			$pdf->Cell8(0, 10, $this->caption, 0, 1);
			$pdf->SetFont('Arial', '', $this->fontSize);
		}
		
		if($this->header){
			$pdf->SetFont('Arial', 'B');
			foreach($this->header AS $col => $content)
				$pdf->Cell8(isset($this->colWidth[$col + 1]) ? $this->colWidth[$col + 1] : 20, 5, $content);
			
			$pdf->SetFont('Arial', '');
			
			$pdf->Ln();
			
		}
		
		foreach($this->content AS $line => $v){
			$skip = 0;
			foreach($v AS $col => $value){
				if($skip){
					$skip--;
					continue;
				}
				#print_r($this->rowColspan);
				#die();
				$align = "L";
				$border = "";
				$style = "";
				$fill = 0;
				$fontStyle = "";
				if(isset($this->cellStyles[$line][$col]))
					$style = $this->cellStyles[$line][$col];
				
				if(strpos($style, "border-right") !== false)
					$border .= "R";
				
				if(strpos($style, "border-top") !== false)
					$border .= "T";
				
				if(strpos($style, "border-left") !== false)
					$border .= "L";
				
				if(strpos($style, "border-bottom") !== false)
					$border .= "B";
				
				if(strpos($style, "text-align:right") !== false)
					$align = "R";
				
				if(strpos($style, "background-color") !== false){
					preg_match("/background-color:#([A-F0-9a-f]+);/ismU", $style, $matches);
					$pdf->SetFillColor(hexdec($matches[1][0].$matches[1][1]), hexdec($matches[1][2].$matches[1][3]), hexdec($matches[1][4].$matches[1][5]));
					$fill = 1;
				}
				
				if(strpos($style, "font-weight:bold") !== false){
					$fontStyle .= "B";
				}
				
				$width = isset($this->colWidth[$col + 1]) ? $this->colWidth[$col + 1] : 20;
				if(isset($this->rowColspan[$line]) AND $this->rowColspan[$line][0] == $col){
					for($i = $this->rowColspan[$line][0] + 1; $i < $this->rowColspan[$line][1] ; $i++){
						$width += isset($this->colWidth[$col + 1 + $i]) ? $this->colWidth[$col + 1 + $i] : 20;
						$skip++;
					}
				}
				
				$pdf->SetFont("Arial", $fontStyle);
				$pdf->Cell8($width, 5, $value, $border, 0, $align, $fill);
				
				if($col + 1 > $this->numCols)
					break;
			}
			
			$pdf->Ln();
		}
		
		
		$pdf->Output($filename, $output);
	}
}
?>

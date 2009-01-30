<?php require_once 'headerDB.inc.php';

// initialise user options:
$pdfparams = array( 'fpdfpath' =>'../fpdf/',
                    'papersize'=>'letter',
                    'fontname' =>'Arial',
                    'nextonly' =>true,
                    'fontsize' => 8 );
                  
foreach ($pdfparams as $key=>$default)
    $$key = (empty($addon[$key])) ? $default : $addon[$key];
// end of initialising user options

require_once "{$fpdfpath}fpdf.php";
define('FPDF_FONTPATH',"{$fpdfpath}font/");

class PDF extends FPDF
{
var $fontname;
var $fontsize;

var $angle=0;
var $projects=array();
var $records=array();
var $recCount;
var $currentRecord=0;
var $currentContext;
var $currentProject;

function initFontParams($name=null,$size=null)
{
    $this->fontname=$name;
    $this->fontsize=$size;
}

function Rotate($angle,$x=-1,$y=-1)
{
    if($x==-1)
        $x=$this->x;
    if($y==-1)
        $y=$this->y;
    if($this->angle!=0)
        $this->_out('Q');
    $this->angle=$angle;
    if($angle!=0)
    {
        $angle*=M_PI/180;
        $c=cos($angle);
        $s=sin($angle);
        $cx=$x*$this->k;
        $cy=($this->h-$y)*$this->k;
        $this->_out(
                sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm',
                        $c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
    }
}

function _endpage()
{
    if($this->angle!=0)
    {
        $this->angle=0;
        $this->_out('Q');
    }
    parent::_endpage();
}
function RotatedText($x,$y,$txt,$angle)
{
    //Text rotated around its origin
    $this->Rotate($angle,$x,$y);
    $this->Text($x,$y,$txt);
    $this->Rotate(0);
}

function RotatedImage($file,$x,$y,$w,$h,$angle)
{
    //Image rotated around its upper-left corner
    $this->Rotate($angle,$x,$y);
    $this->Image($file,$x,$y,$w,$h);
    $this->Rotate(0);
}

function PrintContext($x,$y,$name,$angle)
{
    $this->SetFont($this->fontname,'B',$this->fontsize+2);
    $this->RotatedText($x,$y,$name,$angle);
    $this->SetFont($this->fontname,'',$this->fontsize);
}

function PrintProject($x,$y,$name,$angle)
{
    $this->SetFont($this->fontname,'BU',$this->fontsize);
    $this->RotatedText($x,$y,$name,$angle);
    $this->SetFont($this->fontname,'',$this->fontsize);

}

function PrintNextAction($x,$y,$name,$angle)
{
        $this->SetFont($this->fontname,'',$this->fontsize);
        $this->RotatedText($x,$y,$name."*",$angle);
        $this->SetFont($this->fontname,'',$this->fontsize);

}

function PrintPageNumber($x,$y,$number,$angle)
{
        $this->SetFont($this->fontname,'',$this->fontsize-2);
        $this->RotatedText($x,$y,$number,$angle);
        $this->SetFont($this->fontname,'',$this->fontsize);
}

function PrintRows($y,$left)
{
        $line = 0;
        $increment=0;
        $angle=90;
        $x=0;
        $indent=0;

        if ( $left == true ) {
                $x=10.16;                // TOOFIX hard-coded constant
                $angle=270;
                $increment=-.37;         // TOOFIX hard-coded constant
                $indent=.37;             // TOOFIX hard-coded constant
        } else {
                $x=11.43;                // TOOFIX hard-coded constant
                $angle=90;
                $increment=.37;          // TOOFIX hard-coded constant
                $indent=-.37;            // TOOFIX hard-coded constant
        }

        while ( $this->currentRecord < $this->recCount
                        && $line < 23 ) {                          // TOOFIX hard-coded constant
                $current = $this->records[$this->currentRecord];
                // Prints the Context
                if ( $current['cname'] != $this->currentContext ) {
                        $this->PrintContext($x,$y,$current['cname'],$angle);
                        $this->currentContext = $current['cname'];
                        $line++;
                        $x += $increment;
                        continue;
                }
                // Prints the Project name
                if ( $current['parentId'] != $this->currentProject && $current['parentId'] != null ) {
                        $this->PrintProject($x,$y+$indent,$this->projects[$current['parentId']],$angle);
                        $this->currentProject = $current['parentId'];
                        $line++;
                        $x += $increment;
                        continue;
                }
                // Prints the action
                if ( $current['nextaction'] == null ) {
                        $this->RotatedText($x,$y+$indent+$indent,"[ ] ".$current['title'],$angle);
                } else {
                        $this->PrintNextAction($x,$y+$indent+$indent,"[ ] ".$current['title'],$angle);
                }
                $this->currentRecord++;
                $line++;
                $x += $increment;
        }
}

function PrintNotes($y,$left)
{
        $increment=0;
        $angle=90;
        $x=0;
        $endY=0;
        if ( $left == true ) {
                $x=10.16;                   // TOOFIX hard-coded constant
                $increment=-.635;           // TOOFIX hard-coded constant
                $angle=270;
                $endY=$y+5.08;              // TOOFIX hard-coded constant
        } else {
                $x=11.43;                   // TOOFIX hard-coded constant
                $angle=90;
                $increment=.635;            // TOOFIX hard-coded constant
                $endY=$y-5.08;              // TOOFIX hard-coded constant
        }
   // First we must print the title
   $this->PrintContext($x,$y,"Notes",$angle);
   $x +=$increment;

   for ( $i=0; $i < 14; $i++ ) {            // TOOFIX hard-coded constant
      $this->Line($x,$y,$x,$endY);
      $x +=$increment;
   }
}

function PrintPages($y,$y1,$pagenum,$rotate180,$forceNotes=false)
{
    if ($this->currentRecord < $this->recCount && !$forceNotes) {
        $this->PrintRows($y,$rotate180);
    } else {
        $this->PrintNotes($y,$rotate180);
    }
    // Add page number
    $this->PrintPageNumber($y1,$y,$pagenum,($rotate180) ? 270 : 90);
}

function PrintBooklet()
{
    $margin0 =  0.635;                 // TOOFIX hard-coded constant
    $margin1 =  6.985;                 // TOOFIX hard-coded constant
    $margin2 = 2 * $margin1;
    $margin3 = 3 * $margin1;
    $margin4 = 4 * $margin1 - $margin0;
    $this->PrintPages($margin1 + $margin0, $margin0, 1, true);

    // Add timestamp to page 1
    $this->SetFont($this->fontname,'I',$this->fontsize-2);
    $this->RotatedText($margin0, 8 ,"as of ".date("r"),270);   // TOOFIX hard-coded constant
    $this->SetFont($this->fontname,'',$this->fontsize);

    $this->PrintPages($margin2 + $margin0, $margin0, 2, true);
    $this->PrintPages($margin3 + $margin0, $margin0, 3, true);
    $this->PrintPages($margin4 - $margin0, $margin3, 4, false);
    $this->PrintPages($margin3 - $margin0, $margin3, 5, false);
    $this->PrintPages($margin2 - $margin0, $margin3, 6, false);
    $this->PrintPages($margin1 - $margin0, $margin3, 7, false);
    $this->PrintPages($margin0 + $margin0, $margin0, 8, true, true);
}

function GetData()
{
   // Get Projects and next actions
   $values=array();
   $values['parentfilterquery'] =
                  sqlparts('pendingitems', array())
        .' AND ' .sqlparts('issomeday', array('isSomeday'=>'n'))
        .' AND ' .sqlparts('activeitems', array());
        
    $values['filterquery'] =$values['parentfilterquery']
        .' AND ' .sqlparts('typefilter', array('type'=>'a'))
    // TODO - check options to see if we want next actions only
        .' AND ' .sqlparts('isNAonly', array());

    $prefix=$_SESSION['prefix'];
    $q="SELECT x.`title`, x.`cname`,x.`nextaction`,y.`parentId`,y.`ptitle`
			FROM (
				SELECT i.`title`, cn.`name` AS cname, lu.`parentId`,its.`nextaction`
					FROM `{$prefix}items` AS i
					JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
					LEFT OUTER JOIN `{$prefix}lookup` as lu
						ON (i.`itemId` = lu.`itemId`)
					LEFT OUTER JOIN `{$prefix}context` as cn
						ON (its.`contextId` = cn.`contextId`)
                    WHERE {$values['filterquery']}
			) AS x
			LEFT OUTER JOIN (
				SELECT i.`itemId` AS parentId, i.`title` AS ptitle
    				FROM `{$prefix}itemstatus` AS its
                    JOIN `{$prefix}items` AS i USING (`itemId`)
                    WHERE its.`itemId` IN (SELECT DISTINCT `parentId` FROM `{$prefix}lookup`)
                    AND {$values['parentfilterquery']}
			) AS y ON (y.`parentId` = x.`parentId`)
			ORDER BY cname, y.`ptitle`, x.`title`";

    $result=query($q,$values,array());
    if ($result) {
        foreach ($result as $line) {
            $this->projects[$line['parentId']] = $line['ptitle'];
            $this->records[] = $line;
        }
    }
    $this->recCount = count($this->records);
}

}

$pdf=new PDF('P','cm',$papersize);
$pdf->initFontParams($fontname,$fontsize);
$pdf->GetData();
$pdf->AddPage();
$pdf->SetFont($fontname,'',$fontsize);
$pdf->printBooklet();
$pdf->Output();
?>
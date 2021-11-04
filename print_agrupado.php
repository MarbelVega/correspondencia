<?php
    if(isset($_GET['code']))
    {
    $nur=$_GET['code'];
    require ('application/vendor/fpdf17/fpdf.php');
    require ('application/vendor/fpdf17/code39.php');
    //conexion a la base de datos
    //$dbh = new PDO('mysql:host=localhost;port=3306;dbname=paperwork', 'root', 'r0salinda', array( PDO::ATTR_PERSISTENT => false));
    $dbh = new PDO('mysql:host=localhost;port=3306;dbname=dbsigec', 'root', 'vertrigo', array( PDO::ATTR_PERSISTENT => false));
    $sql="SELECT * FROM agrupaciones a INNER  JOIN nurs n ON a.padre=n.nur WHERE a.padre='$nur'";
    $stmt = $dbh->prepare($sql);        
    $stmt->execute();        
    while ($padre = $stmt->fetch(PDO::FETCH_OBJ)) 
    {           
    $pdf = new PDF_Code39('P','mm','Letter');
    $pdf->SetMargins(10, 10,5);
    $pdf->AddPage();    
    $pdf->SetFont('Arial', '', 18);    
    $pdf->SetXY(30,20);
    $pdf->Cell(155, 10, 'CARATULA DE AGRUPACION', 1,FALSE,'C'); 
    $pdf->Ln();
    $image_file = 'media/logos/logo_aev.png';
    $pdf->Image($image_file, 85, 32, 43, 25, 'png', '', '', FALSE, 200, '', FALSE, FALSE, 1);
    $pdf->SetXY(30,30);
    $pdf->Cell(155, 25, '', 'LR',FALSE,'C'); 
    $pdf->Ln();
    $pdf->SetX(30);    
    $pdf->SetFont('Arial', '', 16);   
    $pdf->Cell(155, 10, $padre->nur, 'LR',FALSE,'C'); 
    $pdf->Ln();
    $pdf->Code39(83,65,$padre->nur,0.71,8);
    $pdf->Ln();
    $pdf->SetXY(30,65);    
    $pdf->Cell(155, 10, '', 'LRB',FALSE,'C'); 
    $pdf->Ln();
    $pdf->SetX(30);    
    $pdf->Cell(30, 10, 'AGRUPADO POR', 1,FALSE,'C'); 
    $pdf->Cell(125, 5, $padre->nombre, 'LR',FALSE,'L'); 
    $pdf->Ln();
    $pdf->SetX(60);    
    $pdf->Cell(125, 5, $padre->cargo, 'LRB',FALSE,'L'); 
    $pdf->Ln();
    $pdf->SetX(30);    
    $pdf->Cell(30, 10, 'FECHA', 1,FALSE,'L'); 
    $pdf->Cell(47, 10, date('d-m-Y',  strtotime($padre->fecha)), 1,FALSE,'L'); 
    $pdf->Cell(30, 10, 'HORA', 1,FALSE,'L'); 
    $pdf->Cell(48, 10, date('H:i:s',  strtotime($padre->fecha)), 1,FALSE,'L'); 
    
    $pdf->Ln(20);
    $pdf->SetX(20);
    $pdf->Cell(175, 10, 'HOJA(S) DE RUTA AGRUPADO(S)', 1,FALSE,'C'); 
    $pdf->Ln();
    $pdf->SetX(20);
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(25, 5, 'HOJA  RUTA', 1,FALSE,'C',TRUE); 
    $pdf->Cell(28, 5, 'F. CREACION', 1,FALSE,'C',TRUE); 
    $pdf->Cell(74, 5, 'CREADO POR', 1,FALSE,'C',TRUE);     
    $pdf->Cell(28, 5, 'F. RECEPCION', 1,FALSE,'C',TRUE);     
    $pdf->Cell(20, 5, 'OFICIAL', 1,FALSE,'C',TRUE);
    $pdf->SetFont('Arial', '', 8);    
    //hijos
    $sql="SELECT * FROM seguimiento s 
            INNER JOIN nurs n ON s.nur=n.nur 
            INNER JOIN agrupaciones a ON s.id=a.id_seguimiento 
            WHERE a.padre='$nur'";
    $stmt = $dbh->prepare($sql);        
    $stmt->execute();        
    while ($hijo = $stmt->fetch(PDO::FETCH_OBJ)) 
    {       
        $pdf->Ln();
            $pdf->SetX(20);
            $pdf->Cell(25, 5, $hijo->nur, 1,FALSE,'C'); 
            $pdf->Cell(28, 5, date('d-m-Y',  strtotime($hijo->fecha_creacion)), 1,FALSE,'C'); 
            $pdf->Cell(74, 5, utf8_decode($hijo->username), 1,FALSE,'C');     
            $pdf->Cell(28, 5, date('d-m-Y',  strtotime($hijo->fecha_recepcion)), 1,FALSE,'C');                 
            if($hijo->oficial==1)
                    $oficial='SI';
            else
                $oficial='NO';
            $pdf->Cell(20, 5, $oficial, 1,FALSE,'C');             
            
    }
    $pdf->Output('hoja_ruta_agrupada.pdf','D');        
    }
 }
 else
  {
   
  }        
  
?>

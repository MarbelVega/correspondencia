<?php

if (isset($_GET['code'])) {
    $nur = $_GET['code'];
    require ('application/vendor/fpdf17/fpdf.php');
    require ('application/vendor/fpdf17/code39.php');
    //conexion a la base de datos
    //$dbh = new PDO('mysql:host=localhost;port=3306;dbname=paperwork', 'root', 'r0salinda', array(PDO::ATTR_PERSISTENT => false));
    $dbh = new PDO('mysql:host=localhost;port=3306;dbname=dbsigec', 'root', 'vertrigo', array(PDO::ATTR_PERSISTENT => false));
    $sql = "SELECT d.id_tipo,d.hojas,d.codigo,d.nur,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.referencia,d.fecha_creacion,d.adjuntos,d.copias,d.institucion_destinatario,d.institucion_remitente
    ,d.cite_original, e.entidad,e.sigla,e.logo2,e.sigla2,p.proceso FROM documentos d
    INNER JOIN users u ON u.id=d.id_user 
    INNER JOIN oficinas o ON o.id=u.id_oficina
    INNER JOIN entidades e ON e.id=o.id_entidad 
    INNER JOIN procesos p ON d.id_proceso=p.id
    WHERE d.nur='$nur'
    AND d.original='1'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    while ($rs = $stmt->fetch(PDO::FETCH_OBJ)) {
        $pdf = new PDF_Code39('P', 'mm', 'Letter');
        $pdf->SetMargins(10, 10, 5);
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetX(10);
        $pdf->SetFont('Arial', 'B', 18);
        $image_file = 'media/logos/' . $rs->logo2;
        $pdf->Image($image_file, 10, 5, 55, 25, 'png', '', '', FALSE, 300, '', FALSE, FALSE, 1);
        $pdf->Ln(12);
        $pdf->SetXY(150, 11);
        $pdf->SetFont('Arial', '', 14);
        //hoja de seguimiento    
        if ($rs->id_tipo != 6):  //tipo 6 = carta externa
            $pdf->Cell(60, 6, 'HOJA DE RUTA INTERNA', 1, FALSE, 'C');
            $pdf->Code39(153, 5, $rs->nur, 0.71, 5);
        //$pdf->Code39(152,21,$rs->nur,0.71,8);    
        else:
            $pdf->Code39(153, 5, $rs->nur, 0.71, 5);
            $pdf->Cell(60, 6, 'HOJA DE RUTA EXTERNA', 1, FALSE, 'C');
        endif;
        //$pdf->Code39(155,21,$rs->nur,0.71,8);
        //fin codigo barra                                                                     
        $pdf->SetX(145);
        //NUA
//    $pdf->SetFont('Arial', 'B', 10);    
//    $pdf->Cell(65, 5, $rs->sigla, 'TR',FALSE,'C');
        $pdf->SetXY(150, 17);
        $pdf->SetFont('Arial', '', 18);
        $pdf->Cell(60, 9, $rs->nur, 1, FALSE, 'C');

        $pdf->SetXY(145, 5);
        $pdf->Cell(65, 19, '', 0, FALSE, 'C');


        $pdf->SetXY(10, 29);
        //columna 1
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(25, 10, 'PROCEDENCIA:', 'TBL', FALSE, 'L');
        $pdf->SetFont('helvetica', 'B', 8);

        if (trim($rs->institucion_remitente) != '') {
            if (strlen($rs->institucion_remitente) > 100) {
                $pdf->MultiCell(115, 10, utf8_decode(strtoupper($rs->institucion_remitente)), 'T', 'L');
            } else {
                $pdf->Cell(115, 10, utf8_decode(strtoupper($rs->institucion_remitente)), 'T', 'L');
            }
        } else {
            if (strlen($rs->entidad) > 80) {
                $pdf->MultiCell(115, 5, utf8_decode(strtoupper($rs->entidad)), 'T', 'L');
            } else {
                $pdf->Cell(115, 10, utf8_decode(strtoupper($rs->entidad)), 'T', 'L');
            }
        }
        $pdf->SetXY(150, 29);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(60, 5, 'CITE ORIGINAL', 'TRL', FALSE, 'C');
        $pdf->SetXY(150, 34);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(60, 5, $rs->cite_original, 'RL', FALSE, 'C');
        $pdf->Ln();

        //REMITENTE
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(25, 10, 'REMITENTE:', 'TL', FALSE, 'L');
        $pdf->Cell(115, 6, $rs->nombre_remitente, 'T', FALSE, 'L');

        $pdf->Cell(13, 5, 'FECHA:', 'LT', FALSE, 'L');
        $pdf->Cell(47, 5, date('d/m/Y', strtotime($rs->fecha_creacion)), 'TR', FALSE, 'L');
        $pdf->SetXY(150, 44);
        $pdf->Cell(13, 5, 'HORA:', 'L', FALSE, 'L');
        $pdf->Cell(47, 5, date('h:i:s A', strtotime($rs->fecha_creacion)), 'R', FALSE, 'L');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(35, 44);
        $pdf->Cell(115, 3, strtoupper($rs->cargo_remitente), 0, FALSE, 'L');
        $pdf->SetFont('helvetica', '', 9);
        //DETINATARIO
        if (strlen(trim($rs->cargo_destinatario)) == 0) {
            $pdf->SetXY(10, 49);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell(25, 10, 'DESTINATARIO:', 'TL', FALSE, 'L');
            $pdf->MultiCell(175, 3, $rs->nombre_destinatario, 'TR', 'L');
            //$pdf->MultiCell($w, $h, $txt, $border, $align)
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 9);
        } else {
            $pdf->SetXY(10, 49);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(25, 10, 'DESTINATARIO:', 'TL', FALSE, 'L');
            $pdf->Cell(175, 6, $rs->nombre_destinatario, 'TR', FALSE, 'L');
            $pdf->Ln();
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell(25, 3, '', 0, FALSE, 'L');
            $pdf->Cell(175, 3, strtoupper($rs->cargo_destinatario), 'R', FALSE, 'L');
            $pdf->SetFont('helvetica', '', 9);
        }
        //proceso
        //fecha

        $pdf->SetXY(10, 59);
        $pdf->SetFontSize(8);
        $pdf->Cell(25, 10, 'REFERENCIA:', 'LT', FALSE, 'L');
        $pdf->SetFont('helvetica', '', 7);
        if (strlen($rs->referencia) > 121) {
            //$pdf->SetFont('helvetica', '', 6); 
            if (strlen($rs->referencia) > 240)
                $text = substr($rs->referencia, 0, 240) . '..';
            else
                $text = $rs->referencia;
            $pdf->MultiCell(175, 5, $text, 'TR', 'L');
            $pdf->Ln(1);
        }
        else {
            $pdf->Cell(175, 10, $rs->referencia, 'TR', 'L');
            $pdf->Ln(10);
        }

        $pdf->SetFont('helvetica', '', 8);
        //$pdf->Cell(25, 5, 'PROCESO', 'LTB', FALSE, 'l');
        //$pdf->Cell(47, 5, $rs->proceso, 'TRB', 'L');
        /*if($rs->id_tipo != 6)
        {
            $pdf->Cell(25, 5, 'PROCESO', 'LTB', FALSE, 'l');
            $pdf->Cell(47, 5, $rs->proceso, 'TRB', 'L');
        }
        else
        {
            $pdf->Cell(25, 5, '', 'LTB', FALSE, 'l');
            $pdf->Cell(47, 5, '', 'TRB', 'L');
        }*/        
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(100, 5, 'ADJUNTO: ' . utf8_decode($rs->adjuntos), 1, FALSE, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(100, 5, 'HOJAS : ' . $rs->hojas, 1, FALSE, 'L');
        $pdf->Ln(10);
        //primera pagina
        $pdf->SetXY(10, 70);
        for ($i = 1; $i < 4; $i++) {
            $pdf->Ln(4);
            $pdf->SetFontSize(10);
            $pdf->SetFillColor(240, 245, 255);
            $pdf->Cell(8, 7, 'A:', 1, FALSE, 'L', true);
            $pdf->SetFillColor(0);
            $pdf->Cell(192, 7, '', 1, FALSE, 'L');
            $pdf->SetFillColor(0);
            $pdf->ln();
            $pdf->SetFontSize(4);
            $pdf->Cell(200, 1, '', 'RL', FALSE);
            $pdf->Ln(0);
                /*$pdf->Cell(20, 5, 'ATENCION URGENTE', 1, FALSE, 'L');$pdf->Cell(4, 5, '', 1, FALSE, 'L');
                //ESPACIO
                $pdf->Cell(4, 5, '', 0, FALSE, 'L');
                $pdf->Cell(23, 5, 'PARA SU CONOCIMIENTO', 1, FALSE, 'L');$pdf->Cell(4, 5, '', 1, FALSE, 'L');$pdf->Cell(5, 5, '', 0, FALSE, 'L');
                $pdf->Cell(20, 5, 'ELABORAR INFORME', 1, FALSE, 'L');$pdf->Cell(4, 5, '', 1, FALSE, 'L');$pdf->Cell(5, 5, '', 0, FALSE, 'L');
                $pdf->Cell(20, 5, 'ELABORAR RESPUESTA', 1, FALSE, 'L');$pdf->Cell(4, 5, '', 1, FALSE, 'L');$pdf->Cell(5, 5, '', 0, FALSE, 'L');
                $pdf->Cell(20, 5, 'PARA FIRMA', 1, FALSE, 'L');$pdf->Cell(4, 5, '', 1, FALSE, 'L');$pdf->Cell(5, 5, '', 0, FALSE, 'L');
                $pdf->Cell(20, 5, 'PARA SU VoBo', 1, FALSE, 'L');$pdf->Cell(4, 5, '', 1, FALSE, 'L');$pdf->Cell(5, 5, '', 0, FALSE, 'L');
                $pdf->Cell(20, 5, 'ARCHIVAR', 1, FALSE, 'L');$pdf->Cell(4, 5, '', 1, FALSE, 'L');$pdf->Cell(5, 5, '', 0, FALSE, 'L');
                
                $pdf->Ln();
                $pdf->Cell(200, 1, '', 'BRL', FALSE);
                $pdf->Ln(1);*/
            //proveido
            $pdf->Cell(144, 47, '', 'RL', FALSE, 'L');
            $pdf->SetTextColor(230, 230, 230);
            $pdf->SetFontSize(20);
            $pdf->Cell(56, 47, 'Sello Recibido', 1, FALSE, 'C');
            $pdf->SetTextColor(0);
            $pdf->Ln(47);
            $pdf->SetFillColor(240, 245, 255);

            $pdf->SetFontSize(10);
            $pdf->Cell(20, 5, 'Adjunto:', 1, FALSE, 'L', true);
            $pdf->Cell(124, 5, '', 1, FALSE, 'L');
            $pdf->SetFillColor(240, 245, 255);
            $pdf->Cell(20, 5, 'Hora:', 1, FALSE, 'L', true);
            $pdf->Cell(36, 5, '', 1, FALSE, 'L');
            $pdf->Ln();
        }
        $pdf->Cell(20, 5, $rs->nur, 0, FALSE, 'L');
        $pdf->ln();
        //segunda APgina
        for ($i = 1; $i < 5; $i++) {
            $pdf->ln(2);
            $pdf->SetFontSize(10);
            $pdf->SetFillColor(240, 245, 255);
            $pdf->Cell(8, 7, 'A:', 1, FALSE, 'L', true);
            $pdf->SetFillColor(0);
            $pdf->Cell(192, 7, '', 1, FALSE, 'C');
            $pdf->ln();
            $pdf->SetFontSize(4);
            $pdf->Cell(200, 1, '', 'RL', FALSE);
            $pdf->Ln(0);
                /*$pdf->Cell(20, 5, 'ATENCION URGENTE', 1, FALSE, 'L');
                $pdf->Cell(4, 5, '', 1, FALSE, 'L');
                //ESPACIO
                $pdf->Cell(4, 5, '', 0, FALSE, 'L');

                $pdf->Cell(23, 5, 'PARA SU CONOCIMIENTO', 1, FALSE, 'L');
                $pdf->Cell(4, 5, '', 1, FALSE, 'L');
                $pdf->Cell(5, 5, '', 0, FALSE, 'L');


                $pdf->Cell(20, 5, 'ELABORAR INFORME', 1, FALSE, 'L');
                $pdf->Cell(4, 5, '', 1, FALSE, 'L');
                $pdf->Cell(5, 5, '', 0, FALSE, 'L');

                $pdf->Cell(20, 5, 'ELABORAR RESPUESTA', 1, FALSE, 'L');
                $pdf->Cell(4, 5, '', 1, FALSE, 'L');
                $pdf->Cell(5, 5, '', 0, FALSE, 'L');

                $pdf->Cell(20, 5, 'PARA FIRMA', 1, FALSE, 'L');
                $pdf->Cell(4, 5, '', 1, FALSE, 'L');
                $pdf->Cell(5, 5, '', 0, FALSE, 'L');

                $pdf->Cell(20, 5, 'PARA SU VoBo', 1, FALSE, 'L');
                $pdf->Cell(4, 5, '', 1, FALSE, 'L');
                $pdf->Cell(5, 5, '', 0, FALSE, 'L');

                $pdf->Cell(20, 5, 'ARCHIVAR', 1, FALSE, 'L');
                $pdf->Cell(4, 5, '', 1, FALSE, 'L');
                $pdf->Cell(5, 5, '', 0, FALSE, 'L');
                $pdf->Ln();
                $pdf->Cell(200, 1, '', 'BRL', FALSE);
                $pdf->Ln(1*/
            //proveido
            $pdf->Cell(144, 47, '', 'RL', FALSE, 'L');
            $pdf->SetTextColor(230, 230, 230);
            $pdf->SetFontSize(20);
            $pdf->Cell(56, 47, 'Sello Recibido', 1, FALSE, 'C');
            $pdf->SetTextColor(0);
            $pdf->Ln(47);
            $pdf->SetFillColor(240, 245, 255);

            $pdf->SetFontSize(10);
            $pdf->Cell(20, 5, 'Adjunto:', 1, FALSE, 'L', true);
            $pdf->Cell(124, 5, '', 1, FALSE, 'L');
            $pdf->SetFillColor(240, 245, 255);
            $pdf->Cell(20, 5, 'Hora:', 1, FALSE, 'L', true);
            $pdf->Cell(36, 5, '', 1, FALSE, 'L');
            $pdf->Ln();
        }
        if (stripos($nur, '/')) {
            $nur = explode('/', $nur);
            $nur = $nur[0] . $nur[1];
        }
        $pdf->Output('Hoja Ruta ' . $nur . '.pdf', 'D');
    }
}
?>

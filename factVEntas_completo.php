<?php
    set_time_limit(0);
    //date_default_timezone_set('America/Mexico_City');
    $hoy = (string)date("Ymd");
    $today = "'".date('Y-m-d')."'";
    $ayer =date("Y-m-d",strtotime ( '-1 day' , strtotime ($hoy)));
    //$ayer = '2024-04-30';
    echo "Hoy $today   Ayer: $ayer_2<br>";

    $file ="CSV_COMPLETO_FACT_VENTAS_$ayer.csv";
    if(file_exists($file)){
        unlink($file);
    }
    $fp=fopen($file, 'a+');

    $file_fv ="QUERY_FACT_VENTAS_$ayer.sql";
    if(file_exists($file_fv)){
        unlink($file_fv);
    }
    $fp2=fopen($file_fv, 'a+');

    $encabezados="EMPRESA,CODIGO_CLIENTE,NOMBRE_CLIENTE,FECHA_FACTURA,NUMERO_FACTURA,TIPO_DOCUMENTO,CANTIDAD,PRODUCT_ID_CORP,PRODUCT_ID,PRODUCTO_NOMBRE,COSTO_PRODUCTO,PRECIO_VENTA,VENTA_NETA,VentaBruta,Descuento,VENDEDOR,CODIGO_LOCAL,VOLUMEN,VENTASEMANA,UtilidadVenta,UtilidadPorcentaje,LitrosUnidades,ANULADA,ALMACEN,UUID_SAT\n";
    fwrite($fp, $encabezados);
    //--------> Conexión ODBC
    //$dsn = 'odbc_LOCAL'; 
    $dsn = 'PRODUCCION';
    $user = 'ODBC';
    $password = 'ODBC';

    $odbcConnection = odbc_connect($dsn, $user, $password);
    if (!$odbcConnection) {
        die("Error al conectar con la base de datos ODBC: " . odbc_errormsg());
    }
    echo "<br>CONECTADO<br>";
    
    $serverName = "175.168.41.4\DW";
    $connectionOptions = array(
        "Database" => "DWMBA3",
        "Uid" => "sa",
        "PWD" => "Blog2020"
    );
        

    $conn_dw = sqlsrv_connect($serverName, $connectionOptions); //returns false
    if( $conn_dw === false ){
        echo "Conexión fallida";
    }
    ELSE {
    echo "CONECTADO CUBO MBA SQL_SERVER<br>";
    $renglon=1;

    $query = "SELECT NTDC_Client_Prov_Detalle.CORP
                ,NTDC_Client_Prov_Principal.PARTY_CODE AS 'PCODE'
                , NTDC_Client_Prov_Principal.PARTY_NAME AS 'PNAME'
                , NTDC_Client_Prov_Principal.ARRIVAL_DATE AS 'ADATE'
                , NTDC_Client_Prov_Principal.[PACK ID] AS 'PACKID'
                , NTDC_Client_Prov_Detalle.TYPE
                , NTDC_Client_Prov_Detalle.DESCP_SHORT as 'DSHORT'
                , NTDC_Client_Prov_Principal.ORIGIN
                , NTDC_Client_Prov_Principal.CORP
                , NTDC_Client_Prov_Principal.VOID_TEXT AS 'VTEXT'
                , NTDC_Client_Prov_Principal.TOTAL_AMOUNT AS 'TAMOUNT'
                ,NTDC_Client_Prov_Principal.CODE_SALESMAN as 'VENDEDOR'
                FROM    NTDC_Client_Prov_Principal ,NTDC_Client_Prov_Detalle 
                WHERE   NTDC_Client_Prov_Principal.DOC_ID_CORP=NTDC_Client_Prov_Detalle.DOC_ID_CORP 
                AND  NTDC_Client_Prov_Principal.VOID_TEXT<>'Anulado'
                AND NTDC_Client_Prov_Detalle.TYPE<>'DEV'
                AND  NTDC_Client_Prov_Principal.ARRIVAL_DATE ='$ayer'";

        echo "<br>Query Fact Ventas <br>BONIFICACIONES<br>";
        $check = odbc_prepare($odbcConnection, $query);
        if ($check===false) {
                echo "<br>Query mal interpretado<br>";
        } else {
            $result = odbc_exec($odbcConnection, $query);
        }
        $duplicateData = array('1');
        echo "\nEXISTEN : " . odbc_num_rows($result) ." en la consulta \n<br>";
        while ($row = odbc_fetch_array($result)) {
            $aDate = utf8_decode(trim(str_replace('in','',str_replace('i686*','',$row['ADATE']))));
            $fechaVencimientoFactura = DateTime::createFromFormat('d/m/Y', $aDate);
            if ($fechaVencimientoFactura !== false) {
                $fechaVencimientoFactura = $fechaVencimientoFactura->format('Y-m-d');
            }
            $fechaSegundos = strtotime($fechaVencimientoFactura);
            $semana = date('W', $fechaSegundos);
            $VENTASEMANA= "SEM-$semana";
            $empresa_PD = "'".utf8_decode($row['CORP']);
            $codClienteEmpresa = utf8_decode(str_replace('-ALVMX','',str_replace('-PRPIR','',str_replace('-TRDPO','',str_replace('-IMYRQ','',$row['PCODE'])))));
            $nombreCliente = utf8_decode(trim(str_replace(',','',str_replace('"','',str_replace("'","",strtoupper($row['PNAME']))))));
            $aDate = utf8_decode(trim(str_replace('in','',str_replace('i686*','',$row['ADATE']))));
            $factura = utf8_decode($row['PACKID']);
            $codigoProducto = utf8_decode($row['TYPE']);
                $tipoDocumento = 'Bonificacion';
                $QUANTITY = 0;
                $PRODUCT_TOTAL=0;
                $UNIT_COST=0;
                $VentaBruta=0;
                $Descuento=0;
                $UtilidadVenta = 0;
                $UtilidadPorcentaje =0;
                $LitrosUnidades =0;
                $VOLUMEN=0;
            if ($codigoProducto == 'FLETE'||  $codigoProducto == '10PP'||  $codigoProducto == 'INT'||  $codigoProducto == 'SAIN'||  $codigoProducto == 'DPSAL'||  $codigoProducto == 'DEP'||  $codigoProducto == 'VOL'||  $codigoProducto == 'REF'||  $codigoProducto == 'DEV'||  $codigoProducto == 'DESEP'||  $codigoProducto == 'DTRIM'||  $codigoProducto == '5DESP'||  $codigoProducto == '3DESP'||  $codigoProducto == '5PP'||  $codigoProducto == 'NCPAGO'||  $codigoProducto == 'NCANT'||  $codigoProducto == '6DCON'||  $codigoProducto == 'DPS'||  $codigoProducto == 'INI' ||  $codigoProducto == 'NDI'){
                $tipoDocumento = 'Bonificacion';
                $QUANTITY = 0;
                $PRODUCT_TOTAL=0;
                $UNIT_COST=0;
                $VentaBruta=0;
                $Descuento=0;
                $UtilidadVenta = 0;
                $UtilidadPorcentaje =0;
                $LitrosUnidades =0;
                $VOLUMEN=0;
            }
            $codigoProductoEmpresa = "$empresa_PD-$codigoProducto";
            $dShort = utf8_decode($row['DSHORT']);
            $sucursal = utf8_decode($row['ORIGIN']);
            $vendedor= utf8_decode($row['VENDEDOR']);
            $VentaNeta = round((-1)*$row['TAMOUNT']/1.16,2);
            $ANULADA = "false";
            echo "$renglon: Insertado \t" .$row['CORP'] ."<br>";
            $renglon++;
            $query_fv= "INSERT INTO STEP1_FACT_VENTAS VALUES($empresa_PD','$codClienteEmpresa','$nombreCliente','$aDate','$factura','$tipoDocumento','$QUANTITY','$codigoProducto','$dShort','$PRODUCT_TOTAL','$UNIT_COST','$VentaNeta','$VentaBruta','$Descuento','$vendedor','$sucursal','$VOLUMEN','$VENTASEMANA','$UtilidadVenta','$UtilidadPorcentaje','$LitrosUnidades','$ANULADA',' ',' ');\n";
            $queryInsert = "$empresa_PD,$codClienteEmpresa,$nombreCliente,$aDate,$factura,$tipoDocumento,$QUANTITY,$codigoProductoEmpresa,$codigoProducto,$dShort,$PRODUCT_TOTAL,$UNIT_COST,$VentaNeta,$VentaBruta,$Descuento,$vendedor,$sucursal,$VOLUMEN,$VENTASEMANA,$UtilidadVenta,$UtilidadPorcentaje,$LitrosUnidades,'false',' ',' '\n";

            if($row['CORP'] == 'ALVMX'){
                fwrite($fp, $queryInsert);
                fwrite($fp2, $query_fv); 
                sqlsrv_query($conn_dw, $query_fv);
            } 

        }
        $qFactVentas = "SELECT INVT_Producto_Movimientos.DOC_ID_CORP
                        , INVT_Producto_Movimientos.PRODUCT_NAME
                        , INVT_Producto_Movimientos.PRODUCT_ID
                        , INVT_Producto_Movimientos.TRANS_DATE
                        , INVT_Producto_Movimientos.Anulada
                        , INVT_Producto_Movimientos.ADJUSTMENT_TYPE
                        , INVT_Producto_Movimientos.CORP
                        , INVT_Producto_Movimientos.COD_CLIENTE
                        , INVT_Producto_Movimientos.QUANTITY
                        , INVT_Producto_Movimientos.PRODUCT_TOTAL
                        , INVT_Producto_Movimientos.UNIT_COST
                        , INVT_Producto_Movimientos.LINE_TOTAL
                        , INVT_Producto_Movimientos.COD_SALESMAN
                        , INVT_Producto_Movimientos.ORIGIN
                        , INVT_Producto_Movimientos.VOLUMEN
                        , INVT_Producto_Movimientos.ORIGIN_REF
                        , INVT_Producto_Movimientos.ORIGIN_MEMO
                    FROM   INVT_Producto_Movimientos INVT_Producto_Movimientos
                    WHERE  INVT_Producto_Movimientos.ADJUSTMENT_TYPE='RT' AND INVT_Producto_Movimientos.TRANS_DATE='$ayer'";    
            echo "<br><br>CONECTADO<br><br>";
            
            if ($check===false) {
                echo "<br>qFactVentas mal interpretado<br>";
            } else {
                $results = odbc_exec($odbcConnection, $qFactVentas);
            }
            echo "\nEXISTEN : " . odbc_num_rows($results) ." en la consulta \n<br>";
        while ($row_3 = odbc_fetch_array($results)) {
            $ANULADA = $row_3['ANULADA'];
            $EMPRESA= "'".utf8_decode($row_3['CORP']); 
            $CODIGO_CLIENTE= utf8_decode(trim($row_3['COD_CLIENTE']));
            $NOMBRE_CLIENTE= ''; 
            $TRANS_DATE= utf8_decode(str_replace('t=Thu','',substr(trim($row_3['TRANS_DATE']),0,10))); 
            $fechaVencimientoFactura = DateTime::createFromFormat('d/m/Y', $TRANS_DATE);
                if ($fechaVencimientoFactura !== false) {
                $fechaVencimientoFactura = $fechaVencimientoFactura->format('Y-m-d');
                }
            $NUMERO_FACTURA= utf8_decode($row_3['ORIGIN_REF']);
            $QUANTITY= (-1)*$row_3['QUANTITY'];
            $PRODUCT_ID_CORP= '';
            $PRODUCT_ID= utf8_decode($row_3['PRODUCT_ID']);
            $PRODUCT_NAME= utf8_decode($row_3['PRODUCT_NAME']); 
            $PRODUCT_TOTAL_2=$row_3['PRODUCT_TOTAL'];
            $UNIT_COST= $row_3['UNIT_COST']; 
            $LINE_TOTAL_2= (-1)*$row_3['LINE_TOTAL'];  
            $VentaBruta= $UNIT_COST*$QUANTITY;
            $Descuento= 0;
            $COD_SALESMAN= utf8_decode($row_3['COD_SALESMAN']);
            $CODIGO_LOCAL= utf8_decode($row_3['ORIGIN']);
            $VOLUMEN= $row_3['VOLUMEN'];
            $fechaSegundos = strtotime($fechaVencimientoFactura); 
            $semana = date('W', $fechaSegundos);
            $VENTASEMANA= "SEM-$semana";
            $UtilidadVenta= round($LINE_TOTAL_2+$PRODUCT_TOTAL_2);
            if($LINE_TOTAL_2==0){
                $UtilidadPorcentaje=0;
            } else {
                $UtilidadPorcentaje= round(($UtilidadVenta/$LINE_TOTAL_2)*100,4);
            }            
            $LitrosUnidades= $QUANTITY*$VOLUMEN;
            $ADJUSTMENT_TYPE= utf8_decode($row_3['ADJUSTMENT_TYPE']);
            if ($ADJUSTMENT_TYPE== 'FT'){
                $tipoDocumento = 'Ventas';
            } elseif ($ADJUSTMENT_TYPE== 'RT'){
                $tipoDocumento = utf8_decode('Devolución');

            }
            else {
                $tipoDocumento = "No definido";
            }
            echo "<br>$renglon: Insertado \t";
            $renglon++;
            $query_fv= "INSERT INTO STEP1_FACT_VENTAS VALUES($EMPRESA','$CODIGO_CLIENTE','$NOMBRE_CLIENTE','$TRANS_DATE','$NUMERO_FACTURA','$tipoDocumento','$QUANTITY','$PRODUCT_ID','$PRODUCT_NAME',$PRODUCT_TOTAL_2,$UNIT_COST,$LINE_TOTAL_2,$VentaBruta,'$Descuento','$COD_SALESMAN','$CODIGO_LOCAL',$VOLUMEN,'$VENTASEMANA','$UtilidadVenta','$UtilidadPorcentaje','$LitrosUnidades','$ANULADA',' ',' ');\n";
            $qFactVentasInsert = "$EMPRESA,$CODIGO_CLIENTE,$NOMBRE_CLIENTE,$TRANS_DATE,$NUMERO_FACTURA,$tipoDocumento,$QUANTITY,$PRODUCT_ID_CORP,$PRODUCT_ID,$PRODUCT_NAME,$PRODUCT_TOTAL_2,$UNIT_COST,$LINE_TOTAL_2,$VentaBruta,$Descuento,$COD_SALESMAN,$CODIGO_LOCAL,$VOLUMEN,$VENTASEMANA,$UtilidadVenta,$UtilidadPorcentaje,$LitrosUnidades,$ANULADA,' ',' '\n";
            if($row_3['CORP'] == 'ALVMX'){
                fwrite($fp, $qFactVentasInsert);
                fwrite($fp2, $query_fv); 
                sqlsrv_query($conn_dw, $query_fv);
            }      

        }
    
    $qFactVentas = "SELECT CLNT_Factura_Principal.EMPRESA, 
                            CLNT_Factura_Principal.CODIGO_CLIENTE,
                            CLNT_Ficha_Principal.NOMBRE_CLIENTE, 
                            INVT_Producto_Movimientos.TRANS_DATE, 
                            CLNT_Factura_Principal.NUMERO_FACTURA,
                            INVT_Producto_Movimientos.ADJUSTMENT_TYPE,
                            INVT_Producto_Movimientos.QUANTITY,
                            INVT_Producto_Movimientos.PRODUCT_ID_CORP,
                            INVT_Producto_Movimientos.PRODUCT_ID,
                            INVT_Producto_Movimientos.PRODUCT_NAME, 
                            INVT_Producto_Movimientos.PRODUCT_TOTAL,
                            INVT_Producto_Movimientos.UNIT_COST,
                            INVT_Producto_Movimientos.TRANS_COST, 
                            INVT_Producto_Movimientos.LINE_TOTAL,  
                            INVT_Producto_Movimientos.COD_SALESMAN,
                            CLNT_Factura_Principal.CODIGO_LOCAL,
                            INVT_Producto_Movimientos.WAR_CODE,
                            INVT_Producto_Movimientos.VOLUMEN,
                            WEEK(TRANS_DATE) AS 'VENTASEMANA',
                            CLNT_Factura_Principal.ANULADA,
                            CLNT_Factura_Principal.IdentificacionUUID as 'UUID'
                            FROM CLNT_Ficha_Principal CLNT_Ficha_Principal INNER JOIN CLNT_Factura_Principal CLNT_Factura_Principal 
                                ON      (CLNT_Ficha_Principal.CODIGO_CLIENTE_EMPRESA=CLNT_Factura_Principal.CODIGO_CLIENTE_EMPRESA)
                            INNER JOIN INVT_Producto_Movimientos INVT_Producto_Movimientos      
                                ON CLNT_Factura_Principal.CODIGO_FACTURA=INVT_Producto_Movimientos.DOC_ID_CORP2
                            WHERE ((INVT_Producto_Movimientos.ADJUSTMENT_TYPE='FT')  OR (INVT_Producto_Movimientos.ADJUSTMENT_TYPE='PR')) 
                            AND (INVT_Producto_Movimientos.TRANS_DATE='$ayer')";
        

        echo "<br><br> Nueva Consulta";
        $check = odbc_prepare($odbcConnection, $qFactVentas);
        if ($check===false) {
                echo "<br>qFactVentas mal interpretado<br>";
        } else {
            $results = odbc_exec($odbcConnection, $qFactVentas);
        }
        echo "\nEXISTEN : " . odbc_num_rows($results) ." en la consulta \n<br>";
    while ($row_2 = odbc_fetch_array($results)) {
            $UUID = $row_2['UUID'];
            $ANULADA = $row_2['ANULADA'];
            $EMPRESA= "'".utf8_decode($row_2['EMPRESA']); 
            $CODIGO_CLIENTE= utf8_decode(trim($row_2['CODIGO_CLIENTE']));
            $NOMBRE_CLIENTE= utf8_decode(trim(str_replace(',','',str_replace('"','',str_replace("'","",strtoupper($row_2['NOMBRE_CLIENTE'])))))); 
            $TRANS_DATE= utf8_decode(str_replace('t=Thu','',substr($row_2['TRANS_DATE'],0,10)));
            $NUMERO_FACTURA= utf8_decode($row_2['NUMERO_FACTURA']);
            $QUANTITY= $row_2['QUANTITY'];
            $PRODUCT_ID_CORP= utf8_decode($row_2['PRODUCT_ID_CORP']);
            $PRODUCT_ID= utf8_decode($row_2['PRODUCT_ID']);
            $PRODUCT_NAME= utf8_decode($row_2['PRODUCT_NAME']); 
            $PRODUCT_TOTAL=$row_2['PRODUCT_TOTAL'];
            $COSTO_UNITARIO=ROUND($row_2['TRANS_COST'],4);
            $COSTO_TOTAL_PRODUCTO=(-1)*($COSTO_UNITARIO*$QUANTITY);
            $UNIT_COST= round($row_2['UNIT_COST'],4); 
            $LINE_TOTAL= round($row_2['LINE_TOTAL'],4);  
            $VentaBruta= $UNIT_COST*$QUANTITY;
            $Descuento= round(($LINE_TOTAL-$VentaBruta),4);
            $COD_SALESMAN= utf8_decode($row_2['COD_SALESMAN']);
            $CODIGO_LOCAL= utf8_decode($row_2['CODIGO_LOCAL']);
            $WAR_CODE= utf8_decode($row_2['WAR_CODE']);
            $VOLUMEN= $row_2['VOLUMEN'];
            $VENTASEMANA= "SEM-".utf8_decode($row_2['VENTASEMANA']);
            $UtilidadVenta= $VentaBruta-$COSTO_TOTAL_PRODUCTO;
            if($LINE_TOTAL==0){
                $UtilidadPorcentaje=0;
            } else {
                $UtilidadPorcentaje= round(($UtilidadVenta/$VentaBruta)*100,4);
            }            
            $LitrosUnidades= $QUANTITY*$VOLUMEN;
            $ADJUSTMENT_TYPE= utf8_decode($row_2['ADJUSTMENT_TYPE']);
            if ($ADJUSTMENT_TYPE== 'FT' || $ADJUSTMENT_TYPE== 'PR'){
                $tipoDocumento = 'Ventas';
                //$COSTO_PRODUCTO = (-1)*$PRODUCT_TOTAL;
            } elseif ($ADJUSTMENT_TYPE== 'RT'){
                $tipoDocumento = utf8_decode('Devolución');
            }
            else {
                $tipoDocumento = "No definido";
            }
            echo "$renglon: Insertado \t";
            $renglon++;
            $query_fv= "INSERT INTO STEP1_FACT_VENTAS VALUES($EMPRESA','$CODIGO_CLIENTE','$NOMBRE_CLIENTE','$TRANS_DATE','$NUMERO_FACTURA','$tipoDocumento','$QUANTITY','$PRODUCT_ID','$PRODUCT_NAME','$COSTO_TOTAL_PRODUCTO','$UNIT_COST','$LINE_TOTAL','$VentaBruta','$Descuento','$COD_SALESMAN','$CODIGO_LOCAL','$VOLUMEN','$VENTASEMANA','$UtilidadVenta','$UtilidadPorcentaje','$LitrosUnidades','$ANULADA','$WAR_CODE','$UUID');\n";
            $qFactVentasInsert = "$EMPRESA,$CODIGO_CLIENTE,$NOMBRE_CLIENTE,$TRANS_DATE,$NUMERO_FACTURA,$tipoDocumento,$QUANTITY,$PRODUCT_ID_CORP,$PRODUCT_ID,$PRODUCT_NAME,$COSTO_TOTAL_PRODUCTO,$UNIT_COST,$LINE_TOTAL,$VentaBruta,$Descuento,$COD_SALESMAN,$CODIGO_LOCAL,$VOLUMEN,$VENTASEMANA,$UtilidadVenta,$UtilidadPorcentaje,$LitrosUnidades,$ANULADA,$WAR_CODE,$UUID \n";
            if($row_2['EMPRESA'] == 'ALVMX'){
                fwrite($fp, $qFactVentasInsert);
                fwrite($fp2, $query_fv); 
                sqlsrv_query($conn_dw, $query_fv);
            } 
 
        }
}       

        echo "\n\nEMPIEZAN EXECS";
        fwrite($fp2, "EXEC P_FACT_VENTAS;");
        echo "\nSE EJECUTARON LAS P_FACT_VENTAS";
        Sqlsrv_query($conn_dw, "EXEC P_FACT_VENTAS;");
        $msn="EJECUTO 1er EXEC (FACT_VENTAS)";

        fwrite($fp2, "EXEC P_FACT_VENTAS_CANCELADAS;");
        echo "\nSE EJECUTARON LAS P_FACT_VENTAS_CANCELADAS"; 
        Sqlsrv_query($conn_dw, "EXEC P_FACT_VENTAS_CANCELADAS;");
        $msn.="   EJECUTO 2do EXEC (FACT_VENTAS_CANCELADAS)";

        fwrite($fp2, "EXEC P_DIMFACTURAS;"); 
        Sqlsrv_query($conn_dw, "EXEC P_DIMFACTURAS;");
        echo "\nSE EJECUTARON LAS P_DIMFACTURAS";
        $msn.="   EJECUTO 3er EXEC (DIMFACTURAS)";

        fclose($fp2);
        chmod($file_fv, 0777);
        fclose($fp);
        chmod($file, 0777);
        odbc_close($odbcConnection);
        sqlsrv_close($conn_dw);

    function enviarCorreoConArchivoAdjunto($receptor, $asunto, $mensaje, $archivoAdjunto) {
        $from = "rene.ojeda@alvamex.com.mx";
        $headers = "From: $from\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $fileAttachment = chunk_split(base64_encode(file_get_contents($archivoAdjunto)));

        $headers .= "Content-Type: multipart/mixed; boundary=\"mixedboundary\"\r\n\r\n";
        $message = "--mixedboundary\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"alternativeboundary\"\r\n\r\n";
        $message .= "--alternativeboundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= strip_tags($mensaje) . "\r\n\r\n";
        $message .= "--alternativeboundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $mensaje . "\r\n\r\n";
        $message .= "--alternativeboundary--\r\n";
        $message .= "--mixedboundary\r\n";
        $message .= "Content-Type: application/octet-stream; name=\"$archivoAdjunto\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment\r\n\r\n";
        $message .= $fileAttachment . "\r\n";
        $message .= "--mixedboundary--";
        
        return mail($receptor, $asunto, $message, $headers);
    }
    //$receptor = "rene.ojeda@alvamex.com.mx";
    $receptor = "joseangel.mejia@alvamex.com.mx,karla.garcia@alvamex.com.mx,josmar.bautista@alvamex.com.mx,cynthya.ramirez@alvamex.com.mx,alondra.tolentino@alvamex.com.mx,rene.ojeda@alvamex.com.mx";
    $asunto = "FACT VENTAS $ayer";
    $mensaje = "Se han insertado nuevos registros en la tabla de DWMBA3. \n\n\n  $msn";

    if (!empty($duplicateData)) {
        $fileName = $file;
        if (enviarCorreoConArchivoAdjunto($receptor, $asunto, $mensaje, $fileName)) {
            echo "El correo con los datos duplicados ha sido enviado correctamente.";
        } else {
            echo "Error al enviar el correo.";
        }
    }


?>
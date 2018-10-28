<?php

    global $debug;
    global $pole_bobo_investovanych;

    $debug = false;
    $pole_bobo_investovanych = array();

    date_default_timezone_set('Europe/Prague');
    setlocale(LC_COLLATE,"cz_CZ.UTF-8");
    set_time_limit(0);

    require_once dirname(__FILE__) . '/Classes/PHPExcel.php';

    function uloz_bobo_investice ($pole_pujcek) {

        $f = fopen('Soubory/BoBo.txt','w');
        fwrite($f, serialize($pole_pujcek));
        fclose($f);

    }

    function nacti_bobo_investice () {

        $fileName = 'Soubory/BoBo.txt'; 
        $f = fopen( $fileName,'r');
        $pole_pujcek = unserialize(fread($f,filesize($fileName)));
        print_r($pole_pujcek);
        fclose($f);

        $fileName = 'Soubory/BoBo'.hash('ripemd160',time().mt_rand(10,1000)).'.txt';

        echo $fileName;
        flush();

        $f = fopen( $fileName,'w');
        fwrite($f, serialize($pole_pujcek));
        fclose($f);

        print_r($pole_pujcek);

        return $pole_pujcek;
    }

    if ( !file_exists('Soubory/BoBo.txt')) {

        $BoBo = array ( 139402, 107442, 140398, 143009, 144041, 143265, 144087, 142313, 141386, 143232,
                        129936, 144184, 143852, 141550, 144263, 143718, 144507, 139095, 143946, 140485,
                        143988, 139728, 144235, 142458, 142577,
                        171635, // 22.11.2017
                        181939, // 7.12.2017
                        208817, // 15.2.2018
                        213682, // 20.2.2018
                        227821, // 23.3.2018
                        247527, // 17.5.2018
                        249231, // 24.5.2018
                        252000, // 24.5.2018
                        259320, // 9.6.2018
                        259016, // 13.6.2018
                        263951, // 20.6.2018
                        278064, // 19.7.2018
                        291543, // 20.8.2018
                        294261, 301288, 303886
                        
                      );

        uloz_bobo_investice($BoBo);

    } else {
        $BoBo = nacti_bobo_investice();
    }

    $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
    PHPExcel_Settings::setCacheStorageMethod($cacheMethod);


    ini_set("memory_limit","8G");

    function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    function DB_null ( $promena ) {

        if ( $promena == '' ) $promena = 'NULL';

        return $promena;
    }

    function DB_datum ( $datum ) {
        // echo $datum.' '.substr($datum, 2, 1).' = '.substr($datum, 5, 1).' = '.substr($datum, 6, 4).'-'.substr($datum, 3, 2).'-'.substr($datum,0,2);

        if ( substr($datum, 1, 1) == '.' ) $datum = '0'.$datum;
        if ( substr($datum, 4, 1) == '.' ) $datum = substr($datum, 0, 3).'0'.substr($datum,3,6);
        if ( substr($datum, 2, 1) == '.' && substr($datum, 5, 1) == '.' )
                $datum = substr($datum, 6, 4).'-'.substr($datum, 3, 2).'-'.substr($datum,0,2);

        if ( $datum == '' ) $datum = 'NULL'; else $datum = "'".$datum."'";

        return $datum;
    }

    function insert_pujcka ( $db, $investor, $id_klienta, $nazev, $stav, $id_pribehu, $datum_zafinancovani, $vyse_investice, $celkova_splatka,
                             $vyse_splatky, $zbyva_splatek, $puvodni_investice, $investovano_mnou, $vratilo_se_mi, $zbyva, $prodano_za, 
                             $poplatek_za_prodej, $po_splatnosti, $urok_ocekavany, $urok_zaplaceny, $urok_zbyva, $urok_po_splatnosti, 
                             $zaplacena_pokuta, $urok_procenta, $rating, $inv_popl_proc, $souc_poc_spl, $puv_poc_spl, $zps_sekundar,
                             $dalsi_platba, $koupeno_sekundar, $prodano_sekundar ) {

        global $debug;

        $stmt = $db->query("SELECT * FROM pujcka WHERE id_pribehu = $id_pribehu");

        // print_r($db->errorInfo());
        // print_r($stmt);
        
        $row_count = $stmt->rowCount();
        if ( $row_count == 1 ) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id_pujcky = $row['row_id'];
        } else {

            $prodano_za = DB_null($prodano_za);
            $poplatek_za_prodej = DB_null($poplatek_za_prodej);
            $zps_sekundar = DB_null($zps_sekundar);
            $souc_poc_spl = DB_null($souc_poc_spl);

            $datum_zafinancovani = DB_datum($datum_zafinancovani);
            $koupeno_sekundar = DB_datum($koupeno_sekundar);
            $prodano_sekundar = DB_datum($prodano_sekundar);
            $dalsi_platba = DB_datum($dalsi_platba);           

            $sql = "INSERT INTO pujcka ( investor, id_klienta, nazev, stav, id_pribehu, datum_zafinancovani, vyse_investice,
                                                     celkova_splatka, vyse_splatky, zbyva_splatek, puvodni_investice, investovano_mnou, 
                                                     vratilo_se_mi, zbyva, prodano_za, poplatek_za_prodej, po_splatnosti, urok_ocekavany, 
                                                     urok_zaplaceny, urok_zbyva, urok_po_splatnosti, zaplacena_pokuta, urok_procenta, rating, 
                                                     inv_popl_proc, souc_poc_spl, puv_poc_spl, zps_sekundar, dalsi_platba, koupeno_sekundar,
                                                     prodano_sekundar )
                                                         VALUES
                                                     ( '$investor', '$id_klienta', '$nazev', '$stav', $id_pribehu, ".$datum_zafinancovani.", $vyse_investice, $celkova_splatka, $vyse_splatky, $zbyva_splatek, $puvodni_investice, $investovano_mnou, $vratilo_se_mi, $zbyva, $prodano_za, $poplatek_za_prodej, $po_splatnosti, $urok_ocekavany, $urok_zaplaceny, $urok_zbyva, $urok_po_splatnosti, $zaplacena_pokuta, $urok_procenta, '$rating', $inv_popl_proc, $souc_poc_spl, $puv_poc_spl, $zps_sekundar, ".$dalsi_platba.", ".$koupeno_sekundar.", ".$prodano_sekundar.")";

            $stmt = $db->query($sql);
            if ( $debug ) echo $nazev.' '.$sql.' ';
            if ( $debug ) print_r($db->errorInfo());
            if ( $debug ) echo '<BR>';
            $id_pujcky = $db->lastInsertId();
        }

        return $id_pujcky;

    }

    function insert_pohyb ( $db, $datum, $typ, $castka, $jistina, $urok, $odkaz ) {

        global $debug;

        $id_pribehu = '';
        $id_pujcky = 'NULL';

        if ( $odkaz <> '' ) {
            //  najdu id_pribehu a selctuji klic z tabulky pujcka
            $pozice = strlen($odkaz) - 2;
            while ( substr($odkaz,$pozice,1) <> '/' || $pozice < 0 ) {
                $id_pribehu = substr($odkaz,$pozice,1).$id_pribehu;
                $pozice--;
            }
            if ( $debug ) echo $id_pribehu.'|'; flush();
            $stmt = $db->query("SELECT * FROM pujcka WHERE id_pribehu = ".$id_pribehu);
            if ( $debug ) print_r($db->errorInfo());
            $row_count = $stmt->rowCount();
            if ( $row_count == 1 ) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $id_pujcky = $row['row_id'];
                $row = null; unset($row);
            } else {
                $id_pujcky = 'NULL';
            }
            $stmt = null; unset($stmt);
        }

        $datum = DB_datum($datum);
        $jistina = DB_null($jistina);
        $urok = DB_null($urok);
        $odkaz = DB_null($odkaz);

        $sql = "INSERT INTO pohyb ( datum, typ, castka, jistina, urok, pujcka_id, odkaz )
                            VALUES
                            ( ".$datum.", '$typ', $castka, $jistina, $urok, $id_pujcky, '$odkaz')";

        $stmt = $db->query($sql);
        // echo $sql.' ';print_r($db->errorInfo());
        $id_pohybu = $db->lastInsertId();
        $stmt = null;
        unset($stmt);
        return $id_pohybu;

    }

    function zpracuj_soubor( $file, $db ) {

        print_r($db);

        global $BoBo;
        global $debug;

        $reader = PHPExcel_IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $objXLS = $reader->load($file);

        print_r($file);

        if ( strpos ($file, 'transakce') > 0 ) {
    
            // importuj peněženku

            $stmt = $db->query("DELETE FROM pohyb WHERE 1");
    
            $row = 1;
            while ( $objXLS->getActiveSheet()->getCellByColumnAndRow(0, $row)->getValue() <> 'Datum' ) {
                $row++;
            }

            $row++;

            echo $row.'<BR>';flush();

            $sheet = $objXLS->getActiveSheet();

            $zpracovano = 0;
            $zpracovano_krok = 1000;

            while ( $sheet->getCellByColumnAndRow(0, $row)->getValue() <> '' ) {

                $datum = $sheet->getCellByColumnAndRow(0, $row)->getValue();
                $typ = $sheet->getCellByColumnAndRow(2, $row)->getValue();
                $castka = $sheet->getCellByColumnAndRow(3, $row)->getValue();
                $jistina = $sheet->getCellByColumnAndRow(4, $row)->getValue();
                $urok = $sheet->getCellByColumnAndRow(5, $row)->getValue();
                $pujcka_id = $sheet->getCellByColumnAndRow(8, $row)->getValue();
                $odkaz = $sheet->getCellByColumnAndRow(9, $row)->getValue();
                
                $klic_pohybu = insert_pohyb ( $db, $datum, $typ, $castka, $jistina, $urok, $odkaz );

                if ( $debug ) echo $datum.' '.$typ.' '.$castka.' ['.convert(memory_get_peak_usage(true)).'] mem<BR>';
                if ( $zpracovano % $zpracovano_krok == 0 ) echo $zpracovano.'<BR>';

                $zpracovano++;

                flush();
                $row++;
            }

            hledej_bobo_investice ( $db );

        } else {

            // importuj portfolio

            $stmt = $db->query("DELETE FROM pujcka WHERE 1");

            $row = 4;

            while ( $objXLS->getActiveSheet()->getCellByColumnAndRow(0, $row)->getValue() <> '' ) {

                $investor = 'Z';

                $id_klienta = $objXLS->getActiveSheet()->getCellByColumnAndRow(0, $row)->getValue();
                $nazev = $objXLS->getActiveSheet()->getCellByColumnAndRow(1, $row)->getValue();
                $stav = $objXLS->getActiveSheet()->getCellByColumnAndRow(2, $row)->getValue();
                $id_pribehu = $objXLS->getActiveSheet()->getCellByColumnAndRow(3, $row)->getValue();
                $datum_zafinancovani = $objXLS->getActiveSheet()->getCellByColumnAndRow(4, $row)->getFormattedValue();
                if ( $datum_zafinancovani > 0 ) $datum_zafinancovani = date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($datum_zafinancovani)); 
                $vyse_investice = $objXLS->getActiveSheet()->getCellByColumnAndRow(5, $row)->getValue();
                $celkova_splatka = $objXLS->getActiveSheet()->getCellByColumnAndRow(6, $row)->getValue();
                $vyse_splatky = $objXLS->getActiveSheet()->getCellByColumnAndRow(7, $row)->getValue();
                $zbyva_splatek = $objXLS->getActiveSheet()->getCellByColumnAndRow(8, $row)->getValue();
                $puvodni_investice = $objXLS->getActiveSheet()->getCellByColumnAndRow(11, $row)->getValue();
                $investovano_mnou = $objXLS->getActiveSheet()->getCellByColumnAndRow(12, $row)->getValue();
                $vratilo_se_mi = $objXLS->getActiveSheet()->getCellByColumnAndRow(13, $row)->getValue();
                $zbyva = $objXLS->getActiveSheet()->getCellByColumnAndRow(14, $row)->getValue();
                $prodano_za = $objXLS->getActiveSheet()->getCellByColumnAndRow(15, $row)->getValue();
                $poplatek_za_prodej = $objXLS->getActiveSheet()->getCellByColumnAndRow(16, $row)->getValue();
                $po_splatnosti = $objXLS->getActiveSheet()->getCellByColumnAndRow(17, $row)->getValue();
                $urok_ocekavany = $objXLS->getActiveSheet()->getCellByColumnAndRow(18, $row)->getValue();
                $urok_zaplaceny = $objXLS->getActiveSheet()->getCellByColumnAndRow(19, $row)->getValue();
                $urok_zbyva = $objXLS->getActiveSheet()->getCellByColumnAndRow(20, $row)->getValue();
                $urok_po_splatnosti = $objXLS->getActiveSheet()->getCellByColumnAndRow(21, $row)->getValue();
                $zaplacena_pokuta = $objXLS->getActiveSheet()->getCellByColumnAndRow(22, $row)->getValue();
                $urok_procenta = $objXLS->getActiveSheet()->getCellByColumnAndRow(23, $row)->getValue();
                $rating = $objXLS->getActiveSheet()->getCellByColumnAndRow(24, $row)->getValue();
                $inv_popl_proc = $objXLS->getActiveSheet()->getCellByColumnAndRow(25, $row)->getValue();
                $souc_poc_spl = $objXLS->getActiveSheet()->getCellByColumnAndRow(26, $row)->getValue();
                $puv_poc_spl = $objXLS->getActiveSheet()->getCellByColumnAndRow(27, $row)->getValue();
                $zps_sekundar = $objXLS->getActiveSheet()->getCellByColumnAndRow(28, $row)->getValue();
                $dalsi_platba = $objXLS->getActiveSheet()->getCellByColumnAndRow(29, $row)->getFormattedValue();
                if ( $dalsi_platba > 0 ) $dalsi_platba = date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($dalsi_platba));
                $koupeno_sekundar = $objXLS->getActiveSheet()->getCellByColumnAndRow(30, $row)->getValue();
                $prodano_sekundar = $objXLS->getActiveSheet()->getCellByColumnAndRow(31, $row)->getValue();

                if ( in_array($id_pribehu, $BoBo) ) $investor = 'B';

                $klic_pujcky = insert_pujcka ( $db, $investor, $id_klienta, $nazev, $stav, $id_pribehu, $datum_zafinancovani, $vyse_investice, $celkova_splatka,
                                 $vyse_splatky, $zbyva_splatek, $puvodni_investice, $investovano_mnou, $vratilo_se_mi, $zbyva, $prodano_za, 
                                 $poplatek_za_prodej, $po_splatnosti, $urok_ocekavany, $urok_zaplaceny, $urok_zbyva, $urok_po_splatnosti, 
                                 $zaplacena_pokuta, $urok_procenta, $rating, $inv_popl_proc, $souc_poc_spl, $puv_poc_spl, $zps_sekundar,
                                 $dalsi_platba, $koupeno_sekundar, $prodano_sekundar );

                $row++;
            }

        }


    }

    function hledej_bobo_investice ( $db ) {

        global $debug;
        global $pole_bobo_investovanych;
        global $BoBo;

        $last_item = '2017.10.01';
        $last_array = 0;

        $pole_penez = array();
        $pole_penez[] = array ( 'datum' => $last_item,
                                            'pohyb' => 5000,
                                            'zustatek' => 5000 );

        $stmt = $db->query("SELECT date_format(datum,'%Y.%m.%d') as datum, sum(castka) as obrat FROM `pohyb` WHERE pujcka_id in ( select row_id from pujcka where investor = 'B' ) group by datum order by datum");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $last_array++;

            $pole_penez[$last_array] = array ( 'datum' => $row['datum'],
                                                'pohyb' => $row['obrat'],
                                                'zustatek' => $pole_penez[$last_array-1]['zustatek'] + $row['obrat'] );
            $last_item = $row['datum'];

            if ( $debug ) echo $row['datum'].' ..... '.number_format($row['obrat'],2,'.',' ').' ..... '.number_format($pole_penez[$last_array]['zustatek'],2,'.',' ').'<BR>';

        }
        if (!$debug) echo $pole_penez[$last_array]['datum'].' ..... '.number_format($pole_penez[$last_array]['pohyb'],2,'.',' ').' ..... '.number_format($pole_penez[$last_array]['zustatek'],2,'.',' ').'<BR>';


        if ( $pole_penez[$last_array]['zustatek'] > 200 ) { // dost peněz na další investici
            
            krsort($pole_penez);
            if ( $debug ) print_r($pole_penez);

            while ( $pole_penez[$last_array]['zustatek'] > 200 ) {
                $last_array--;
            }
            $last_array++;

            $pole_investic = array();

            $sql = "SELECT id_pribehu FROM pujcka 
                        WHERE investor = 'Z' 
                          and exists ( select * from pohyb where pujcka_id = pujcka.row_id )
                          and datum_zafinancovani = ( SELECT min(datum_zafinancovani) from pujcka 
                                                        WHERE investor = 'Z'
                                                          and date_format(datum_zafinancovani,'%Y.%m.%d') >= '".$pole_penez[$last_array]['datum']."')";

            if ( $debug ) echo $sql.'<BR>';

            $stmt = $db->query($sql);
            while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
                $pole_investic[] = $row['id_pribehu'];
            }

            $random_item = $pole_investic[rand(0,count($pole_investic) - 1)];
            $stmt = $db->query("UPDATE pujcka SET investor = 'B' WHERE id_pribehu = ".$random_item);
            $pole_bobo_investovanych[] = $random_item;
            $BoBo[] = $random_item;

            echo 'Bohdance investováno do půjčky s id = '.$random_item.'<BR>';

            // rekurzivně to zavolám, abych zainvestoval vše volné
            hledej_bobo_investice($db);

        }


        if ( isset($pole_bobo_investovanych) && !empty($pole_bobo_investovanych) ) {

            $pocet_investic = count($pole_bobo_investovanych);

            if ( $pocet_investic > 0 ) {

                uloz_bobo_investice($BoBo);

                // tady můžu posílat mail, že bylo zainvestováno do dalších příběhů
            }
        }
    }

    echo '<HTML>';
    echo '<HEAD><TITLE>Zonky Load v 1.0</TITLE>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '</HEAD>';
    echo '<BODY>';

    require_once ('define_db.php');

    if (!isset($_REQUEST["submit"])) {

        // zobrazení formuláře pro odeslání souboru na server
        echo '<FORM ACTION="zonky_load.php" METHOD="POST" ENCTYPE="multipart/form-data">';
        echo '<input type="file" name="fileToUpload" id="fileToUpload">';
        echo '<input type="submit" value="Upload File" name="submit">';
        echo '</FORM>';

    } else {

        // zpracování uploadovaného souboru
        $target_dir = "Soubory/";
        print_r($_FILES);
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.<BR>";

            zpracuj_soubor ( $target_file, $db );

        } else {
            echo "Sorry, there was an error uploading your file.";
        }

    }

    echo '</BODY>';
    echo '</HTML>';


?>
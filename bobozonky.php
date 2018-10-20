<?php

    require_once('jpgraph/src/jpgraph.php');
    require_once('jpgraph/src/jpgraph_line.php');
    require_once('jpgraph/src/jpgraph_bar.php');

    require_once('define_me.php');

    date_default_timezone_set('Europe/Prague');
    setlocale(LC_COLLATE,"cz_CZ.UTF-8");

    function najdi_x_size ( $retezec ) {

        $x_total = '';
        $pozice_x = strpos($retezec,'X-Total:') + 9;

        while ( ord(substr($retezec,$pozice_x,1)) <> 13 ) {
            $x_total .= substr($retezec,$pozice_x,1);
            $pozice_x ++;
        }

        return $x_total;

    }

    function refresh_token ( $refresh_token ) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.zonky.cz/oauth/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "refresh_token=$refresh_token&grant_type=refresh_token&scope=SCOPE_APP_WEB");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic d2ViOndlYg=="
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $results = json_decode($response);

        return ($results);
    }
    
    require_once('define_db.php');

    if ( isset($_REQUEST['investor'])) {
        $investor = $_REQUEST['investor'];
    } else {
        $investor = 'B';
    }

    if ( isset($_REQUEST['problemove'])) {
        $jake_pujcky = ' problémové';
    } else {
        $jake_pujcky = '';
    }

    if ( isset($_REQUEST['prob_only'])) {
        $prob_only = TRUE;
    } else {
        $prob_only = FALSE;
    }

    // Zjistime, kolik jsme do Zonky nalili
    $stmt2 = $db->query("SELECT sum(castka) as vlozeno FROM pohyb WHERE typ = 'Nabití vaší peněženky'" );
        
    if ( $stmt2->rowCount() > 0 ) {
        $vlozeno = $stmt2->fetch(PDO::FETCH_ASSOC);
        $vlozeno = $vlozeno['vlozeno'];
    } else {
        $odkaz = '';
    }

    if ( $investor == 'Z' ) {
        $celkem_vlozeno = $vlozeno - 5000;
        $koho = 'Zdeňkovy';
    } else if ( $investor == 'B' ) {
        $investor = 'B';
        $celkem_vlozeno = 5000;
        $koho = 'Bohdančiny';
    } else {
        $celkem_vlozeno = $vlozeno;
        $investor = 'X';
        $koho = 'Všechny';
    }

    echo '<HTML>';
    echo '<HEAD><TITLE>'.$koho.$jake_pujcky.' investice na Zonky v 1.0</TITLE>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />';
    echo '<meta http-equiv="Pragma" content="no-cache" />';
    echo '<meta http-equiv="Expires" content="0" />';
    echo '<style>';
    echo '  table {';
    echo '      border-collapse: collapse;';

    echo '  }';

    echo '  th, td {';
    echo '      padding: 3px;';
    echo '  }';
    echo '';
    echo '  tr:nth-child(even){background-color: #f2f2f2}';
    echo '  tr:hover{background-color:#AAf5f5}';
    echo '</style>';
    echo '<script language="javascript">
      var body  = document.getElementsByTagName(\'body\')[0];
    
      function showTip (texticek, current, e) {
         elm=document.getElementById("jednani");
         elml=current;
    
         //alert (elml.clientTop + "," + elml.clientLeft);
         elm.innerHTML=texticek;
    
         if (document.all) {
           elm.style.top=e.y + document.documentElement.scrollTop + body.scrollTop ;
           var pom = e.x + document.documentElement.scrollLeft + body.scrollLeft;
           if (pom > screen.availWidth - 250) {
             pom = screen.availWidth - 250;
           }
           elm.style.left=pom;
         } else {
           elm.style.top=e.pageY + 5;
           var pom = e.pageX + 5;
           /*
           alert (pom);
           alert (e.screenX);
           alert (e.clientX);
           */
           //alert (screen.availWidth);
           if (pom > screen.availWidth - 250) {
             pom = screen.availWidth - 250;
           }
           elm.style.left=pom;
         }
    
    
         elm.style.visibility="visible";
         elm.style.height=elml.style.height;
      }
    
      function endTip() {
        elm=document.getElementById("jednani")
        elm.style.visibility=\'hidden\';
      }
        </script>
        ';

    echo '</HEAD>';
    echo '<BODY>';
    echo '<div id="jednani" style="position:absolute;visibility:hidden;border:2px groove black;font-size:10px;background-color:lightyellow;color:red;"></div>';

    $hlavicka = $koho.'<font color="red"><b>'.$jake_pujcky.'</b></font> investice na Zonky v 1.0';

    $celkem_investice = 0;
    $celkem_splaceno = 0;
    $celkem_urok_ocekavany= 0;
    $celkem_urok_zaplaceny= 0;
    $celkem_poplatek_zonky = 0;

    $pocet_moznych_ztrat = 0;
    $celkem_mozna_ztrata = 0;
    
    $celkem_reinvestice = 0;
    $zustatek_k_vyberu = 0;

    $radek = 0;

    $ratingy = array ();

    $ratingy['A**']['pocet'] = 0;
    $ratingy['A**']['investovano_mnou'] = 0;
    $ratingy['A**']['vratilo_se_mi'] = 0;
    $ratingy['A**']['urok_ocekavany'] = 0;
    $ratingy['A**']['urok_zaplaceny'] = 0;
    $ratingy['A**']['poplatek_zonky'] = 0;
    $ratingy['A**']['mozna_ztrata'] = 0;


    $ratingy['A*']['pocet'] = 0;
    $ratingy['A*']['investovano_mnou'] = 0;
    $ratingy['A*']['vratilo_se_mi'] = 0;
    $ratingy['A*']['urok_ocekavany'] = 0;
    $ratingy['A*']['urok_zaplaceny'] = 0;
    $ratingy['A*']['poplatek_zonky'] = 0;
    $ratingy['A*']['mozna_ztrata'] = 0;

    $ratingy['A++']['pocet'] = 0;
    $ratingy['A++']['investovano_mnou'] = 0;
    $ratingy['A++']['vratilo_se_mi'] = 0;
    $ratingy['A++']['urok_ocekavany'] = 0;
    $ratingy['A++']['urok_zaplaceny'] = 0;
    $ratingy['A++']['poplatek_zonky'] = 0;
    $ratingy['A++']['mozna_ztrata'] = 0;

    $ratingy['A+']['pocet'] = 0;
    $ratingy['A+']['investovano_mnou'] = 0;
    $ratingy['A+']['vratilo_se_mi'] = 0;
    $ratingy['A+']['urok_ocekavany'] = 0;
    $ratingy['A+']['urok_zaplaceny'] = 0;
    $ratingy['A+']['poplatek_zonky'] = 0;
    $ratingy['A+']['mozna_ztrata'] = 0;

    $ratingy['A']['pocet'] = 0;
    $ratingy['A']['investovano_mnou'] = 0;
    $ratingy['A']['vratilo_se_mi'] = 0;
    $ratingy['A']['urok_ocekavany'] = 0;
    $ratingy['A']['urok_zaplaceny'] = 0;
    $ratingy['A']['poplatek_zonky'] = 0;
    $ratingy['A']['mozna_ztrata'] = 0;

    $ratingy['B']['pocet'] = 0;
    $ratingy['B']['investovano_mnou'] = 0;
    $ratingy['B']['vratilo_se_mi'] = 0;
    $ratingy['B']['urok_ocekavany'] = 0;
    $ratingy['B']['urok_zaplaceny'] = 0;
    $ratingy['B']['poplatek_zonky'] = 0;
    $ratingy['B']['mozna_ztrata'] = 0;

    $ratingy['C']['pocet'] = 0;
    $ratingy['C']['investovano_mnou'] = 0;
    $ratingy['C']['vratilo_se_mi'] = 0;
    $ratingy['C']['urok_ocekavany'] = 0;
    $ratingy['C']['urok_zaplaceny'] = 0;
    $ratingy['C']['poplatek_zonky'] = 0;
    $ratingy['C']['mozna_ztrata'] = 0;

    $ratingy['D']['pocet'] = 0;
    $ratingy['D']['investovano_mnou'] = 0;
    $ratingy['D']['vratilo_se_mi'] = 0;
    $ratingy['D']['urok_ocekavany'] = 0;
    $ratingy['D']['urok_zaplaceny'] = 0;
    $ratingy['D']['poplatek_zonky'] = 0;
    $ratingy['D']['mozna_ztrata'] = 0;

    $ratingy_active = array ();

    $ratingy_active['A**']['pocet'] = 0;
    $ratingy_active['A**']['investovano_mnou'] = 0;
    $ratingy_active['A**']['vratilo_se_mi'] = 0;
    $ratingy_active['A**']['urok_ocekavany'] = 0;
    $ratingy_active['A**']['urok_zaplaceny'] = 0;
    $ratingy_active['A**']['poplatek_zonky'] = 0;
    $ratingy_active['A**']['mozna_ztrata'] = 0;


    $ratingy_active['A*']['pocet'] = 0;
    $ratingy_active['A*']['investovano_mnou'] = 0;
    $ratingy_active['A*']['vratilo_se_mi'] = 0;
    $ratingy_active['A*']['urok_ocekavany'] = 0;
    $ratingy_active['A*']['urok_zaplaceny'] = 0;
    $ratingy_active['A*']['poplatek_zonky'] = 0;
    $ratingy_active['A*']['mozna_ztrata'] = 0;

    $ratingy_active['A++']['pocet'] = 0;
    $ratingy_active['A++']['investovano_mnou'] = 0;
    $ratingy_active['A++']['vratilo_se_mi'] = 0;
    $ratingy_active['A++']['urok_ocekavany'] = 0;
    $ratingy_active['A++']['urok_zaplaceny'] = 0;
    $ratingy_active['A++']['poplatek_zonky'] = 0;
    $ratingy_active['A++']['mozna_ztrata'] = 0;

    $ratingy_active['A+']['pocet'] = 0;
    $ratingy_active['A+']['investovano_mnou'] = 0;
    $ratingy_active['A+']['vratilo_se_mi'] = 0;
    $ratingy_active['A+']['urok_ocekavany'] = 0;
    $ratingy_active['A+']['urok_zaplaceny'] = 0;
    $ratingy_active['A+']['poplatek_zonky'] = 0;
    $ratingy_active['A+']['mozna_ztrata'] = 0;

    $ratingy_active['A']['pocet'] = 0;
    $ratingy_active['A']['investovano_mnou'] = 0;
    $ratingy_active['A']['vratilo_se_mi'] = 0;
    $ratingy_active['A']['urok_ocekavany'] = 0;
    $ratingy_active['A']['urok_zaplaceny'] = 0;
    $ratingy_active['A']['poplatek_zonky'] = 0;
    $ratingy_active['A']['mozna_ztrata'] = 0;

    $ratingy_active['B']['pocet'] = 0;
    $ratingy_active['B']['investovano_mnou'] = 0;
    $ratingy_active['B']['vratilo_se_mi'] = 0;
    $ratingy_active['B']['urok_ocekavany'] = 0;
    $ratingy_active['B']['urok_zaplaceny'] = 0;
    $ratingy_active['B']['poplatek_zonky'] = 0;
    $ratingy_active['B']['mozna_ztrata'] = 0;

    $ratingy_active['C']['pocet'] = 0;
    $ratingy_active['C']['investovano_mnou'] = 0;
    $ratingy_active['C']['vratilo_se_mi'] = 0;
    $ratingy_active['C']['urok_ocekavany'] = 0;
    $ratingy_active['C']['urok_zaplaceny'] = 0;
    $ratingy_active['C']['poplatek_zonky'] = 0;
    $ratingy_active['C']['mozna_ztrata'] = 0;

    $ratingy_active['D']['pocet'] = 0;
    $ratingy_active['D']['investovano_mnou'] = 0;
    $ratingy_active['D']['vratilo_se_mi'] = 0;
    $ratingy_active['D']['urok_ocekavany'] = 0;
    $ratingy_active['D']['urok_zaplaceny'] = 0;
    $ratingy_active['D']['poplatek_zonky'] = 0;
    $ratingy_active['D']['mozna_ztrata'] = 0;

    $ratingy_active_pocet_celkem = 0;
    $ratingy_active_investovano_mnou_celkem = 0;
    $ratingy_active_vratilo_se_mi_celkem = 0;
    $ratingy_active_urok_ocekavany_celkem = 0;
    $ratingy_active_urok_zaplaceny_celkem = 0;
    $ratingy_active_poplatek_zonky_celkem = 0;
    $ratingy_active_mozna_ztrata_celkem = 0;

    $ratingy_inactive = array ();

    $ratingy_inactive['A**']['pocet'] = 0;
    $ratingy_inactive['A**']['investovano_mnou'] = 0;
    $ratingy_inactive['A**']['vratilo_se_mi'] = 0;
    $ratingy_inactive['A**']['urok_ocekavany'] = 0;
    $ratingy_inactive['A**']['urok_zaplaceny'] = 0;
    $ratingy_inactive['A**']['poplatek_zonky'] = 0;
    $ratingy_inactive['A**']['mozna_ztrata'] = 0;


    $ratingy_inactive['A*']['pocet'] = 0;
    $ratingy_inactive['A*']['investovano_mnou'] = 0;
    $ratingy_inactive['A*']['vratilo_se_mi'] = 0;
    $ratingy_inactive['A*']['urok_ocekavany'] = 0;
    $ratingy_inactive['A*']['urok_zaplaceny'] = 0;
    $ratingy_inactive['A*']['poplatek_zonky'] = 0;
    $ratingy_inactive['A*']['mozna_ztrata'] = 0;

    $ratingy_inactive['A++']['pocet'] = 0;
    $ratingy_inactive['A++']['investovano_mnou'] = 0;
    $ratingy_inactive['A++']['vratilo_se_mi'] = 0;
    $ratingy_inactive['A++']['urok_ocekavany'] = 0;
    $ratingy_inactive['A++']['urok_zaplaceny'] = 0;
    $ratingy_inactive['A++']['poplatek_zonky'] = 0;
    $ratingy_inactive['A++']['mozna_ztrata'] = 0;

    $ratingy_inactive['A+']['pocet'] = 0;
    $ratingy_inactive['A+']['investovano_mnou'] = 0;
    $ratingy_inactive['A+']['vratilo_se_mi'] = 0;
    $ratingy_inactive['A+']['urok_ocekavany'] = 0;
    $ratingy_inactive['A+']['urok_zaplaceny'] = 0;
    $ratingy_inactive['A+']['poplatek_zonky'] = 0;
    $ratingy_inactive['A+']['mozna_ztrata'] = 0;

    $ratingy_inactive['A']['pocet'] = 0;
    $ratingy_inactive['A']['investovano_mnou'] = 0;
    $ratingy_inactive['A']['vratilo_se_mi'] = 0;
    $ratingy_inactive['A']['urok_ocekavany'] = 0;
    $ratingy_inactive['A']['urok_zaplaceny'] = 0;
    $ratingy_inactive['A']['poplatek_zonky'] = 0;
    $ratingy_inactive['A']['mozna_ztrata'] = 0;

    $ratingy_inactive['B']['pocet'] = 0;
    $ratingy_inactive['B']['investovano_mnou'] = 0;
    $ratingy_inactive['B']['vratilo_se_mi'] = 0;
    $ratingy_inactive['B']['urok_ocekavany'] = 0;
    $ratingy_inactive['B']['urok_zaplaceny'] = 0;
    $ratingy_inactive['B']['poplatek_zonky'] = 0;
    $ratingy_inactive['B']['mozna_ztrata'] = 0;

    $ratingy_inactive['C']['pocet'] = 0;
    $ratingy_inactive['C']['investovano_mnou'] = 0;
    $ratingy_inactive['C']['vratilo_se_mi'] = 0;
    $ratingy_inactive['C']['urok_ocekavany'] = 0;
    $ratingy_inactive['C']['urok_zaplaceny'] = 0;
    $ratingy_inactive['C']['poplatek_zonky'] = 0;
    $ratingy_inactive['C']['mozna_ztrata'] = 0;

    $ratingy_inactive['D']['pocet'] = 0;
    $ratingy_inactive['D']['investovano_mnou'] = 0;
    $ratingy_inactive['D']['vratilo_se_mi'] = 0;
    $ratingy_inactive['D']['urok_ocekavany'] = 0;
    $ratingy_inactive['D']['urok_zaplaceny'] = 0;
    $ratingy_inactive['D']['poplatek_zonky'] = 0;
    $ratingy_inactive['D']['mozna_ztrata'] = 0;

    $ratingy_inactive_pocet_celkem = 0;
    $ratingy_inactive_investovano_mnou_celkem = 0;
    $ratingy_inactive_vratilo_se_mi_celkem = 0;
    $ratingy_inactive_urok_ocekavany_celkem = 0;
    $ratingy_inactive_urok_zaplaceny_celkem = 0;
    $ratingy_inactive_poplatek_zonky_celkem = 0;
    $ratingy_inactive_mozna_ztrata_celkem = 0;
    // přihlášení k ZONKY
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.zonky.cz/oauth/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    curl_setopt($ch, CURLOPT_POST, TRUE);

    curl_setopt($ch, CURLOPT_POSTFIELDS, "username=$x&$y&grant_type=password&scope=SCOPE_APP_WEB");

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/x-www-form-urlencoded",
        "Authorization: Basic d2ViOndlYg=="
    ));

    $response = curl_exec($ch);
    curl_close($ch);
    $results = json_decode($response);

    $pocet_zaznamu = 10;

    echo '<TABLE BORDER="1" CELLPADDING="3" CELLSPACING="1">';
    echo '<TR><TH COLSPAN="16" ALIGN="CENTER">'.$hlavicka.'</TH></TR>';
    echo '<TR><TH>Id</TH><TH>Stav</TH><TH>Název</TH><TH>Datum</TH><TH>Částka půjčky</TH><TH>Měsíců</TH><TH>% p.a.</TH><TH>Investice</TH><TH>Splátka</TH><TH>Splaceno</TH><TH>Oček. úrok</TH><TH>Splac. úrok</TH><TH>Poplatek % p.a.</TH><TH>Poplatek Kč</TH><TH>Další platba</TH><TH>Možná ztráta</TH></TR>';

    if ( $jake_pujcky == '' ) {
        if ( $investor == 'X' ) {
            $sql_pujcky = "SELECT * FROM pujcka WHERE 1=1 ORDER by datum_zafinancovani DESC";
        } else {
            $sql_pujcky = "SELECT * FROM pujcka WHERE investor = '".$investor."' ORDER by datum_zafinancovani DESC";
        }
    } else {
        if ( $investor == 'X' ) {
            $sql_pujcky = "SELECT * FROM pujcka WHERE stav in ('zesplatněná','po splatnosti') ORDER by datum_zafinancovani DESC";
        } else {
            $sql_pujcky = "SELECT * FROM pujcka WHERE investor = '".$investor."' and stav in ('zesplatněná','po splatnosti') ORDER by datum_zafinancovani DESC";
        }
    }

    $stmt = $db->query($sql_pujcky);
    // print_r($db->errorInfo());
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // print_r($row);

        $stmt2 = $db->query("SELECT distinct odkaz FROM pohyb WHERE odkaz is not null and pujcka_id = ".$row['row_id'] );
        
        if ( $stmt2->rowCount() > 0 ) {
            $odkaz = $stmt2->fetch(PDO::FETCH_ASSOC);
            $odkaz = $odkaz['odkaz'];
        } else {
            $odkaz = '';
        }
        
        $status_color = ( $row['stav'] == 'zesplatněná' ? 'red' : ( $row['stav'] == 'po splatnosti' ? 'orange' : 'green'));

        $radek++;

        $celkem_investice += $row['investovano_mnou'];
        $celkem_splaceno += $row['vratilo_se_mi'];
        $celkem_urok_zaplaceny += $row['urok_zaplaceny'];
        $celkem_urok_ocekavany += $row['urok_ocekavany'];

        // spočítáme poplatek Zonky
        $poplatek_zonky = 0;
        $pocet_dni = 0;
        $pocet_plateb = 0;

        /* --- poplatek zonky nepočítám, protože to neumím

        if ( $row['datum_zafinancovani'] != '' ) {
            $datum = new DateTime($row['datum_zafinancovani']);
            $datum_end = new DateTime();

            $investovano = $row['investovano_mnou'];
            while ( $datum < $datum_end && 0 < floatval($investovano) ) {

                // echo $investovano.' '.$poplatek_zonky.'<BR>';


                $sql = "SELECT castka FROM pohyb WHERE castka > 0 and odkaz is not null and pujcka_id = ".$row['row_id']." and datum = '".$datum->format('Y-m-d')."'";
                $stmt3 = $db->query($sql);

                //print_r($sql);
                //print_r($db->errorInfo());
        
                if ( $stmt3->rowCount() > 0 ) {
                    $platba = $stmt3->fetch(PDO::FETCH_ASSOC);
                    $investovano -= $platba['castka'];
                }

                if ( 0 < floatval($investovano) ) {
                    $pocet_dni++;
                }

                $poplatek_zonky += $investovano * ( $row['inv_popl_proc'] / 100 / 365 );

                date_add($datum,date_interval_create_from_date_string("1 day"));
            }
        }

        */

        $celkem_poplatek_zonky += $poplatek_zonky;

        $sql = "SELECT count(*) as pocet_plateb FROM pohyb WHERE castka > 0 and odkaz is not null and pujcka_id = ".$row['row_id'];
        $stmt3 = $db->query($sql);

        if ( $stmt3->rowCount() > 0 ) {
            $platba = $stmt3->fetch(PDO::FETCH_ASSOC);
            $pocet_plateb = $platba['pocet_plateb'];
        }

        // nactu vsechny platby
        $sql = "SELECT * FROM pohyb WHERE castka > 0 and odkaz is not null and pujcka_id = ".$row['row_id'];
        $stmt3 = $db->query($sql);

        $platby = '<TR><TD>Datum</TD><TD>Splátka</TD><TD>Jistina</TD><TD>Úrok</TD></TR>';
        $platby_splatka = 0;
        $platby_jistina = 0;
        $platby_urok = 0;

        //  ALIGN='."\"".'right'."\"".'

        while ($row_platba = $stmt3->fetch(PDO::FETCH_ASSOC)) {
            $platby .= '<TR>'.
                '<TD ALIGN=right>'.$row_platba['datum'].'</TD>'.
                '<TD ALIGN=right>'.number_format($row_platba['castka'],2,'.',' ').' Kč </TD>'.
                '<TD ALIGN=right>'.number_format($row_platba['jistina'],2,'.',' ').' Kč </TD>'.
                '<TD ALIGN=right>'.number_format($row_platba['urok'],2,'.',' ').' Kč </TD>'.
                '</TR>';
            $platby_splatka += $row_platba['castka'];
            $platby_jistina += $row_platba['jistina'];
            $platby_urok += $row_platba['urok'];
        }

        if ( $platby_splatka > 0 ) {
            $platby .= '<TR>'.
                '<TD> CELKEM </TD>'.
                '<TD ALIGN=right>'.number_format($platby_splatka,2,'.',' ').' Kč </TD>'.
                '<TD ALIGN=right>'.number_format($platby_jistina,2,'.',' ').' Kč </TD>'.
                '<TD ALIGN=right>'.number_format($platby_urok,2,'.',' ').' Kč </TD>'.
                '</TR>';
        }

        if ( $row['stav'] == 'zesplatněná' || $row['stav'] == 'po splatnosti' ) {

            $mozna_ztrata = $row['investovano_mnou'] - $row['vratilo_se_mi'] - $row['urok_zaplaceny'];

            $pocet_moznych_ztrat++;
            $celkem_mozna_ztrata += $mozna_ztrata;

        } else {
            $mozna_ztrata = 0;
        }


        if ( isset ($ratingy[$row['rating']]) ) {
            $ratingy[$row['rating']]['pocet']++;
            $ratingy[$row['rating']]['investovano_mnou'] += $row['investovano_mnou'];
            $ratingy[$row['rating']]['vratilo_se_mi'] += $row['vratilo_se_mi'];
            $ratingy[$row['rating']]['urok_ocekavany'] += $row['urok_ocekavany'];
            $ratingy[$row['rating']]['urok_zaplaceny'] += $row['urok_zaplaceny'];
            $ratingy[$row['rating']]['poplatek_zonky'] += $poplatek_zonky;
            $ratingy[$row['rating']]['mozna_ztrata'] += $mozna_ztrata;
        } else {
            $ratingy[$row['rating']]['pocet'] = 1;
            $ratingy[$row['rating']]['investovano_mnou'] = $row['investovano_mnou'];
            $ratingy[$row['rating']]['vratilo_se_mi'] = $row['vratilo_se_mi'];
            $ratingy[$row['rating']]['urok_ocekavany'] = $row['urok_ocekavany'];
            $ratingy[$row['rating']]['urok_zaplaceny'] = $row['urok_zaplaceny'];
            $ratingy[$row['rating']]['poplatek_zonky'] = $poplatek_zonky;
            $ratingy[$row['rating']]['mozna_ztrata'] = $mozna_ztrata;
        }

        if ( $row['stav'] != 'zaplacená' ) {
            if ( isset ($ratingy_active[$row['rating']]) ) {
                $ratingy_active[$row['rating']]['pocet']++;
                $ratingy_active[$row['rating']]['investovano_mnou'] += $row['investovano_mnou'];
                $ratingy_active[$row['rating']]['vratilo_se_mi'] += $row['vratilo_se_mi'];
                $ratingy_active[$row['rating']]['urok_ocekavany'] += $row['urok_ocekavany'];
                $ratingy_active[$row['rating']]['urok_zaplaceny'] += $row['urok_zaplaceny'];
                $ratingy_active[$row['rating']]['poplatek_zonky'] += $poplatek_zonky;
                $ratingy_active[$row['rating']]['mozna_ztrata'] += $mozna_ztrata;
            } else {
                $ratingy_active[$row['rating']]['pocet'] = 1;
                $ratingy_active[$row['rating']]['investovano_mnou'] = $row['investovano_mnou'];
                $ratingy_active[$row['rating']]['vratilo_se_mi'] = $row['vratilo_se_mi'];
                $ratingy_active[$row['rating']]['urok_ocekavany'] = $row['urok_ocekavany'];
                $ratingy_active[$row['rating']]['urok_zaplaceny'] = $row['urok_zaplaceny'];
                $ratingy_active[$row['rating']]['poplatek_zonky'] = $poplatek_zonky;
                $ratingy_active[$row['rating']]['mozna_ztrata'] = $mozna_ztrata;
            }

            $ratingy_active_pocet_celkem++;
            $ratingy_active_investovano_mnou_celkem += $row['investovano_mnou'];
            $ratingy_active_vratilo_se_mi_celkem += $row['vratilo_se_mi'];
            $ratingy_active_urok_ocekavany_celkem += $row['urok_ocekavany'];
            $ratingy_active_urok_zaplaceny_celkem += $row['urok_zaplaceny'];
            $ratingy_active_poplatek_zonky_celkem += $poplatek_zonky;
            $ratingy_active_mozna_ztrata_celkem += $mozna_ztrata;
        } else {
            if ( isset ($ratingy_inactive[$row['rating']]) ) {
                $ratingy_inactive[$row['rating']]['pocet']++;
                $ratingy_inactive[$row['rating']]['investovano_mnou'] += $row['investovano_mnou'];
                $ratingy_inactive[$row['rating']]['vratilo_se_mi'] += $row['vratilo_se_mi'];
                $ratingy_inactive[$row['rating']]['urok_ocekavany'] += $row['urok_ocekavany'];
                $ratingy_inactive[$row['rating']]['urok_zaplaceny'] += $row['urok_zaplaceny'];
                $ratingy_inactive[$row['rating']]['poplatek_zonky'] += $poplatek_zonky;
                $ratingy_inactive[$row['rating']]['mozna_ztrata'] += $mozna_ztrata;
            } else {
                $ratingy_inactive[$row['rating']]['pocet'] = 1;
                $ratingy_inactive[$row['rating']]['investovano_mnou'] = $row['investovano_mnou'];
                $ratingy_inactive[$row['rating']]['vratilo_se_mi'] = $row['vratilo_se_mi'];
                $ratingy_inactive[$row['rating']]['urok_ocekavany'] = $row['urok_ocekavany'];
                $ratingy_inactive[$row['rating']]['urok_zaplaceny'] = $row['urok_zaplaceny'];
                $ratingy_inactive[$row['rating']]['poplatek_zonky'] = $poplatek_zonky;
                $ratingy_inactive[$row['rating']]['mozna_ztrata'] = $mozna_ztrata;
            }

            $ratingy_inactive_pocet_celkem++;
            $ratingy_inactive_investovano_mnou_celkem += $row['investovano_mnou'];
            $ratingy_inactive_vratilo_se_mi_celkem += $row['vratilo_se_mi'];
            $ratingy_inactive_urok_ocekavany_celkem += $row['urok_ocekavany'];
            $ratingy_inactive_urok_zaplaceny_celkem += $row['urok_zaplaceny'];
            $ratingy_inactive_poplatek_zonky_celkem += $poplatek_zonky;
            $ratingy_inactive_mozna_ztrata_celkem += $mozna_ztrata;
        }

        // Načtu komentáře ze Zonky API
        if ( $status_color <> 'green' ) {
            $text_otazek = '';
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://api.zonky.cz/loans/".$row['id_pribehu']."/questions");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, TRUE);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorization: Bearer ".$results->access_token,
                "X-Page: 0",
                "X-Size: 1"
            ));

            $response = curl_exec($ch);
            curl_close($ch);

            $pocet_zaznamu_v_portfoliu = najdi_x_size($response);

            for ( $i = 0; $i < $pocet_zaznamu_v_portfoliu / $pocet_zaznamu; $i++ ) {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "https://api.zonky.cz/loans/".$row['id_pribehu']."/questions");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);

                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorization: Bearer ".$results->access_token,
                    "X-Page: $i",
                    "X-Size: $pocet_zaznamu"
                ));

                $response = curl_exec($ch);
                curl_close($ch);

                $portfolio = json_decode($response,true);

                foreach ( $portfolio as $key => $otazka ) {

                    $komentar = $otazka['message'];
                    $komentar = str_replace( '"', '', $komentar);
                    $komentar = htmlentities( $komentar, ENT_QUOTES);
                    $komentar = str_replace("\n", '<BR>', $komentar);
                    $datum = new DateTime($otazka['timeCreated']);
                    $text_otazek .= '<TR><TD>'. date_format($datum, 'Y-m-d H:i:s').'</TD><TD WIDTH=400>'.$komentar.'</TD></TR>';
                }

                $results = refresh_token($results->refresh_token);

            }
        } else {
            $text_otazek = '';
        }

        if ( !$prob_only || ( $prob_only && ( $row['stav'] == 'po splatnosti') || $row['stav'] == 'zesplatněná' ) ) { 
            echo '<TR>'.
                '<TD ALIGN="right">'.$radek.'</TD>'.
                '<TD nowrap onMouseMove='."'".'showTip("<table border=1 cellspacing=0 cellpadding=0>'.$text_otazek.'</TABLE>", this, event);'."'".' onMouseOut='."'".'endTip();'."'".'><font color="'.$status_color.'">'.
                ( $status_color != 'green' ? '<b>' : '' ).$row['stav'].
                ( $status_color != 'green' ? '</b>' : '' ).
                '</font></TD>'.
                '<TD><A HREF="'.$odkaz.'" TARGET="_blank">'.$row['nazev'].'</A></TD>'.
                '<TD ALIGN="right">'.$row['datum_zafinancovani'].'</TD>'.
                '<TD ALIGN="right">'.number_format($row['vyse_investice'],2,'.',' ').' Kč </TD>'.
                '<TD nowrap onMouseMove='."'".'showTip("<table border=1 cellspacing=0 cellpadding=0>'.$platby.'</TABLE>", this, event);'."'".' onMouseOut='."'".'endTip();'."'".' ALIGN="right">'.$row['puv_poc_spl'].' <font size="1">('.$pocet_plateb.')</font></TD>'.
                '<TD ALIGN="right">'.$row['urok_procenta'].' % </TD>'.
                '<TD ALIGN="right">'.number_format($row['investovano_mnou'],2,'.',' ').' Kč </TD>'.
                '<TD ALIGN="right">'.$row['vyse_splatky'].' Kč </TD>'.
                '<TD ALIGN="right">'.number_format($row['vratilo_se_mi'],2,'.',' ').' Kč </TD>'.
                '<TD ALIGN="right">'.$row['urok_ocekavany'].' Kč </TD>'.
                '<TD ALIGN="right">'.$row['urok_zaplaceny'].' Kč </TD>'.
                '<TD ALIGN="right">'.$row['inv_popl_proc'].' % </TD>'.
                '<TD ALIGN="right">'.number_format($poplatek_zonky,2,'.',' ').' Kč <font size="1">'.$pocet_dni.'</font></TD>'.
                '<TD ALIGN="right">'.$row['dalsi_platba'].'</TD>'.
                '<TD ALIGN="right">'.( $mozna_ztrata == 0 ? '' : number_format($mozna_ztrata,2,'.',' ').' Kč ').'</TD>'.
                '</TD>';
        }
        flush();

    }

    $celkem_pujcek = $radek;

    echo '<TR>'.
             '<TD></TD>'.
             '<TD> CELKEM </TD>'.
             '<TD></TD>'.
             '<TD ALIGN="right"></TD>'.
             '<TD ALIGN="right"></TD>'.
             '<TD ALIGN="right"></TD>'.
             '<TD ALIGN="right"></TD>'.
             '<TD ALIGN="right">'.number_format($celkem_investice,2,'.',' ').' Kč </TD>'.
             '<TD ALIGN="right"></TD>'.
             '<TD ALIGN="right">'.number_format($celkem_splaceno,2,'.',' ').' Kč </TD>'.
             '<TD ALIGN="right">'.number_format($celkem_urok_ocekavany,2,'.',' ').' Kč </TD>'.
             '<TD ALIGN="right">'.number_format($celkem_urok_zaplaceny,2,'.',' ').' Kč </TD>'.
             '<TD ALIGN="right"></TD>'.
             '<TD ALIGN="right">'.number_format($celkem_poplatek_zonky,2,'.',' ').' Kč </TD>'.
             '<TD ALIGN="right"></TD>'.
             '<TD ALIGN="right"><font size="1">('.$pocet_moznych_ztrat.')</font> '.( $celkem_mozna_ztrata == '' ? '' : number_format($celkem_mozna_ztrata,2,'.',' ').' Kč ').'</TD>'.
             '</TD>';

    echo '</TABLE>';

    echo '<BR><BR>';
    echo '<TABLE BORDER="0" WIDTH="350">';
    echo '<TR><TD>Celkem vloženo</TD><TD ALIGN="RIGHT">'.number_format($celkem_vlozeno,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Reinvestice</TD><TD ALIGN="RIGHT">'.number_format($celkem_investice - $celkem_vlozeno,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Vráceno celkem</TD><TD ALIGN="RIGHT">'.number_format($celkem_splaceno+$celkem_urok_zaplaceny,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Vráceno z investic</TD><TD ALIGN="RIGHT">'.number_format($celkem_splaceno,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Obdržený úrok</TD><TD ALIGN="RIGHT">'.number_format($celkem_urok_zaplaceny,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Srážková daň 15%</TD><TD ALIGN="RIGHT">'.number_format($celkem_urok_zaplaceny*0.15,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Poplatek Zonky</TD><TD ALIGN="RIGHT">'.number_format($celkem_poplatek_zonky,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Čistý výnos</TD><TD ALIGN="RIGHT">'.number_format($celkem_urok_zaplaceny-$celkem_urok_zaplaceny*0.15-$celkem_poplatek_zonky,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Zústatek k výběru PŘED zdaněním</TD><TD ALIGN="RIGHT">'.number_format($celkem_vlozeno - $celkem_investice + $celkem_splaceno + $celkem_urok_zaplaceny - $celkem_poplatek_zonky,2,'.',' ').' Kč </TD></TR>';
    echo '<TR><TD>Zústatek k výběru PO zdanění</TD><TD ALIGN="RIGHT">'.number_format($celkem_vlozeno - $celkem_investice + $celkem_splaceno + $celkem_urok_zaplaceny - $celkem_urok_zaplaceny*0.15 - $celkem_poplatek_zonky,2,'.',' ').' Kč </TD></TR>';
    echo '</TABLE>';

    echo '<BR><BR>';

    // ksort ( $ratingy );


    // ------------------------ tabulka na celkové portfolio ---------------------------------
    echo '<TABLE BORDER="1">';
    echo '<TR><TH COLSPAN="10">Ratingová struktura všech investic</TH><TR>';
    echo '<TR><TH>Rating</TH><TH COLSPAN="2">Počet</TH><TH>Investice</TH><TH>Splaceno</TH><TH>Zůstatek</TH><TH>Oček. úrok</TH><TH>Splac. úrok</TH><TH>Poplatek</TH><TH>Možná ztráta</TH></TR>';
    foreach ( $ratingy as $key => $val ) {
        echo '<TR>'.
            '<TD ALIGN="right">'.$key.'</TD>'.
            '<TD ALIGN="right">'.number_format($ratingy[$key]['pocet'],2,'.',' ').'</TD>'.
            '<TD ALIGN="right"><font size="1">('.number_format($ratingy[$key]['pocet']/$celkem_pujcek*100,2,'.',' ').'%)</font>'.'</TD>'.
            '<TD ALIGN="right">'.number_format($ratingy[$key]['investovano_mnou'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy[$key]['vratilo_se_mi'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy[$key]['investovano_mnou']-$ratingy[$key]['vratilo_se_mi'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy[$key]['urok_ocekavany'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy[$key]['urok_zaplaceny'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy[$key]['poplatek_zonky'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy[$key]['mozna_ztrata'],2,'.',' ').' Kč </TD>'.
            '</TR>';
    }

    echo '<TR>'.
            '<TD ALIGN="right">CELKEM</TD>'.
            '<TD ALIGN="right">'.number_format($celkem_pujcek,2,'.',' ').'</TD>'.
            '<TD ALIGN="right"><font size="1">('.number_format($celkem_pujcek/$celkem_pujcek*100,2,'.',' ').'%)</font>'.'</TD>'.
            '<TD ALIGN="right">'.number_format($celkem_investice,2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($celkem_splaceno,2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($celkem_investice-$celkem_splaceno,2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($celkem_urok_ocekavany,2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($celkem_urok_zaplaceny,2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($celkem_poplatek_zonky,2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($celkem_mozna_ztrata,2,'.',' ').' Kč </TD>'.
         '</TR>';

    echo '</TABLE>';

    // ------------------------ tabulka na nesplacené portfolio ---------------------------------
    echo '<TABLE BORDER="1">';
    echo '<TR><TH COLSPAN="10">Ratingová struktura nesplacených investic</TH><TR>';
    echo '<TR><TH>Rating</TH><TH COLSPAN="2">Počet</TH><TH>Investice</TH><TH>Splaceno</TH><TH>Zůstatek</TH><TH>Oček. úrok</TH><TH>Splac. úrok</TH><TH>Poplatek</TH><TH>Možná ztráta</TH></TR>';
    foreach ( $ratingy_active as $key => $val ) {
        echo '<TR>'.
            '<TD ALIGN="right">'.$key.'</TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_active[$key]['pocet'],2,'.',' ').'</TD>'.
            '<TD ALIGN="right"><font size="1">('.number_format($ratingy_active[$key]['pocet']/$ratingy_active_pocet_celkem*100,2,'.',' ').'%)</font>'.'</TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_active[$key]['investovano_mnou'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_active[$key]['vratilo_se_mi'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_active[$key]['investovano_mnou']-$ratingy_active[$key]['vratilo_se_mi'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_active[$key]['urok_ocekavany'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_active[$key]['urok_zaplaceny'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_active[$key]['poplatek_zonky'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_active[$key]['mozna_ztrata'],2,'.',' ').' Kč </TD>'.
            '</TR>';
    }

    echo '<TR>'.
        '<TD ALIGN="right">CELKEM</TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_active_pocet_celkem,2,'.',' ').'</TD>'.
        '<TD ALIGN="right"><font size="1">('.number_format($ratingy_active_pocet_celkem/$ratingy_active_pocet_celkem                                                                                                *100,2,'.',' ').'%)</font>'.'</TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_active_investovano_mnou_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_active_vratilo_se_mi_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_active_investovano_mnou_celkem-$ratingy_active_vratilo_se_mi_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_active_urok_ocekavany_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_active_urok_zaplaceny_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_active_poplatek_zonky_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_active_mozna_ztrata_celkem,2,'.',' ').' Kč </TD>'.
        '</TR>';
    echo '</TABLE>';

// ------------------------ tabulka na splacené portfolio ---------------------------------
    echo '<TABLE BORDER="1">';
    echo '<TR><TH COLSPAN="10">Ratingová struktura splacených investic</TH><TR>';
    echo '<TR><TH>Rating</TH><TH COLSPAN="2">Počet</TH><TH>Investice</TH><TH>Splaceno</TH><TH>Zůstatek</TH><TH>Oček. úrok</TH><TH>Splac. úrok</TH><TH>Poplatek</TH><TH>Možná ztráta</TH></TR>';
    foreach ( $ratingy_active as $key => $val ) {
        echo '<TR>'.
            '<TD ALIGN="right">'.$key.'</TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_inactive[$key]['pocet'],2,'.',' ').'</TD>'.
            '<TD ALIGN="right"><font size="1">('.number_format($ratingy_inactive[$key]['pocet']/$ratingy_inactive_pocet_celkem*100,2,'.',' ').'%)</font>'.'</TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_inactive[$key]['investovano_mnou'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_inactive[$key]['vratilo_se_mi'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_inactive[$key]['investovano_mnou']-$ratingy_inactive[$key]['vratilo_se_mi'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_inactive[$key]['urok_ocekavany'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_inactive[$key]['urok_zaplaceny'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_inactive[$key]['poplatek_zonky'],2,'.',' ').' Kč </TD>'.
            '<TD ALIGN="right">'.number_format($ratingy_inactive[$key]['mozna_ztrata'],2,'.',' ').' Kč </TD>'.
            '</TR>';
    }

    echo '<TR>'.
        '<TD ALIGN="right">CELKEM</TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_inactive_pocet_celkem,2,'.',' ').'</TD>'.
        '<TD ALIGN="right"><font size="1">('.number_format($ratingy_inactive_pocet_celkem/$ratingy_inactive_pocet_celkem                                                                                                *100,2,'.',' ').'%)</font>'.'</TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_inactive_investovano_mnou_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_inactive_vratilo_se_mi_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_inactive_investovano_mnou_celkem-$ratingy_inactive_vratilo_se_mi_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_inactive_urok_ocekavany_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_inactive_urok_zaplaceny_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_inactive_poplatek_zonky_celkem,2,'.',' ').' Kč </TD>'.
        '<TD ALIGN="right">'.number_format($ratingy_inactive_mozna_ztrata_celkem,2,'.',' ').' Kč </TD>'.
        '</TR>';
    echo '</TABLE>';

    echo '<BR><BR>';

    // pokusný graf
    if ( $investor == 'X' ) {
        $sql = "SELECT * FROM pohyb";
    } else {
        $sql = "SELECT * FROM pohyb WHERE pujcka_id in ( SELECT row_id FROM pujcka WHERE investor = '".$investor."')";
    }

    $stmt = $db->query($sql);
    // print_r($db->errorInfo());
    // print_r($sql);
    // echo 'Počet záznamů = '.$stmt->rowCount().'<BR>';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // print_r($row);


        $rok_mes = substr($row['datum'],0,7);

        if ( !isset($data_poplatky[$rok_mes]) ) $data_poplatky[$rok_mes] = 0;
        if ( !isset($data_investice[$rok_mes]) ) $data_investice[$rok_mes] = 0;
        if ( !isset($data_splatky_jistiny[$rok_mes]) ) $data_splatky_jistiny[$rok_mes] = 0;
        if ( !isset($data_splatky_uroku[$rok_mes]) ) $data_splatky_uroku[$rok_mes] = 0;

        switch ( substr($row['typ'],0,1) ) {
            case 'P':   $data_poplatky[$rok_mes] += $row['castka'];
                        break;
            case 'I':   $data_investice[$rok_mes] += -$row['castka'];
                        break;
            case 'S':   $data_splatky_jistiny[$rok_mes] += $row['jistina'];
                        $data_splatky_uroku[$rok_mes] += $row['urok'];
                        break;
        }

    }

    if ( isset ( $data_poplatky ) ) ksort($data_poplatky);
    if ( isset ( $data_investice ) ) ksort($data_investice);
    if ( isset ( $data_splatky_jistiny ) ) ksort($data_splatky_jistiny);
    if ( isset ( $data_splatky_uroku ) ) ksort($data_splatky_uroku);


    // print_r($data_poplatky);

    // print_r($data_investice);

    // print_r($data_splatky_jistiny);

    // print_r($data_splatky_uroku);

    $a_y_investice = array();
    $a_x_investice = array();
    $a_y_splatky_uroku = array();
    foreach ( $data_investice as $key => $val ) {
        $a_y_investice[] = $val;
        $a_x_investice[] = $key;

        $a_y_splatky[] = $data_splatky_uroku[$key] + $data_splatky_jistiny[$key];
        $a_y_splatky_uroku[] = $data_splatky_uroku[$key];
        $a_y_splatky_jistiny[] = $data_splatky_jistiny[$key];
    }

    // print_r($a_x_investice);
    // print_r($a_y_investice);
    // print_r($a_y_splatky_uroku);

    // Globální graf všeho

    $graph = new Graph(1024,768);
    $graph->SetMargin(80,150,40,130);
    $graph->SetMarginColor('white');
     
    $graph->SetScale('intlin');
    $graph->title->Set('Investice, Splátky, Jistina a Úroky');
     
    $graph->SetYScale(0,'lin');
     
    $p1 = new BarPlot($a_y_investice);
    $p1->SetLegend('Investice');
    $graph->Add($p1);
     
    $p2 = new LinePlot($a_y_splatky);
    $p2->SetColor('green');
    $p2->mark->SetType(MARK_FILLEDCIRCLE,'green',0.5);
    $p2->mark->SetFillColor('green');
    $p2->SetLegend('Splátky celkem');
    $graph->AddY(0,$p2);

     
    $p3 = new LinePlot($a_y_splatky_jistiny);
    $p3->SetColor('red');
    $p3->mark->SetType(MARK_FILLEDCIRCLE,'red',0.5);
    $p3->mark->SetFillColor('red');
    $p3->SetLegend('Splátky jistiny');
    $graph->AddY(0,$p3);

     
    $p4 = new LinePlot($a_y_splatky_uroku);
    $p4->SetColor('blue');
    $p4->mark->SetType(MARK_FILLEDCIRCLE,'blue',0.5);
    $p4->mark->SetFillColor('blue');
    $p4->SetLegend('Splátky úroků');
    $graph->AddY(0,$p4);


    $graph->ynaxis[0]->SetColor('teal');

    $graph->xaxis->title->Set('Období');
    $graph->yaxis->title->Set('Suma investic');

    $graph->xaxis->SetTickLabels($a_x_investice);
    $graph->xaxis->SetLabelAngle(90);

    // Output line
    $graph->Stroke('Soubory/global.png');

    echo '<IMG SRC="Soubory/global.png?hash="'.filemtime('Soubory/global.png').'"></IMG>';

    // Graf splátek

    $graph1 = new Graph(1024,768);
    $graph1->img->SetAntiAliasing(false);
    $graph1->SetMargin(80,150,40,130);
    $graph1->SetMarginColor('white');
     
    $graph1->SetScale('intlin');
    $graph1->title->Set('Graf splátek');
     
    $p10 = new BarPlot($a_y_splatky);
    $p10->SetLegend('Splátky celkem');
    $graph1->Add($p10);
     
    $p30 = new LinePlot($a_y_splatky_jistiny);
    $p30->SetColor('red');
    $p30->SetLegend('Splátky jistiny');
    $p30->mark->SetType(MARK_FILLEDCIRCLE,'red',1);
    $p30->mark->SetFillColor('red');
    $graph1->Add($p30);

    
    $p40 = new LinePlot($a_y_splatky_uroku);
    $p40->SetColor('blue');
    $p40->mark->SetType(MARK_FILLEDCIRCLE,'blue',0.5);
    $p40->mark->SetFillColor('blue');
    $p40->SetFillColor('yellow');
    $p40->SetLegend('Splátky úroků');
    $graph1->Add($p40);

    $graph1->xaxis->SetTickLabels($a_x_investice);
    $graph1->xaxis->SetLabelAngle(90);


    $graph1->xaxis->title->Set('Období');
    $graph1->yaxis->title->Set('Suma');
    // Output line
    $graph1->Stroke('Soubory/splatky.png');

    echo '<IMG SRC="Soubory/splatky.png?hash="'.filemtime('Soubory/splatky.png').'"></IMG>';

    // číselný přehled po měsících
    // -----------------------------------------------------------------------------------------------
    // select year(datum) as rok, month(datum) as mesic, investor, typ, sum(castka) castka, sum(jistina) as jistina, sum(urok) as urok from pujcka, pohyb where pujcka.row_id = pohyb.pujcka_id group by rok, mesic, typ, investor
    $sql = "select year(datum) as rok, month(datum) as mesic, investor, typ, sum(castka) castka, sum(jistina) as jistina, sum(urok) as urok from pujcka, pohyb where pujcka.row_id = pohyb.pujcka_id group by rok, mesic, typ, investor";

    $stmt = $db->query($sql);
    // print_r($db->errorInfo());
    // print_r($sql);
    // echo 'Počet záznamů = '.$stmt->rowCount().'<BR>';

    $data = array();
    $min_rok = array();
    $min_rok['Z'] = 3000;
    $min_rok['B'] = 3000;
    $min_rok['X'] = 3000;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        if ($investor == 'X') $row['investor'] = 'X';

        if ($min_rok[$row['investor']] > $row['rok'] ) $min_rok[$row['investor']] = $row['rok'];

        $rok_mes = $row['rok'].'/'.$row['mesic'];
        $data[$row['investor']][$rok_mes][$row['typ']] = abs($row['castka']);
        if ($row['typ'] == 'Splátka půjčky') {
            $data[$row['investor']][$rok_mes]['Obdržená jistina'] = abs($row['jistina']);
            $data[$row['investor']][$rok_mes]['Obdržený úrok'] = abs($row['urok']);
        }
    }


    $sql = "select year(datum) as rok, month(datum) as mesic, typ, sum(castka) as castka from pohyb where pohyb.pujcka_id is null group by rok, mesic, typ";

    $stmt = $db->query($sql);
    // print_r($db->errorInfo());
    // print_r($sql);
    // echo 'Počet záznamů = '.$stmt->rowCount().'<BR>';

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rok_mes = $row['rok'].'/'.$row['mesic'];
        if ($rok_mes == '2017/10' && $row['typ'] == 'Nabití vaší peněženky') {
            $investor_uc = 'B';
        } else {
            $investor_uc = 'Z';
        }
        if ($investor == 'X') $investor_uc = 'X';
        $data[$investor_uc][$rok_mes][$row['typ']] = abs($row['castka']);
    }

    $akt_rok = date('Y');
    for ( $rok = $min_rok[$investor]; $rok <= $akt_rok; $rok++ ) {
        echo '<TABLE BORDER="1">';
        echo '<TR><TH COLSPAN="13" BGCOLOR="grey">'.$rok.'</TH></TR>';

        $tabulka_hlavicka = '<TR><TH>Popis</TH>';
        $tabulka_nabiti = '<TR><TD>Nabítí peněženky</TD>';
        $tabulka_investice = '<TR><TD>Investice</TD>';
        $tabulka_splatky = '<TR><TD>Obdrženo ve splátkách</TD>';
        $tabulka_jistina = '<TR><TD>Obdržená jistina</TD>';
        $tabulka_urok = '<TR><TD>Obdržený úrok</TD>';
        $tabulka_poplatek = '<TR><TD>Zonky poplatek</TD>';

        for ( $mesic = 1; $mesic <= 12; $mesic++ ) {
            $rok_mes = $rok.'/'.$mesic;

            $nabiti = ( isset($data[$investor][$rok_mes]['Nabití vaší peněženky']) ? number_format($data[$investor][$rok_mes]['Nabití vaší peněženky'],2,'.',' ').' Kč ' : '');
            $investice = ( isset($data[$investor][$rok_mes]['Investice na tržišti příběhů']) ? number_format($data[$investor][$rok_mes]['Investice na tržišti příběhů'],2,'.',' ').' Kč ' : '');
            $splatky = ( isset($data[$investor][$rok_mes]['Splátka půjčky']) ? number_format($data[$investor][$rok_mes]['Splátka půjčky'],2,'.',' ').' Kč ' : '');
            $jistina = ( isset($data[$investor][$rok_mes]['Obdržená jistina']) ? number_format($data[$investor][$rok_mes]['Obdržená jistina'],2,'.',' ').' Kč ' : '');
            $urok = ( isset($data[$investor][$rok_mes]['Obdržený úrok']) ? number_format($data[$investor][$rok_mes]['Obdržený úrok'],2,'.',' ').' Kč ' : '');
            $poplatek = ( isset($data[$investor][$rok_mes]['Poplatek za investování']) ? number_format($data[$investor][$rok_mes]['Poplatek za investování'],2,'.',' ').' Kč ' : '');


            $tabulka_hlavicka .= '<TH WIDTH="120">'.$rok_mes.'</TH>';
            $tabulka_nabiti .= '<TD ALIGN="RIGHT">'.$nabiti.'</TD>';
            $tabulka_investice .= '<TD ALIGN="RIGHT">'.$investice.'</TD>';
            $tabulka_splatky .= '<TD ALIGN="RIGHT">'.$splatky.'</TD>';
            $tabulka_jistina .= '<TD ALIGN="RIGHT">'.$jistina.'</TD>';
            $tabulka_urok .= '<TD ALIGN="RIGHT">'.$urok.'</TD>';
            $tabulka_poplatek .= '<TD ALIGN="RIGHT">'.$poplatek.'</TD>';
        }

        $tabulka_hlavicka .= '</TR>';
        $tabulka_nabiti .= '</TR>';
        $tabulka_investice .= '</TR>';
        $tabulka_splatky .= '</TR>';
        $tabulka_jistina .= '</TR>';
        $tabulka_urok .= '</TR>';
        $tabulka_poplatek .= '</TR>';

        echo $tabulka_hlavicka;
        echo $tabulka_nabiti;
        echo $tabulka_investice;
        echo $tabulka_splatky;
        echo $tabulka_jistina;
        echo $tabulka_urok;
        echo $tabulka_poplatek;

        echo '</TABLE>';
        echo '<BR>';
    }

    echo '</BODY>';
    echo '</HTML>';


?>
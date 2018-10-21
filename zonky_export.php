<?php

    global $scope;

    global $debug;

    $debug = false;

    $scope='SCOPE_FILE_DOWNLOAD';
    $scope='SCOPE_APP_WEB';

    $location = '';

    require_once('define_me.php');

    date_default_timezone_set('Europe/Prague');
    setlocale(LC_COLLATE,"cz_CZ.UTF-8");

    function refresh_token ( $refresh_token ) {

        global $scope;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.zonky.cz/oauth/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "refresh_token=$refresh_token&grant_type=refresh_token&scope=".$scope);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/x-www-form-urlencoded",
          "Authorization: Basic d2ViOndlYg=="
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $results = json_decode($response);

        return $results;
    }

    function download_zonky ( $url, $name ) {

        global $debug;
        global $x;
        global $y;

        $ch = curl_init();

        $scope = 'SCOPE_APP_WEB';

        curl_setopt($ch, CURLOPT_URL, "https://api.zonky.cz/oauth/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "username=$x&password=$y&grant_type=password&scope=".$scope);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/x-www-form-urlencoded",
        "Authorization: Basic d2ViOndlYg=="
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $results = json_decode($response);

        // POST na /export nastartuje exportovaní
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Bearer ".$results->access_token
            ));
    
        if ( $debug ) {
            echo '<HR>POST<HR>'.var_dump(curl_getinfo($ch)); 
            echo '<HR>';
        }
        
        $response = curl_exec($ch);

        echo '<HR>2. POST export<HR>';
        if ( $debug ) {
            var_dump($response); echo '<HR>';
            var_dump(curl_getinfo ($ch)); echo '<HR>';
        }
        $http_code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        while ( $http_code == 202 ) {
            sleep(5);
            flush();

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorization: Bearer ".$results->access_token
                ));


            $response = curl_exec($ch);

            echo '<HR>3. GET export - returning<HR>';
            if ( $debug ) {
                var_dump($response); echo '<HR>';
                var_dump(curl_getinfo ($ch)); echo '<HR>';
            } 
            $http_code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        flush();
        if ( $http_code == 204 ) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://api.zonky.cz/oauth/token");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
        
            curl_setopt($ch, CURLOPT_POST, TRUE);
        
            $scope = 'SCOPE_FILE_DOWNLOAD';
            curl_setopt($ch, CURLOPT_POSTFIELDS, "username=$x&password=$y&grant_type=password&scope=".$scope);
        
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic d2ViOndlYg=="
            ));
        
            $response = curl_exec($ch);
        
            echo '<HR>4. Token na file download<HR>';
            if ( $debug ) {
                var_dump($response);
                var_dump(curl_getinfo ($ch)); echo '<HR>';
            }
            curl_close($ch);
        
            flush();
            $results = json_decode($response);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url.'/data');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, TRUE);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer ".$results->access_token
            ));

            $response = curl_exec($ch);

            echo '<HR>5. GET export - data<HR>';
            if ( $debug ) {
                var_dump($response); echo '<HR>';
                var_dump(curl_getinfo ($ch)); echo '<HR>';
            }
            $http_code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
            if ( $http_code == 302 ) {
                $location = curl_getinfo ($ch);
                $location = $location['redirect_url'];
            }    
            curl_close($ch);
            flush();

            if ( $location != '' ) {
                // Download the file

                $pos_start = strpos($location,$name);
                $pos_end = strpos($location,'?');
                $filename = substr($location, $pos_start, $pos_end - $pos_start);

                set_time_limit(0);
                //This is the file where we save the information
                $fp = fopen (dirname(__FILE__) . '/Soubory/'.$filename, 'w+');
                //Here is the file we are downloading, replace spaces with %20
                $ch = curl_init($location);
                curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                // write curl response to file
                curl_setopt($ch, CURLOPT_FILE, $fp); 
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                // get curl response
                curl_exec($ch); 

                echo '<HR>6. GET export - data<HR>';
                echo 'filename='.$filename.'<BR>';
                if ( $debug ) {
                    var_dump($response); echo '<HR>';
                    var_dump(curl_getinfo ($ch)); echo '<HR>';
                }
                curl_close($ch);
                fclose($fp);

                // Upload the file to zonky_load
                $ch = curl_init();

                $file_name_with_full_path = dirname(__FILE__) . '/Soubory/'.$filename;
                if (function_exists('curl_file_create')) { // php 5.5+
                    $cFile = curl_file_create($file_name_with_full_path,'application/vnd.ms-excel',$filename);
                } else { // 
                    $cfile = new CURLFile($file_name_with_full_path,'application/vnd.ms-excel',$filename);
                }
                $post = array( 'submit' => 'sakra', 'fileToUpload' => $cFile);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://rankxaroth.wz.cz/zonky_load.php');
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_HTTPHEADER,array('User-Agent: Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15','Referer: http://someaddress.tld','Content-Type: multipart/form-data'));

                $response = curl_exec($ch);

                echo '<HR>7. POST zonky_load<HR>';
                if ( $debug ) {
                    var_dump($response); echo '<HR>';
                    var_dump(curl_getinfo ($ch)); echo '<HR>';
                }
                $http_code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

            }

        }
   
        flush();
    }
    
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.zonky.cz/oauth/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    curl_setopt($ch, CURLOPT_POST, TRUE);

    curl_setopt($ch, CURLOPT_POSTFIELDS, "username=$x&password=$y&grant_type=password&scope=".$scope);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Content-Type: application/x-www-form-urlencoded",
      "Authorization: Basic d2ViOndlYg=="
    ));

    $response = curl_exec($ch);

    if ( $debug ) {
        var_dump($response);
    }
    curl_close($ch);
    
    $results = json_decode($response);

    echo '<HTML>';
    echo '<HEAD><TITLE>Zonky Export v 1.0</TITLE>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '</HEAD>';
    echo '<BODY>';

    if ( $results ) {

        download_zonky ( 'https://api.zonky.cz/users/me/investments/export', 'investice');
        download_zonky ( 'https://api.zonky.cz/users/me/wallet/transactions/export', 'transakce');

    } else {
        echo 'Chyba autorizace '.curl_errno($ch).' - '.curl_error($ch).'<BR>';
        var_dump ($ch);
        print_r($ch);
        curl_close($ch);
    }

    echo '</BODY>';
    echo '</HTML>';
    
?>
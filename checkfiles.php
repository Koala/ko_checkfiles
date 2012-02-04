<?php
/**
 * Checkfiles vergleicht alle Dateien ab einem bestimmten Pfad mit Dateien 
 * aus einer Referenzdatei. 
 * 
 * Die Referenzdatei kann nur neu angelegt werden, wenn sie noch nicht existiert.
 * Dies soll ein versehentliches Ueberschreiben der Datei verhindern.
 * 
 * 
 * @author Sven.Eichler@redaxo.org
 * @license GPL
 * @version 1.2 - 28.05.2010
 */


/**
 * Laufzeit start
 * @see $setup['Arbeitszeit_Ausgabe']
 */
$time_start = microtime(true); 

/**
 * Startpfad
 * 
 * Es koennen mehrere durch Komma getrennte Pfade angegeben werden.
 * z.B.: array('redaxo/include/classes/','redaxo/include/pages/')
 * Damit wuerden nur diese Unterverzeichnisse erfasst und ausgewertet werden.
 * 
 * @param array
 */
$setup['pfad'] = array('.'); 

/**
 * enthaelt die Verzeichnis/Datei Uebersicht
 * @param string
 */
$setup['textdatei'] = 'checkfiles.txt';

/**
 * Zeilenumbruch dient nur der Formatierung der Ausgabe im Browser
 * @param string
 */
$setup['umbruch'] = "<br />\n";

/**
 * Arbeitszeitausgabe
 * @param bool
 */
$setup['Arbeitszeit_Ausgabe'] = false;



/**
 * Dateipuffer (interne Variable)
 * @param array
 * @access private
 */
$setup['dateiens'] = array();




/**
 * Ermittelt die Unterschiede zur Referenzdatei und gibt diese aus
 *
 * @param bool $writeReferenz - wenn true dann wird die Referenzdatei neu geschrieben
 * @return string
 */
function dateiAusgabe($writeReferenz = false) {
  global $setup;
  $error = '';
  $rueckgabe = '';
  
  foreach ($setup['pfad'] as $val) {
  //  echo 'Pfad: '.$val.$setup['umbruch'];
    if ($error = dir_size($val) !== true) {
      return $error;
    }
  }
  $file1 = array();
  if (is_file ($setup['textdatei'])) {
    $file1 = unserialize (file_get_contents ($setup['textdatei']));
  } else if (!$writeReferenz){
    return false;
  }
  
  $array_a =& $file1;
  $array_b =& $setup['dateiens'];

  // tausche Checksum und Dateiname im Array fuer zweiten testdurchlauf
  $array_c = array_flip($array_a);
  $array_d = array_flip($array_b);
  
  // der erste Test gilt dem Dateinamen
  // der zweite Test checkt die md5-Summe
  for ($i = 1; $i <= 2; $i++) {
    switch ($i) {
      case 1: $array_1 = $array_a; $array_2 = $array_b; break;
      case 2: $array_1 = $array_c; $array_2 = $array_d; break;
    }
    
    //    if ($filedifference = array_diff (array_merge ($array_a, $array_b), array_intersect ($array_a, $array_b))) {
    $filedifference = filedifferenze((array) $array_1, (array) $array_2);
    if ($i == 2) {
      $filedifference = array_flip($filedifference);
      //print_r($filedifference);
    }
    
    // filtere eigene Checkdatei heraus
    // Warum auch immer, aber diese Datei erscheint immer als Differenze!
    if (in_array ('./'.$setup['textdatei'], $filedifference)) { 
      // temporaere Variable
      $filedifference_tmp = array ();
      foreach ($filedifference as $key => $value) {
        if (trim ($value) !== "./".$setup['textdatei']) {
          $filedifference_tmp[$key] = $value;
        }
      }
      $filedifference = $filedifference_tmp;
      unset ($filedifference_tmp);
    }
    

    if ($filedifference) {
      if ($writeReferenz) {
        $rueckgabe = '<span style="font-weight:bold; font-size: 1em;">Schreibe folgende Dateien in "<span style="font-style:italic;">'.$setup['textdatei'].'</span>": </span>'.$setup['umbruch'];
      } else {
        if ($i == 2) {
          $rueckgabe .= '<span style="font-weight:bold; font-size: 1em;">Die unterschiedlichen Dateien sind: </span>'.$setup['umbruch'];
        } else {
          $rueckgabe .= '<span style="font-weight:bold; font-size: 1em;">Die fehlenden Dateien sind: </span>'.$setup['umbruch'];
        }
      }
      
      $rueckgabe .= implode ($setup['umbruch'], $filedifference).$setup['umbruch'].$setup['umbruch'];
      // Schreibe Referenzdatei
      if ($writeReferenz) {
        if ($error = write_file(serialize ($setup['dateiens'])) === true) {
          $rueckgabe .= $setup['umbruch'].$setup['umbruch'].'<span style="font-weight:bold; font-size: 1em;">Referenzdatei neu geschrieben.</span>';
        } else {
          $rueckgabe .= $error.$setup['umbruch'];
        }
      }
//      break;
    } else {
      if (!$writeReferenz) {
        $rueckgabe = 'Keine fehlende Datei gefunden.'.$setup['umbruch'];
      } else {
        $rueckgabe = '<span style="font-weight:bold; font-size: 1em;">Keine Unterschiede gefunden.</span>';
        $rueckgabe .= $setup['umbruch'].'Referenzdatei wurde nicht neu geschrieben.';
      }
    }
  }
  
  return $rueckgabe;
}


/**
 * Geht durch alle Verzeichnisse und liest die Dateinamen aus.
 * Diese werden inkl. Verzeichnispfad in einem Array abgelegt.
 *
 * @param string $d Startverzeichnis
 * @return mixed
 */
function dir_size($d='.') {
  global $setup;
  $d = (substr($d,-1)=='/') ? $d : $d.'/'; 
  clearstatcache(); 
  if ($dir = @opendir($d)) {
    while (($f=readdir($dir)) !== FALSE) 
      if (filetype($d.$f) != 'dir') { 
        $Dateipfad = $d.$f;
        $Dateipfad_md5 = md5_file($Dateipfad);
        // mit Zeilenumbruch ist keine Dateinamenpruefung moeglich, aber zur Ausgabe ist es brauchbar
        // bleibt ersteinmal kommentiert drin, basta!
//        $setup['dateiens'][$Dateipfad_md5] = $Dateipfad."\n";
        $setup['dateiens'][$Dateipfad_md5] = $Dateipfad;
        
      } else if (!in_array($f,array('.','..'))) { 
        $t = dir_size($d.$f.'/'); 
      }
    closedir($dir); 
  } else {
    return 'Fehler bei '.$d; 
  }
  return true;
}

/**
 * Schreibe Datei
 *
 * @param string $dateien - Inhalt welcher geschrieben werden soll
 * @return bool/string - Im Fehlerfall Rueckgabe einer Fehlermeldung
 */
function write_file($dateien) {
  global $setup;
  $filename = $setup['textdatei'];
  $content = $dateien;
  // Let's make sure the file exists and is writable first.
//  if (is_writable($filename)) {
      // In our example we're opening $filename in append mode.
      // The file pointer is at the bottom of the file hence
      // that's where $somecontent will go when we fwrite() it.
      if (!$handle = fopen($filename, 'w+')) {
          return "Cannot open file ($filename)";
          //exit;
      }
      // Write $somecontent to our opened file.
      if (fwrite($handle, $content) === FALSE) {
          return "Cannot write to file ($filename)";
          //exit;
      }
      //echo "Success, wrote ($content) to file ($filename)";
      fclose($handle);
      // versuche Dateirechte zu setzen
      @chmod ($filename, octdec(666));
/*  } else {
      echo "The file $filename is not writable";
  }*/
  return true;
}


/**
 * Finde Unterschiede in zwei Arrays
 *
 * @param array $array_1
 * @param array $array_2
 * @return array mit den Unterschieden zwischen $array_1 und $array_2
 */
function filedifferenze($array_1, $array_2) {
  return array_diff (array_merge ($array_1, $array_2), array_intersect ($array_1, $array_2));
}


/**
 * Pruefe Schreibrechte
 * 
 * @param $file Datei/Verzeichnis das geprueft werden soll
 * @return bool 
 */
function checkWriteAccess($file = '.') {
  if (is_writable($file)) {
      return true;
  } else {
      return false;
  }
}




?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
  <title>Pr&#252;fe Dateien</title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>

<h1>Dateiprüfung einer Redaxoinstallation</h1>
<form action="checkfiles.php" method="post">
    <p>
      <input type="radio" id="alleDateien" name="Dateicheck" value="alleDateien" />
      <label for="alleDateien">alle Dateien prüfen</label>
    </p>
    <p>
    <?php 
    if (checkWriteAccess()) {
      echo '<input type="radio" id="DateiNeuSchreiben" name="Dateicheck" value="DateiNeuSchreiben" />
      <label for="DateiNeuSchreiben">Referenzdatei neu anlegen</label>';
    } else {
      echo '<input type="radio" id="DateiNeuSchreiben" name="" value="DateiNeuSchreiben" />
      <label for="DateiNeuSchreiben"><span style="text-decoration:line-through;">Referenzdatei neu anlegen</span>
      <span style="font-weight:bold; color:red;">Keine Schreibrechte vorhanden!</span></label>';
    }
    ?>
    </p>
    <p>
      <input type="submit" name="sendit" value="Checkit" />
    </p>
</form>

<?php




if (isset($_POST['Dateicheck']) and $_POST['Dateicheck'] == 'alleDateien') {
  // Ausgabe der unterschiedlichen Dateien
  echo '<p>'.dateiAusgabe().'</p>';
}

if (isset($_POST['Dateicheck']) and $_POST['Dateicheck'] == 'DateiNeuSchreiben') {
  // Schreibe Referenzdatei neu
  echo '<p>'.dateiAusgabe(true).'</p>';
  //dateiAusgabe(true);
}

if (!is_file ($setup['textdatei'])) {
  $rueckgabe = '<span style="font-weight:bold; font-size: 1em;">Referenzdatei ist nicht vorhanden oder konnte nicht gelesen werden.'.$setup['umbruch'];
  $rueckgabe .= 'Dateivergleich nicht m&#246;glich.</span>'.$setup['umbruch'].$setup['umbruch'];
  echo $rueckgabe;
}


if ($setup['Arbeitszeit_Ausgabe']) {
  $time_end = microtime(true); 
  $time = $time_end - $time_start; 
  echo "<p>Ende: $time Sekunden\n</p>"; 
}

?>
<p>&#160;</p>
<h2>Beschreibung</h2>
<p>
Checkfiles vergleicht alle Dateien ab einem bestimmten Pfad mit Dateien 
aus einer Referenzdatei. 
</p>
<p>
Die Referenzdatei kann nur neu angelegt werden, wenn sie noch nicht existiert.<br />
Dies soll ein versehentliches Überschreiben der Datei verhindern.
</p>

<p>
Erscheint die Meldung "<span style="color:red;">Keine Schreibrechte vorhanden!</span>", so kann die Referenzdatei nicht angelegt werden.<br />
Überprüfe die Schreibrechte des Verzeichnises, in dem sich die checkfiles.php befindet.
</p>


<p style="font-style:italic;">
<br />
Version: 1.2 - 28.05.2010 by Koala
</p>


</body>
</html>
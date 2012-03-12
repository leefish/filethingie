<?php
/**
 * @file
 * ZIP plugin for File Thingie.
 * Author: Bonuxlee
 * v1.01 
 */

/**
 * Implementation of hook_info.
 */
function ft_zip_info() {
   return array(
     'name' => 'Zip: Enabling download zipped file/folder.',
   );
}


/**
 * get list of file from folder
 */
function ft_zip_getfiles($dir){
  $filelist = array();
  if ( file_exists($dir) ){
    if (is_dir($dir)){
      if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
          if ($file == '.' || $file == '..') continue;
          if (is_file($dir.'/'.$file)){
            $filelist[] = $dir.'/'.$file;
          }else{
            $filelist = array_merge($filelist, ft_zip_getfiles($dir.'/'.$file));
          }
        }
        closedir($dh);
        return $filelist;
      }
    }else{
      return array($dir);
    }
  }else{
    return array();
  }
}

/**
 * Implementation of hook_page.
 * Zip current file/folder 
 */
function ft_zip_page($act) {
  global $ft;
  if ($act == 'zip') {
    $_REQUEST['file'] = trim(ft_stripslashes($_REQUEST['file'])); // nom de fichier/repertoire

    $zip = new zipfile();
    if ($ft["plugins"]["zip"]["filebuffer"]){
      $zip->setOutFile($ft["plugins"]["zip"]["filebuffer"]);
    }
    $substr_base = strlen(ft_get_dir()) + 1;
    foreach ( ft_zip_getfiles(ft_get_dir().'/'.$_REQUEST['file']) as $file) {
      $filename = substr($file,$substr_base);
      $filesize = filesize($file);
      if ($filesize > 0) {
        $fp = fopen($file, 'r');
        $content = fread($fp, $filesize);
        fclose($fp);
      } else {
        $content = '';
      }
      $zip->addfile($content, $filename);
    }

    if ($ft["plugins"]["zip"]["filebuffer"]){
      $zip->finish();
      $filesize = filesize($ft["plugins"]["zip"]["filebuffer"]);
    }else{
      $archive = $zip->file();
      $filesize = strlen($archive);
    }

    header('Content-Type: application/x-zip');
    header('Content-Disposition: inline; filename="'.$_REQUEST['file'].'.zip"');
    header('Content-Length: '.$filesize);

    if ($ft["plugins"]["zip"]["filebuffer"]){
      readfile($ft["plugins"]["zip"]["filebuffer"]);
      unlink($ft["plugins"]["zip"]["filebuffer"]);
    }else{
      echo $archive;
    }
    exit;
  }
}

/**
 * Implementation of hook_fileextras.
 * add zip class on file/folder to activate zip action
 */
function ft_zip_fileextras($file, $dir) {
  return 'zip';
}


function ft_zip_add_js_call() {
  global $ft;
  if ($ft["plugins"]["zip"]["active"]){
    return 'ft.fileactions.zip = {type: "sendoff", link: "'.t('zip').'", text: "'.t('Do you want to get zipped folder ?').'", button: "'.t('Yes, Give me zip file').'"};';
  }  
}




/* $Id: zip.lib.php,v 2.4 2004/11/03 13:56:52 garvinhicking Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Zip file creation class.
 * Makes zip files.
 *
 * Based on :
 *
 *  http://www.zend.com/codex.php?id=535&single=1
 *  By Eric Mueller <eric@themepark.com>
 *
 *  http://www.zend.com/codex.php?id=470&single=1
 *  by Denis125 <webmaster@atlant.ru>
 *
 *  a patch from Peter Listiak <mlady@users.sourceforge.net> for last modified
 *  date and time of the compressed file
 *
 * patched by d. guez <davguez@free.fr> to add the possibility of sending
 * the zipped file as a stream via http connection
 *
 * Official ZIP file format: http://www.pkware.com/appnote.txt
 *
 * updated by david guez : file buffer
 *   
 * @access  public
 */
class zipfile
{
    /**
     * Array to store compressed data
     *
     * @var  array    $datasec
     */
    var $datasec      = array();

    /**
     * Central directory
     *
     * @var  array    $ctrl_dir
     */
    var $ctrl_dir     = array();

    /**
     * End of central directory record
     *
     * @var  string   $eof_ctrl_dir
     */
    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    /**
     * Last offset position
     *
     * @var  integer  $old_offset
     */
    var $old_offset   = 0;

    /**
     * File where the data are written
     *
     * @var  handle  $out_handle
     */
     var $out_handle   = 0;
     
     var $out_size=0;




    /**
     * Converts an Unix timestamp to a four byte DOS date and time format (date
     * in high two bytes, time in low two bytes allowing magnitude comparison).
     *
     * @param  integer  the current Unix timestamp
     *
     * @return integer  the current date in a four byte DOS format
     *
     * @access private
     */
    function unix2DosTime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
            $timearray['year']    = 1980;
            $timearray['mon']     = 1;
            $timearray['mday']    = 1;
            $timearray['hours']   = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        } // end if

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    } // end of the 'unix2DosTime()' method


    /**
     * Define the file where the data will be stored
     *
     * @param  string  the file name
     *
     * @return bool does the file association succeeded ?
     *
     * @access public
     */

    function setOutFile($outName)
    {
    	$this->out_handle = fopen($outName,'wb');
    	return ($this->out_handle != 0);
    } // end of the 'setOutFile()' method


    /**
     * Adds "file" to archive
     *
     * @param  string   file contents
     * @param  string   name of the file in the archive (may contains the path)
     * @param  integer  the current timestamp
     *
     * @access public
     */
    function addFile($data, $name, $time = 0)
    {
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len   = strlen($zdata);
        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        // nijel(2004-10-19): this seems not to be needed at all and causes
        // problems in some cases (bug #1037737)
        //$fr .= pack('V', $crc);                 // crc32
        //$fr .= pack('V', $c_len);               // compressed filesize
        //$fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
    	if ( $this->out_handle == 0){
            $this -> datasec[] = $fr;
	} else {
	    fwrite($this->out_handle,$fr);
	    $this->out_size += strlen($fr);
	}

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset ); // relative offset of local header
        $this -> old_offset += strlen($fr);

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
    } // end of the 'addFile()' method

    /**
     * Dumps out file
     *
     * @return  string  the zipped file
     *
     * @access public
     */
    function file()
    {
    	if ($this->out_handle != 0) return;
        $data    = implode('', $this -> datasec);
        $ctrldir = implode('', $this -> ctrl_dir);

        return
            $data .
            $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', strlen($data)) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    } // end of the 'file()' method

    /**
     * Terminate the file writing
     *
     * @return  void
     *
     * @access public
     */
    function finish()
    {
    	if ($this->out_handle == 0) return;
        $ctrldir = implode('', $this -> ctrl_dir);

        $fin =    $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', $this->out_size) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
	    
    	fwrite($this->out_handle,$fin);
	fclose($this->out_handle);
    } // end of the 'finish()' method
} // end of the 'zipfile' class
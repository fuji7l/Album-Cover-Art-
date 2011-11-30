<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <rfujimoto@imap.cc> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Richard Fujimoto
 * ----------------------------------------------------------------------------
 */

require_once dirname(__FILE__) . '/saAlbumCoverArtSearchAmazon.class.php';

/**
 * The main class which should be instantiated to get music album covers.
 *
 * 
 * @package  saAlbumCoverArt
 * @author   Richard Fujimoto <rfujimoto@imap.cc>
 * @license  The Beer-ware License
 * @see      saAlbumCoverArtSearch
 * @link     http://www.simplyamused.com
 */
class saAlbumCoverArt
{
  protected
    $albumName   = '',
    $artistName  = '',
    $baseDir     = '',
    $settings    = array();


  /**
   * Inits the object
   * @param string $album     the album name to search for
   * @param string $artist    the artist name to search for
   * @param string $baseDir   Base directory to search for images and write
   *                          images to
   * @param array  $settings  Overwrite the default settings or configure class
   *                          on instantiting.
   * @access public
   * @throws Exception if base directory is not readable/writable
   * @return saAlbumCoverArt  returns instance of class for method chaining
   */
  public function __construct($album, $artist, $baseDir = '', $settings = array())
  {
    $this->albumName  = $album;
    $this->artistName = $artist;
    $this->baseDir    = ($baseDir == '') ? './' : $baseDir;

    if (!is_readable($this->baseDir) || !is_writable($this->baseDir))
    {
      throw new Exception('Unable to read or write the base directory. Please '
        . 'check the permissions of ' . $this->baseDir);
    }

    // set the default config to determine what needs to be parsed
    $this->settings = array_merge(
      array(
        'search'       => array(
                                'amazon'   => 1,
                          ),
        'save'         => 1,
        'dirseparator' => '/',
      ),
      $settings
    );

    return $this;
  }

  /**
   * Executes the album cover art search based on the search settings
   * @access public
   * @throws Exception when it can't get a cover
   * @return bool Return true if an image is found
   */
  public function execute()
  {
    if ($this->searchDirectory())
    {
       return $this->getCoverFile();
    }

    foreach ($this->settings['search'] as $name => $enabled)
    {
      if ($enabled != 1) { continue; }
      $className = 'saAlbumCoverArtSearch' . ucfirst($name);

      $search = new $className($this);

      if ($search->execute() == true)
      {
        return $this->getCoverFile();
      }
    }
    throw new Exception('Can\'t get a cover for ' . $this->getSearchName());
    return false;
  }

  // ACCESSORS

  /**
   * Concat the artist and album name
   * @access public
   * @return string the string to search for
   */
  public function getSearchName()
  {
    return $this->artistName . ' ' . $this->albumName;
  }

  /**
   * Accessor for the base directory
   * @access public
   * @return string the base directory
   */
  public function getBaseDir()
  {
    return $this->baseDir;
  }

  /**
   * Sets the cover file
   * @param string $file the name of the file
   * @access public
   * @return void
   */
  public function setCoverFile($file)
  {
    $this->coverFile = $file;
  }

  /**
   * Determine if a cover has been found
   * @access public
   * @return bool true if a cover file has been set
   */
  public function hasValidCoverFile()
  {
    return ($this->coverFile == '');
  }

  /**
   * Searches the base directory for a cover image
   * @return bool true if cover is found in this basedir
   */
  public function searchDirectory()
  {
    $coverImages = array();

    foreach (new DirectoryIterator($this->getBaseDir()) as $file)
    {
      // Test if this file we're reading is an image
      if (@exif_imagetype($file->getPathname()) !== false)
      {
        $coverImages[] = $file->getFilename();

        // search order defined in array.
        foreach (array('front', 'cover') as $needle)
        {
          if (stripos($file->getFilename(), $needle) !== false)
          {
            // found the cover in the directory - break out of DirectoryIterator
            $this->setCoverFile($file->getFilename());
            return true;
          }
        }
      }
    }

    // We did not find a valid cover in the directory
    if (count($coverImages) == 1)
    {
      // if the file doesn't match our search, but only 1 image is present,
      //  assume it's the cover image
      $this->setCoverFile($coverImages[0]);
      return true;
    }

    return false;
  }

  /**
   * Gets the cover file name
   * @access public
   * @return string 
   */
  public function getCoverFile()
  {
    return $this->coverFile;
  }

  /**
   * Get the full path - convienence method
   * @access public
   * @return string
   */
  public function getFullCoverFilePath()
  {
    return $this->getBaseDir() . $this->settings['dirseparator'] . $this->getCoverFile();
  }
  
  /**
   * Gets a setting.  If a setting does not exist, return 0
   * @param $name the name of the setting
   * @return mixed
   * @access public
   */
  public function getSetting($name)
  {
    return (isset($this->settings[$name])) ? $this->settings[$name] : 0;
  }
}

<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <rfujimoto@imap.cc> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Richard Fujimoto
 * ----------------------------------------------------------------------------
 */


/**
 * Abstract class to define how the search works
 *
 * 
 * @package  saAlbumCoverArt
 * @author   Richard Fujimoto <rfujimoto@imap.cc>
 * @license  The Beer-ware License
 * @see      saAlbumCoverArt
 * @link     http://www.simplyamused.com
 */
abstract class saAlbumCoverArtSearch
{
  // Default name which will be appended to the cover file name
  const DEFAULT_FILE_NAME    = 'cover.jpg';

  protected
    $albumCover  = null,
    $coverFile   = '';

  /**
   * Instantiates the search class.  Returns self for method chaining
   * @param saAlbumCoverArt $albumCover the controlling class
   * @return saAlbumCoverArtSearch
   */
  public function __construct(saAlbumCoverArt $albumCover)
  {
    $this->albumCover = $albumCover;
    return $this;
  }


  /**
   * Searches for the album's cover art.  This method must be redefined.
   * @access public
   * @return bool 
   */
  abstract public function execute();
  

  /**
   * Saves the file to the harddrive.
   * @param string $file the file name to save it as
   * @param string $data the image data to save (binary)
   * @access public
   * @throws exception when it can't save the file
   * @return bool
   */
  public function save($file, $data)
  {
    if (!@file_put_contents( $file, $data ))
    {
      throw new Exception ('Failed to save ' . $file);
      return false;
    }
    return true;
  }

  /**
   * Returns a file-system safe name stripping out problamatic file names.  Can
   * and probably should be expanded, but for now it works.  Just add more lines
   * like $name = [function to clean the name...]
   * @returns string
   */
  protected function getSafeFileName()
  {
    $name = str_replace(array(' ', '/', "'"), '_', $this->albumCover->getSearchName());
    $name = str_replace('#', 'No.', $name);
    // add more here

    return $name . '-' . self::DEFAULT_FILE_NAME;
  } 

}

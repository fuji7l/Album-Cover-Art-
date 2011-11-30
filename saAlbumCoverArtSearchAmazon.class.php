<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <rfujimoto@imap.cc> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Richard Fujimoto
 * ----------------------------------------------------------------------------
 */

require_once dirname(__FILE__) . '/saAlbumCoverArtSearch.class.php';

/**
 * Use Amazon's service to fetch album covers
 *
 * 
 * @package  saAlbumCoverArt
 * @author   Richard Fujimoto <rfujimoto@imap.cc>
 * @license  The Beer-ware License
 * @see      saAlbumCoverArtSearch
 * @link     http://www.simplyamused.com
 */

class saAlbumCoverArtSearchAmazon extends saAlbumCoverArtSearch
{
  // sign up to get the access key id and secret access key
  // at http://aws.amazon.com
  //  -- this won't work until you enter your keys below
  const AMZN_ACCESSKEYID     = '';
  const AMZN_SECRETACCESSKEY = '';

  // you can leave the rest as-is
  const AMZN_REQ_HOST        = 'ecs.amazonaws.com';
  const AMZN_REQ_PATH        = '/onca/xml';

  protected $amazonUrl   = '';


  /**
   * @see AlbumCoverArtSearch::execute()
   * @throws Exception when the GET request for amazon fails
   * @return bool true if a cover was successfully found in this search.
   */
  public function execute()
  {
    if (!($data = file_get_contents($this->getAmazonUrl())))
    {
      throw new Exception('Cannot open URL ' . $this->getAmazonUrl());
    }

    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, $data, $values, $index);
    xml_parser_free($parser);

    $imgUrl = '';

    foreach (array('LargeImage', 'MediumImage', 'SmallImage') as $imgSize)
    {
      if (isset($index[$imgSize]))
      {

        $urlIndex = $index[$imgSize][0] + 1;
        if ($values[$urlIndex]['tag'] == 'URL')
        {
          $imgUrl = $values[$urlIndex]['value'];
          break;
        }
      }
    }

    // no image found, return false
    if ($imgUrl == '') { return false; }
    
    $this->albumCover->setCoverFile($this->getSafeFileName());
    if ($this->albumCover->getSetting('save') == 1)
    {
      if (!$this->save(
             $this->albumCover->getBaseDir()
             . $this->albumCover->getSetting('dirseparator')
             . $this->getSafeFileName(),
             file_get_contents($imgUrl)
      ))
      {
        return false;
      }
    }

    return true;

  }


  /**
   * Generates a proper REST request to Amazon's web services, fully signed
   * @param array $params an optional array which can overwrite the defaults
   * @return string The full URL to query Amazon
   */
  protected function getAmazonUrl(array $params = array())
  {
    if ($this->amazonUrl == '' || count($params) > 0)
    {
      $query = array_merge(
        array(
          'Service'        => 'AWSECommerceService',
          'AWSAccessKeyId' => self::AMZN_ACCESSKEYID,
          'Operation'      => 'ItemSearch',
          'Version'        => '2009-11-01',
          'Timestamp'      => gmdate('Y-m-d\TH:i:s\Z'),
          'ResponseGroup'  => 'ItemAttributes,Images',
          'SearchIndex'    => 'Music',
          'salesrank'      => 'Bestselling',
          'Keywords'       => $this->albumCover->getSearchName(),
        ),
        $params
      );
      // assign to temp var first to ensure that Signature param is not
      //  passed to the getSignature() method
      $signature = $this->getSignature($query);
      $query['Signature'] = $signature;

      $this->amazonUrl = 'http://' . self::AMZN_REQ_HOST .
        self::AMZN_REQ_PATH . '?' . $this->getUrlQuery($query);
    }
    return $this->amazonUrl;
  }

  /**
   * Takes the array holding the query, sorts it based on the key and then
   * returns a numerically indexed array of url-encoded query arguements.
   *
   * @param array $params the query array to url encode.
   * @return array The url-encoded array of query keys and values.
   */
  protected function getUrlQuery(array $params)
  {
    $newArray = array();
    ksort($params);
    foreach ($params as $key => $value)
    {
      $newArray[] = rawurlencode($key) . '=' . rawurlencode($value);
    }
    return implode('&', $newArray);
  }


  /**
   * Signs the query according to Amazon's ECS requirements
   * and returns a base64 encoded version. No URL encoding is done in this
   * function.
   * @param array $query the query array that should be signed
   * @return string
   */
  protected function getSignature(array $query)
  {
    // hash the AWS Secret Key and generate a signature
    $hash = hash_hmac(
      'sha256',
      "GET\n"
      . self::AMZN_REQ_HOST . "\n"
      . self::AMZN_REQ_PATH . "\n"
      . $this->getUrlQuery($query),
      self::AMZN_SECRETACCESSKEY
    );

    $signature  = '';
    $hashLength = strlen($hash);
    for ($i = 0; $i < $hashLength; $i += 2)
    {
      $signature .= chr(hexdec(substr($hash, $i, 2)));
    }
    return base64_encode($signature);
  }

 
}




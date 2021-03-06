<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Audioscrobbler
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Service\Audioscrobbler;

use Zend\Http,
    Zend\Service\AbstractService;

/**
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Audioscrobbler
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Audioscrobbler extends AbstractService
{
    /**
     * Array that contains parameters being used by the webservice
     */
    protected $params;

    /**
     * Holds error information (e.g., for handling simplexml_load_string() warnings)
     */
    protected $error = null;

    /**
     * Sets up character encoding, instantiates the HTTP client, and assigns the web service version.
     */
    public function __construct()
    {
        $this->set('version', '1.0');

        iconv_set_encoding('output_encoding', 'UTF-8');
        iconv_set_encoding('input_encoding', 'UTF-8');
        iconv_set_encoding('internal_encoding', 'UTF-8');
    }

    /**
     * Returns a field value, or false if the named field does not exist
     *
     * @param  string $field
     * @return string|false
     */
    public function get($field)
    {
        if (array_key_exists($field, $this->params)) {
            return $this->params[$field];
        } else {
            return false;
        }
    }

    /**
     * Generic set action for a field in the parameters being used
     *
     * @param  string $field Name of field to set
     * @param  string $value Value to assign to the named field
     * @return Audioscrobbler Provides a fluent interface
     */
    public function set($field, $value)
    {
        $this->params[$field] = urlencode($value);

        return $this;
    }

    /**
     * Protected method that queries REST service and returns SimpleXML response set
     *
     * @throws Exception\RuntimeException
     * @param  string $service  Name of Audioscrobbler service file we're accessing
     * @param  string $params   Parameters that we send to the service if needded
     * @return \SimpleXMLElement Result set
     */
    protected function getInfo($service, $params = null)
    {
        $service = (string) $service;
        $params  = (string) $params;

        if ($params === '') {
            $this->getHttpClient()->setUri("http://ws.audioscrobbler.com{$service}");
        } else {
            $this->getHttpClient()->setUri("http://ws.audioscrobbler.com{$service}?{$params}");
        }

        $response     = $this->getHttpClient()->send();
        $responseBody = $response->getBody();

        if (preg_match('/No such path/', $responseBody)) {
            throw new Exception\RuntimeException('Could not find: ' . $this->getHttpClient()->getUri());
        } elseif (preg_match('/No user exists with this name/', $responseBody)) {
            throw new Exception\RuntimeException('No user exists with this name');
        } elseif (!$response->isSuccess()) {
            throw new Exception\RuntimeException('The web service ' . $this->getHttpClient()->getUri() . ' returned the following status code: ' . $response->getStatusCode());
        }

        set_error_handler(array($this, 'errorHandler'));

        if (!$simpleXmlElementResponse = simplexml_load_string($responseBody)) {
            restore_error_handler();
            $exception = new Exception\RuntimeException('Response failed to load with SimpleXML');
            $exception->error    = $this->error;
            $exception->response = $responseBody;
            throw $exception;
        }

        restore_error_handler();

        return $simpleXmlElementResponse;
    }

    /**
    * Utility function to get Audioscrobbler profile information (eg: Name, Gender)
     *
    * @return array containing information
    */
    public function userGetProfileInformation()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/profile.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function get this user's 50 most played artists
     *
     * @return array containing info
    */
    public function userGetTopArtists()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/topartists.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function to get this user's 50 most played albums
     *
     * @return \SimpleXMLElement object containing result set
    */
    public function userGetTopAlbums()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/topalbums.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function to get this user's 50 most played tracks
     * @return SimpleXML object containing resut set
    */
    public function userGetTopTracks()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/toptracks.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function to get this user's 50 most used tags
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetTopTags()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/tags.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns the user's top tags used most used on a specific artist
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetTopTagsForArtist()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/artisttags.xml";
        $params = "artist={$this->get('artist')}";
        return $this->getInfo($service, $params);
    }

    /**
     * Utility function that returns this user's top tags for an album
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetTopTagsForAlbum()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/albumtags.xml";
        $params = "artist={$this->get('artist')}&album={$this->get('album')}";
        return $this->getInfo($service, $params);
    }

    /**
     * Utility function that returns this user's top tags for a track
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetTopTagsForTrack()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/tracktags.xml";
        $params = "artist={$this->get('artist')}&track={$this->get('track')}";
        return $this->getInfo($service, $params);
    }

    /**
     * Utility function that retrieves this user's list of friends
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetFriends()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/friends.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of people with similar listening preferences to this user
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetNeighbours()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/neighbours.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of the 10 most recent tracks played by this user
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetRecentTracks()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/recenttracks.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of the 10 tracks most recently banned by this user
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetRecentBannedTracks()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/recentbannedtracks.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of the 10 tracks most recently loved by this user
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetRecentLovedTracks()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/recentlovedtracks.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of dates of available weekly charts for a this user
     *
     * Should actually be named userGetWeeklyChartDateList() but we have to follow audioscrobbler's naming
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetWeeklyChartList()
    {
        $service = "/{$this->get('version')}/user/{$this->get('user')}/weeklychartlist.xml";
        return $this->getInfo($service);
    }


    /**
     * Utility function that returns weekly album chart data for this user
     *
     * @param integer $from optional UNIX timestamp for start of date range
     * @param integer $to optional UNIX timestamp for end of date range
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetWeeklyAlbumChart($from = NULL, $to = NULL)
    {
        $params = "";

        if ($from != NULL && $to != NULL) {
            $from = (int)$from;
            $to = (int)$to;
            $params = "from={$from}&to={$to}";
        }

        $service = "/{$this->get('version')}/user/{$this->get('user')}/weeklyalbumchart.xml";
        return $this->getInfo($service, $params);
    }

    /**
     * Utility function that returns weekly artist chart data for this user
     *
     * @param integer $from optional UNIX timestamp for start of date range
     * @param integer $to optional UNIX timestamp for end of date range
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetWeeklyArtistChart($from = NULL, $to = NULL)
    {
        $params = "";

        if ($from != NULL && $to != NULL) {
            $from = (int)$from;
            $to = (int)$to;
            $params = "from={$from}&to={$to}";
        }

        $service = "/{$this->get('version')}/user/{$this->get('user')}/weeklyartistchart.xml";
        return $this->getInfo($service, $params);
    }

    /**
     * Utility function that returns weekly track chart data for this user
     *
     * @param integer $from optional UNIX timestamp for start of date range
     * @param integer $to optional UNIX timestamp for end of date range
     * @return \SimpleXMLElement object containing result set
     */
    public function userGetWeeklyTrackChart($from = NULL, $to = NULL)
    {
        $params = "";

        if ($from != NULL && $to != NULL) {
            $from = (int)$from;
            $to = (int)$to;
            $params = "from={$from}&to={$to}";
        }

        $service = "/{$this->get('version')}/user/{$this->get('user')}/weeklytrackchart.xml";
        return $this->getInfo($service, $params);
    }


    /**
     * Utility function that returns a list of artists similiar to this artist
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function artistGetRelatedArtists()
    {
        $service = "/{$this->get('version')}/artist/{$this->get('artist')}/similar.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of this artist's top listeners
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function artistGetTopFans()
    {
        $service = "/{$this->get('version')}/artist/{$this->get('artist')}/fans.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of this artist's top-rated tracks
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function artistGetTopTracks()
    {
        $service = "/{$this->get('version')}/artist/{$this->get('artist')}/toptracks.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of this artist's top-rated albums
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function artistGetTopAlbums()
    {
        $service = "/{$this->get('version')}/artist/{$this->get('artist')}/topalbums.xml";
        return $this->getInfo($service);
    }

    /**
     * Utility function that returns a list of this artist's top-rated tags
     *
     * @return \SimpleXMLElement object containing result set
     */
    public function artistGetTopTags()
    {
        $service = "/{$this->get('version')}/artist/{$this->get('artist')}/toptags.xml";
        return $this->getInfo($service);
    }


    /**
     * Get information about an album
     *
     * @return \SimpleXMLElement
     */
    public function albumGetInfo()
    {
        $service = "/{$this->get('version')}/album/{$this->get('artist')}/{$this->get('album')}/info.xml";
        return $this->getInfo($service);
    }

    /**
     * Get top fans of the current track.
     *
     * @return \SimpleXMLElement
     */
    public function trackGetTopFans()
    {
        $service = "/{$this->get('version')}/track/{$this->get('artist')}/{$this->get('track')}/fans.xml";
        return $this->getInfo($service);
    }

    /**
     * Get top tags of the current track.
     *
     * @return \SimpleXMLElement
     */
    public function trackGetTopTags()
    {
        $service = "/{$this->get('version')}/track/{$this->get('artist')}/{$this->get('track')}/toptags.xml";
        return $this->getInfo($service);
    }

    /**
     * Get Top Tags.
     *
     * @return \SimpleXMLElement
     */
    public function tagGetTopTags()
    {
        $service = "/{$this->get('version')}/tag/toptags.xml";
        return $this->getInfo($service);
    }

    /**
     * Get top albums by current tag.
     *
     * @return \SimpleXMLElement
     */
    public function tagGetTopAlbums()
    {
        $service = "/{$this->get('version')}/tag/{$this->get('tag')}/topalbums.xml";
        return $this->getInfo($service);
    }

    /**
     * Get top artists by current tag.
     *
     * @return \SimpleXMLElement
     */
    public function tagGetTopArtists()
    {
        $service = "/{$this->get('version')}/tag/{$this->get('tag')}/topartists.xml";
        return $this->getInfo($service);
    }

    /**
     * Get Top Tracks by currently set tag.
     *
     * @return \SimpleXMLElement
     */
    public function tagGetTopTracks()
    {
        $service = "/{$this->get('version')}/tag/{$this->get('tag')}/toptracks.xml";
        return $this->getInfo($service);
    }

    /**
     * Get weekly chart list by current set group.
     *
     * @see set()
     * @return \SimpleXMLElement
     */
    public function groupGetWeeklyChartList()
    {
        $service = "/{$this->get('version')}/group/{$this->get('group')}/weeklychartlist.xml";
        return $this->getInfo($service);
    }

    /**
     * Retrieve weekly Artist Charts
     *
     * @param  int $from
     * @param  int $to
     * @return \SimpleXMLElement
     */
    public function groupGetWeeklyArtistChartList($from = NULL, $to = NULL)
    {

        if ($from != NULL && $to != NULL) {
            $from = (int)$from;
            $to = (int)$to;
            $params = "from={$from}&$to={$to}";
        } else {
            $params = "";
        }

        $service = "/{$this->get('version')}/group/{$this->get('group')}/weeklyartistchart.xml";
        return $this->getInfo($service, $params);
    }

    /**
     * Retrieve Weekly Track Charts
     *
     * @param  int $from
     * @param  int $to
     * @return \SimpleXMLElement
     */
    public function groupGetWeeklyTrackChartList($from = NULL, $to = NULL)
    {
        if ($from != NULL && $to != NULL) {
            $from = (int)$from;
            $to = (int)$to;
            $params = "from={$from}&to={$to}";
        } else {
            $params = "";
        }

        $service = "/{$this->get('version')}/group/{$this->get('group')}/weeklytrackchart.xml";
        return $this->getInfo($service, $params);
    }

    /**
     * Retrieve Weekly album charts.
     *
     * @param int $from
     * @param int $to
     * @return \SimpleXMLElement
     */
    public function groupGetWeeklyAlbumChartList($from = NULL, $to = NULL)
    {
        if ($from != NULL && $to != NULL) {
            $from = (int)$from;
            $to = (int)$to;
            $params = "from={$from}&to={$to}";
        } else {
            $params = "";
        }

        $service = "/{$this->get('version')}/group/{$this->get('group')}/weeklyalbumchart.xml";
        return $this->getInfo($service, $params);
    }

    /**
     * Saves the provided error information to this instance
     *
     * @param  integer $errno
     * @param  string  $errstr
     * @param  string  $errfile
     * @param  integer $errline
     * @param  array   $errcontext
     * @return void
     */
    protected function errorHandler($errno, $errstr, $errfile, $errline, array $errcontext)
    {
        $this->error = array(
            'errno'      => $errno,
            'errstr'     => $errstr,
            'errfile'    => $errfile,
            'errline'    => $errline,
            'errcontext' => $errcontext,
        );
    }

    /**
     * Call Intercept for set($name, $field)
     *
     * @param  string $method
     * @param  array  $args
     * @return Audioscrobbler
     */
    public function __call($method, $args)
    {
        if (substr($method, 0, 3) !== "set") {
            throw new Exception\BadMethodCallException(
                "Method ".$method." does not exist in class Audioscrobbler."
            );
        }
        $field = strtolower(substr($method, 3));

        if (!is_array($args) || count($args) != 1) {
            throw new Exception\InvalidArgumentException(
                "A value is required for setting a parameter field."
            );
        }
        $this->set($field, $args[0]);

        return $this;
    }
}

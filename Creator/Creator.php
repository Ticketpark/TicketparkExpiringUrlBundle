<?php

namespace Ticketpark\ExpiringUrlBundle\Creator;

/**
 * Creator
 *
 * A service to create hashes for expiring urls
 */
class Creator
{
    /**
     * Constructor
     *
     * @param int    $ttlInMinutes  Default time-to-live of expiration hashes
     * @param string $secret
     */
    public function __construct($ttlInMinutes, $secret)
    {
        $this->secret = $secret;
        $this->ttlInMinutes = $ttlInMinutes;
    }

    /**
     * Create a hash
     * @param string $identifier   An additional identifier to create a unique hash for this url
     * @param int    $ttlInMinutes Optional, overwrites the default time-to-live of expiration hashes
     * @return string
     */
    public function create($identifier=null, $ttlInMinutes=null)
    {
        $eh = new \ExpiringHash($this->secret.''.$identifier);

        if (null === $ttlInMinutes) {
            $ttlInMinutes = $this->ttlInMinutes;
        }

        return $eh->generate($ttlInMinutes . " minutes");
    }
}
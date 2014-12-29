<?php

namespace Apis;

/**
 * Represents something bad occured while trying to fetch an external API,
 * due to something from CloudFlare, so it might be temporary or a throttling
 * problem.
 */
class CloudFlareException extends FetchException {

}

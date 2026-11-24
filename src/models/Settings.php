<?php

namespace brikdigital\queuemonitor\models;

use Craft;
use craft\base\Model;

/**
 * Queue Monitor settings
 */
class Settings extends Model
{
    public string $slackWebhookUrl = '';
}

<?php

namespace brikdigital\queuemonitor;

use Craft;
use brikdigital\queuemonitor\models\Settings;
use craft\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use craft\queue\Queue;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Level;
use Monolog\Logger;
use yii\queue\ExecEvent;

/**
 * Queue Monitor plugin
 *
 * @method static QueueMonitor getInstance()
 * @method Settings getSettings()
 * @author brikdigital
 * @copyright brikdigital
 * @license MIT
 */
class QueueMonitor extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'logger' => $this->getSettings()->slackWebhookUrl
                ? (new Logger('slack'))
                    ->pushHandler(new SlackWebhookHandler(
                        $this->getSettings()->slackWebhookUrl,
                        level: Level::Error
                    ))
                : null,
        ]);

        $this->attachEventHandlers();
    }

    public function getLogger(): ?Logger
    {
        return $this->get('logger');
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    private function attachEventHandlers(): void
    {
        if ($this->getLogger()) {
            Event::on(
                Queue::class,
                Queue::EVENT_AFTER_ERROR,
                function (ExecEvent $event) {
                    if (!$event->retry) {
                        $this->getLogger()->error("Queue job failed!\n" . json_encode([
                            'jobId' => $event->id ?? null,
                            'job' => $event->job ? $event->job::class : null,
                            'attempt' => $event->attempt ?? null,
                            'error' => $event->error?->getMessage() ?? null,
                        ], JSON_PRETTY_PRINT));
                    }
                }
            );
        }
    }
}

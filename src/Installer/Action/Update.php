<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt New BSD License
 */

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */

namespace Module\Analytic\Installer\Action;

use Pi;
use Pi\Application\Installer\Action\Update as BasicUpdate;
use Pi\Application\Installer\SqlSchema;
use Zend\EventManager\Event;

class Update extends BasicUpdate
{
    /**
     * {@inheritDoc}
     */
    protected function attachDefaultListeners()
    {
        $events = $this->events;
        $events->attach('update.pre', [$this, 'updateSchema']);
        parent::attachDefaultListeners();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function updateSchema(Event $e)
    {
        $moduleVersion = $e->getParam('version');

        // Update to version 0.0.5
        if (version_compare($moduleVersion, '0.0.5', '<')) {
            // Add table of author
            $sql
                = <<<'EOD'
CREATE TABLE `{user}` (
  `id`              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_images` TEXT,
  `description`     TEXT,
  PRIMARY KEY (`id`)
);
EOD;
            SqlSchema::setType($this->module);
            $sqlHandler = new SqlSchema;
            try {
                $sqlHandler->queryContent($sql);
            } catch (\Exception $exception) {
                $this->setResult(
                    'db', [
                    'status'  => false,
                    'message' => 'SQL schema query for author table failed: '
                        . $exception->getMessage(),
                ]
                );

                return false;
            }
        }

        // Update to version 0.0.6
        if (version_compare($moduleVersion, '0.0.6', '<')) {
            // Add table of author
            $sql
                = <<<'EOD'
CREATE TABLE `{comment}` (
  `id`          INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT,
  `uid`         INT(10) UNSIGNED NOT NULL  DEFAULT '0',
  `by`          INT(10) UNSIGNED NOT NULL  DEFAULT '0',
  `time_create` INT(10) UNSIGNED NOT NULL  DEFAULT '0',
  `note`        TEXT,
  PRIMARY KEY (`id`)
);
EOD;
            SqlSchema::setType($this->module);
            $sqlHandler = new SqlSchema;
            try {
                $sqlHandler->queryContent($sql);
            } catch (\Exception $exception) {
                $this->setResult(
                    'db', [
                    'status'  => false,
                    'message' => 'SQL schema query for author table failed: '
                        . $exception->getMessage(),
                ]
                );

                return false;
            }
        }

        return true;
    }
}
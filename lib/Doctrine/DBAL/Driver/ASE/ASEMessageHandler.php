<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Driver\ASE;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;

/**
 * ASE message handling implementation.
 *
 * @since 2.6
 * @author Maximilian Ruta <mr@xtain.net>
 */
class ASEMessageHandler
{
    /**
     * @var bool
     */
    protected static $registred = false;

    /**
     * @var ASEMessageException[]
     */
    protected static $globalMessages = [];

    /**
     * @var ASEMessageException[]
     */
    protected $messages = [];

    public static function registerLogger()
    {
        if (self::$registred) {
            return;
        }

        self::$registred = true;
        sybase_set_message_handler(function($id, $severity, $state, $line, $text) {
            self::$globalMessages[] = new ASEMessageException($text, $severity, null, $state, $line, $id);
        });
    }

    /**
     * @param \Throwable $e
     * @return ASEMessageException
     */
    public static function fromThrowable($e)
    {
        $message = $e->getMessage();

        $matches = [];
        if (preg_match('/((Warning|Fatal Error|Error|Notice)\: )?(.*\)\:[\s])?(.*)/', $message, $matches)) {
            $message = $matches[4];
        }

        return new ASEMessageException($message);
    }

    /**
     * ASEMessageHandler constructor.
     * @param resource $resource
     */
    public function __construct($resource = null)
    {
        if ($resource !== null) {
            $self = $this;

            sybase_set_message_handler(function($id, $severity, $state, $line, $text) use ($self) {
                $self->messages[] = new ASEMessageException($text, $severity, null, $state, $line, $id);
            }, $resource);
        }
    }

    /**
     * @return ASEMessageException|null
     */
    public function getLastMessage($level = 10)
    {
        /** @var ASEMessageException $message */
        foreach (array_reverse($this->messages) as $message) {
            if ($message->getCode() >= $level) {
                return $message;
            }
        }

        /** @var ASEMessageException $message */
        foreach (array_reverse(self::$globalMessages) as $message) {
            if ($message->getCode() >= $level) {
                return $message;
            }
        }

        return null;
    }

    public static function clearGlobal()
    {
        self::$globalMessages = [];
    }

    public function clear()
    {
        self::$globalMessages = [];
        $this->messages = [];
    }

    /**
     * @return ASEMessageException|null
     */
    public function getLastException()
    {
        $message = $this->getLastMessage();

        if ($message === null) {
            $message = new ASEMessageException("ASE error occurred but no error message was retrieved from driver.");
        }

        return $message;
    }

    /**
     * @return ASEMessageException|null
     */
    public function getLastError()
    {
        return $this->getLastMessage(11);
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->getLastMessage(11) !== null;
    }
}
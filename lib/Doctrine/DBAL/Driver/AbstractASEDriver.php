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

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\ASEPlatform;
use Doctrine\DBAL\Platforms\ASE150Platform;
use Doctrine\DBAL\Platforms\ASE155Platform;
use Doctrine\DBAL\Platforms\ASE157Platform;
use Doctrine\DBAL\Platforms\ASE160Platform;
use Doctrine\DBAL\Schema\ASESchemaManager;
use Doctrine\DBAL\VersionAwarePlatformDriver;

/**
 * Abstract base implementation of the {@link Doctrine\DBAL\Driver} interface for ASE based drivers.
 *
 * @author Maximilian Ruta <mr@xtain.net>
 * @link   www.doctrine-project.org
 * @since  2.6
 */
abstract class AbstractASEDriver implements Driver, VersionAwarePlatformDriver
{
    /**
     * {@inheritdoc}
     */
    public function createDatabasePlatformForVersion($version)
    {
        $versionParts = [];
        if (!preg_match('/Adaptive Server Enterprise\/([0-9]+)\.([0-9]+).?([0-9]+)?.?([0-9]+)?\/.*/i', $version, $versionParts)) {
            throw DBALException::invalidPlatformVersionSpecified(
                $version,
                '<product>/<major_version>.<minor_version>.<patch_version>.<build_version>'
            );
        }

        for ($i = 1; $i <= 4; $i++) {
            if (!isset($versionParts[$i])) {
                $versionParts[$i] = "0";
            }
        }

        array_shift($versionParts);

        $version = "";
        foreach ($versionParts as $versionPart) {
            $version .= $versionPart . ".";
        }

        $version = rtrim($version, ".");

        switch(true) {
            case version_compare($version, '16.0.0.0', '>='):
                return new ASE160Platform();
            case version_compare($version, '15.7.0.0', '>='):
                return new ASE157Platform();
            case version_compare($version, '15.5.0.0', '>='):
                return new ASE155Platform();
            case version_compare($version, '15.0.0.0', '>='):
                return new ASE150Platform();
            default:
                return new ASEPlatform();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        $params = $conn->getParams();

        if (isset($params['dbname'])) {
            return $params['dbname'];
        }

        return $conn->query('SELECT DB_NAME()')->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new ASE150Platform();
    }

    /**
     * {@inheritdoc}
     */

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new ASESchemaManager($conn);
    }
}

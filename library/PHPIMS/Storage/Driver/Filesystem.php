<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package PHPIMS
 * @subpackage StorageDriver
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Storage\Driver;

use PHPIMS\Storage\DriverInterface;
use PHPIMS\Storage\Exception as StorageException;
use PHPIMS\Image\ImageInterface;

/**
 * Filesystem storage driver
 *
 * This storage driver stores image files in a local filesystem.
 *
 * Configuration options supported by this driver:
 *
 * - <pre>(string) dataDir</pre> Absolute path to the base directory the images should be stored in
 *
 * @package PHPIMS
 * @subpackage StorageDriver
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Filesystem implements DriverInterface {
    /**
     * Parameters for the filesystem driver
     *
     * @var array
     */
    private $params = array(
        'dataDir' => null,
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     */
    public function __construct(array $params) {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * @see PHPIMS\Storage\DriverInterface::store()
     */
    public function store($publicKey, $imageIdentifier, ImageInterface $image) {
        if (!is_writable($this->params['dataDir'])) {
            throw new StorageException('Could not store image', 500);
        }

        // Create path for the image
        $imageDir = $this->getImagePath($publicKey, $imageIdentifier, false);
        $oldUmask = umask(0);

        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0775, true);
        }

        umask($oldUmask);

        $imagePath = $imageDir . '/' . $imageIdentifier;

        return file_put_contents($imagePath, $image->getBlob());
    }

    /**
     * @see PHPIMS\Storage\DriverInterface::delete()
     */
    public function delete($publicKey, $imageIdentifier) {
        $path = $this->getImagePath($publicKey, $imageIdentifier);

        if (!is_file($path)) {
            throw new StorageException('File not found', 404);
        }

        return unlink($path);
    }

    /**
     * @see PHPIMS\Storage\DriverInterface::load()
     */
    public function load($publicKey, $imageIdentifier, ImageInterface $image) {
        $path = $this->getImagePath($publicKey, $imageIdentifier);

        if (!is_file($path)) {
            throw new StorageException('File not found', 404);
        }

        $image->setBlob(file_get_contents($path));

        return true;
    }

    /**
     * Get the path to an image
     *
     * @param string $publicKey The key
     * @param string $imageIdentifier Image identifier
     * @param boolean $includeFilename Wether or not to include the last part of the path (the
     *                                 filename itself)
     * @return string
     */
    public function getImagePath($publicKey, $imageIdentifier, $includeFilename = true) {
        $parts = array(
            $this->params['dataDir'],
            $publicKey[0],
            $publicKey[1],
            $publicKey[2],
            $publicKey,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
        );

        if ($includeFilename) {
            $parts[] = $imageIdentifier;
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
